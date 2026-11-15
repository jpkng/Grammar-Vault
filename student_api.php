<?php
require __DIR__ . '/gv_db.php';
const STU_MAX_TRIES_IP   = 150;
const STU_MAX_TRIES_ACCT = 8;
const STU_WINDOW_SEC     = 600;
header('Content-Type: application/json');
header('Cache-Control: no-store');
function out(array $a, int $code = 200): void { http_response_code($code); echo json_encode($a); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['success' => false, 'error' => 'POST required'], 405);
$raw = file_get_contents('php://input');
if (strlen($raw) > 5000) out(['success' => false, 'error' => 'Request too large'], 413);
$in = json_decode($raw, true);
if (!is_array($in) || !is_string($in['action'] ?? null)) out(['success' => false, 'error' => 'Invalid request'], 400);
if (!class_exists('PDO') || !in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    error_log('Grammar Vault student_api: pdo_sqlite is not enabled.');
    out(['success' => false, 'error' => 'The server is not ready (SQLite missing).'], 500);
}
try {
    $db = gv_open_db();
    $db->prepare('DELETE FROM class_code_failures WHERE at < ?')->execute([time() - STU_WINDOW_SEC]);
    $count = function (string $key) use ($db): int {
        $c = $db->prepare('SELECT COUNT(*) FROM class_code_failures WHERE ip = ?');
        $c->execute([$key]);
        return (int)$c->fetchColumn();
    };
    $ipKey = 'st:' . substr((string)($_SERVER['REMOTE_ADDR'] ?? '?'), 0, 60);
    $acctKey = function ($ident): string {
        $ident = is_scalar($ident) ? trim((string)$ident) : '';
        $no = gv_norm_student_no($ident);
        return 'sa:' . ($no !== '' ? $no : strtolower(substr($ident, 0, 40)));
    };
    $addFail = function (string ...$keys) use ($db): void {
        foreach ($keys as $k) $db->prepare('INSERT INTO class_code_failures (ip, at) VALUES (?, ?)')->execute([$k, time()]);
    };
    $tooMany = fn() => out(['success' => false, 'error' => 'Too many tries. Please wait a few minutes and try again.'], 429);
    if ($count($ipKey) >= STU_MAX_TRIES_IP) $tooMany();
    $reply = fn(array $s) => out(['success' => true, 'student' => gv_student_public($db, $s), 'token' => gv_student_token($db, $s)]);
    switch ($in['action']) {
        case 'login': {
            $ident = $in['id'] ?? ($in['username'] ?? '');
            $ak = $acctKey($ident);
            if ($count($ak) >= STU_MAX_TRIES_ACCT) $tooMany();
            [$s, $err] = gv_student_login($db, $ident, $in['password'] ?? '');
            if ($err === 'NEEDPW') out(['success' => false, 'needs_password' => true, 'error' => 'Your account needs a password. Please create one.']);
            if (!$s) { $addFail($ipKey, $ak); out(['success' => false, 'error' => $err]); }
            $reply($s);
        }
        case 'register': {
            [$s, $err] = gv_student_register($db, $in['student_no'] ?? '', $in['full_name'] ?? '', $in['teacher'] ?? '', $in['grade'] ?? 0, $in['username'] ?? '', $in['password'] ?? '');
            if (!$s) { $addFail($ipKey); out(['success' => false, 'error' => $err]); }
            $reply($s);
        }
        case 'set_password': {
            $ak = $acctKey($in['id'] ?? '');
            if ($count($ak) >= STU_MAX_TRIES_ACCT) $tooMany();
            [$s, $err] = gv_student_set_first_password($db, $in['id'] ?? '', $in['full_name'] ?? '', $in['password'] ?? '');
            if (!$s) { $addFail($ipKey, $ak); out(['success' => false, 'error' => $err]); }
            $reply($s);
        }
        case 'whoami': {
            $s = gv_student_from_token($db, $in['token'] ?? '');
            if (!$s) out(['success' => false, 'error' => 'not found']);
            out(['success' => true, 'student' => gv_student_public($db, $s)]);
        }
        case 'progress': {
            require __DIR__ . '/gv_stats.php';
            $s = gv_student_from_token($db, $in['token'] ?? '');
            if (!$s) out(['success' => false, 'error' => 'not found']);
            out(['success' => true, 'progress' => gv_student_progress($db, $s)]);
        }
        default:
            out(['success' => false, 'error' => 'Unknown action'], 400);
    }
} catch (Throwable $e) {
    error_log('Grammar Vault student_api: ' . $e->getMessage());
    out(['success' => false, 'error' => 'Database error'], 500);
}
