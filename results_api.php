<?php
require __DIR__ . '/gv_db.php';
const MAX_BODY_BYTES = 200000;
const MAX_EVENTS = 100;
const CODE_MAX_FAILS = 30;
const CODE_WINDOW_SEC = 600;
const SUGGEST_MAX = 400;
header('Content-Type: application/json');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST required']);
    exit;
}
$raw = file_get_contents('php://input');
if (strlen($raw) > MAX_BODY_BYTES) {
    http_response_code(413);
    echo json_encode(['success' => false, 'error' => 'Request too large']);
    exit;
}
$input = json_decode($raw, true);
$isCheck = is_array($input) && ($input['action'] ?? '') === 'check_class';
$isSuggest = is_array($input) && ($input['action'] ?? '') === 'suggest_teachers';
if (!is_array($input) || (!$isCheck && !$isSuggest && (!isset($input['events']) || !is_array($input['events'])))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
    exit;
}
if (!class_exists('PDO') || !in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    error_log('Grammar Vault results_api: the PHP SQLite driver (pdo_sqlite) is not enabled. '
            . 'Enable extension=pdo_sqlite and extension=sqlite3 in php.ini, then restart PHP.');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'SQLite driver missing on the server']);
    exit;
}
function s($v, int $max): string {
    $v = is_scalar($v) ? (string)$v : '';
    $v = trim(preg_replace('/[\x00-\x1F\x7F]/u', ' ', $v) ?? '');
    return function_exists('mb_substr') ? mb_substr($v, 0, $max) : substr($v, 0, $max);
}
function n($v, int $min, int $max, int $default = 0): int {
    if (!is_numeric($v)) return $default;
    return max($min, min($max, (int)$v));
}
function now(): string { return gmdate('Y-m-d H:i:s'); }
function player_id(PDO $db, array $e, int $teacherId): int {
    $name = s($e['name'] ?? '', 20);
    if ($name === '') throw new InvalidArgumentException('name required');
    $grade = n($e['grade'] ?? 0, 0, 12);
    $ts = now();
    $db->prepare('INSERT INTO players (teacher_id, name, grade, created_at, last_seen) VALUES (?,?,?,?,?)
                  ON CONFLICT (teacher_id, name, grade) DO UPDATE SET last_seen = excluded.last_seen')
       ->execute([$teacherId, $name, $grade, $ts, $ts]);
    $q = $db->prepare('SELECT id FROM players WHERE teacher_id = ? AND name = ? AND grade = ?');
    $q->execute([$teacherId, $name, $grade]);
    return (int)$q->fetchColumn();
}
function run_id(PDO $db, array $e, int $playerId): int {
    $uuid = s($e['run'] ?? '', 64);
    if ($uuid === '') throw new InvalidArgumentException('run required');
    $db->prepare('INSERT OR IGNORE INTO runs (run_uuid, player_id, started_at) VALUES (?,?,?)')
       ->execute([$uuid, $playerId, now()]);
    $q = $db->prepare('SELECT id, player_id FROM runs WHERE run_uuid = ?');
    $q->execute([$uuid]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (!$row || (int)$row['player_id'] !== $playerId) throw new InvalidArgumentException('run belongs to someone else');
    return (int)$row['id'];
}
if ($isSuggest) {
    try {
        $db = gv_open_db();
        $ip = 's:' . substr((string)($_SERVER['REMOTE_ADDR'] ?? '?'), 0, 62);
        $db->prepare('DELETE FROM class_code_failures WHERE at < ?')->execute([time() - CODE_WINDOW_SEC]);
        $cnt = $db->prepare('SELECT COUNT(*) FROM class_code_failures WHERE ip = ?');
        $cnt->execute([$ip]);
        if ((int)$cnt->fetchColumn() >= SUGGEST_MAX) {
            http_response_code(429);
            echo json_encode(['success' => false, 'error' => 'Too many tries. Please wait a few minutes.']);
            exit;
        }
        $db->prepare('INSERT INTO class_code_failures (ip, at) VALUES (?, ?)')->execute([$ip, time()]);
        $typed = is_scalar($input['teacher'] ?? null) ? (string)$input['teacher'] : '';
        $exact = gv_teacher_by_name($db, $typed);
        echo json_encode(['success' => true, 'matches' => gv_teacher_suggestions($db, $typed), 'exact' => $exact ? $exact['display_name'] : null]);
    } catch (Throwable $ex) {
        error_log('Grammar Vault results_api (suggest_teachers): ' . $ex->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}
if ($isCheck) {
    try {
        $db = gv_open_db();
        $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? '?'), 0, 64);
        $db->prepare('DELETE FROM class_code_failures WHERE at < ?')->execute([time() - CODE_WINDOW_SEC]);
        $cnt = $db->prepare('SELECT COUNT(*) FROM class_code_failures WHERE ip = ?');
        $cnt->execute([$ip]);
        if ((int)$cnt->fetchColumn() >= CODE_MAX_FAILS) {
            http_response_code(429);
            echo json_encode(['success' => false, 'error' => 'Too many tries. Please wait a few minutes.']);
            exit;
        }
        $t = gv_teacher_by_name($db, is_scalar($input['teacher'] ?? null) ? (string)$input['teacher'] : '');
        if (!$t) {
            $db->prepare('INSERT INTO class_code_failures (ip, at) VALUES (?, ?)')->execute([$ip, time()]);
            echo json_encode(['success' => true, 'valid' => false]);
        } else {
            echo json_encode(['success' => true, 'valid' => true, 'teacher' => $t['display_name']]);
        }
    } catch (Throwable $ex) {
        error_log('Grammar Vault results_api (check_class): ' . $ex->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}
$events = array_slice($input['events'], 0, MAX_EVENTS);
$accepted = 0;
$teacherCache = [];
$studentCache = [];
try {
    $db = gv_open_db();
    $db->beginTransaction();
    foreach ($events as $e) {
        if (!is_array($e)) continue;
        $type = s($e['type'] ?? '', 20);
        $eid  = s($e['id'] ?? '', 64);
        if ($eid === '') continue;
        $sno = gv_norm_student_no($e['student'] ?? '');
        try {
            if ($sno !== '') {
                $tk = is_string($e['token'] ?? null) ? $e['token'] : '';
                $sk = $sno . '|' . $tk;
                if (!array_key_exists($sk, $studentCache)) {
                    $st = gv_student_from_token($db, $tk);
                    $studentCache[$sk] = ($st && $st['student_no'] === $sno) ? gv_student_player_id($db, $st) : 0;
                }
                if ($studentCache[$sk] === 0) continue;
                $pid = $studentCache[$sk];
            } else {
                $typed = is_scalar($e['teacher'] ?? null) ? (string)$e['teacher'] : '';
                $legacy = gv_clean_code($e['cls'] ?? '');
                $ck = $typed !== '' ? 'n:' . gv_teacher_key($typed) : ($legacy !== '' ? 'c:' . $legacy : '');
                if ($ck === '' || $ck === 'n:') continue;
                if (!isset($teacherCache[$ck])) {
                    $t = $typed !== '' ? gv_teacher_by_name($db, $typed)
                                       : (strlen($legacy) === GV_CODE_LEN ? gv_teacher_by_code($db, $legacy) : null);
                    $teacherCache[$ck] = $t ? (int)$t['id'] : 0;
                }
                if ($teacherCache[$ck] === 0) continue;
                $pid = player_id($db, $e, $teacherCache[$ck]);
            }
            $rid = run_id($db, $e, $pid);
        } catch (InvalidArgumentException $ex) {
            continue;
        }
        if ($type === 'answer') {
            $db->prepare('INSERT OR IGNORE INTO answers
                (event_id, run_id, level, category, question, qtype, given, correct, answered_at, seconds, timed_out)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)')
               ->execute([
                   $eid, $rid,
                   n($e['level'] ?? 1, 1, 100, 1),
                   s($e['category'] ?? '', 40),
                   s($e['question'] ?? '', 400),
                   s($e['qtype'] ?? '', 10),
                   s($e['given'] ?? '', 200),
                   !empty($e['correct']) ? 1 : 0,
                   now(),
                   isset($e['seconds']) && is_numeric($e['seconds']) ? round(max(0, min(600, (float)$e['seconds'])), 1) : null,
                   !empty($e['timed_out']) ? 1 : 0,
               ]);
            $accepted++;
        } elseif ($type === 'level_complete') {
            $db->prepare('INSERT INTO level_results (run_id, level, correct, total, stars, completed_at)
                          VALUES (?,?,?,?,?,?)
                          ON CONFLICT (run_id, level) DO UPDATE SET
                            correct = excluded.correct, total = excluded.total,
                            stars = excluded.stars, completed_at = excluded.completed_at')
               ->execute([
                   $rid,
                   n($e['level'] ?? 1, 1, 100, 1),
                   n($e['correct'] ?? 0, 0, 100000),
                   n($e['total'] ?? 0, 0, 100000),
                   n($e['stars'] ?? 0, 0, 3),
                   now(),
               ]);
            $accepted++;
        } elseif ($type === 'run_finished') {
            $db->prepare('UPDATE runs SET finished_at = ?, final_score = ?, final_total = ? WHERE id = ?')
               ->execute([now(), n($e['score'] ?? 0, 0, 100000), n($e['total'] ?? 0, 0, 100000), $rid]);
            $accepted++;
        }
    }
    $db->commit();
    echo json_encode(['success' => true, 'accepted' => $accepted]);
} catch (Throwable $ex) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('Grammar Vault results_api: ' . $ex->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
