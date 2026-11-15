<?php
declare(strict_types=1);
require __DIR__ . '/gv_db.php';
require_once __DIR__ . '/gv_mail.php';
require __DIR__ . '/gv_stats.php';
const MAX_FAILS   = 5;
const LOCK_MINUTES = 15;
const IDLE_SECONDS = 7200;
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('gv_teacher');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict', 'secure' => $isHttps]);
session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function db(): PDO {
    static $db = null;
    if ($db) return $db;
    $db = gv_open_db();
    return $db;
}
function is_local(): bool { return gv_is_local(); }
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? '?'), 0, 64); }
function failed_attempts(): int {
    $q = db()->prepare('SELECT COUNT(*) FROM teacher_login_attempts WHERE ip = ? AND at > ?');
    $q->execute([client_ip(), time() - LOCK_MINUTES * 60]);
    return (int)$q->fetchColumn();
}
function record_failure(): void {
    db()->prepare('INSERT INTO teacher_login_attempts (ip, at) VALUES (?, ?)')->execute([client_ip(), time()]);
    db()->prepare('DELETE FROM teacher_login_attempts WHERE at < ?')->execute([time() - 86400]);
}
function clear_failures(): void {
    db()->prepare('DELETE FROM teacher_login_attempts WHERE ip = ?')->execute([client_ip()]);
}
function page_start(string $title): void {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="robots" content="noindex,nofollow"><title>' . h($title) . '</title><style>' . css() . '</style></head><body>';
}
function page_end(): void { echo '</body></html>'; }
function css(): string {
    return <<<'CSS'
:root{--ink:#2D3047;--soft:#5C6B8A;--line:#E1E7F0;--bg:#F4F8FC;--panel:#fff;--purple:#7C5CFF;--purple-d:#5B3FE0;
--green:#1E7A3C;--green-bg:#E6F9EC;--lime-bg:#EEF8DC;--lime:#4D7A12;--orange:#9A5A00;--orange-bg:#FFF0D6;--red:#B5352F;--red-bg:#FDEAEA;--grey:#8A96AB;--grey-bg:#F1F3F7}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
a{color:var(--purple-d)}
.wrap{max-width:1280px;margin:0 auto;padding:20px 20px 60px}
header.top{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px}
h1{font-size:28px;margin:0}h2{font-size:20px;margin:28px 0 10px}h3{font-size:17px;margin:0 0 6px}
.sub{color:var(--soft);margin:2px 0 0}
.card{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:18px;box-shadow:0 2px 0 rgba(45,48,71,.05)}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px}
.stat .n{font-size:30px;font-weight:800}.stat .l{color:var(--soft);font-size:14px}
button,.btn{display:inline-block;border:0;border-radius:10px;background:var(--purple);color:#fff;font:600 15px system-ui,sans-serif;padding:9px 16px;cursor:pointer;text-decoration:none}
button:hover,.btn:hover{background:var(--purple-d)}
.btn.ghost,button.ghost{background:#fff;color:var(--ink);border:1px solid var(--line)}
.btn.ghost:hover,button.ghost:hover{background:var(--grey-bg)}
form.filters{display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin:14px 0}
label{display:block;font-size:13px;color:var(--soft);margin-bottom:3px}
input[type=text],input[type=password],select{font:inherit;padding:8px 10px;border:1px solid #C9D3E2;border-radius:10px;background:#fff;min-width:150px}
.tablewrap{overflow-x:auto;border:1px solid var(--line);border-radius:14px;background:#fff}
table{border-collapse:collapse;width:100%;min-width:900px}
th,td{padding:10px 12px;border-bottom:1px solid var(--line);text-align:left;vertical-align:top}
th{background:#F8FAFD;font-size:13px;color:var(--soft);text-transform:uppercase;letter-spacing:.03em;white-space:nowrap}
tr:last-child td{border-bottom:0}
td.num,th.num{text-align:center}
.pill{display:inline-block;border-radius:999px;padding:2px 10px;font-size:13px;font-weight:700;white-space:nowrap}
.g-hi{background:var(--green-bg);color:var(--green)}.g-ok{background:var(--lime-bg);color:var(--lime)}
.g-mid{background:var(--orange-bg);color:var(--orange)}.g-lo{background:var(--red-bg);color:var(--red)}
.g-na{background:var(--grey-bg);color:var(--grey)}
.cell small{display:block;color:var(--grey);font-size:12px;margin-top:2px}
.chips{display:flex;gap:6px;flex-wrap:wrap}
.focus{border-left:6px solid var(--orange);background:#FFFBF2}
.focus.none{border-left-color:#4CD964;background:#F5FFF8}
.focus.nodata{border-left-color:#B7C4D6;background:#F8FAFD}
.skillrow{display:grid;grid-template-columns:200px 1fr 170px;gap:12px;align-items:center;margin:10px 0}
.bar{height:14px;background:#EAEFF5;border-radius:999px;overflow:hidden}.bar>i{display:block;height:100%}
.tips{margin:6px 0 0 18px;padding:0;color:var(--soft);font-size:14px}
.err{background:var(--red-bg);color:var(--red);padding:10px 14px;border-radius:10px;margin:12px 0}
.login{max-width:420px;margin:12vh auto}
.muted{color:var(--soft)}
.legend{font-size:13px;color:var(--soft);margin:8px 0}
.codecard{display:flex;gap:28px;align-items:center;flex-wrap:wrap;margin:0 0 14px;border-left:6px solid var(--purple)}
.codecard .code{font:800 38px system-ui,sans-serif;letter-spacing:.01em;color:var(--purple-d)}
.codecard .l{color:var(--soft);font-size:14px}
.codecard p{margin:0;max-width:560px;color:var(--soft);font-size:14px}
.ok{background:var(--green-bg);color:var(--green);padding:10px 14px;border-radius:10px;margin:12px 0}
.hdrbtns{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.login .field{width:100%;margin-bottom:12px}
.pager{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin:10px 0}.pgs{display:flex;gap:6px;flex-wrap:wrap}
.pg{padding:5px 12px;border:1px solid #C9D3E2;border-radius:8px;background:#fff;text-decoration:none;color:var(--purple-d);font-weight:600;font-size:14px}
.pg.on{background:var(--purple-d);color:#fff;border-color:var(--purple-d)}.pg.off{color:#B7C4D6;background:var(--grey-bg)}
.legendbox{margin:14px 0}.legendbox summary{cursor:pointer;font-weight:700;color:var(--purple-d)}
@media (max-width:700px){.skillrow{grid-template-columns:1fr}}
.grid2{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:14px;margin:14px 0}
nav.tabs{display:flex;gap:6px;flex-wrap:wrap;margin:0 0 18px;border-bottom:2px solid var(--line)}
nav.tabs a{padding:9px 16px;border-radius:10px 10px 0 0;text-decoration:none;color:var(--soft);font-weight:600}
nav.tabs a.on{background:#fff;color:var(--purple-d);border:1px solid var(--line);border-bottom:2px solid #fff;margin-bottom:-2px}
.btn.small{padding:4px 10px;font-size:13px}
button.danger{background:#C0392B}button.danger:hover{background:#962d22}
textarea{font:inherit;padding:8px 10px;border:1px solid #C9D3E2;border-radius:10px}
.trend{font-weight:700;font-size:14px;white-space:nowrap}.trend.up{color:var(--green)}.trend.down{color:var(--red)}.trend.flat{color:var(--grey)}
.lvls{display:inline-flex;gap:3px}.lvls i,.lvls-key{display:inline-block;width:22px;height:22px;border-radius:6px;font:700 12px/22px system-ui;text-align:center;font-style:normal;color:#fff}
.lvls-key{width:14px;height:14px;vertical-align:-2px;margin:0 2px 0 8px}
.lv-none{background:#E1E7F0;color:#8A96AB!important}.lv-part{background:#FFD23F;color:#7a5a00!important}
.lv-s1{background:#FF9F45}.lv-s2{background:#9ACD32}.lv-s3{background:#2FAE49}
.stars{color:#E8A400;letter-spacing:2px}
.lvrow{display:grid;grid-template-columns:240px 1fr 220px 190px;gap:12px;align-items:center;margin:10px 0}
.weeks{display:flex;gap:10px;align-items:flex-end}.wk{flex:1;text-align:center;font-size:13px}
.wkbar{height:110px;display:flex;align-items:flex-end;background:#F4F8FC;border-radius:8px}.wkbar i{display:block;width:100%;border-radius:8px 8px 0 0;min-height:2px}
.wkn{font-weight:700;margin-top:4px}.wkl{color:var(--soft)}.wka{color:var(--soft);font-size:12px;min-height:16px}
@media (max-width:900px){.lvrow{grid-template-columns:1fr}}
@media print{nav.tabs,.hdrbtns,form.filters{display:none}}
CSS;
}
function grade_class(?int $p, int $n): string {
    if ($p === null) return 'g-na';
    if ($n < MIN_ANSWERS) return 'g-na';
    if ($p >= 90) return 'g-hi';
    if ($p >= WEAK_BELOW) return 'g-ok';
    if ($p >= HELP_BELOW) return 'g-mid';
    return 'g-lo';
}
function bar_color(?int $p, int $n): string {
    return match (grade_class($p, $n)) {
        'g-hi' => '#4CD964', 'g-ok' => '#9ACD32', 'g-mid' => '#FF9F45', 'g-lo' => '#E14F4F', default => '#B7C4D6',
    };
}
function skill_cell(?array $s): string {
    $n = $s['n'] ?? 0; $c = $s['c'] ?? 0; $p = pct($c, $n);
    if ($p === null) return '<td class="num cell"><span class="pill g-na">—</span></td>';
    $extra = $n < MIN_ANSWERS ? '<small>only ' . $n . ' answer' . ($n === 1 ? '' : 's') . '</small>' : '<small>' . $c . '/' . $n . '</small>';
    return '<td class="num cell"><span class="pill ' . grade_class($p, $n) . '">' . $p . '%</span>' . $extra . '</td>';
}
function weak_chips(array $weak): string {
    if (!$weak) return '<span class="muted">—</span>';
    $h = '<div class="chips">';
    foreach ($weak as $w) {
        $cls = $w['level'] === 'help' ? 'g-lo' : 'g-mid';
        $h .= '<span class="pill ' . $cls . '" title="' . h($w['c'] . ' of ' . $w['n'] . ' correct') . '">' . h($w['skill']) . ' ' . $w['pct'] . '%</span>';
    }
    return $h . '</div>';
}
if (!class_exists('PDO') || !in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    page_start('Teacher Dashboard — SQLite needed');
    echo '<div class="wrap"><div class="card login" style="max-width:640px"><h1>One more setup step</h1>';
    echo '<p>Your PHP is missing the <strong>SQLite</strong> driver, which the results database needs.</p>';
    echo '<ol><li>In a terminal run <code>php --ini</code> and look at <em>Loaded Configuration File</em>.<br>';
    echo '<span class="muted">If it says <code>(none)</code>, copy <code>php.ini-development</code> (in your PHP folder) to <code>php.ini</code> first.</span></li>';
    echo '<li>Open that <code>php.ini</code> in Notepad. Remove the <code>;</code> at the start of these lines (add them if missing):<br>';
    echo '<code>extension_dir = "ext"</code><br><code>extension=pdo_sqlite</code><br><code>extension=sqlite3</code></li>';
    echo '<li>Save, stop the server (Ctrl+C), and start it again.</li>';
    echo '<li>Check it worked: <code>php -m</code> should list <code>pdo_sqlite</code> and <code>sqlite3</code>.</li></ol>';
    echo '<p class="muted">Then reload this page.</p></div></div>';
    page_end();
    exit;
}
$db = db();
function csrf_ok(): bool { return hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? '')); }
function start_teacher_session(int $id): void {
    session_regenerate_id(true);
    $_SESSION['teacher_id'] = $id;
    $_SESSION['last_seen'] = time();
    $_SESSION['csrf'] = bin2hex(random_bytes(24));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    if (csrf_ok()) {
        $_SESSION = [];
        session_destroy();
    }
    header('Location: teacher.php');
    exit;
}
$loginError = '';
$signupError = '';
$teacherCount = (int)$db->query('SELECT COUNT(*) FROM teachers')->fetchColumn();
$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['action'] ?? '') : '';
if ($action === 'login') {
    if (!csrf_ok()) {
        $loginError = 'Your page expired. Please try again.';
    } elseif (failed_attempts() >= MAX_FAILS) {
        $loginError = 'Too many wrong passwords. Please wait ' . LOCK_MINUTES . ' minutes and try again.';
    } else {
        $q = $db->prepare('SELECT id, password_hash FROM teachers WHERE username = ?');
        $q->execute([trim((string)($_POST['username'] ?? ''))]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        $okPw = password_verify((string)($_POST['password'] ?? ''), $row ? (string)$row['password_hash'] : password_hash('no-such-user', PASSWORD_DEFAULT));
        if ($row && $okPw) {
            clear_failures();
            start_teacher_session((int)$row['id']);
            header('Location: teacher.php');
            exit;
        }
        record_failure();
        usleep(600000);
        $loginError = 'That username or password is not correct.';
    }
} elseif ($action === 'signup') {
    if (!csrf_ok()) {
        $signupError = 'Your page expired. Please try again.';
    } elseif (failed_attempts() >= MAX_FAILS) {
        $signupError = 'Too many tries. Please wait ' . LOCK_MINUTES . ' minutes and try again.';
    } else {
        {
            $pw = (string)($_POST['password'] ?? '');
            if ($pw !== (string)($_POST['password2'] ?? '')) {
                $signupError = 'The two passwords did not match.';
            } else {
                $em = gv_clean_email($_POST['email'] ?? '');
                if (!gv_valid_email($em)) { $newId = null; $err = 'Please enter a valid email address. It is used if you forget your password.'; }
                else [$newId, $err] = gv_create_teacher($db, (string)($_POST['username'] ?? ''), (string)($_POST['display_name'] ?? ''), $pw, $em);
                if ($newId) {
                    start_teacher_session($newId);
                    header('Location: teacher.php');
                    exit;
                }
                $signupError = $err;
            }
        }
    }
}
$forgotMsg = ''; $forgotErr = ''; $showForgot = isset($_GET['forgot']) || $action === 'forgot';
if ($action === 'forgot') {
    $fu = trim((string)($_POST['username'] ?? '')); $fe = gv_clean_email($_POST['email'] ?? '');
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if (!csrf_ok()) {
        $forgotErr = 'Your page expired. Please try again.';
    } elseif (!gv_mail_ready()) {
        $forgotErr = 'Email is not set up on this computer yet, so a new password cannot be sent. Ask whoever installed Grammar Vault to fill in mail_config.php (see README, section 4c).';
    } else {
        $since = time() - 3600;
        $db->prepare('DELETE FROM reset_requests WHERE at < ?')->execute([$since]);
        $c1 = $db->prepare('SELECT COUNT(*) FROM reset_requests WHERE ip = ?'); $c1->execute([$ip]);
        $c2 = $db->prepare('SELECT COUNT(*) FROM reset_requests WHERE uname = ?'); $c2->execute([strtolower($fu)]);
        if ((int)$c1->fetchColumn() >= 10 || (int)$c2->fetchColumn() >= 3) {
            $forgotErr = 'Too many requests. Please wait an hour and try again.';
        } else {
            $db->prepare('INSERT INTO reset_requests (at, ip, uname) VALUES (?,?,?)')->execute([time(), $ip, strtolower($fu)]);
            $t = gv_teacher_for_reset($db, $fu, $fe);
            if ($t) {
                $new = gv_generate_password(12);
                $db->exec('BEGIN IMMEDIATE');
                $db->prepare('UPDATE teachers SET password_hash = ?, pw_temp = 1 WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $t['id']]);
                $body = "Hello " . $t['display_name'] . ",\n\nA new password was requested for your Grammar Vault teacher account.\n\n"
                      . "Username: " . $t['username'] . "\nNew password: " . $new . "\n\n"
                      . "Log in with it, then go to Account and change it to a password you will remember.\n"
                      . "If you did not ask for this, please log in and change your password right away.\n\nGrammar Vault";
                if (gv_send_mail($t['email'], 'Your new Grammar Vault password', $body)) $db->exec('COMMIT');
                else { $db->exec('ROLLBACK'); error_log('Grammar Vault: could not send reset email'); }
            }
            $forgotMsg = 'If that username and email match a teacher account, a new password has been sent to that email. It can take a few minutes; please check the spam folder too.';
        }
    }
}
$me = null;
if (!empty($_SESSION['teacher_id'])) {
    if ((time() - (int)($_SESSION['last_seen'] ?? 0)) < IDLE_SECONDS) {
        $me = gv_teacher_by_id($db, (int)$_SESSION['teacher_id']);
        if ($me) $_SESSION['last_seen'] = time();
    } else {
        $_SESSION = ['csrf' => bin2hex(random_bytes(24))];
        $loginError = 'You were logged out after a long break. Please log in again.';
    }
}
if (!$me) {
    $showSignup = !$showForgot && ($teacherCount === 0 || isset($_GET['signup']) || $action === 'signup');
    page_start($showForgot ? 'Forgot password' : ($showSignup ? 'Create a teacher account' : 'Teacher login'));
    echo '<div class="wrap"><div class="card login">';
    if ($showForgot) {
        echo '<h1>Forgot your password?</h1><p class="sub">Type your username and the email you used when you registered.</p>';
        if ($forgotErr) echo '<div class="err">' . h($forgotErr) . '</div>';
        if ($forgotMsg) echo '<div class="ok">' . h($forgotMsg) . '</div>';
        echo '<form method="post" autocomplete="off"><input type="hidden" name="action" value="forgot"><input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
        echo '<label for="fu">Username</label><input id="fu" class="field" type="text" name="username" autofocus required>';
        echo '<label for="fe">Email</label><input id="fe" class="field" type="email" name="email" required>';
        echo '<button type="submit">Send me a new password</button></form>';
        echo '<p class="muted" style="margin-top:16px"><a href="teacher.php">← Back to log in</a></p>';
    } elseif ($showSignup) {
        echo '<h1>Create a teacher account</h1><p class="sub">Grammar Vault results</p>';
        if ($teacherCount === 0) echo '<p class="muted">There are no teacher accounts yet. Make the first one — students will type the name you choose to find you.</p>';
        if ($signupError) echo '<div class="err">' . h($signupError) . '</div>';
        {
            echo '<form method="post" autocomplete="off"><input type="hidden" name="action" value="signup"><input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
            echo '<label for="dn">Name your students will see (for example “Ms. Rivera”)</label><input id="dn" class="field" type="text" name="display_name" maxlength="30" required autofocus value="' . h((string)($_POST['display_name'] ?? '')) . '">';
            echo '<label for="un">Username (for logging in)</label><input id="un" class="field" type="text" name="username" maxlength="30" required value="' . h((string)($_POST['username'] ?? '')) . '">';
            echo '<label for="em">Email (used to reset your password if you forget it)</label><input id="em" class="field" type="email" name="email" maxlength="120" required value="' . h((string)($_POST['email'] ?? '')) . '">';
            echo '<label for="p1">Password (at least 10 characters)</label><input id="p1" class="field" type="password" name="password" minlength="10" maxlength="72" required>';
            echo '<label for="p2">Type the password again</label><input id="p2" class="field" type="password" name="password2" minlength="10" maxlength="72" required>';
            echo '<button type="submit">Create account</button></form>';
        }
        if ($teacherCount > 0) echo '<p class="muted" style="margin-top:16px"><a href="teacher.php">← Back to log in</a></p>';
    } else {
        echo '<h1>Teacher Login</h1><p class="sub">Grammar Vault results</p>';
        if ($loginError) echo '<div class="err">' . h($loginError) . '</div>';
        echo '<form method="post" autocomplete="off"><input type="hidden" name="action" value="login"><input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
        echo '<label for="un">Username</label><input id="un" class="field" type="text" name="username" autofocus required>';
        echo '<label for="pw">Password</label><input id="pw" class="field" type="password" name="password" required>';
        echo '<button type="submit">Log in</button></form>';
        echo '<p class="muted" style="margin-top:12px"><a href="teacher.php?forgot=1">I forgot my password</a></p>';
        echo '<p class="muted" style="margin-top:16px">New teacher? <a href="teacher.php?signup=1">Create a teacher account</a></p>';
    }
    echo '<p class="muted"><a href="index2.html">← Back to the game</a></p>';
    echo '</div></div>';
    page_end();
    exit;
}
$tid = (int)$me['id'];
$acctMsg = ''; $acctErr = '';
if ($action === 'change_password') {
    $cur = (string)($_POST['current'] ?? ''); $n1 = (string)($_POST['new1'] ?? ''); $n2 = (string)($_POST['new2'] ?? '');
    if (!csrf_ok()) $acctErr = 'Your page expired. Please try again.';
    elseif (!password_verify($cur, (string)$me['password_hash'])) { usleep(600000); $acctErr = 'Your current password is not correct.'; }
    elseif (strlen($n1) < 10) $acctErr = 'Please use a password of at least 10 characters.';
    elseif (strlen($n1) > 72) $acctErr = 'That password is too long (limit 72 characters).';
    elseif ($n1 !== $n2) $acctErr = 'The two new passwords did not match.';
    else {
        $db->prepare('UPDATE teachers SET password_hash = ?, pw_temp = 0 WHERE id = ?')->execute([password_hash($n1, PASSWORD_DEFAULT), $tid]);
        $me['pw_temp'] = 0;
        $acctMsg = 'Your password was changed.';
    }
}
function teacher_name_card(array $me): void {
    echo '<div class="card codecard"><div><div class="l">Your name for students</div><div class="code">' . h($me['display_name']) . '</div></div>';
    echo '<p>New students type this name when they <strong>register</strong> in the game (capitals, dots and “Ms.” don’t matter). You can also add students yourself on the <a href="teacher.php?p=roster">Roster</a> page. Other teachers cannot see your students, and you cannot see theirs.</p></div>';
}
function top_bar(array $me, string $sub, string $active = 'overview'): void {
    echo '<header class="top"><div><h1>Grammar Vault — Teacher Dashboard</h1><p class="sub">' . h($me['display_name']) . ' · ' . h($sub) . '</p></div><div class="hdrbtns">';
    echo '<a class="btn ghost" href="index2.html">Back to game</a>';
    echo '<form method="post" style="margin:0"><input type="hidden" name="action" value="logout"><input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '"><button class="ghost" type="submit">Log out</button></form></div></header>';
    if (!empty($me['pw_temp'])) echo '<div class="err" role="status">You are using a temporary password that was emailed to you. Please <a href="teacher.php?account=1">change it</a>.</div>';
    elseif (($me['email'] ?? '') === '') echo '<div class="err" role="status">Add a recovery email in <a href="teacher.php?account=1">Account</a> so you can reset your password if you forget it.</div>';
    echo '<nav class="tabs">';
    foreach (['overview' => ['Overview', 'teacher.php'], 'students' => ['Students', 'teacher.php?p=students'], 'roster' => ['Roster', 'teacher.php?p=roster'],
              'reports' => ['Reports', 'teacher.php?p=reports'], 'legend' => ['Legend', 'teacher.php?p=legend'], 'account' => ['Account', 'teacher.php?account=1']] as $k => [$lbl, $href]) {
        echo '<a href="' . $href . '"' . ($k === $active ? ' class="on"' : '') . '>' . $lbl . '</a>';
    }
    echo '</nav>';
}
if ($action === 'set_email') {
    if (!csrf_ok()) $acctErr = 'Your page expired. Please try again.';
    elseif (!password_verify((string)($_POST['current'] ?? ''), (string)$me['password_hash'])) { usleep(600000); $acctErr = 'Your password is not correct.'; }
    elseif (!gv_set_teacher_email($db, $tid, (string)($_POST['email'] ?? ''))) $acctErr = 'Please enter a valid email address.';
    else { $me['email'] = gv_clean_email($_POST['email']); $acctMsg = 'Your recovery email was saved.'; }
}
if ($action === 'rename') {
    $new = trim(preg_replace('/[\x00-\x1F\x7F]/u', ' ', (string)($_POST['display_name'] ?? '')) ?? '');
    $len = function_exists('mb_strlen') ? mb_strlen($new) : strlen($new);
    if (!csrf_ok()) $acctErr = 'Your page expired. Please try again.';
    elseif ($new === '' || $len > 30) $acctErr = 'Please enter a name of up to 30 characters, for example "Ms. Rivera".';
    else {
        $clash = array_filter(gv_teachers_matching($db, $new), fn($t) => (int)$t['id'] !== $tid);
        if ($clash) $acctErr = 'Another teacher already uses that name. Add something to tell you apart, for example "Ms. Rivera (Room 5)".';
        else {
            $db->prepare('UPDATE teachers SET display_name = ? WHERE id = ?')->execute([$new, $tid]);
            $me['display_name'] = $new;
            $acctMsg = 'Students now find you by typing “' . $new . '”.';
        }
    }
}
if (isset($_GET['account']) || $action === 'change_password' || $action === 'rename' || $action === 'set_email') {
    page_start('Account — Teacher Dashboard');
    echo '<div class="wrap">';
    top_bar($me, 'Account', 'account');
    teacher_name_card($me);
    echo '<div class="card" style="max-width:480px"><h2>Name students type</h2>';
    if ($action === 'rename' && $acctErr) echo '<div class="err">' . h($acctErr) . '</div>';
    if ($action === 'rename' && $acctMsg) echo '<div class="ok">' . h($acctMsg) . '</div>';
    echo '<form method="post" autocomplete="off"><input type="hidden" name="action" value="rename"><input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
    echo '<label for="rn">Your name, for example “Ms. Rivera”</label><input id="rn" class="field" type="text" name="display_name" maxlength="30" required value="' . h($me['display_name']) . '" style="width:100%;margin-bottom:12px">';
    echo '<button type="submit">Save name</button></form></div>';
    echo '<div class="card" style="max-width:480px"><h2>Recovery email</h2><p class="muted">Used only to send you a new password if you forget it.</p>';
    if ($action === 'set_email' && $acctErr) echo '<div class="err">' . h($acctErr) . '</div>';
    if ($action === 'set_email' && $acctMsg) echo '<div class="ok">' . h($acctMsg) . '</div>';
    echo '<form method="post" autocomplete="off">' . '<input type="hidden" name="action" value="set_email"><input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
    echo '<label for="ee">Email</label><input id="ee" class="field" type="email" name="email" maxlength="120" required value="' . h((string)($me['email'] ?? '')) . '" style="width:100%;margin-bottom:12px">';
    echo '<label for="ec">Your password (to confirm)</label><input id="ec" class="field" type="password" name="current" required style="width:100%;margin-bottom:12px">';
    echo '<button type="submit">Save email</button></form></div>';
    echo '<div class="card" style="max-width:480px"><h3>Signed in as <code>' . h($me['username']) . '</code></h3><h2 style="margin-top:14px">Change password</h2>';
    if ($action === 'change_password' && $acctErr) echo '<div class="err">' . h($acctErr) . '</div>';
    if ($action === 'change_password' && $acctMsg) echo '<div class="ok">' . h($acctMsg) . '</div>';
    echo '<form method="post" autocomplete="off"><input type="hidden" name="action" value="change_password"><input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
    echo '<label for="c0">Current password</label><input id="c0" class="field" type="password" name="current" required style="width:100%;margin-bottom:12px">';
    echo '<label for="c1">New password (at least 10 characters)</label><input id="c1" class="field" type="password" name="new1" minlength="10" maxlength="72" required style="width:100%;margin-bottom:12px">';
    echo '<label for="c2">New password again</label><input id="c2" class="field" type="password" name="new2" minlength="10" maxlength="72" required style="width:100%;margin-bottom:12px">';
    echo '<button type="submit">Change password</button></form></div></div>';
    page_end();
    exit;
}
$class = gv_load_class($db, $tid);
$students = $class['students'];
$allSkillNames = $class['skillNames'];
$sum = gv_class_summary($class);
function flash_set(string $kind, string $msg): void { $_SESSION['flash'] = [$kind, $msg]; }
function flash_show(): void {
    if (empty($_SESSION['flash'])) return;
    [$k, $m] = $_SESSION['flash']; unset($_SESSION['flash']);
    echo '<div class="' . ($k === 'ok' ? 'ok' : 'err') . '" role="status">' . h($m) . '</div>';
}
function csrf_field(string $action): string {
    return '<input type="hidden" name="action" value="' . h($action) . '"><input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
}
if ($action === 'add_student' || $action === 'school_list_add' || $action === 'bulk_add' || $action === 'remove_do' || $action === 'reset_pw') {
    if (!csrf_ok()) {
        flash_set('err', 'Your page expired. Please try again.');
    } elseif ($action === 'add_student') {
        $r = gv_teacher_add_student($db, $tid, $_POST['student_no'] ?? '', $_POST['full_name'] ?? '', $_POST['grade'] ?? 0);
        [$id, $err] = $r; $moved = !empty($r[2]);
        flash_set($id ? 'ok' : 'err', $id ? ($moved ? 'This student already had an account. They are now in your class, and all their earlier results moved with them.' : 'Added ' . gv_clean_full_name($_POST['full_name'] ?? '') . ' to your class. They can now register with their Student ID.') : $err);
    } elseif ($action === 'school_list_add') {
        [$n, $bad] = gv_school_list_add($db, substr((string)($_POST['lines'] ?? ''), 0, 400000), (int)($_POST['grade'] ?? 0));
        flash_set($n > 0 ? 'ok' : 'err', $n > 0 ? 'Saved ' . $n . ' name' . ($n === 1 ? '' : 's') . ' to the school list.' . ($bad ? ' Skipped ' . $bad . ' line' . ($bad === 1 ? '' : 's') . ' that did not look like “12-3456-789, Full Name”.' : '') : 'Nothing was saved. Each line should look like: 12-3456-789, Juan Dela Cruz');
    } elseif ($action === 'bulk_add') {
        [$added, $skipped] = gv_teacher_bulk_add($db, $tid, substr((string)($_POST['lines'] ?? ''), 0, 20000), (int)($_POST['grade'] ?? 0));
        $msg = 'Added ' . $added . ' student' . ($added === 1 ? '' : 's') . '.';
        if ($skipped) {
            $msg .= ' Skipped ' . count($skipped) . ': ' . implode(' | ', array_map(fn($s) => $s[0] . ' — ' . $s[1], array_slice($skipped, 0, 8)));
            if (count($skipped) > 8) $msg .= ' …';
        }
        flash_set($added > 0 && !$skipped ? 'ok' : ($added > 0 ? 'ok' : 'err'), $msg);
    } elseif ($action === 'reset_pw') {
        $key = (string)($_POST['key'] ?? '');
        $pw = (string)($_POST['new_password'] ?? '');
        if (!preg_match('/^s(\d+)$/', $key, $m)) flash_set('err', 'Could not find that student.');
        elseif (!gv_valid_password($pw)) flash_set('err', gv_password_error());
        elseif (gv_teacher_set_student_password($db, $tid, (int)$m[1], $pw)) flash_set('ok', 'New password saved. Give it to the student; they can log in with it right away.');
        else flash_set('err', 'Could not change the password. (The student must have registered first.)');
    } else {
        $key = (string)($_POST['key'] ?? '');
        $ok = false; $kept = isset($students[$key]) && !empty($students[$key]['registered']) && $students[$key]['student_no'] !== '';
        if (preg_match('/^s(\d+)$/', $key, $m)) $ok = gv_teacher_remove_student($db, $tid, (int)$m[1]);
        elseif (preg_match('/^p(\d+)$/', $key, $m)) $ok = gv_teacher_remove_legacy_player($db, $tid, (int)$m[1]);
        flash_set($ok ? 'ok' : 'err', $ok ? ($kept ? 'Removed from your class. Their account and results were kept.' : 'The student was removed.') : 'Could not remove that student.');
    }
    header('Location: teacher.php?p=roster');
    exit;
}
function status_pill(string $st): string {
    $cls = ['good' => 'g-hi', 'help' => 'g-lo', 'idle' => 'g-mid', 'new' => 'g-na', 'pending' => 'g-na'][$st] ?? 'g-na';
    return '<span class="pill ' . $cls . '">' . h(GV_STATUS_LABEL[$st] ?? $st) . '</span>';
}
function trend_html(array $r): string {
    if ($r['trend'] === 'up')   return '<span class="trend up" title="Improving: last ' . TREND_WINDOW . ' answers are ' . (int)$r['trendDelta'] . ' points better than the ' . TREND_WINDOW . ' before">▲ +' . (int)$r['trendDelta'] . '</span>';
    if ($r['trend'] === 'down') return '<span class="trend down" title="Dropping: last ' . TREND_WINDOW . ' answers are ' . abs((int)$r['trendDelta']) . ' points worse than the ' . TREND_WINDOW . ' before">▼ ' . (int)$r['trendDelta'] . '</span>';
    if ($r['trend'] === 'flat') return '<span class="trend flat" title="Steady">● steady</span>';
    return '<span class="muted" title="Needs at least ' . (2 * TREND_WINDOW) . ' answers">—</span>';
}
function level_boxes(array $r): string {
    $o = '<span class="lvls" title="' . $r['levelsDone'] . ' of ' . gv_level_count() . ' levels completed">';
    for ($i = 1; $i <= gv_level_count(); $i++) {
        $st = $r['levelStars'][$i] ?? null;
        $cls = $st === null ? (isset($r['levelStat'][$i]) ? 'lv-part' : 'lv-none') : 'lv-s' . $st;
        $o .= '<i class="' . $cls . '" title="' . h(gv_level_names()[$i - 1] ?? 'Level ' . $i) . ($st === null ? (isset($r['levelStat'][$i]) ? ': started' : ': not started') : ': ' . $st . ' star' . ($st === 1 ? '' : 's')) . '">' . $i . '</i>';
    }
    return $o . '</span>';
}
function acc_pill(?int $p, int $n): string { return $p === null ? '<span class="muted">—</span>' : '<span class="pill ' . grade_class($p, $n) . '">' . $p . '%</span>'; }
function report_buttons(string $type, string $key = ''): string {
    $q = 'type=' . $type . ($key !== '' ? '&amp;s=' . h($key) : '');
    return '<a class="btn" href="teacher_report.php?' . $q . '&amp;format=docx">Download Word (.docx)</a> '
         . '<a class="btn ghost" href="teacher_report.php?' . $q . '&amp;format=print" target="_blank" rel="noopener">Print / Save as PDF</a>';
}
function student_label(array $r): string {
    return '<span style="white-space:nowrap"><a href="teacher.php?student=' . h($r['key']) . '"><strong>' . h($r['name']) . '</strong></a></span>'
         . ($r['student_no'] !== '' ? '<br><small class="muted" style="white-space:nowrap">' . h($r['student_no']) . ($r['username'] !== '' ? ' · @' . h($r['username']) : '') . '</small>' : '<br><small class="muted">no account</small>');
}
$fGrade = isset($_GET['grade']) && in_array($_GET['grade'], ['2', '3', '4'], true) ? (int)$_GET['grade'] : 0;
$fSearch = trim(substr((string)($_GET['q'] ?? ''), 0, 40));
$fStatus = isset($_GET['status']) && isset(GV_STATUS_LABEL[$_GET['status']]) ? (string)$_GET['status'] : '';
$sort = in_array($_GET['sort'] ?? '', ['help', 'name', 'accuracy', 'grade', 'last', 'progress', 'time'], true) ? $_GET['sort'] : 'help';
$view = array_filter($students, function ($s) use ($fGrade, $fSearch, $fStatus) {
    if ($fGrade && $s['grade'] !== $fGrade) return false;
    if ($fStatus !== '' && $s['status'] !== $fStatus) return false;
    if ($fSearch !== '' && stripos($s['name'] . ' ' . $s['student_no'] . ' ' . $s['username'], $fSearch) === false) return false;
    return true;
});
$statusOrder = ['help' => 0, 'idle' => 1, 'pending' => 2, 'new' => 3, 'good' => 4];
usort($view, function ($a, $b) use ($sort, $statusOrder) {
    $pa = $a['acc'] ?? 101; $pb = $b['acc'] ?? 101;
    switch ($sort) {
        case 'name':     return strcasecmp($a['name'], $b['name']);
        case 'accuracy': return $pa <=> $pb;
        case 'grade':    return $a['grade'] <=> $b['grade'] ?: strcasecmp($a['name'], $b['name']);
        case 'last':     return strcmp((string)$b['last'], (string)$a['last']);
        case 'progress': return $a['levelsDone'] <=> $b['levelsDone'] ?: $pa <=> $pb;
        case 'time':     return ($b['avgSec'] ?? -1) <=> ($a['avgSec'] ?? -1);
        default:         return ($statusOrder[$a['status']] <=> $statusOrder[$b['status']]) ?: (count($b['weak']) <=> count($a['weak'])) ?: ($pa <=> $pb);
    }
});
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="grammar_vault_results.csv"');
    $safe = function ($v) { $v = (string)$v; return ($v !== '' && strpos("=+-@\t\r", $v[0]) !== false) ? "'" . $v : $v; };
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    $head = ['Student ID', 'Student', 'Username', 'Grade', 'Status', 'Answers', 'Correct', 'Accuracy %', 'Recent accuracy % (last ' . TREND_WINDOW . ')', 'Avg seconds per answer', 'Timed out', 'Timed out %', 'Levels completed', 'Total stars'];
    foreach ($allSkillNames as $sk) { $head[] = $sk . ' accuracy %'; $head[] = $sk . ' answers'; }
    array_push($head, 'Needs improvement', 'Days played', 'Last played (' . GV_TZ . ')');
    fputcsv($out, $head);
    foreach ($view as $s) {
        $row = [$s['student_no'], $safe($s['name']), $safe($s['username']), $s['grade'] ?: '', GV_STATUS_LABEL[$s['status']], $s['n'], $s['c'], $s['acc'] ?? '', $s['recent'] ?? '',
                $s['avgSec'] !== null ? round($s['avgSec'], 1) : '', $s['timeouts'], $s['timeoutPct'] ?? '', $s['levelsDone'], $s['stars']];
        foreach ($allSkillNames as $sk) { $row[] = pct($s['skills'][$sk]['c'], $s['skills'][$sk]['n']) ?? ''; $row[] = $s['skills'][$sk]['n']; }
        $row[] = implode('; ', array_map(fn($w) => $w['skill'] . ' (' . $w['pct'] . '%)', $s['weak']));
        array_push($row, $s['days'], $s['last'] ? gv_local($s['last'], 'Y-m-d H:i') : '');
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}
if (isset($_GET['resetpw']) && preg_match('/^s\d+$/', (string)$_GET['resetpw']) && isset($students[(string)$_GET['resetpw']])) {
    $s = $students[(string)$_GET['resetpw']];
    page_start('Reset password — Teacher Dashboard');
    echo '<div class="wrap">';
    top_bar($me, 'Reset a student\'s password', 'roster');
    echo '<div class="card" style="max-width:620px"><h2 style="margin-top:0">New password for ' . h($s['name']) . '</h2>';
    if (!$s['registered']) echo '<p class="muted">This student has not registered yet. They choose their own password when they register.</p>';
    else {
        echo '<p class="muted">Type a new password (at least ' . GV_PW_MIN . ' letters or numbers) and tell it to the student. Their old password stops working, and they will be logged out on other devices.</p>';
        echo '<form method="post" autocomplete="off">' . csrf_field('reset_pw') . '<input type="hidden" name="key" value="' . h($s['key']) . '">';
        echo '<label for="np">New password</label><input id="np" class="field" type="text" name="new_password" minlength="' . GV_PW_MIN . '" maxlength="72" required style="width:100%" autocomplete="off">';
        echo '<p><button type="submit">Save new password</button> <a class="btn ghost" href="teacher.php?p=roster">Cancel</a></p></form>';
    }
    echo '</div></div>';
    page_end();
    exit;
}
if (isset($_GET['remove']) && preg_match('/^[sp]\d+$/', (string)$_GET['remove']) && isset($students[(string)$_GET['remove']])) {
    $s = $students[(string)$_GET['remove']];
    page_start('Remove student — Teacher Dashboard');
    echo '<div class="wrap">';
    top_bar($me, 'Remove a student', 'roster');
    echo '<div class="card" style="max-width:620px"><h2 style="margin-top:0">Remove ' . h($s['name']) . '?</h2>';
    if (!empty($s['registered']) && $s['student_no']) {
        echo '<p>This takes <strong>' . h($s['name']) . '</strong> (' . h($s['student_no']) . ') out of your class. Their account, password and all ' . $s['n'] . ' recorded answers are <strong>kept</strong>; you just will not see them any more. If another teacher adds the same Student ID, their results move to that teacher.</p>';
    } else {
        echo '<p>This takes <strong>' . h($s['name']) . '</strong>' . ($s['student_no'] ? ' (' . h($s['student_no']) . ')' : '') . ' out of your class and <strong>permanently deletes</strong> them' . ($s['n'] ? ' and all ' . $s['n'] . ' recorded answers' : '') . '. This cannot be undone.</p>';
    }
    echo '<p class="muted">Tip: if you want a copy of their results first, download their report.</p>';
    echo '<div class="hdrbtns">' . report_buttons('student', $s['key']) . '</div>';
    echo '<form method="post" style="margin-top:16px" class="hdrbtns">' . csrf_field('remove_do') . '<input type="hidden" name="key" value="' . h($s['key']) . '">';
    echo '<button type="submit" class="danger">Yes, remove ' . h($s['name']) . '</button> <a class="btn ghost" href="teacher.php?p=roster">Cancel</a></form></div></div>';
    page_end();
    exit;
}
if (isset($_GET['student']) && preg_match('/^[sp]\d+$/', (string)$_GET['student']) && isset($students[(string)$_GET['student']])) {
    $s = $students[(string)$_GET['student']];
    $detail = gv_student_detail($db, $tid, $s);
    page_start($s['name'] . ' — Teacher Dashboard');
    echo '<div class="wrap">';
    top_bar($me, $s['name'], 'students');
    echo '<div class="card" style="display:flex;gap:16px;justify-content:space-between;align-items:center;flex-wrap:wrap"><div><h2 style="margin:0">' . h($s['name']) . ' ' . status_pill($s['status']) . '</h2>';
    echo '<div class="muted">' . ($s['grade'] ? 'Grade ' . $s['grade'] : 'Grade not set') . ($s['student_no'] ? ' · Student ID ' . h($s['student_no']) : '') . ($s['username'] !== '' ? ' · username ' . h($s['username']) : '') . '</div></div>';
    echo '<div class="hdrbtns">' . report_buttons('student', $s['key']) . ' <a class="btn ghost" href="teacher.php?remove=' . h($s['key']) . '">Remove student</a></div></div>';
    echo '<div class="stats" style="margin-top:14px">';
    echo '<div class="card stat"><div class="n">' . ($s['acc'] === null ? '—' : $s['acc'] . '%') . '</div><div class="l">Overall accuracy (' . $s['c'] . ' of ' . $s['n'] . ')</div></div>';
    echo '<div class="card stat"><div class="n">' . ($s['recent'] === null ? '—' : $s['recent'] . '%') . ' <span style="font-size:16px">' . trend_html($s) . '</span></div><div class="l">Last ' . TREND_WINDOW . ' answers</div></div>';
    echo '<div class="card stat"><div class="n">' . $s['levelsDone'] . ' / ' . gv_level_count() . '</div><div class="l">Levels completed · ' . $s['stars'] . ' ★</div></div>';
    echo '<div class="card stat"><div class="n">' . gv_fmt_seconds($s['avgSec']) . '</div><div class="l">Average time per answer (limit 30 s)</div></div>';
    echo '<div class="card stat"><div class="n">' . $s['timeouts'] . ($s['timeoutPct'] !== null ? ' <span style="font-size:16px">(' . $s['timeoutPct'] . '%)</span>' : '') . '</div><div class="l">Questions that ran out of time</div></div>';
    echo '<div class="card stat"><div class="n" style="font-size:20px">' . h(gv_ago($s['last'])) . '</div><div class="l">Last played · ' . $s['days'] . ' day' . ($s['days'] === 1 ? '' : 's') . ' played</div></div></div>';
    echo '<h2>Teacher notes</h2>';
    if ($s['why']) {
        echo '<div class="card focus"><strong>Worth a check-in:</strong><ul class="tips" style="color:inherit">';
        foreach ($s['why'] as $w) echo '<li>' . h($w) . '</li>';
        echo '</ul></div>';
    } elseif ($s['n'] > 0) {
        echo '<div class="card focus none"><strong>On track.</strong> No skill below ' . WEAK_BELOW . '%, activity is recent, and accuracy is not dropping.</div>';
    } else {
        echo '<div class="card focus nodata"><strong>No answers yet.</strong></div>';
    }
    $weak = $s['weak'];
    if ($weak) {
        echo '<h2>Skills to practice</h2><div class="card">';
        foreach ($weak as $i => $w) {
            $tag = $w['level'] === 'help' ? '<span class="pill g-lo">Needs a lot of help</span>' : '<span class="pill g-mid">Needs practice</span>';
            echo '<div style="margin:' . ($i ? '16' : '0') . 'px 0 0"><h3>' . ($i + 1) . '. ' . h($w['skill']) . ' — ' . $w['pct'] . '% ' . $tag . '</h3>';
            echo '<div class="muted" style="font-size:14px">' . $w['c'] . ' of ' . $w['n'] . ' answers correct</div><ul class="tips">';
            foreach (TIPS[$w['skill']] ?? [] as $tip) echo '<li>' . h($tip) . '</li>';
            echo '</ul></div>';
        }
        echo '</div>';
    }
    echo '<h2>Skill breakdown</h2><div class="card">';
    foreach ($allSkillNames as $sk) {
        $x = $s['skills'][$sk]; $sp = pct($x['c'], $x['n']);
        echo '<div class="skillrow"><strong>' . h($sk) . '</strong><div class="bar"><i style="width:' . ($sp ?? 0) . '%;background:' . bar_color($sp, $x['n']) . '"></i></div>';
        echo '<span>' . ($sp === null ? 'No answers yet' : '<span class="pill ' . grade_class($sp, $x['n']) . '">' . $sp . '%</span> ' . $x['c'] . '/' . $x['n'] . ($x['n'] < MIN_ANSWERS ? ' (too few to judge)' : '')) . '</span></div>';
    }
    echo '</div>';
    echo '<h2>Progress by level</h2><div class="tablewrap"><table style="min-width:640px"><thead><tr><th>Level</th><th>Result</th><th class="num">Answers</th><th class="num">Accuracy</th><th class="num">Timed out</th></tr></thead><tbody>';
    foreach (gv_level_names() as $i0 => $nm) {
        $i = $i0 + 1; $ls = $s['levelStat'][$i] ?? null; $st = $s['levelStars'][$i] ?? null;
        $res = $st !== null ? '<span class="stars">' . str_repeat('★', $st) . str_repeat('☆', 3 - $st) . '</span> completed' : ($ls ? '<span class="muted">In progress</span>' : '<span class="muted">Not started</span>');
        echo '<tr><td>' . $i . '. ' . h($nm) . '</td><td>' . $res . '</td><td class="num">' . ($ls['n'] ?? 0) . '</td><td class="num">' . ($ls ? acc_pill(pct($ls['c'], $ls['n']), $ls['n']) : '—') . '</td><td class="num">' . ($ls['t'] ?? 0) . '</td></tr>';
    }
    echo '</tbody></table></div>';
    echo '<h2>Recent playthroughs</h2>';
    if ($detail['sessions']) {
        echo '<div class="tablewrap"><table style="min-width:640px"><thead><tr><th>Started</th><th class="num">Answers</th><th class="num">Accuracy</th><th class="num">Timed out</th><th>Last answer</th><th>Finished the vault?</th></tr></thead><tbody>';
        foreach ($detail['sessions'] as $r) {
            echo '<tr><td>' . h(gv_local($r['started_at'])) . '</td><td class="num">' . (int)$r['n'] . '</td><td class="num">' . acc_pill(pct((int)$r['c'], (int)$r['n']), (int)$r['n']) . '</td><td class="num">' . (int)$r['t'] . '</td><td>' . h(gv_local($r['last'])) . '</td><td>' . ($r['finished_at'] ? 'Yes' : '—') . '</td></tr>';
        }
        echo '</tbody></table></div>';
    } else echo '<p class="muted">No playthroughs yet.</p>';
    echo '<h2>Recent mistakes</h2>';
    if ($detail['mistakes']) {
        echo '<div class="tablewrap"><table><thead><tr><th>When</th><th>Level</th><th>Skill</th><th>Question</th><th>Student answered</th></tr></thead><tbody>';
        foreach ($detail['mistakes'] as $r) {
            $given = (int)$r['timed_out'] ? '<span class="pill g-mid">Time ran out</span>' : h($r['given'] === '' ? '(blank)' : $r['given']);
            echo '<tr><td>' . h(gv_local($r['answered_at'])) . '</td><td>' . (int)$r['level'] . '</td><td>' . h($r['category']) . '</td><td>' . h($r['question']) . '</td><td>' . $given . '</td></tr>';
        }
        echo '</tbody></table></div>';
    } else echo '<p class="muted">No wrong answers recorded.</p>';
    echo '</div>';
    page_end();
    exit;
}
$page = in_array($_GET['p'] ?? '', ['students', 'roster', 'reports', 'legend'], true) ? (string)$_GET['p'] : 'overview';
function legend_strip(): string {
    return '<div class="card keystrip" style="padding:10px 16px;margin:10px 0"><strong>Colour key:</strong> '
        . '<span class="pill g-hi">90%+ excellent</span> <span class="pill g-ok">' . WEAK_BELOW . '–89% good</span> <span class="pill g-mid">' . HELP_BELOW . '–' . (WEAK_BELOW - 1) . '% needs practice</span> '
        . '<span class="pill g-lo">under ' . HELP_BELOW . '% needs a lot of help</span> <span class="pill g-na">grey: too few answers (under ' . MIN_ANSWERS . ')</span> '
        . '<a href="teacher.php?p=legend" style="margin-left:8px">What else do these mean? Open the Legend →</a></div>';
}
function legend_html(bool $full): string {
    $row = fn(string $sample, string $text) => '<tr><td style="width:190px;white-space:nowrap">' . $sample . '</td><td>' . $text . '</td></tr>';
    $tbl = fn(string $rows) => '<div class="tablewrap"><table style="min-width:0"><tbody>' . $rows . '</tbody></table></div>';
    $o = '<h2>Accuracy colours</h2><p class="muted" style="margin-top:0">Accuracy is the share of answers that were correct. A question that runs out of time counts as wrong.</p>';
    $o .= $tbl(
        $row('<span class="pill g-hi">90–100%</span>', '<strong>Excellent.</strong> The skill is solid.')
      . $row('<span class="pill g-ok">' . WEAK_BELOW . '–89%</span>', '<strong>Good.</strong> At or above the goal of ' . WEAK_BELOW . '%.')
      . $row('<span class="pill g-mid">' . HELP_BELOW . '–' . (WEAK_BELOW - 1) . '%</span>', '<strong>Needs practice.</strong> Below the goal of ' . WEAK_BELOW . '%; the skill is flagged.')
      . $row('<span class="pill g-lo">under ' . HELP_BELOW . '%</span>', '<strong>Needs a lot of help.</strong> Plan a re-teach or one-to-one time.')
      . $row('<span class="pill g-na">grey</span>', '<strong>Not enough answers yet.</strong> A skill is judged only after ' . MIN_ANSWERS . ' answers in it, so a grey percentage may change.')
    );
    $o .= '<h2>Student status</h2>';
    $o .= $tbl(
        $row('<span class="pill g-hi">On track</span>', 'Has played recently and no skill is flagged.')
      . $row('<span class="pill g-lo">Needs help</span>', 'Any one of: a skill below ' . WEAK_BELOW . '% (with ' . MIN_ANSWERS . '+ answers), overall accuracy under 60% (with 10+ answers), or accuracy dropping.')
      . $row('<span class="pill g-mid">Inactive</span>', 'Has played before but not for ' . INACTIVE_DAYS . ' days or more.')
      . $row('<span class="pill g-na">Not started</span>', 'Registered an account but has not answered a question yet.')
      . $row('<span class="pill g-na">Not registered</span>', 'Added to your class, but the student has not created their account in the game yet (they need their Student ID, full name and a password).')
    );
    $o .= '<p class="muted">“Needs attention” lists every student who is not on track, with the reason, most urgent first.</p>';
    if (!$full) return $o;
    $o .= '<h2>Trend arrows</h2>';
    $o .= $tbl(
        $row('<span class="trend up">▲ +12</span>', 'Improving: the latest ' . TREND_WINDOW . ' answers are ' . TREND_DELTA . '+ points better than the ' . TREND_WINDOW . ' before them.')
      . $row('<span class="trend down">▼ −15</span>', 'Dropping: the latest ' . TREND_WINDOW . ' answers are ' . TREND_DELTA . '+ points worse. Worth a check-in.')
      . $row('<span class="trend flat">● steady</span>', 'Less than ' . TREND_DELTA . ' points of change.')
      . $row('<span class="muted">—</span>', 'No trend yet. It needs at least ' . (2 * TREND_WINDOW) . ' answers.')
    );
    $o .= '<h2>Level boxes</h2><p class="muted" style="margin-top:0">One small box per level, numbered 1 to ' . gv_level_count() . '.</p>';
    $o .= $tbl(
        $row('<span class="lvls"><i class="lv-none">1</i></span>', 'Grey: not started.')
      . $row('<span class="lvls"><i class="lv-part">2</i></span>', 'Yellow: started but not finished yet.')
      . $row('<span class="lvls"><i class="lv-s1">3</i><i class="lv-s2">4</i><i class="lv-s3">5</i></span>', 'Orange, light green, green: completed with 1, 2 or 3 stars. More stars means a stronger result on that level.')
    );
    $o .= '<h2>Time</h2>';
    $o .= '<div class="card"><ul class="tips" style="margin:0 0 0 18px"><li>Every question has a <strong>30-second limit</strong>; each NPC asks 3 questions, so about 1:30 per NPC.</li>'
        . '<li><strong>Ran out of time</strong> means the timer reached zero. It counts as a wrong answer.</li>'
        . '<li><strong>Average time per answer</strong> is how long students took on the questions they answered. A very short time with low accuracy can mean guessing.</li>'
        . '<li>A student is flagged when ' . SLOW_TIMEOUT_PCT . '% or more of their answers ran out of time (with 10+ answers). That often means reading difficulty rather than not knowing the answer.</li></ul></div>';
    $o .= '<h2>Reading the tables</h2><div class="card"><ul class="tips" style="margin:0 0 0 18px">'
        . '<li><strong>Accuracy</strong> in the Students table is the overall share of correct answers; the small coloured boxes per skill use the colours above.</li>'
        . '<li><strong>Avg time</strong> is the average seconds per answer (limit 30). <strong>Timed out</strong> counts questions where the timer reached zero.</li>'
        . '<li><strong>Plays</strong> is how many playthroughs the student has started.</li>'
        . '<li><strong>Levels</strong> shows how many levels are finished out of ' . gv_level_count() . ', and the stars earned (3 stars per level at most).</li>'
        . '<li>Click a student’s name for their full page: skills, levels, weekly activity and recent mistakes.</li></ul></div>';
    $o .= '<h2>Reports</h2><div class="card"><ul class="tips" style="margin:0 0 0 18px">'
        . '<li><strong>Print / Save as PDF</strong> opens a paged view. Use the tabs, Previous / Next or the arrow keys. Choose “Save as PDF” in the print window to make a PDF.</li>'
        . '<li><strong>Who needs attention</strong> is shown 5 students at a time; use the page buttons below it. The printout lists everyone.</li>'
        . '<li><strong>Word (.docx)</strong> downloads the same report to edit or share.</li>'
        . '<li><strong>Questions the class misses most</strong> are questions answered at least 3 times with at least 2 wrong answers.</li></ul></div>';
    $o .= '<h2>Roster, accounts and passwords</h2><div class="card"><ul class="tips" style="margin:0 0 0 18px">'
        . '<li><strong>Add a student:</strong> start typing a name or Student ID; matches from the school list appear and the other box fills in for you.</li>'
        . '<li><strong>School list:</strong> names and Student IDs shared by all teachers for suggestions. It does not add anyone to a class.</li>'
        . '<li><strong>Recovery email</strong> (Account): used only to send you a new password if you forget it. After a reset, a <strong>temporary password</strong> banner reminds you to change it.</li>'
        . '<li>Students log in with their Student ID or username and their own password, then see their own My Progress page.</li></ul></div>';
    $o .= '<h2>Words used on this dashboard</h2>';
    $o .= '<div class="card"><ul class="tips" style="margin:0 0 0 18px">'
        . '<li><strong>Skills:</strong> ' . h(implode(', ', SKILLS)) . '. Every question belongs to one skill.</li>'
        . '<li><strong>Class-wide focus:</strong> the weakest skills across your whole class, with a teaching tip for each.</li>'
        . '<li><strong>Skill map</strong> (in reports): every student’s accuracy in every skill, using the colours above.</li>'
        . '<li><strong>Playthrough:</strong> one run through the game from the start. A student can play several.</li>'
        . '<li><strong>Last played:</strong> the last time the student answered a question.</li>'
        . '<li><strong>Roster:</strong> add or remove students. Removing a student who has an account keeps their account and results; if another teacher adds the same Student ID, the results move with them.</li>'
        . '<li><strong>Reset password</strong> (on a student): sets a new password for a student who forgot theirs. Teachers can also use “I forgot my password” on the login page, which emails a new password to the email used at registration.</li>'
        . '<li><strong>Reports:</strong> the paged print view lets you flip through pages. Printing or downloading the Word file includes everything.</li>'
        . '<li>Weeks start on Monday and times are shown in Philippine time (' . h(GV_TZ) . ').</li></ul></div>';
    return $o;
}
if ($page === 'legend') {
    page_start('Legend — Teacher Dashboard');
    echo '<div class="wrap">';
    top_bar($me, 'Legend: what the colours and words mean', 'legend');
    flash_show();
    echo '<div style="max-width:900px">' . legend_html(true) . '</div>';
    page_end();
    exit;
}
if ($page === 'roster') {
    page_start('Roster — Teacher Dashboard');
    echo '<div class="wrap">';
    top_bar($me, 'Your students', 'roster');
    flash_show();
    echo '<div class="grid2"><div class="card"><h3>Add a student</h3><p class="muted" style="margin-top:0">Students you add can then register with their Student ID and full name. You can also let students register themselves by typing your name (“' . h($me['display_name']) . '”).</p>';
    $sugg = gv_school_suggestions($db);
    echo '<form method="post" autocomplete="off" id="addForm">' . csrf_field('add_student');
    echo '<label for="sn">Student ID (like 12-3456-789)</label><input id="sn" class="field" type="text" name="student_no" inputmode="numeric" maxlength="11" placeholder="12-3456-789" pattern="\d{2}-?\d{4}-?\d{3}" required style="width:100%" list="dlNo">';
    echo '<label for="fn" style="margin-top:10px">Full name</label><input id="fn" class="field" type="text" name="full_name" maxlength="60" placeholder="Juan Dela Cruz" required style="width:100%" list="dlName">';
    echo '<datalist id="dlNo">'; foreach ($sugg as $r) echo '<option value="' . h($r['no']) . '" label="' . h($r['name']) . '"></option>'; echo '</datalist>';
    echo '<datalist id="dlName">'; foreach ($sugg as $r) echo '<option value="' . h($r['name']) . '" label="' . h($r['no']) . '"></option>'; echo '</datalist>';
    echo '<script type="application/json" id="schoolData">' . json_encode($sugg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>';
    echo '<p id="afHint" class="muted" style="margin:6px 0 0;min-height:1.3em">' . ($sugg ? 'Start typing a name or Student ID — matches from the school list appear, and the other box fills in by itself.' : 'No school list yet — add one below to get suggestions.') . '</p>';
    echo '<label for="gr" style="margin-top:10px">Grade</label><select id="gr" name="grade" required><option value="2">Grade 2</option><option value="3">Grade 3</option><option value="4">Grade 4</option></select>';
    echo '<p><button type="submit">Add student</button></p></form><script src="teacher_autofill.js"></script></div>';
    echo '<div class="card"><h3>Add many students at once</h3><p class="muted" style="margin-top:0">One student per line: Student ID, full name (and optionally the grade). Paste straight from a spreadsheet.</p>';
    echo '<form method="post" autocomplete="off">' . csrf_field('bulk_add');
    echo '<textarea name="lines" rows="6" placeholder="12-3456-789, Juan Dela Cruz, 3&#10;12-3457-123, Maria Santos, 3" style="width:100%"></textarea>';
    echo '<label for="bg" style="margin-top:8px">Grade for lines without one</label><select id="bg" name="grade"><option value="2">Grade 2</option><option value="3">Grade 3</option><option value="4">Grade 4</option></select>';
    echo '<p><button type="submit">Add students</button></p></form></div></div>';
    $slc = (int)$db->query('SELECT COUNT(*) FROM school_roster')->fetchColumn();
    echo '<div class="card"><h3>School list for suggestions</h3><p class="muted" style="margin-top:0">' . $slc . ' name' . ($slc === 1 ? '' : 's') . ' on the school list. It only holds Student IDs and names, shared by all teachers so the Add-a-student boxes can suggest and auto-fill. It does not add anyone to a class. Paste lines like <code>12-3456-789, Juan Dela Cruz, 3</code>; a repeated ID updates the name.</p>';
    echo '<form method="post" autocomplete="off">' . csrf_field('school_list_add');
    echo '<textarea name="lines" rows="4" placeholder="12-3456-789, Juan Dela Cruz, 3" style="width:100%" required></textarea>';
    echo '<p><button type="submit" class="ghost">Save to school list</button></p></form></div>';
    echo '<h2>' . count($students) . ' student' . (count($students) === 1 ? '' : 's') . ' in your class</h2>';
    if (!$students) {
        echo '<div class="card muted">No students yet. Add them above, or ask them to register in the game using the name “' . h($me['display_name']) . '”.</div>';
    } else {
        $list = $students;
        usort($list, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        echo '<div class="tablewrap"><table style="min-width:860px"><thead><tr><th>Student</th><th>Student ID</th><th>Username</th><th class="num">Grade</th><th>Account</th><th class="num">Answers</th><th></th></tr></thead><tbody>';
        foreach ($list as $r) {
            echo '<tr><td><a href="teacher.php?student=' . h($r['key']) . '"><strong>' . h($r['name']) . '</strong></a></td><td>' . ($r['student_no'] !== '' ? h($r['student_no']) : '<span class="muted">—</span>') . '</td>';
            echo '<td>' . ($r['username'] !== '' ? h($r['username']) : '<span class="muted">—</span>') . '</td><td class="num">' . ($r['grade'] ?: '—') . '</td>';
            echo '<td>' . ($r['legacy'] ? '<span class="pill g-na">Old record (no account)</span>' : ($r['registered'] ? ($r['has_pw'] ? '<span class="pill g-hi">Registered</span>' : '<span class="pill g-mid">Needs a password</span>') : '<span class="pill g-mid">Waiting to register</span>')) . '</td>';
            echo '<td class="num">' . $r['n'] . '</td><td class="num" style="white-space:nowrap">' . (!$r['legacy'] && $r['registered'] ? '<a class="btn ghost small" href="teacher.php?resetpw=' . h($r['key']) . '">Reset password</a> ' : '') . '<a class="btn ghost small" href="teacher.php?remove=' . h($r['key']) . '">Remove</a></td></tr>';
        }
        echo '</tbody></table></div>';
    }
    echo '</div>';
    page_end();
    exit;
}
if ($page === 'reports') {
    page_start('Reports — Teacher Dashboard');
    echo '<div class="wrap">';
    top_bar($me, 'Printable reports', 'reports');
    echo '<p class="muted">Reports use your class data right now. “Word” downloads a .docx you can edit and print. “Print / Save as PDF” opens a clean page — choose <em>Save as PDF</em> as the printer to make a PDF.</p>';
    echo '<div class="grid2"><div class="card"><h3>Class report</h3><p class="muted" style="margin-top:0">Summary, level progress, skills, weekly activity, students who need attention and a table of every student.</p><div class="hdrbtns">' . report_buttons('class') . '</div></div>';
    echo '<div class="card"><h3>Report for every student</h3><p class="muted" style="margin-top:0">One page per student — handy for parent–teacher conferences.</p><div class="hdrbtns">' . report_buttons('pack') . '</div></div></div>';
    echo '<h2>One student</h2>';
    if (!$students) echo '<div class="card muted">No students yet.</div>';
    else {
        $list = $students; usort($list, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        echo '<div class="tablewrap"><table style="min-width:640px"><thead><tr><th>Student</th><th>Status</th><th class="num">Accuracy</th><th>Report</th></tr></thead><tbody>';
        foreach ($list as $r) {
            echo '<tr><td>' . student_label($r) . '</td><td>' . status_pill($r['status']) . '</td><td class="num">' . acc_pill($r['acc'], $r['n']) . '</td><td>' . report_buttons('student', $r['key']) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
    echo '<h2>Spreadsheet</h2><div class="card"><a class="btn ghost" href="teacher.php?export=csv">Download all results (CSV for Excel)</a></div>';
    echo '</div>';
    page_end();
    exit;
}
if ($page === 'students') {
    page_start('Students — Teacher Dashboard');
    echo '<div class="wrap">';
    top_bar($me, 'Student progress', 'students');
echo legend_strip();
    echo '<form class="filters" method="get"><input type="hidden" name="p" value="students"><div><label for="g">Grade</label><select id="g" name="grade"><option value="">All grades</option>';
    foreach ([2, 3, 4] as $g) echo '<option value="' . $g . '"' . ($fGrade === $g ? ' selected' : '') . '>Grade ' . $g . '</option>';
    echo '</select></div><div><label for="st">Status</label><select id="st" name="status"><option value="">Any</option>';
    foreach (GV_STATUS_LABEL as $k => $lbl) echo '<option value="' . $k . '"' . ($fStatus === $k ? ' selected' : '') . '>' . h($lbl) . '</option>';
    echo '</select></div><div><label for="q">Name or ID</label><input id="q" type="text" name="q" maxlength="40" value="' . h($fSearch) . '"></div>';
    echo '<div><label for="s">Sort by</label><select id="s" name="sort">';
    foreach (['help' => 'Needs attention first', 'name' => 'Name', 'accuracy' => 'Lowest accuracy', 'progress' => 'Least progress', 'time' => 'Slowest answers', 'grade' => 'Grade', 'last' => 'Last played'] as $k => $lbl) echo '<option value="' . $k . '"' . ($sort === $k ? ' selected' : '') . '>' . h($lbl) . '</option>';
    echo '</select></div><button type="submit">Apply</button> <a class="btn ghost" href="teacher.php?p=students">Reset</a>';
    echo ' <a class="btn ghost" href="teacher.php?' . h(http_build_query(['export' => 'csv', 'grade' => $fGrade ?: '', 'q' => $fSearch, 'status' => $fStatus])) . '">Download CSV</a></form>';
    echo '<p class="legend">Level boxes: <i class="lvls-key lv-none"></i> not started <i class="lvls-key lv-part"></i> in progress <i class="lvls-key lv-s1"></i> 1★ <i class="lvls-key lv-s2"></i> 2★ <i class="lvls-key lv-s3"></i> 3★. “Trend” compares the last ' . TREND_WINDOW . ' answers with the ' . TREND_WINDOW . ' before. A skill is flagged when a student has answered ' . MIN_ANSWERS . '+ questions in it and is below ' . WEAK_BELOW . '%.</p>';
    if (!$students) {
        echo '<div class="card"><h3>No students yet</h3><p class="muted">Add students on the <a href="teacher.php?p=roster">Roster</a> page, or have them register in the game with your name “' . h($me['display_name']) . '”.</p></div>';
    } elseif (!$view) {
        echo '<div class="card muted">No students match those filters.</div>';
    } else {
        echo '<div class="tablewrap"><table style="min-width:1250px"><thead><tr><th>Student</th><th class="num">Grade</th><th>Levels</th><th class="num">Accuracy</th><th class="num">Trend</th><th class="num">Avg time</th><th class="num">Timed out</th>';
        foreach ($allSkillNames as $sk) echo '<th class="num">' . h($sk) . '</th>';
        echo '<th>Last played</th><th>Status</th></tr></thead><tbody>';
        foreach ($view as $s) {
            echo '<tr><td>' . student_label($s) . '</td><td class="num">' . ($s['grade'] ?: '—') . '</td><td>' . level_boxes($s) . '<small class="muted"><br>' . $s['stars'] . ' ★</small></td>';
            echo '<td class="num">' . acc_pill($s['acc'], $s['n']) . '<small class="muted"><br>' . $s['n'] . ' answers</small></td><td class="num">' . trend_html($s) . '</td>';
            echo '<td class="num">' . gv_fmt_seconds($s['avgSec']) . '</td><td class="num">' . ($s['n'] ? $s['timeouts'] . ($s['timeoutPct'] !== null ? '<small class="muted"><br>' . $s['timeoutPct'] . '%</small>' : '') : '—') . '</td>';
            foreach ($allSkillNames as $sk) echo skill_cell($s['skills'][$sk]);
            echo '<td>' . h(gv_ago($s['last'])) . ($s['last'] ? '<small class="muted"><br>' . h(gv_local($s['last'], 'M j, g:i A')) . '</small>' : '') . '</td><td>' . status_pill($s['status']) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
    echo '</div>';
    page_end();
    exit;
}
page_start('Teacher Dashboard');
echo '<div class="wrap">';
top_bar($me, 'Class overview', 'overview');
echo legend_strip();
flash_show();
teacher_name_card($me);
if (!$students) {
    echo '<div class="card"><h3>No students yet</h3><p class="muted">Add your class on the <a href="teacher.php?p=roster">Roster</a> page (Student ID + full name), or ask students to register in the game and type your name “' . h($me['display_name']) . '”. Their progress will appear here as soon as they play.</p></div>';
    page_end();
    exit;
}
echo '<div class="stats">';
echo '<div class="card stat"><div class="n">' . $sum['registered'] . ' <span style="font-size:16px;color:var(--soft)">/ ' . $sum['students'] . '</span></div><div class="l">Students registered</div></div>';
echo '<div class="card stat"><div class="n">' . $sum['active7'] . '</div><div class="l">Played in the last ' . INACTIVE_DAYS . ' days</div></div>';
echo '<div class="card stat"><div class="n">' . ($sum['acc'] === null ? '—' : $sum['acc'] . '%') . '</div><div class="l">Class accuracy (' . number_format($sum['n']) . ' answers)</div></div>';
echo '<div class="card stat"><div class="n">' . gv_fmt_seconds($sum['avgSec']) . '</div><div class="l">Average time per answer (limit 30 s)</div></div>';
echo '<div class="card stat"><div class="n">' . ($sum['timeoutPct'] === null ? '—' : $sum['timeoutPct'] . '%') . '</div><div class="l">Questions that ran out of time</div></div>';
echo '<div class="card stat"><div class="n">' . $sum['needHelp'] . '</div><div class="l">Students who need help</div></div>';
echo '</div>';
$attn = array_values(array_filter($students, fn($r) => $r['status'] !== 'good' && $r['why']));
usort($attn, fn($a, $b) => ($statusOrder[$a['status']] <=> $statusOrder[$b['status']]) ?: (($a['acc'] ?? 101) <=> ($b['acc'] ?? 101)));
echo '<h2 id="attention">Needs attention</h2>';
if (!$attn) {
    echo '<div class="card focus none"><strong>Everyone who has started is on track.</strong></div>';
} else {
    $perPage = 10; $pages = (int)ceil(count($attn) / $perPage);
    $ap = min($pages, max(1, (int)($_GET['att'] ?? 1)));
    echo '<div class="tablewrap"><table style="min-width:760px"><thead><tr><th>Student</th><th>Status</th><th>Why</th><th class="num">Accuracy</th><th>Last played</th></tr></thead><tbody>';
    foreach (array_slice($attn, ($ap - 1) * $perPage, $perPage) as $r) {
        echo '<tr><td>' . student_label($r) . '</td><td>' . status_pill($r['status']) . '</td><td>' . h(implode(' · ', $r['why'])) . '</td><td class="num">' . acc_pill($r['acc'], $r['n']) . '</td><td>' . h(gv_ago($r['last'])) . '</td></tr>';
    }
    echo '</tbody></table></div>';
    $from = ($ap - 1) * $perPage + 1; $to = min(count($attn), $ap * $perPage);
    echo '<nav class="pager" aria-label="Needs attention pages"><span class="muted">Showing ' . $from . '–' . $to . ' of ' . count($attn) . '</span>';
    if ($pages > 1) {
        $lk = fn(int $n, string $label, string $cls = '') => '<a class="pg ' . $cls . '" href="teacher.php?att=' . $n . '#attention"' . ($cls === 'on' ? ' aria-current="page"' : '') . '>' . $label . '</a>';
        echo '<span class="pgs">';
        echo $ap > 1 ? $lk($ap - 1, '← Previous') : '<span class="pg off">← Previous</span>';
        for ($i = 1; $i <= $pages; $i++) echo $lk($i, (string)$i, $i === $ap ? 'on' : '');
        echo $ap < $pages ? $lk($ap + 1, 'Next →') : '<span class="pg off">Next →</span>';
        echo '</span>';
    }
    echo '</nav>';
}
echo '<details class="card legendbox" open><summary>What do the colours and words mean? (quick legend)</summary>' . legend_html(false) . '<p class="muted"><a href="teacher.php?p=legend">Open the full Legend</a></p></details>';
echo '<h2>Level progress</h2><div class="card">';
$reg = max(1, $sum['registered']);
foreach ($class['levels'] as $i => $lv) {
    $lp = pct($lv['c'], $lv['n']);
    $w = (int)round(100 * $lv['done'] / max(1, count($students)));
    echo '<div class="lvrow"><strong>' . $i . '. ' . h($lv['name']) . '</strong><div class="bar"><i style="width:' . $w . '%;background:#7C5CFF"></i></div>';
    echo '<span>' . $lv['done'] . ' of ' . count($students) . ' completed' . ($lv['started'] > $lv['done'] ? ' · ' . ($lv['started'] - $lv['done']) . ' in progress' : '') . '</span>';
    echo '<span class="muted">' . ($lp === null ? 'no answers yet' : 'accuracy ' . $lp . '%' . ($lv['t'] ? ' · ' . $lv['t'] . ' timed out' : '')) . '</span></div>';
}
echo '</div>';
$maxN = max(1, max(array_column($class['weeks'], 'n')));
echo '<h2>Activity by week</h2><div class="card"><div class="weeks">';
foreach ($class['weeks'] as $w) {
    $ap = pct($w['c'], $w['n']);
    echo '<div class="wk"><div class="wkbar"><i style="height:' . (int)round(100 * $w['n'] / $maxN) . '%;background:' . ($ap === null ? '#E1E7F0' : bar_color($ap, MIN_ANSWERS)) . '"></i></div>';
    echo '<div class="wkn">' . $w['n'] . '</div><div class="wkl">' . h($w['label']) . '</div><div class="wka">' . ($ap === null ? '' : $ap . '%') . '</div></div>';
}
echo '</div><p class="legend" style="margin-bottom:0">Bar height = answers that week; colour and % = class accuracy; weeks start on Monday.</p></div>';
echo '<h2>Skills across the class</h2><div class="card">';
foreach ($allSkillNames as $sk) {
    $x = $sum['skills'][$sk]; $sp = pct($x['c'], $x['n']);
    echo '<div class="skillrow"><strong>' . h($sk) . '</strong><div class="bar"><i style="width:' . ($sp ?? 0) . '%;background:' . bar_color($sp, $x['n']) . '"></i></div><span>' . ($sp === null ? 'No answers yet' : '<span class="pill ' . grade_class($sp, $x['n']) . '">' . $sp . '%</span> ' . $x['c'] . '/' . $x['n']) . '</span></div>';
}
if ($sum['weak']) {
    echo '<div class="focus card" style="margin-top:14px"><strong>Class-wide focus:</strong> ' . h(implode(', ', array_map(fn($w) => $w['skill'] . ' ' . $w['pct'] . '%', $sum['weak']))) . '<ul class="tips">';
    foreach ($sum['weak'] as $w) foreach (array_slice(TIPS[$w['skill']] ?? [], 0, 1) as $tip) echo '<li><strong>' . h($w['skill']) . ':</strong> ' . h($tip) . '</li>';
    echo '</ul></div>';
}
echo '</div>';
$byGrade = [];
foreach ($students as $s) {
    $g = $s['grade'];
    if (!isset($byGrade[$g])) $byGrade[$g] = ['students' => 0, 'n' => 0, 'c' => 0, 'skills' => [], 'help' => 0];
    $byGrade[$g]['students']++; $byGrade[$g]['n'] += $s['n']; $byGrade[$g]['c'] += $s['c'];
    if ($s['status'] === 'help') $byGrade[$g]['help']++;
    foreach ($s['skills'] as $sk => $x) {
        if (!isset($byGrade[$g]['skills'][$sk])) $byGrade[$g]['skills'][$sk] = ['n' => 0, 'c' => 0];
        $byGrade[$g]['skills'][$sk]['n'] += $x['n']; $byGrade[$g]['skills'][$sk]['c'] += $x['c'];
    }
}
ksort($byGrade);
echo '<h2>By grade</h2><div class="tablewrap"><table><thead><tr><th>Grade</th><th class="num">Students</th><th class="num">Answers</th><th class="num">Accuracy</th>';
foreach ($allSkillNames as $sk) echo '<th class="num">' . h($sk) . '</th>';
echo '<th class="num">Need help</th></tr></thead><tbody>';
foreach ($byGrade as $g => $x) {
    echo '<tr><td><strong>' . ($g ? 'Grade ' . $g : 'Not set') . '</strong></td><td class="num">' . $x['students'] . '</td><td class="num">' . $x['n'] . '</td><td class="num">' . acc_pill(pct($x['c'], $x['n']), $x['n']) . '</td>';
    foreach ($allSkillNames as $sk) echo skill_cell($x['skills'][$sk] ?? null);
    echo '<td class="num">' . $x['help'] . ' of ' . $x['students'] . '</td></tr>';
}
echo '</tbody></table></div>';
echo '</div>';
page_end();
