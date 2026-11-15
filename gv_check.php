<?php
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
ini_set('display_errors', '1');
error_reporting(E_ALL);
function line(string $label, $value): void { echo str_pad($label, 34) . (is_bool($value) ? ($value ? 'YES' : 'NO  <-- problem?') : $value) . "\n"; }
echo "Grammar Vault setup check\n=========================\n";
line('PHP version', PHP_VERSION . ($ok = version_compare(PHP_VERSION, '8.0.0', '>=') ? '' : '   <-- needs 8.0+: pick PHP 8.x in your host control panel'));
line('PDO available', class_exists('PDO'));
line('SQLite driver (pdo_sqlite)', class_exists('PDO') && in_array('sqlite', PDO::getAvailableDrivers(), true));
line('gv_db.php present', is_file(__DIR__ . '/gv_db.php'));
line('teacher.php present', is_file(__DIR__ . '/teacher.php'));
line('results_api.php present', is_file(__DIR__ . '/results_api.php'));
foreach (['gv_stats.php', 'student_api.php', 'student.html', 'student_progress.html', 'teacher_report.php', 'gv_mail.php', 'mail_config.php', 'teacher_autofill.js'] as $f) line($f . ' present', is_file(__DIR__ . '/' . $f));
line('Folder writable', is_writable(__DIR__));
line('data/ folder exists', is_dir(__DIR__ . '/data'));
line('data/ folder writable', is_dir(__DIR__ . '/data') ? is_writable(__DIR__ . '/data') : is_writable(__DIR__));
line('Sessions work', (function () { if (session_status() === PHP_SESSION_NONE) @session_start(); $_SESSION['t'] = 1; return isset($_SESSION['t']); })());
line('Connection is HTTPS', !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
line('Server software', $_SERVER['SERVER_SOFTWARE'] ?? '?');
if (!is_file(__DIR__ . '/gv_db.php') || !class_exists('PDO') || !in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    echo "\nStopping here: fix the lines marked NO first.\n";
    exit;
}
echo "\nOpening the database...\n";
try {
    require __DIR__ . '/gv_db.php';
    $db = gv_open_db();
    line('Database opened', true);
    line('SQLite version', (string)$db->query('SELECT sqlite_version()')->fetchColumn());
    line('Journal mode', (string)$db->query('PRAGMA journal_mode')->fetchColumn());
    line('Teacher accounts', (string)$db->query('SELECT COUNT(*) FROM teachers')->fetchColumn());
    line('Students recorded', (string)$db->query('SELECT COUNT(*) FROM players')->fetchColumn());
    line('Unassigned old students', (string)$db->query('SELECT COUNT(*) FROM players WHERE teacher_id = 0')->fetchColumn());
    echo "\nTeacher usernames (log in with one of these):\n";
    foreach ($db->query('SELECT username, display_name FROM teachers ORDER BY id') as $r) {
        echo "  username: {$r['username']}    name students type: {$r['display_name']}\n";
    }
    line('Old teacher password found', gv_legacy_hash() !== '');
    line('This computer counts as "local"', gv_is_local());
    if ((int)$db->query('SELECT COUNT(*) FROM teachers')->fetchColumn() === 0) {
        echo "\nNo teacher account exists yet. Open teacher.php and choose Create a teacher account.\n";
    }
} catch (Throwable $e) {
    line('Database opened', false);
    echo "Error: " . $e->getMessage() . "\n";
}
echo "\nDone. Delete gv_check.php from the server when you are finished.\n";
