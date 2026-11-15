<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath((string)$_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    http_response_code(404);
    exit;
}
const WEAK_BELOW   = 75;
const HELP_BELOW   = 50;
const MIN_ANSWERS  = 5;
const INACTIVE_DAYS = 7;
const TREND_WINDOW = 20;
const TREND_DELTA  = 10;
const SLOW_TIMEOUT_PCT = 20;
const GV_TZ        = 'Asia/Manila';
const SKILLS = ['Grammar', 'Punctuation', 'Spelling', 'Vocabulary', 'Reading Comprehension'];
const TIPS = [
    'Grammar' => [
        'Do a sentence-strip sort: students cut sentences into words and sort them into nouns, verbs, and adjectives.',
        'Play "fix the sentence": give sentences with one wrong word form and have students correct and explain it.',
    ],
    'Punctuation' => [
        'Read short passages aloud together, pausing at commas and stopping at periods, then have students add the marks.',
        'Give unpunctuated sentences and ask students to choose the right end mark (. ? !) and say why.',
    ],
    'Spelling' => [
        'Use "look, say, cover, write, check" with the words they missed in the game.',
        'Group missed words by pattern (plural endings, -ed, silent e) and practice one pattern at a time.',
    ],
    'Vocabulary' => [
        'Start a class word wall: add one new word a day and have students use it in a spoken sentence.',
        'Practice context clues: cover a word in a sentence and let students guess its meaning from nearby words.',
    ],
    'Reading Comprehension' => [
        'After reading, have students retell the story using who, what, where, and why.',
        'Practice finding the proof: students point to the exact sentence that supports their answer.',
    ],
];
function pct(int $c, int $n): ?int { return $n > 0 ? (int)round(100 * $c / $n) : null; }
function gv_level_names(): array {
    static $names = null;
    if ($names !== null) return $names;
    $names = [];
    $src = @file_get_contents(__DIR__ . '/levels.js');
    if ($src !== false && preg_match_all("/\{\s*name:\s*'([^']+)'\s*,\s*req:/", $src, $m)) $names = $m[1];
    if (!$names) $names = ['Level 1', 'Level 2', 'Level 3'];
    return $names;
}
function gv_level_count(): int { return count(gv_level_names()); }
function gv_local(?string $utc, string $fmt = 'M j, Y g:i A'): string {
    if (!$utc) return '—';
    try {
        $d = new DateTime($utc, new DateTimeZone('UTC'));
        $d->setTimezone(new DateTimeZone(GV_TZ));
        return $d->format($fmt);
    } catch (Throwable $e) { return $utc; }
}
function gv_ago(?string $utc): string {
    if (!$utc) return 'never';
    $t = strtotime($utc . ' UTC');
    if (!$t) return '—';
    $d = time() - $t;
    if ($d < 90) return 'just now';
    if ($d < 3600) return intdiv($d, 60) . ' min ago';
    if ($d < 86400) return intdiv($d, 3600) . ' h ago';
    $days = intdiv($d, 86400);
    return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
}
function gv_fmt_seconds(?float $s): string {
    return $s === null ? '—' : number_format($s, 0) . ' s';
}
function weak_skills(array $skills): array {
    $out = [];
    foreach ($skills as $name => $s) {
        $p = pct($s['c'], $s['n']);
        if ($s['n'] >= MIN_ANSWERS && $p !== null && $p < WEAK_BELOW) {
            $out[] = ['skill' => $name, 'pct' => $p, 'n' => $s['n'], 'c' => $s['c'], 'level' => $p < HELP_BELOW ? 'help' : 'practice'];
        }
    }
    usort($out, fn($a, $b) => $a['pct'] <=> $b['pct']);
    return $out;
}
function gv_load_class(PDO $db, int $tid, ?int $onlyStudentId = null): array {
    $pf = $onlyStudentId !== null ? ' AND p.student_id = ' . (int)$onlyStudentId : '';
    $recs = [];
    $byPlayer = [];
    $nLevels = gv_level_count();
    $blank = function (): array {
        return ['skills' => [], 'n' => 0, 'c' => 0, 'timeouts' => 0, 'secSum' => 0.0, 'secN' => 0, 'runs' => 0,
                'last' => null, 'first' => null, 'days' => 0, 'levelStars' => [], 'levelStat' => [], 'trend' => null,
                'recent' => null, 'weak' => [], 'perRun' => []];
    };
    $q = $db->prepare('SELECT s.*, p.id AS pid FROM students s LEFT JOIN players p ON p.student_id = s.id WHERE s.teacher_id = ?' . ($onlyStudentId !== null ? ' AND s.id = ' . (int)$onlyStudentId : '') . '');
    $q->execute([$tid]);
    foreach ($q as $s) {
        $key = 's' . $s['id'];
        $recs[$key] = $blank() + [
            'key' => $key, 'student_id' => (int)$s['id'], 'student_no' => $s['student_no'], 'name' => $s['full_name'],
            'username' => (string)($s['username'] ?? ''), 'grade' => (int)$s['grade'], 'registered' => (int)$s['registered'] === 1,
            'created_at' => $s['created_at'], 'last_login' => $s['last_login'], 'legacy' => false,
            'has_pw' => !empty($s['password_hash']),
        ];
        if ($s['pid']) $byPlayer[(int)$s['pid']] = $key;
    }
    $q = $db->prepare('SELECT id, name, grade FROM players WHERE teacher_id = ? AND student_id IS NULL');
    $q->execute([$tid]);
    foreach ($onlyStudentId !== null ? [] : $q as $p) {
        $key = 'p' . $p['id'];
        $recs[$key] = $blank() + [
            'key' => $key, 'student_id' => null, 'student_no' => '', 'name' => $p['name'], 'username' => '',
            'grade' => (int)$p['grade'], 'registered' => true, 'created_at' => null, 'last_login' => null, 'legacy' => true,
        ];
        $byPlayer[(int)$p['id']] = $key;
    }
    $skillNames = SKILLS;
    $q = $db->prepare('SELECT r.player_id AS pid, a.category AS cat, COUNT(*) AS n, SUM(a.correct) AS c, SUM(a.timed_out) AS t,
                              SUM(CASE WHEN a.seconds IS NOT NULL AND a.timed_out = 0 THEN a.seconds ELSE 0 END) AS ssum,
                              SUM(CASE WHEN a.seconds IS NOT NULL AND a.timed_out = 0 THEN 1 ELSE 0 END) AS scnt
                       FROM answers a JOIN runs r ON r.id = a.run_id JOIN players p ON p.id = r.player_id
                       WHERE p.teacher_id = ?' . $pf . ' GROUP BY r.player_id, a.category');
    $q->execute([$tid]);
    foreach ($q as $row) {
        $k = $byPlayer[(int)$row['pid']] ?? null;
        if ($k === null) continue;
        $cat = (string)$row['cat'];
        if (!in_array($cat, $skillNames, true)) $skillNames[] = $cat;
        $r = &$recs[$k];
        $r['skills'][$cat] = ['n' => (int)$row['n'], 'c' => (int)$row['c']];
        $r['n'] += (int)$row['n'];
        $r['c'] += (int)$row['c'];
        $r['timeouts'] += (int)$row['t'];
        $r['secSum'] += (float)$row['ssum'];
        $r['secN'] += (int)$row['scnt'];
        unset($r);
    }
    $q = $db->prepare('SELECT r.player_id AS pid, a.level AS level, COUNT(*) AS n, SUM(a.correct) AS c, SUM(a.timed_out) AS t
                       FROM answers a JOIN runs r ON r.id = a.run_id JOIN players p ON p.id = r.player_id
                       WHERE p.teacher_id = ?' . $pf . ' GROUP BY r.player_id, a.level');
    $q->execute([$tid]);
    foreach ($q as $row) {
        $k = $byPlayer[(int)$row['pid']] ?? null;
        if ($k !== null) $recs[$k]['levelStat'][(int)$row['level']] = ['n' => (int)$row['n'], 'c' => (int)$row['c'], 't' => (int)$row['t']];
    }
    $q = $db->prepare('SELECT r.player_id AS pid, MIN(a.answered_at) AS f, MAX(a.answered_at) AS l, COUNT(DISTINCT substr(a.answered_at,1,10)) AS d
                       FROM answers a JOIN runs r ON r.id = a.run_id JOIN players p ON p.id = r.player_id
                       WHERE p.teacher_id = ?' . $pf . ' GROUP BY r.player_id');
    $q->execute([$tid]);
    foreach ($q as $row) {
        $k = $byPlayer[(int)$row['pid']] ?? null;
        if ($k === null) continue;
        $recs[$k]['first'] = $row['f']; $recs[$k]['last'] = $row['l']; $recs[$k]['days'] = (int)$row['d'];
    }
    $q = $db->prepare('SELECT r.player_id AS pid, COUNT(*) AS runs FROM runs r JOIN players p ON p.id = r.player_id WHERE p.teacher_id = ?' . $pf . ' GROUP BY r.player_id');
    $q->execute([$tid]);
    foreach ($q as $row) { $k = $byPlayer[(int)$row['pid']] ?? null; if ($k !== null) $recs[$k]['runs'] = (int)$row['runs']; }
    $q = $db->prepare('SELECT r.player_id AS pid, lr.level AS level, MAX(lr.stars) AS stars
                       FROM level_results lr JOIN runs r ON r.id = lr.run_id JOIN players p ON p.id = r.player_id
                       WHERE p.teacher_id = ?' . $pf . ' GROUP BY r.player_id, lr.level');
    $q->execute([$tid]);
    foreach ($q as $row) { $k = $byPlayer[(int)$row['pid']] ?? null; if ($k !== null) $recs[$k]['levelStars'][(int)$row['level']] = (int)$row['stars']; }
    $win = [];
    $q = $db->prepare('SELECT r.player_id AS pid, r.id AS rid, a.correct AS c FROM answers a JOIN runs r ON r.id = a.run_id JOIN players p ON p.id = r.player_id
                       WHERE p.teacher_id = ?' . $pf . ' ORDER BY a.id');
    $q->execute([$tid]);
    $runAgg = [];
    foreach ($q as $row) {
        $k = $byPlayer[(int)$row['pid']] ?? null;
        if ($k === null) continue;
        $win[$k][] = (int)$row['c'];
        $rk = $k . '|' . $row['rid'];
        if (!isset($runAgg[$rk])) $runAgg[$rk] = ['k' => $k, 'n' => 0, 'c' => 0];
        $runAgg[$rk]['n']++; $runAgg[$rk]['c'] += (int)$row['c'];
    }
    foreach ($runAgg as $ra) $recs[$ra['k']]['perRun'][] = ['n' => $ra['n'], 'c' => $ra['c']];
    foreach ($win as $k => $list) {
        $cnt = count($list);
        if ($cnt >= TREND_WINDOW) {
            $recent = array_slice($list, -TREND_WINDOW);
            $recs[$k]['recent'] = (int)round(100 * array_sum($recent) / TREND_WINDOW);
            if ($cnt >= 2 * TREND_WINDOW) {
                $prev = array_slice($list, -2 * TREND_WINDOW, TREND_WINDOW);
                $delta = $recs[$k]['recent'] - (int)round(100 * array_sum($prev) / TREND_WINDOW);
                $recs[$k]['trend'] = $delta >= TREND_DELTA ? 'up' : ($delta <= -TREND_DELTA ? 'down' : 'flat');
                $recs[$k]['trendDelta'] = $delta;
            }
        }
    }
    $now = time();
    foreach ($recs as $k => &$r) {
        foreach ($skillNames as $sk) if (!isset($r['skills'][$sk])) $r['skills'][$sk] = ['n' => 0, 'c' => 0];
        $r['weak'] = weak_skills($r['skills']);
        $r['acc'] = pct($r['c'], $r['n']);
        $r['avgSec'] = $r['secN'] > 0 ? $r['secSum'] / $r['secN'] : null;
        $r['timeoutPct'] = pct($r['timeouts'], $r['n']);
        $r['levelsDone'] = count($r['levelStars']);
        $r['stars'] = array_sum($r['levelStars']);
        $lastTs = $r['last'] ? strtotime($r['last'] . ' UTC') : 0;
        $r['daysIdle'] = $lastTs ? (int)floor(($now - $lastTs) / 86400) : null;
        $why = [];
        if (!$r['registered'])      $why[] = 'Has not registered yet';
        elseif ($r['n'] === 0)      $why[] = 'Has not started playing';
        else {
            if ($r['daysIdle'] !== null && $r['daysIdle'] >= INACTIVE_DAYS) $why[] = 'Has not played for ' . $r['daysIdle'] . ' days';
            if ($r['n'] >= 10 && $r['acc'] !== null && $r['acc'] < 60)      $why[] = 'Overall accuracy is ' . $r['acc'] . '%';
            if ($r['trend'] === 'down')                                     $why[] = 'Accuracy dropped ' . abs((int)$r['trendDelta']) . ' points recently';
            foreach ($r['weak'] as $w)                                      $why[] = $w['skill'] . ' at ' . $w['pct'] . '%';
            if ($r['n'] >= 10 && $r['timeoutPct'] !== null && $r['timeoutPct'] >= SLOW_TIMEOUT_PCT) $why[] = $r['timeoutPct'] . '% of questions ran out of time';
        }
        $r['why'] = $why;
        if (!$r['registered'])                                              $r['status'] = 'pending';
        elseif ($r['n'] === 0)                                              $r['status'] = 'new';
        elseif ($r['daysIdle'] !== null && $r['daysIdle'] >= INACTIVE_DAYS) $r['status'] = 'idle';
        elseif ($r['weak'] || ($r['acc'] !== null && $r['n'] >= 10 && $r['acc'] < 60) || $r['trend'] === 'down') $r['status'] = 'help';
        else                                                                $r['status'] = 'good';
    }
    unset($r);
    $weeks = [];
    $tz = new DateTimeZone(GV_TZ);
    $mon = new DateTime('monday this week', $tz);
    for ($i = 7; $i >= 0; $i--) {
        $d = (clone $mon)->modify("-$i week");
        $weeks[$d->format('Y-m-d')] = ['label' => $d->format('M j'), 'n' => 0, 'c' => 0, 'players' => []];
    }
    $q = $db->prepare('SELECT a.answered_at AS at, a.correct AS c, r.player_id AS pid FROM answers a JOIN runs r ON r.id = a.run_id JOIN players p ON p.id = r.player_id
                       WHERE p.teacher_id = ?' . $pf . ' AND a.answered_at >= ?');
    $q->execute([$tid, gmdate('Y-m-d H:i:s', strtotime('-9 weeks'))]);
    foreach ($q as $row) {
        try {
            $d = new DateTime($row['at'], new DateTimeZone('UTC')); $d->setTimezone($tz);
            $d->modify('monday this week');
            $wk = $d->format('Y-m-d');
        } catch (Throwable $e) { continue; }
        if (!isset($weeks[$wk])) continue;
        $weeks[$wk]['n']++; $weeks[$wk]['c'] += (int)$row['c']; $weeks[$wk]['players'][$row['pid']] = true;
    }
    foreach ($weeks as &$w) { $w['active'] = count($w['players']); unset($w['players']); }
    unset($w);
    $levels = [];
    $names = gv_level_names();
    for ($i = 1; $i <= $nLevels; $i++) $levels[$i] = ['name' => $names[$i - 1] ?? 'Level ' . $i, 'done' => 0, 'started' => 0, 'n' => 0, 'c' => 0, 't' => 0, 'stars' => 0];
    foreach ($recs as $r) {
        foreach ($levels as $i => &$lv) {
            if (isset($r['levelStat'][$i])) { $lv['started']++; $lv['n'] += $r['levelStat'][$i]['n']; $lv['c'] += $r['levelStat'][$i]['c']; $lv['t'] += $r['levelStat'][$i]['t']; }
            if (isset($r['levelStars'][$i])) { $lv['done']++; $lv['stars'] += $r['levelStars'][$i]; }
        }
        unset($lv);
    }
    return ['students' => $recs, 'skillNames' => $skillNames, 'weeks' => $weeks, 'levels' => $levels];
}
function gv_class_summary(array $class): array {
    $st = $class['students'];
    $registered = array_filter($st, fn($r) => $r['registered']);
    $t = ['students' => count($st), 'registered' => count($registered), 'playing' => 0, 'active7' => 0, 'n' => 0, 'c' => 0, 'timeouts' => 0,
          'secSum' => 0.0, 'secN' => 0, 'needHelp' => 0, 'skills' => [], 'finished' => 0];
    foreach ($class['skillNames'] as $sk) $t['skills'][$sk] = ['n' => 0, 'c' => 0];
    foreach ($st as $r) {
        if ($r['n'] > 0) $t['playing']++;
        if ($r['n'] > 0 && $r['daysIdle'] !== null && $r['daysIdle'] < INACTIVE_DAYS) $t['active7']++;
        $t['n'] += $r['n']; $t['c'] += $r['c']; $t['timeouts'] += $r['timeouts'];
        $t['secSum'] += $r['secSum']; $t['secN'] += $r['secN'];
        if ($r['status'] === 'help') $t['needHelp']++;
        if ($r['levelsDone'] >= gv_level_count()) $t['finished']++;
        foreach ($r['skills'] as $sk => $x) { $t['skills'][$sk]['n'] += $x['n']; $t['skills'][$sk]['c'] += $x['c']; }
    }
    $t['acc'] = pct($t['c'], $t['n']);
    $t['avgSec'] = $t['secN'] > 0 ? $t['secSum'] / $t['secN'] : null;
    $t['timeoutPct'] = pct($t['timeouts'], $t['n']);
    $t['weak'] = weak_skills($t['skills']);
    return $t;
}
const GV_STATUS_LABEL = [
    'good' => 'On track', 'help' => 'Needs help', 'idle' => 'Inactive', 'new' => 'Not started', 'pending' => 'Not registered',
];
function gv_student_detail(PDO $db, int $tid, array $rec): array {
    $out = ['mistakes' => [], 'sessions' => []];
    $q = $db->prepare('SELECT p.id FROM players p WHERE p.teacher_id = ? AND ' . ($rec['student_id'] ? 'p.student_id = ?' : 'p.id = ?'));
    $q->execute([$tid, $rec['student_id'] ?: (int)substr($rec['key'], 1)]);
    $pid = (int)$q->fetchColumn();
    if (!$pid) return $out;
    $m = $db->prepare('SELECT a.answered_at, a.level, a.category, a.question, a.given, a.timed_out FROM answers a JOIN runs r ON r.id = a.run_id
                       WHERE r.player_id = ? AND a.correct = 0 ORDER BY a.id DESC LIMIT 25');
    $m->execute([$pid]);
    $out['mistakes'] = $m->fetchAll(PDO::FETCH_ASSOC);
    $s = $db->prepare('SELECT r.started_at, r.finished_at, COUNT(a.id) AS n, SUM(a.correct) AS c, SUM(a.timed_out) AS t, MAX(a.answered_at) AS last
                       FROM runs r LEFT JOIN answers a ON a.run_id = r.id WHERE r.player_id = ? GROUP BY r.id ORDER BY r.id DESC LIMIT 10');
    $s->execute([$pid]);
    $out['sessions'] = $s->fetchAll(PDO::FETCH_ASSOC);
    return $out;
}
const KID_TIPS = [
    'Grammar' => ['Read the sentence out loud. Does it sound right? Your ears often know the answer!', 'Ask: who or what is the sentence about, and what are they doing? That helps you pick the right word.'],
    'Punctuation' => ['Questions end with ?, exciting sentences end with !, and telling sentences end with a period.', 'Read the sentence and listen for where your voice stops or goes up. That is where the mark goes!'],
    'Spelling' => ['Say the word slowly, then look, cover, write and check it. Try it with the words you missed!', 'Look for patterns: words that end the same way are often spelled the same way.'],
    'Vocabulary' => ['When you see a new word, look at the words around it. They give clues about what it means.', 'Tap a coloured word in the game to read what it means, then use it in your own sentence.'],
    'Reading Comprehension' => ['Read the story slowly. Then ask: who, what, where, and why?', 'Find the sentence in the story that proves your answer before you choose.'],
];
function gv_student_progress(PDO $db, array $s): array {
    $tid = (int)$s['teacher_id'];
    $class = gv_load_class($db, $tid, (int)$s['id']);
    $r = $class['students']['s' . $s['id']] ?? null;
    if (!$r) return [];
    $detail = gv_student_detail($db, $tid, $r);
    $names = gv_level_names();
    $nLevels = count($names);
    $levels = []; $current = null;
    foreach ($names as $i0 => $nm) {
        $i = $i0 + 1; $st = $r['levelStars'][$i] ?? null; $ls = $r['levelStat'][$i] ?? null;
        if ($st === null && $current === null) $current = $i;
        $levels[] = ['n' => $i, 'name' => $nm, 'status' => $st !== null ? 'done' : ($current === $i ? 'current' : 'todo'), 'stars' => $st ?? 0,
                     'answers' => $ls['n'] ?? 0, 'accuracy' => $ls ? pct($ls['c'], $ls['n']) : null, 'timeouts' => $ls['t'] ?? 0];
    }
    $skills = []; $focus = null; $lowest = 101;
    foreach ($class['skillNames'] as $sk) {
        $x = $r['skills'][$sk] ?? ['n' => 0, 'c' => 0]; $p = pct($x['c'], $x['n']);
        $label = $x['n'] < MIN_ANSWERS ? 'Just starting' : ($p >= 90 ? 'Super!' : ($p >= WEAK_BELOW ? 'Great' : ($p >= HELP_BELOW ? 'Getting there' : 'Keep practicing')));
        $skills[] = ['name' => $sk, 'answers' => $x['n'], 'correct' => $x['c'], 'accuracy' => $p, 'label' => $label];
        if ($x['n'] >= MIN_ANSWERS && $p < 90 && $p < $lowest) { $lowest = $p; $tips = KID_TIPS[$sk] ?? []; $focus = ['skill' => $sk, 'accuracy' => $p, 'tip' => $tips ? $tips[$x['n'] % count($tips)] : '']; }
    }
    $days = [];
    $q = $db->prepare('SELECT a.answered_at AS at, a.correct AS c FROM answers a JOIN runs r ON r.id = a.run_id JOIN players p ON p.id = r.player_id WHERE p.student_id = ? ORDER BY a.id DESC LIMIT 3000');
    $q->execute([(int)$s['id']]);
    foreach ($q as $row) {
        $k = gv_local($row['at'], 'Y-m-d');
        if (!isset($days[$k])) $days[$k] = ['label' => gv_local($row['at'], 'M j'), 'answers' => 0, 'correct' => 0];
        $days[$k]['answers']++; $days[$k]['correct'] += (int)$row['c'];
    }
    krsort($days);
    $days = array_reverse(array_slice($days, 0, 10, true));
    $dayList = [];
    foreach ($days as $d) $dayList[] = ['label' => $d['label'], 'answers' => $d['answers'], 'accuracy' => pct($d['correct'], $d['answers'])];
    $maxStar = $r['levelStars'] ? max($r['levelStars']) : 0;
    $b = function (string $id, string $icon, string $title, string $desc, bool $earned, string $prog = '') { return ['id' => $id, 'icon' => $icon, 'title' => $title, 'desc' => $desc, 'earned' => $earned, 'progress' => $earned ? '' : $prog]; };
    $badges = [
        $b('first',   '🌱', 'First Steps',       'Answer your first question',                 $r['n'] >= 1,   $r['n'] . ' / 1'),
        $b('warm',    '🔥', 'Warming Up',        'Answer 25 questions',                        $r['n'] >= 25,  min(25, $r['n']) . ' / 25'),
        $b('century', '💯', 'Century Club',      'Answer 100 questions',                       $r['n'] >= 100, min(100, $r['n']) . ' / 100'),
        $b('sharp',   '🎯', 'Sharp Mind',        'Score 80% or more after 20 answers',         $r['n'] >= 20 && ($r['acc'] ?? 0) >= 80, $r['n'] < 20 ? $r['n'] . ' / 20 answers' : 'Now ' . ($r['acc'] ?? 0) . '%'),
        $b('stars',   '⭐', 'Star Collector',    'Earn 5 stars in total',                      $r['stars'] >= 5, min(5, $r['stars']) . ' / 5'),
        $b('triple',  '🌟', 'Triple Star',       'Earn 3 stars on a level',                    $maxStar >= 3,  'Best so far: ' . $maxStar),
        $b('explore', '🧭', 'Explorer',          'Finish 3 levels',                            $r['levelsDone'] >= 3, min(3, $r['levelsDone']) . ' / 3'),
        $b('champ',   '🏆', 'Vault Champion',    'Finish every level',                         $r['levelsDone'] >= $nLevels, $r['levelsDone'] . ' / ' . $nLevels),
        $b('regular', '📅', 'Regular Player',    'Play on 3 different days',                   $r['days'] >= 3, min(3, $r['days']) . ' / 3'),
        $b('speedy',  '⚡', 'Speedy Reader',     'Average 12 seconds or less after 20 answers', $r['secN'] >= 20 && $r['avgSec'] !== null && $r['avgSec'] <= 12, $r['secN'] < 20 ? $r['secN'] . ' / 20 answers' : 'Now ' . round((float)$r['avgSec']) . ' s'),
        $b('ontime',  '⏱️', 'Always On Time',    'Answer 30 questions without running out of time', $r['n'] >= 30 && $r['timeouts'] === 0, $r['timeouts'] ? 'Time ran out ' . $r['timeouts'] . 'x' : $r['n'] . ' / 30'),
        $b('rise',    '📈', 'On the Rise',       'Your recent answers are better than before', $r['trend'] === 'up', 'Keep practising!'),
    ];
    if ($r['n'] === 0)                  $goal = ['Play your first round!', 'Talk to a character and answer 3 questions.'];
    elseif ($current !== null)          $goal = ['Finish ' . $names[$current - 1], 'Master the characters and reach the accuracy goal to open the EXIT.'];
    else {
        $low = null; foreach ($r['levelStars'] as $lv => $st) if ($st < 3 && ($low === null || $st < $r['levelStars'][$low])) $low = $lv;
        $goal = $low !== null ? ['Win 3 stars on ' . $names[$low - 1], 'Answer carefully to make fewer mistakes.'] : ['You finished every level with 3 stars!', 'Amazing work! Play again to keep your skills sharp.'];
    }
    $review = [];
    foreach (array_slice($detail['mistakes'], 0, 6) as $m) $review[] = ['skill' => $m['category'], 'level' => (int)$m['level'], 'question' => $m['question'], 'when' => gv_local($m['answered_at'], 'M j'), 'timedOut' => (int)$m['timed_out'] === 1];
    return [
        'name' => $s['full_name'], 'first_name' => gv_first_name($s['full_name']), 'student_no' => $s['student_no'], 'grade' => (int)$s['grade'],
        'teacher' => ($t = gv_teacher_by_id($db, $tid)) ? $t['display_name'] : '',
        'totals' => ['answers' => $r['n'], 'correct' => $r['c'], 'accuracy' => $r['acc'], 'recent' => $r['recent'], 'trend' => $r['trend'], 'trendDelta' => $r['trendDelta'] ?? null,
                     'stars' => $r['stars'], 'maxStars' => $nLevels * 3, 'levelsDone' => $r['levelsDone'], 'levelCount' => $nLevels, 'days' => $r['days'],
                     'avgSec' => $r['avgSec'] !== null ? round($r['avgSec'], 1) : null, 'timeouts' => $r['timeouts'], 'last' => gv_ago($r['last'])],
        'levels' => $levels, 'skills' => $skills, 'days' => $dayList, 'badges' => $badges, 'goal' => ['title' => $goal[0], 'text' => $goal[1]], 'focus' => $focus, 'review' => $review,
    ];
}
function gv_class_hard_questions(PDO $db, int $tid, int $limit = 8): array {
    $q = $db->prepare('SELECT a.question AS question, a.category AS category, MIN(a.level) AS level, COUNT(*) AS n, SUM(1 - a.correct) AS wrong,
                              SUM(a.timed_out) AS timeouts, COUNT(DISTINCT r.player_id) AS kids
                       FROM answers a JOIN runs r ON r.id = a.run_id JOIN players p ON p.id = r.player_id
                       WHERE p.teacher_id = ? GROUP BY a.question, a.category HAVING COUNT(*) >= 3 AND SUM(1 - a.correct) >= 2
                       ORDER BY (1.0 * SUM(1 - a.correct) / COUNT(*)) DESC, COUNT(*) DESC LIMIT ' . (int)$limit);
    $q->execute([$tid]);
    return $q->fetchAll(PDO::FETCH_ASSOC);
}
