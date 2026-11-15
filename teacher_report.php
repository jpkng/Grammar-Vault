<?php
declare(strict_types=1);
require __DIR__ . '/gv_db.php';
require __DIR__ . '/gv_stats.php';
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('gv_teacher');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict', 'secure' => $isHttps]);
session_start();
function fail(int $code, string $msg): void {
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit;
}
if (!class_exists('PDO') || !in_array('sqlite', PDO::getAvailableDrivers(), true)) fail(500, 'SQLite driver missing on the server.');
$db = gv_open_db();
$me = null;
if (!empty($_SESSION['teacher_id']) && (time() - (int)($_SESSION['last_seen'] ?? 0)) < 7200) $me = gv_teacher_by_id($db, (int)$_SESSION['teacher_id']);
if (!$me) { header('Location: teacher.php'); exit; }
$tid = (int)$me['id'];
$type = (string)($_GET['type'] ?? 'class');
$format = (string)($_GET['format'] ?? 'docx');
if (!in_array($type, ['class', 'student', 'pack'], true) || !in_array($format, ['docx', 'print'], true)) fail(400, 'Unknown report.');
$class = gv_load_class($db, $tid);
$sum = gv_class_summary($class);
$students = $class['students'];
uasort($students, fn($a, $b) => strcasecmp($a['name'], $b['name']));
const C_GREEN = 'D9F5E1'; const C_LIME = 'EAF6CF'; const C_ORANGE = 'FFE9C7'; const C_RED = 'FAD9D7'; const C_GREY = 'EEF1F5';
function rating(?int $p, int $n): array {
    if ($p === null || $n === 0) return ['No answers yet', C_GREY];
    if ($n < MIN_ANSWERS)        return ['Too few answers', C_GREY];
    if ($p >= 90)                return ['Excellent', C_GREEN];
    if ($p >= WEAK_BELOW)        return ['Good', C_LIME];
    if ($p >= HELP_BELOW)        return ['Needs practice', C_ORANGE];
    return ['Needs a lot of help', C_RED];
}
function acc_cell(?int $p, int $n): array {
    if ($p === null) return ['t' => '—', 'fill' => C_GREY];
    return ['t' => $p . '%', 'fill' => rating($p, $n)[1], 'b' => true];
}
function trend_text(array $r): string {
    if ($r['trend'] === 'up')   return 'Improving (+' . (int)$r['trendDelta'] . ')';
    if ($r['trend'] === 'down') return 'Dropping (' . (int)$r['trendDelta'] . ')';
    if ($r['trend'] === 'flat') return 'Steady';
    return '—';
}
function status_fill(string $s): string { return ['good' => C_GREEN, 'help' => C_RED, 'idle' => C_ORANGE][$s] ?? C_GREY; }
function level_text(array $r): string { return $r['levelsDone'] . ' of ' . gv_level_count() . ' · ' . $r['stars'] . ' star' . ($r['stars'] === 1 ? '' : 's'); }
function today(): string { return gv_local(gmdate('Y-m-d H:i:s'), 'F j, Y'); }
function slug(string $s): string { return trim(preg_replace('/[^A-Za-z0-9]+/', '_', iconv('UTF-8', 'ASCII//TRANSLIT', $s) ?: $s) ?? '', '_') ?: 'report'; }
function band_counts(array $students): array {
    $c = ['Excellent (90%+)' => 0, 'Good (' . WEAK_BELOW . '–89%)' => 0, 'Needs practice (' . HELP_BELOW . '–' . (WEAK_BELOW - 1) . '%)' => 0, 'Needs a lot of help (under ' . HELP_BELOW . '%)' => 0, 'No answers yet' => 0];
    $k = array_keys($c);
    foreach ($students as $r) {
        if ($r['acc'] === null || $r['n'] === 0) $c[$k[4]]++;
        elseif ($r['acc'] >= 90) $c[$k[0]]++;
        elseif ($r['acc'] >= WEAK_BELOW) $c[$k[1]]++;
        elseif ($r['acc'] >= HELP_BELOW) $c[$k[2]]++;
        else $c[$k[3]]++;
    }
    return $c;
}
function blocks_class(PDO $db, int $tid, array $me, array $class, array $sum, array $students): array {
    $b = [];
    $withData = array_values(array_filter($students, fn($r) => $r['n'] > 0));
    $b[] = ['page', 'Overview'];
    $b[] = ['title', 'Class Progress Report', $me['display_name'] . ' · Grammar Vault · ' . today()];
    $b[] = ['stats', [
        [$sum['registered'] . ' / ' . $sum['students'], 'Students registered'],
        [(string)$sum['playing'], 'Have started playing'],
        [(string)$sum['active7'], 'Played in last ' . INACTIVE_DAYS . ' days'],
        [$sum['acc'] === null ? '—' : $sum['acc'] . '%', 'Class accuracy'],
        [gv_fmt_seconds($sum['avgSec']), 'Avg time per answer'],
        [$sum['timeoutPct'] === null ? '—' : $sum['timeoutPct'] . '%', 'Ran out of time'],
    ]];
    $b[] = ['stats', [
        [(string)$sum['n'], 'Answers recorded'],
        [(string)$sum['needHelp'], 'Students needing help'],
        [(string)count(array_filter($students, fn($r) => $r['status'] === 'idle')), 'Inactive students'],
        [(string)count(array_filter($students, fn($r) => $r['status'] === 'new' || $r['status'] === 'pending')), 'Not started / not registered'],
        [(string)$sum['finished'], 'Finished every level'],
        [(string)array_sum(array_map(fn($r) => $r['stars'], $students)), 'Stars earned by the class'],
    ]];
    $b[] = ['p', 'Each question has a 30-second limit. A question that runs out of time counts as a wrong answer. A skill is flagged when a student has answered at least ' . MIN_ANSWERS . ' questions in it and scores below ' . WEAK_BELOW . '%.', ['italic' => true, 'color' => '5C6B8A', 'size' => 9]];
    $imp = array_values(array_filter($students, fn($r) => $r['trend'] === 'up')); usort($imp, fn($a, $c) => $c['trendDelta'] <=> $a['trendDelta']);
    $drop = array_values(array_filter($students, fn($r) => $r['trend'] === 'down')); usort($drop, fn($a, $c) => $a['trendDelta'] <=> $c['trendDelta']);
    $top = $withData; usort($top, fn($a, $c) => ($c['acc'] ?? 0) <=> ($a['acc'] ?? 0));
    $b[] = ['h2', 'Highlights'];
    $hl = [];
    if ($top) $hl[] = 'Top accuracy: ' . implode(', ', array_map(fn($r) => $r['name'] . ' ' . $r['acc'] . '%', array_slice($top, 0, 3))) . '.';
    if ($imp) $hl[] = 'Improving: ' . implode(', ', array_map(fn($r) => $r['name'] . ' (+' . (int)$r['trendDelta'] . ')', array_slice($imp, 0, 4))) . '.';
    if ($drop) $hl[] = 'Dropping, worth a check-in: ' . implode(', ', array_map(fn($r) => $r['name'] . ' (' . (int)$r['trendDelta'] . ')', array_slice($drop, 0, 4))) . '.';
    $slow = array_values(array_filter($withData, fn($r) => $r['timeoutPct'] !== null && $r['n'] >= 10 && $r['timeoutPct'] >= SLOW_TIMEOUT_PCT));
    if ($slow) $hl[] = 'Often run out of time: ' . implode(', ', array_map(fn($r) => $r['name'] . ' (' . $r['timeoutPct'] . '%)', array_slice($slow, 0, 5))) . '.';
    $b[] = $hl ? ['bullets', $hl] : ['p', 'Highlights will appear once students have played more.'];
    if ($withData) {
        $notes = [];
        foreach ($class['skillNames'] as $sk) {
            $best = null; $worst = null;
            foreach ($withData as $r) { $x = $r['skills'][$sk]; if ($x['n'] < MIN_ANSWERS) continue; $p = pct($x['c'], $x['n']); if ($best === null || $p > $best[1]) $best = [$r['name'], $p]; if ($worst === null || $p < $worst[1]) $worst = [$r['name'], $p]; }
            if ($best && $worst) $notes[] = $sk . ': strongest ' . $best[0] . ' (' . $best[1] . '%), weakest ' . $worst[0] . ' (' . $worst[1] . '%).';
        }
        if ($notes) { $b[] = ['h2', 'Strongest and weakest in each skill']; $b[] = ['bullets', $notes]; }
    }
    if ($sum['weak']) {
        $b[] = ['h2', 'Class-wide focus'];
        $b[] = ['p', 'Weakest skills across the class: ' . implode(', ', array_map(fn($w) => $w['skill'] . ' ' . $w['pct'] . '%', $sum['weak'])), ['bold' => true]];
        $tips = [];
        foreach ($sum['weak'] as $w) foreach (array_slice(TIPS[$w['skill']] ?? [], 0, 1) as $t) $tips[] = $w['skill'] . ': ' . $t;
        $b[] = ['bullets', $tips];
    }
    $b[] = ['h2', 'How the class is doing'];
    $rows = []; $tot = max(1, count($students));
    foreach (band_counts($students) as $lbl => $n) $rows[] = [$lbl, (string)$n, round(100 * $n / $tot) . '%'];
    $b[] = ['table', [['Overall accuracy band', 8], ['Students', 2, 'c'], ['Share of class', 3, 'c']], $rows];
    $attn = array_values(array_filter($students, fn($r) => $r['status'] !== 'good' && $r['why']));
    usort($attn, fn($a, $b) => (($a['status'] === 'help' ? 0 : 1) <=> ($b['status'] === 'help' ? 0 : 1)) ?: (($a['acc'] ?? 101) <=> ($b['acc'] ?? 101)));
    $b[] = ['h2', 'Students who need attention'];
    if ($attn) {
        $rows = [];
        foreach ($attn as $r) $rows[] = [$r['name'], ['t' => GV_STATUS_LABEL[$r['status']], 'fill' => status_fill($r['status'])], implode('; ', $r['why']), acc_cell($r['acc'], $r['n']), gv_ago($r['last'])];
        $b[] = ['table', [['Student', 3], ['Status', 2], ['Why', 7], ['Accuracy', 2, 'c'], ['Last played', 2]], $rows, ['paginate' => 5]];
    } else $b[] = ['p', 'Everyone who has started is on track.'];
    $b[] = ['page', 'Skills & levels'];
    $b[] = ['h2', 'Skills across the class'];
    $rows = [];
    foreach ($class['skillNames'] as $sk) {
        $x = $sum['skills'][$sk]; $p = pct($x['c'], $x['n']); [$lbl, $fill] = rating($p, $x['n']);
        $weakKids = count(array_filter($students, fn($r) => ($r['skills'][$sk]['n'] ?? 0) >= MIN_ANSWERS && pct($r['skills'][$sk]['c'], $r['skills'][$sk]['n']) < WEAK_BELOW));
        $rows[] = [$sk, (string)$x['n'], (string)$x['c'], acc_cell($p, $x['n']), ['t' => $lbl, 'fill' => $fill], (string)$weakKids];
    }
    $b[] = ['table', [['Skill', 5], ['Answers', 2, 'c'], ['Correct', 2, 'c'], ['Accuracy', 2, 'c'], ['Rating', 4], ['Students below ' . WEAK_BELOW . '%', 3, 'c']], $rows];
    $b[] = ['h2', 'Progress by level'];
    $rows = [];
    foreach ($class['levels'] as $i => $lv) {
        $lp = pct($lv['c'], $lv['n']);
        $rows[] = [$i . '. ' . $lv['name'], $lv['done'] . ' of ' . $sum['students'], (string)max(0, $lv['started'] - $lv['done']), (string)max(0, $sum['students'] - $lv['started']), acc_cell($lp, $lv['n']), $lv['done'] ? number_format($lv['stars'] / $lv['done'], 1) : '—', (string)$lv['t']];
    }
    $b[] = ['table', [['Level', 6], ['Completed', 3, 'c'], ['In progress', 3, 'c'], ['Not started', 3, 'c'], ['Accuracy', 2, 'c'], ['Avg stars', 2, 'c'], ['Timed out', 2, 'c']], $rows];
    $b[] = ['page', 'Skill map'];
    $b[] = ['h2', 'Who is strong or weak in which skill'];
    $b[] = ['p', 'Accuracy per skill for every student. Colours match the ratings: green is excellent, light green is good, orange needs practice, red needs a lot of help, grey means too few answers.', ['italic' => true, 'color' => '5C6B8A', 'size' => 9]];
    if ($withData) {
        $rows = [];
        foreach ($students as $r) {
            if ($r['n'] === 0) continue;
            $row = [$r['name']];
            foreach ($class['skillNames'] as $sk) { $x = $r['skills'][$sk]; $row[] = acc_cell(pct($x['c'], $x['n']), $x['n']); }
            $row[] = acc_cell($r['acc'], $r['n']);
            $rows[] = $row;
        }
        $cols = [['Student', 5]]; foreach ($class['skillNames'] as $sk) $cols[] = [$sk, 3, 'c']; $cols[] = ['Overall', 2, 'c'];
        $b[] = ['table', $cols, $rows];
    } else $b[] = ['p', 'No answers yet.'];
    $b[] = ['page', 'Questions & activity'];
    $hard = gv_class_hard_questions($db, $tid, 8);
    $b[] = ['h2', 'Questions the class misses most'];
    if ($hard) {
        $rows = [];
        foreach ($hard as $h) $rows[] = [$h['question'], $h['category'], (string)(int)$h['level'], (string)(int)$h['n'], acc_cell(pct((int)$h['n'] - (int)$h['wrong'], (int)$h['n']), (int)$h['n']), (string)(int)$h['timeouts']];
        $b[] = ['p', 'Good candidates to go over together in class.', ['italic' => true, 'color' => '5C6B8A', 'size' => 9]];
        $b[] = ['table', [['Question', 9], ['Skill', 3], ['Lvl', 1, 'c'], ['Times answered', 2, 'c'], ['Correct', 2, 'c'], ['Timed out', 2, 'c']], $rows];
    } else $b[] = ['p', 'Not enough repeated answers yet to find the hardest questions.'];
    $b[] = ['h2', 'Activity by week'];
    $rows = [];
    foreach ($class['weeks'] as $w) $rows[] = ['Week of ' . $w['label'], (string)$w['active'], (string)$w['n'], acc_cell(pct($w['c'], $w['n']), $w['n'])];
    $b[] = ['table', [['Week', 4], ['Students playing', 3, 'c'], ['Answers', 3, 'c'], ['Accuracy', 3, 'c']], $rows];
    $b[] = ['page', 'All students'];
    $b[] = ['h2', 'All students'];
    if ($students) {
        $rows = [];
        foreach ($students as $r) {
            $rows[] = [$r['name'], $r['student_no'] ?: '—', $r['grade'] ? (string)$r['grade'] : '—', level_text($r), acc_cell($r['acc'], $r['n']), trend_text($r),
                       gv_fmt_seconds($r['avgSec']), $r['n'] ? $r['timeouts'] . ($r['timeoutPct'] !== null ? ' (' . $r['timeoutPct'] . '%)' : '') : '—', (string)$r['runs'], $r['last'] ? gv_local($r['last'], 'M j') : '—',
                       ['t' => GV_STATUS_LABEL[$r['status']], 'fill' => status_fill($r['status'])]];
        }
        $b[] = ['table', [['Student', 4], ['Student ID', 3], ['Grade', 2, 'c'], ['Levels', 4], ['Accuracy', 2, 'c'], ['Trend', 3], ['Avg time', 2, 'c'], ['Timed out', 2, 'c'], ['Plays', 2, 'c'], ['Last played', 2], ['Status', 3]], $rows];
    } else $b[] = ['p', 'No students yet.'];
    return $b;
}
function blocks_student(PDO $db, int $tid, array $me, array $r, bool $pageBreak, array $classSum = [], bool $pages = true): array {
    $d = gv_student_detail($db, $tid, $r);
    $weeks = [];
    if ($r['student_id']) { $one = gv_load_class($db, $tid, (int)$r['student_id']); $weeks = $one['weeks']; }
    $b = [];
    if ($pageBreak) $b[] = ['pagebreak'];
    $pg = fn(string $t) => $pages ? [['page', $t]] : [];
    array_push($b, ...$pg('Summary'));
    $b[] = ['title', 'Student Progress Report', $r['name'] . ' · ' . ($r['grade'] ? 'Grade ' . $r['grade'] . ' · ' : '') . today()];
    $b[] = ['kv', [
        ['Student', $r['name']], ['Student ID', $r['student_no'] ?: '—'], ['Username', $r['username'] ?: '—'],
        ['Grade', $r['grade'] ? (string)$r['grade'] : '—'], ['Teacher', $me['display_name']], ['Status', GV_STATUS_LABEL[$r['status']]],
        ['Joined', !empty($r['created_at']) ? gv_local($r['created_at'], 'M j, Y') : '—'], ['Last login', !empty($r['last_login']) ? gv_local($r['last_login'], 'M j, Y') : '—'], ['Last played', $r['last'] ? gv_ago($r['last']) : 'never'],
    ]];
    $cAcc = $classSum['acc'] ?? null;
    $b[] = ['stats', [
        [$r['acc'] === null ? '—' : $r['acc'] . '%', 'Overall accuracy'],
        [$cAcc === null ? '—' : $cAcc . '%', 'Class average'],
        [$r['recent'] === null ? '—' : $r['recent'] . '%', 'Last ' . TREND_WINDOW . ' answers'],
        [$r['levelsDone'] . ' / ' . gv_level_count(), 'Levels completed'],
        [(string)$r['stars'], 'Stars earned'],
        [gv_fmt_seconds($r['avgSec']), 'Avg time per answer'],
    ]];
    $b[] = ['p', $r['n'] . ' answers recorded over ' . $r['days'] . ' day' . ($r['days'] === 1 ? '' : 's') . ' · ' . $r['runs'] . ' playthrough' . ($r['runs'] === 1 ? '' : 's') . ' · ' . $r['timeouts'] . ' ran out of time' . ($r['timeoutPct'] !== null ? ' (' . $r['timeoutPct'] . '%)' : '') . ' · last played ' . gv_ago($r['last']) . ($r['last'] ? ' (' . gv_local($r['last']) . ')' : '') . ' · trend: ' . trend_text($r) . '.', ['italic' => true, 'color' => '5C6B8A', 'size' => 9]];
    $b[] = ['h2', 'Teacher notes'];
    if ($r['why']) $b[] = ['bullets', $r['why']];
    elseif ($r['n'] > 0) $b[] = ['p', 'On track. No skill is below ' . WEAK_BELOW . '%, activity is recent, and accuracy is not dropping.'];
    else $b[] = ['p', 'No answers yet.'];
    $strong = []; foreach ($r['skills'] as $sk => $x) { if ($x['n'] >= MIN_ANSWERS && pct($x['c'], $x['n']) >= 85) $strong[] = $sk . ' ' . pct($x['c'], $x['n']) . '%'; }
    $b[] = ['h2', 'Strengths'];
    $b[] = ['p', $strong ? implode(', ', $strong) . '.' : ($r['n'] ? 'No skill is above 85% yet (with at least ' . MIN_ANSWERS . ' answers).' : 'No answers yet.')];
    if ($r['weak']) {
        $b[] = ['h2', 'Suggested practice'];
        $tips = [];
        foreach ($r['weak'] as $w) foreach (TIPS[$w['skill']] ?? [] as $t) $tips[] = $w['skill'] . ' (' . $w['pct'] . '%): ' . $t;
        $b[] = ['bullets', $tips];
    }
    array_push($b, ...$pg('Skills & levels'));
    $b[] = ['h2', 'Skills compared with the class'];
    $rows = [];
    foreach ($r['skills'] as $sk => $x) {
        $p = pct($x['c'], $x['n']); $cx = $classSum['skills'][$sk] ?? ['n' => 0, 'c' => 0]; $cp = pct($cx['c'], $cx['n']);
        $diff = ($p !== null && $cp !== null && $x['n'] >= MIN_ANSWERS) ? ($p - $cp >= 0 ? '+' : '') . ($p - $cp) . ' pts' : '—';
        $rows[] = [$sk, (string)$x['n'], (string)$x['c'], acc_cell($p, $x['n']), ['t' => rating($p, $x['n'])[0], 'fill' => rating($p, $x['n'])[1]], $cp === null ? '—' : $cp . '%', $diff];
    }
    $b[] = ['table', [['Skill', 5], ['Answers', 2, 'c'], ['Correct', 2, 'c'], ['Accuracy', 2, 'c'], ['Rating', 4], ['Class', 2, 'c'], ['Versus class', 3, 'c']], $rows];
    $b[] = ['h2', 'Progress by level'];
    $rows = [];
    foreach (gv_level_names() as $i0 => $nm) {
        $i = $i0 + 1; $ls = $r['levelStat'][$i] ?? null; $st = $r['levelStars'][$i] ?? null;
        $res = $st !== null ? 'Completed · ' . $st . ' star' . ($st === 1 ? '' : 's') : ($ls ? 'In progress' : 'Not started');
        $rows[] = [$i . '. ' . $nm, ['t' => $res, 'fill' => $st !== null ? C_GREEN : ($ls ? C_LIME : C_GREY)], $ls ? (string)$ls['n'] : '0', $ls ? acc_cell(pct($ls['c'], $ls['n']), $ls['n']) : '—', $ls ? (string)$ls['t'] : '0'];
    }
    $b[] = ['table', [['Level', 6], ['Result', 4], ['Answers', 2, 'c'], ['Accuracy', 2, 'c'], ['Timed out', 2, 'c']], $rows];
    array_push($b, ...$pg('Activity & mistakes'));
    $b[] = ['h2', 'Playthroughs'];
    if ($r['perRun']) {
        $rows = []; $i = 0;
        foreach ($r['perRun'] as $pr) { $i++; $rows[] = ['Playthrough ' . $i, (string)$pr['n'], (string)$pr['c'], acc_cell(pct($pr['c'], $pr['n']), $pr['n'])]; }
        $b[] = ['table', [['Playthrough', 5], ['Answers', 3, 'c'], ['Correct', 3, 'c'], ['Accuracy', 3, 'c']], $rows];
    } else $b[] = ['p', 'No playthroughs yet.'];
    if ($weeks) {
        $b[] = ['h2', 'Activity by week'];
        $rows = [];
        foreach ($weeks as $w) $rows[] = ['Week of ' . $w['label'], (string)$w['n'], acc_cell(pct($w['c'], $w['n']), $w['n'])];
        $b[] = ['table', [['Week', 5], ['Answers', 3, 'c'], ['Accuracy', 3, 'c']], $rows];
    }
    if ($d['mistakes']) {
        $b[] = ['h2', 'Recent mistakes'];
        $rows = [];
        foreach (array_slice($d['mistakes'], 0, 15) as $m) $rows[] = [gv_local($m['answered_at'], 'M j'), (string)(int)$m['level'], $m['category'], $m['question'], (int)$m['timed_out'] ? 'Time ran out' : ($m['given'] === '' ? '(blank)' : $m['given'])];
        $b[] = ['table', [['Date', 2], ['Lvl', 1, 'c'], ['Skill', 3], ['Question', 8], ['Answered', 3]], $rows];
    }
    return $b;
}
$landscape = $type === 'class';
if ($type === 'class') {
    $blocks = blocks_class($db, $tid, $me, $class, $sum, $students);
    $fileBase = 'GrammarVault_Class_Report_' . gmdate('Y-m-d');
    $docTitle = 'Class Progress Report — ' . $me['display_name'];
} elseif ($type === 'student') {
    $key = (string)($_GET['s'] ?? '');
    if (!preg_match('/^[sp]\d+$/', $key) || !isset($class['students'][$key])) fail(404, 'Student not found.');
    $blocks = blocks_student($db, $tid, $me, $class['students'][$key], false, $sum);
    $fileBase = 'GrammarVault_Report_' . slug($class['students'][$key]['name']) . '_' . gmdate('Y-m-d');
    $docTitle = 'Student Progress Report — ' . $class['students'][$key]['name'];
} else {
    $blocks = [];
    $first = true;
    foreach ($students as $r) { $blocks = array_merge($blocks, blocks_student($db, $tid, $me, $r, !$first, $sum, false)); $first = false; }
    if (!$blocks) fail(404, 'No students yet.');
    $fileBase = 'GrammarVault_All_Student_Reports_' . gmdate('Y-m-d');
    $docTitle = 'Student Progress Reports — ' . $me['display_name'];
}
$footerText = 'Grammar Vault · ' . $me['display_name'] . ' · ' . today();
function html_cell($c): string {
    if (!is_array($c)) return h((string)$c);
    return '<span' . (!empty($c['fill']) ? ' style="background:#' . $c['fill'] . '"' : '') . ' class="pill' . (!empty($c['b']) ? ' b' : '') . '">' . h($c['t']) . '</span>';
}
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function render_print(array $blocks, string $title, bool $landscape, string $footer, bool $breakBetween = false): void {
    $nonce = base64_encode(random_bytes(12));
    header('Content-Type: text/html; charset=utf-8');
    header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; script-src 'nonce-$nonce'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'");
    $pages = []; $cur = null;
    $open = function (string $label) use (&$pages, &$cur) { $cur = count($pages); $pages[$cur] = ['label' => $label, 'blocks' => []]; };
    foreach ($blocks as $bl) {
        if ($bl[0] === 'page') { $open((string)$bl[1]); continue; }
        if ($bl[0] === 'pagebreak') { $open(''); continue; }
        if ($cur === null) $open('');
        if ($bl[0] === 'title' && $pages[$cur]['label'] === '') $pages[$cur]['label'] = trim(explode(' · ', (string)$bl[2])[0]);
        $pages[$cur]['blocks'][] = $bl;
    }
    foreach ($pages as $i => &$pg) if ($pg['label'] === '') $pg['label'] = 'Page ' . ($i + 1);
    unset($pg);
    $count = count($pages);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><title>' . h($title) . '</title><style>';
    echo '@page{size:letter ' . ($landscape ? 'landscape' : 'portrait') . ';margin:14mm 14mm 16mm}';
    echo <<<'CSS'
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;font:11pt/1.4 Arial,Helvetica,sans-serif;color:#2D3047;background:#EEF1F5}
.bar{position:sticky;top:0;background:#fff;border-bottom:1px solid #D6DCE6;z-index:5}
.bar .row{display:flex;gap:12px;align-items:center;flex-wrap:wrap;padding:10px 16px}
.bar button,.bar a.btn{font:600 14px Arial;padding:8px 16px;border-radius:8px;border:0;background:#5B3FE0;color:#fff;cursor:pointer;text-decoration:none}
.bar a.btn.ghost{background:#fff;color:#2D3047;border:1px solid #C9D3E2}.bar .hint{color:#5C6B8A;font-size:13px}
.tabs{display:none;gap:6px;padding:0 16px 10px;flex-wrap:wrap;align-items:center}
.js .tabs{display:flex}
.tabs button{background:#EEF1F5;color:#2D3047;border:1px solid #D6DCE6;font:600 13px Arial;padding:7px 14px;border-radius:999px;cursor:pointer}
.tabs button.on{background:#5B3FE0;color:#fff;border-color:#5B3FE0}
.tabs .sp{flex:1}.tabs .pn{background:#fff}.tabs .pn:disabled{opacity:.4;cursor:default}
.tabs .cnt{color:#5C6B8A;font-size:13px;margin:0 4px}
.sheet{max-width:1100px;margin:16px auto;background:#fff;padding:26px 30px;box-shadow:0 2px 8px rgba(0,0,0,.12);overflow-x:auto}
.js .sheet{display:none}.js .sheet.on{display:block}
.run{color:#8A96AB;font-size:9.5pt;border-bottom:1px solid #E1E7F0;margin:-6px 0 8px;padding-bottom:6px}
.sheet[data-i="0"] .run{display:none}
h1{font-size:23pt;margin:0;color:#5B3FE0}.sub{color:#5C6B8A;margin:2px 0 14px;font-size:11pt;border-bottom:3px solid #5B3FE0;padding-bottom:8px}
h2{font-size:14pt;margin:18px 0 6px;color:#2D3047;border-bottom:1px solid #D6DCE6;padding-bottom:3px;break-after:avoid}
h2:first-child{margin-top:4px}
p{margin:6px 0}ul{margin:6px 0 6px 20px;padding:0}li{margin:2px 0}
table{border-collapse:collapse;width:100%;margin:6px 0 4px;font-size:9.5pt}
th{background:#5B3FE0;color:#fff;text-align:left;padding:5px 7px;font-size:9pt;vertical-align:bottom}
td{border-bottom:1px solid #E1E7F0;padding:5px 7px;vertical-align:top}
tr{break-inside:avoid}thead{display:table-header-group}
.c{text-align:center}
.pill{display:inline-block;padding:1px 8px;border-radius:999px;white-space:nowrap}.b{font-weight:700}
table.stats{table-layout:fixed;margin-bottom:8px}table.stats td{border:1px solid #D6DCE6;text-align:center;background:#F6F4FF;padding:8px 4px;vertical-align:middle}
.big{font-size:17pt;font-weight:700;color:#5B3FE0;display:block}.lab{font-size:8.5pt;color:#5C6B8A;display:block}
table.kv td{border:1px solid #E1E7F0;width:16.6%}table.kv td.k{background:#F4F8FC;color:#5C6B8A;font-weight:700}
.foot{display:none}
.pager{display:none;align-items:center;gap:6px;flex-wrap:wrap;margin:6px 0 10px;font-size:13px;color:#5C6B8A}
.js .pager{display:flex}
.pager button{font:600 13px Arial;min-width:34px;padding:6px 10px;border-radius:8px;border:1px solid #C9D3E2;background:#fff;color:#2D3047;cursor:pointer}
.pager button.on{background:#5B3FE0;border-color:#5B3FE0;color:#fff}.pager button:disabled{opacity:.4;cursor:default}
.pager .info{margin-right:auto}
.js tr.hid{display:none}
@media (max-width:700px){.sheet{padding:16px 14px}table.stats .big{font-size:13pt}}
@media print{
  .js tr.hid{display:table-row}.pager,.js .pager{display:none}
  body{background:#fff}.bar{display:none}.sheet,.js .sheet{display:block;max-width:none;margin:0;padding:0;box-shadow:none;overflow:visible}.run{display:none}
  .foot{display:block;position:fixed;bottom:0;left:0;right:0;text-align:center;font-size:8.5pt;color:#8A96AB}
  * {-webkit-print-color-adjust:exact;print-color-adjust:exact}
}
CSS;
    if ($breakBetween) echo '@media print{.sheet+.sheet{break-before:page}}';
    echo '</style></head><body><div class="bar"><div class="row"><button id="pr" type="button">Print / Save as PDF</button><a class="btn ghost" href="teacher.php?p=reports">← Back to Reports</a><span class="hint">Click Print, then choose “Save as PDF” as the printer to make a PDF. The printout includes every page.</span></div>';
    echo '<div class="tabs" id="tabs">';
    foreach ($pages as $i => $pg) echo '<button type="button" data-i="' . $i . '"' . ($i === 0 ? ' class="on"' : '') . '>' . h($pg['label']) . '</button>';
    echo '<span class="sp"></span><button type="button" class="pn" id="prev">← Previous</button><span class="cnt" id="cnt"></span><button type="button" class="pn" id="next">Next →</button></div></div>';
    foreach ($pages as $i => $pg) {
        echo '<div class="sheet' . ($i === 0 ? ' on' : '') . '" data-i="' . $i . '"><div class="run">' . h($title) . ' · ' . h($pg['label']) . ' · page ' . ($i + 1) . ' of ' . $count . '</div>';
        foreach ($pg['blocks'] as $bl) {
            switch ($bl[0]) {
                case 'title': echo '<h1>' . h($bl[1]) . '</h1><div class="sub">' . h($bl[2]) . '</div>'; break;
                case 'h2': echo '<h2>' . h($bl[1]) . '</h2>'; break;
                case 'p':
                    $o = $bl[2] ?? [];
                    echo '<p style="' . (!empty($o['italic']) ? 'font-style:italic;' : '') . (!empty($o['bold']) ? 'font-weight:700;' : '') . (!empty($o['color']) ? 'color:#' . $o['color'] . ';' : '') . (!empty($o['size']) ? 'font-size:' . $o['size'] . 'pt;' : '') . '">' . h($bl[1]) . '</p>';
                    break;
                case 'bullets': echo '<ul>'; foreach ($bl[1] as $it) echo '<li>' . h($it) . '</li>'; echo '</ul>'; break;
                case 'stats':
                    echo '<table class="stats"><tr>'; foreach ($bl[1] as [$v, $l]) echo '<td><span class="big">' . h($v) . '</span><span class="lab">' . h($l) . '</span></td>'; echo '</tr></table>'; break;
                case 'kv':
                    echo '<table class="kv">'; foreach (array_chunk($bl[1], 3) as $row) { echo '<tr>'; foreach ($row as [$k, $v]) echo '<td class="k">' . h($k) . '</td><td>' . h($v) . '</td>'; echo '</tr>'; } echo '</table>'; break;
                case 'table':
                    [, $cols, $rows] = $bl;
                    $per = (int)($bl[3]['paginate'] ?? 0); $pgd = $per > 0 && count($rows) > $per;
                    echo '<table' . ($pgd ? ' class="pgt" data-per="' . $per . '"' : '') . '><thead><tr>'; foreach ($cols as $c) echo '<th' . (($c[2] ?? '') === 'c' ? ' class="c"' : '') . '>' . h($c[0]) . '</th>'; echo '</tr></thead><tbody>';
                    foreach ($rows as $row) { echo '<tr>'; foreach ($row as $ci => $cell) echo '<td' . (($cols[$ci][2] ?? '') === 'c' ? ' class="c"' : '') . '>' . html_cell($cell) . '</td>'; echo '</tr>'; }
                    echo '</tbody></table>';
                    if ($pgd) echo '<div class="pager" data-total="' . count($rows) . '"></div>';
                    break;
            }
        }
        echo '</div>';
    }
    echo '<div class="foot">' . h($footer) . '</div><script nonce="' . $nonce . '">';
    echo <<<'JS'
(function(){
 var d=document,sheets=[].slice.call(d.querySelectorAll('.sheet')),tabs=[].slice.call(d.querySelectorAll('#tabs button[data-i]')),cur=0;
 d.documentElement.className+=' js';
 function show(i){ if(i<0||i>=sheets.length)return; cur=i;
  sheets.forEach(function(s,k){s.classList.toggle('on',k===i)}); tabs.forEach(function(t,k){t.classList.toggle('on',k===i)});
  d.getElementById('prev').disabled=i===0; d.getElementById('next').disabled=i===sheets.length-1;
  d.getElementById('cnt').textContent='Page '+(i+1)+' of '+sheets.length; window.scrollTo(0,0); }
 tabs.forEach(function(t){t.onclick=function(){show(+t.getAttribute('data-i'))}});
 d.getElementById('prev').onclick=function(){show(cur-1)}; d.getElementById('next').onclick=function(){show(cur+1)};
 d.addEventListener('keydown',function(e){ if(e.key==='ArrowRight')show(cur+1); if(e.key==='ArrowLeft')show(cur-1); });
 d.getElementById('pr').onclick=function(){window.print()};
 [].slice.call(d.querySelectorAll('table.pgt')).forEach(function(t){
  var per=+t.getAttribute('data-per'),rows=[].slice.call(t.tBodies[0].rows),pg=t.nextElementSibling,n=Math.ceil(rows.length/per),cur=0;
  function draw(){ rows.forEach(function(r,k){r.classList.toggle('hid',Math.floor(k/per)!==cur)});
   var a=cur*per+1,z=Math.min(rows.length,(cur+1)*per); pg.innerHTML='';
   var info=d.createElement('span');info.className='info';info.textContent='Showing '+a+'–'+z+' of '+rows.length+' students';pg.appendChild(info);
   function btn(label,to,on,dis){var b=d.createElement('button');b.type='button';b.textContent=label;if(on)b.className='on';b.disabled=!!dis;b.onclick=function(){cur=to;draw()};pg.appendChild(b);}
   btn('‹ Prev',cur-1,false,cur===0); for(var i=0;i<n;i++)btn(String(i+1),i,i===cur,false); btn('Next ›',cur+1,false,cur===n-1); }
  draw(); });
 if(sheets.length<2){ var t=d.getElementById('tabs'); t.style.display='none'; }
 show(0);
})();
JS;
    echo '</script></body></html>';
}
function x($s): string {
    $s = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', (string)$s) ?? '';
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
function d_run(string $t, array $o = []): string {
    $rpr = '';
    if (!empty($o['bold']))   $rpr .= '<w:b/>';
    if (!empty($o['italic'])) $rpr .= '<w:i/>';
    if (!empty($o['color']))  $rpr .= '<w:color w:val="' . $o['color'] . '"/>';
    if (!empty($o['size']))   $rpr .= '<w:sz w:val="' . (int)round($o['size'] * 2) . '"/><w:szCs w:val="' . (int)round($o['size'] * 2) . '"/>';
    return '<w:r>' . ($rpr ? '<w:rPr>' . $rpr . '</w:rPr>' : '') . '<w:t xml:space="preserve">' . x($t) . '</w:t></w:r>';
}
function d_p(string $inner, string $ppr = ''): string { return '<w:p>' . ($ppr ? '<w:pPr>' . $ppr . '</w:pPr>' : '') . $inner . '</w:p>'; }
function d_cell(int $w, string $paras, string $fill = '', int $span = 1): string {
    return '<w:tc><w:tcPr><w:tcW w:w="' . $w . '" w:type="dxa"/>' . ($span > 1 ? '<w:gridSpan w:val="' . $span . '"/>' : '')
         . ($fill ? '<w:shd w:val="clear" w:color="auto" w:fill="' . $fill . '"/>' : '') . '<w:vAlign w:val="center"/></w:tcPr>' . $paras . '</w:tc>';
}
function d_table(array $widths, string $rowsXml, bool $borders = true, string $color = 'D6DCE6'): string {
    $b = $borders ? '<w:tblBorders>' . implode('', array_map(fn($s) => '<w:' . $s . ' w:val="single" w:sz="4" w:space="0" w:color="' . $color . '"/>', ['top', 'left', 'bottom', 'right', 'insideH', 'insideV'])) . '</w:tblBorders>' : '';
    return '<w:tbl><w:tblPr><w:tblW w:w="' . array_sum($widths) . '" w:type="dxa"/>' . $b . '<w:tblLayout w:type="fixed"/><w:tblCellMar><w:top w:w="50" w:type="dxa"/><w:left w:w="90" w:type="dxa"/><w:bottom w:w="50" w:type="dxa"/><w:right w:w="90" w:type="dxa"/></w:tblCellMar></w:tblPr>'
         . '<w:tblGrid>' . implode('', array_map(fn($w) => '<w:gridCol w:w="' . $w . '"/>', $widths)) . '</w:tblGrid>' . $rowsXml . '</w:tbl>';
}
function d_scale(array $cols, int $total): array {
    $sum = array_sum(array_map(fn($c) => $c[1], $cols));
    $w = array_map(fn($c) => (int)floor($total * $c[1] / $sum), $cols);
    $w[count($w) - 1] += $total - array_sum($w);
    return $w;
}
function build_docx_body(array $blocks, int $total): string {
    $o = '';
    foreach ($blocks as $bl) {
        switch ($bl[0]) {
            case 'title':
                $o .= d_p(d_run($bl[1]), '<w:pStyle w:val="Title"/>') . d_p(d_run($bl[2], ['color' => '5C6B8A']), '<w:pStyle w:val="Subtitle"/>');
                break;
            case 'h2': $o .= d_p(d_run($bl[1]), '<w:pStyle w:val="Heading2"/>'); break;
            case 'p':  $o .= d_p(d_run($bl[1], $bl[2] ?? []), '<w:spacing w:before="60" w:after="60"/>'); break;
            case 'bullets':
                foreach ($bl[1] as $i) $o .= d_p(d_run($i), '<w:pStyle w:val="ListBullet"/><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr>');
                break;
            case 'pagebreak': $o .= '<w:p><w:r><w:br w:type="page"/></w:r></w:p>'; break;
            case 'stats': {
                $n = count($bl[1]); $w = array_fill(0, $n, intdiv($total, $n)); $w[$n - 1] += $total - array_sum($w);
                $row = '<w:tr><w:trPr><w:cantSplit/></w:trPr>';
                foreach ($bl[1] as $i => [$v, $l]) {
                    $row .= d_cell($w[$i], d_p(d_run($v, ['bold' => true, 'size' => 16, 'color' => '5B3FE0']), '<w:keepNext/><w:spacing w:before="40" w:after="0"/><w:jc w:val="center"/>')
                          . d_p(d_run($l, ['size' => 8, 'color' => '5C6B8A']), '<w:spacing w:before="0" w:after="40"/><w:jc w:val="center"/>'), 'F6F4FF');
                }
                $o .= d_table($w, $row . '</w:tr>') . d_p('', '<w:spacing w:before="0" w:after="0"/>');
                break;
            }
            case 'kv': {
                $lab = intdiv($total * 13, 100); $val = intdiv($total * 20, 100);
                $w = [$lab, $val, $lab, $val, $lab, $val]; $w[5] += $total - array_sum($w);
                $rows = '';
                foreach (array_chunk($bl[1], 3) as $chunk) {
                    $rows .= '<w:tr><w:trPr><w:cantSplit/></w:trPr>'; $ci = 0;
                    foreach ($chunk as [$k, $v]) { $rows .= d_cell($w[$ci++], d_p(d_run($k, ['bold' => true, 'color' => '5C6B8A', 'size' => 9])), 'F4F8FC') . d_cell($w[$ci++], d_p(d_run($v, ['size' => 10]))); }
                    $rows .= '</w:tr>';
                }
                $o .= d_table($w, $rows, true, 'E1E7F0') . d_p('', '<w:spacing w:before="0" w:after="0"/>');
                break;
            }
            case 'table': {
                [, $cols, $data] = $bl;
                $w = d_scale($cols, $total);
                $rows = '<w:tr><w:trPr><w:cantSplit/><w:tblHeader/></w:trPr>';
                foreach ($cols as $i => $c) $rows .= d_cell($w[$i], d_p(d_run($c[0], ['bold' => true, 'color' => 'FFFFFF', 'size' => 9]), '<w:keepNext/>' . (($c[2] ?? '') === 'c' ? '<w:jc w:val="center"/>' : '')), '5B3FE0');
                $rows .= '</w:tr>';
                foreach ($data as $row) {
                    $rows .= '<w:tr><w:trPr><w:cantSplit/></w:trPr>';
                    foreach ($row as $i => $cell) {
                        $t = is_array($cell) ? $cell['t'] : (string)$cell;
                        $fill = is_array($cell) ? ($cell['fill'] ?? '') : '';
                        $opt = ['size' => 9] + (is_array($cell) && !empty($cell['b']) ? ['bold' => true] : []);
                        $rows .= d_cell($w[$i], d_p(d_run($t, $opt), '<w:spacing w:before="0" w:after="0"/>' . (($cols[$i][2] ?? '') === 'c' ? '<w:jc w:val="center"/>' : '')), $fill);
                    }
                    $rows .= '</w:tr>';
                }
                $o .= d_table($w, $rows) . d_p('', '<w:spacing w:before="0" w:after="0"/>');
                break;
            }
        }
    }
    return $o;
}
function gv_zip(array $files): string {
    $out = ''; $central = ''; $n = 0;
    $t = getdate(); $dosTime = ($t['hours'] << 11) | ($t['minutes'] << 5) | intdiv($t['seconds'], 2);
    $dosDate = ((max(1980, $t['year']) - 1980) << 9) | ($t['mon'] << 5) | $t['mday'];
    foreach ($files as $name => $data) {
        $crc = crc32($data); $size = strlen($data); $off = strlen($out);
        $comp = $data; $method = 0;
        if (function_exists('gzdeflate')) { $z = gzdeflate($data, 6); if ($z !== false && strlen($z) < $size) { $comp = $z; $method = 8; } }
        $csize = strlen($comp);
        $out .= "PK\x03\x04" . pack('vvvvvVVVvv', 20, 0x0800, $method, $dosTime, $dosDate, $crc, $csize, $size, strlen($name), 0) . $name . $comp;
        $central .= "PK\x01\x02" . pack('vvvvvvVVVvvvvvVV', 20, 20, 0x0800, $method, $dosTime, $dosDate, $crc, $csize, $size, strlen($name), 0, 0, 0, 0, 0, $off) . $name;
        $n++;
    }
    return $out . $central . "PK\x05\x06" . pack('vvvvVVv', 0, 0, $n, $n, strlen($central), strlen($out), 0);
}
function render_docx(array $blocks, string $title, bool $landscape, string $footer, string $author): string {
    $pw = 12240; $ph = 15840; $mar = 1008;
    if ($landscape) { [$pw, $ph] = [$ph, $pw]; }
    $total = $pw - 2 * $mar;
    $ns = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"';
    $body = build_docx_body($blocks, $total);
    $sect = '<w:sectPr><w:footerReference w:type="default" r:id="rId3"/><w:pgSz w:w="' . $pw . '" w:h="' . $ph . '"' . ($landscape ? ' w:orient="landscape"' : '') . '/>'
          . '<w:pgMar w:top="' . $mar . '" w:right="' . $mar . '" w:bottom="' . $mar . '" w:left="' . $mar . '" w:header="500" w:footer="500" w:gutter="0"/></w:sectPr>';
    $head = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $document = $head . '<w:document ' . $ns . '><w:body>' . $body . $sect . '</w:body></w:document>';
    $footerXml = $head . '<w:ftr ' . $ns . '><w:p><w:pPr><w:pStyle w:val="Footer"/><w:jc w:val="center"/></w:pPr>' . d_run($footer . ' · Page ', ['size' => 8, 'color' => '8A96AB'])
        . '<w:r><w:rPr><w:color w:val="8A96AB"/><w:sz w:val="16"/></w:rPr><w:fldChar w:fldCharType="begin"/></w:r><w:r><w:rPr><w:color w:val="8A96AB"/><w:sz w:val="16"/></w:rPr><w:instrText xml:space="preserve"> PAGE </w:instrText></w:r><w:r><w:rPr><w:color w:val="8A96AB"/><w:sz w:val="16"/></w:rPr><w:fldChar w:fldCharType="separate"/></w:r>'
        . '<w:r><w:rPr><w:color w:val="8A96AB"/><w:sz w:val="16"/></w:rPr><w:t>1</w:t></w:r><w:r><w:rPr><w:color w:val="8A96AB"/><w:sz w:val="16"/></w:rPr><w:fldChar w:fldCharType="end"/></w:r></w:p></w:ftr>';
    $font = '<w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:eastAsia="Arial" w:cs="Arial"/>';
    $styles = $head . '<w:styles ' . $ns . '><w:docDefaults><w:rPrDefault><w:rPr>' . $font . '<w:sz w:val="21"/><w:szCs w:val="21"/><w:color w:val="2D3047"/><w:lang w:val="en-PH"/></w:rPr></w:rPrDefault>'
        . '<w:pPrDefault><w:pPr><w:spacing w:after="80" w:line="259" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>'
        . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:qFormat/></w:style>'
        . '<w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/><w:pPr><w:keepNext/><w:spacing w:before="0" w:after="40"/></w:pPr><w:rPr>' . $font . '<w:b/><w:color w:val="5B3FE0"/><w:sz w:val="44"/><w:szCs w:val="44"/></w:rPr></w:style>'
        . '<w:style w:type="paragraph" w:styleId="Subtitle"><w:name w:val="Subtitle"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/><w:pPr><w:pBdr><w:bottom w:val="single" w:sz="18" w:space="4" w:color="5B3FE0"/></w:pBdr><w:spacing w:before="0" w:after="200"/></w:pPr><w:rPr>' . $font . '<w:sz w:val="22"/></w:rPr></w:style>'
        . '<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/><w:pPr><w:keepNext/><w:keepLines/><w:pBdr><w:bottom w:val="single" w:sz="4" w:space="2" w:color="D6DCE6"/></w:pBdr><w:spacing w:before="280" w:after="100"/><w:outlineLvl w:val="1"/></w:pPr><w:rPr>' . $font . '<w:b/><w:color w:val="2D3047"/><w:sz w:val="28"/><w:szCs w:val="28"/></w:rPr></w:style>'
        . '<w:style w:type="paragraph" w:styleId="ListBullet"><w:name w:val="List Bullet"/><w:basedOn w:val="Normal"/><w:qFormat/><w:pPr><w:spacing w:before="20" w:after="40"/></w:pPr></w:style>'
        . '<w:style w:type="paragraph" w:styleId="Footer"><w:name w:val="footer"/><w:basedOn w:val="Normal"/><w:pPr><w:spacing w:after="0"/></w:pPr></w:style></w:styles>';
    $numbering = $head . '<w:numbering ' . $ns . '><w:abstractNum w:abstractNumId="0"><w:multiLevelType w:val="hybridMultilevel"/><w:lvl w:ilvl="0"><w:start w:val="1"/><w:numFmt w:val="bullet"/><w:lvlText w:val="•"/><w:lvlJc w:val="left"/><w:pPr><w:ind w:left="500" w:hanging="260"/></w:pPr><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/></w:rPr></w:lvl></w:abstractNum><w:num w:numId="1"><w:abstractNumId w:val="0"/></w:num></w:numbering>';
    $rel = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    $files = [
        '[Content_Types].xml' => $head . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            . '<Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/>'
            . '<Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/></Types>',
        '_rels/.rels' => $head . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="' . $rel . '/officeDocument" Target="word/document.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/></Relationships>',
        'word/_rels/document.xml.rels' => $head . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="' . $rel . '/styles" Target="styles.xml"/>'
            . '<Relationship Id="rId2" Type="' . $rel . '/numbering" Target="numbering.xml"/><Relationship Id="rId3" Type="' . $rel . '/footer" Target="footer1.xml"/></Relationships>',
        'word/document.xml' => $document,
        'word/styles.xml' => $styles,
        'word/numbering.xml' => $numbering,
        'word/footer1.xml' => $footerXml,
        'docProps/core.xml' => $head . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>' . x($title) . '</dc:title><dc:creator>' . x($author) . '</dc:creator><dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:created></cp:coreProperties>',
    ];
    return gv_zip($files);
}
if ($format === 'print') {
    render_print($blocks, $docTitle, $landscape, $footerText, $type === 'pack');
} else {
    $bin = render_docx($blocks, $docTitle, $landscape, $footerText, $me['display_name']);
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $fileBase . '.docx"');
    header('Content-Length: ' . strlen($bin));
    echo $bin;
}
