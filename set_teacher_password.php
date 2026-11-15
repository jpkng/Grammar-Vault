<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/gv_db.php';
if (!class_exists('PDO') || !in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    fwrite(STDERR, "PHP's SQLite driver is not enabled. See README.txt step 2, then run this again.\n");
    exit(1);
}
function ask(string $prompt, bool $hidden = false): string {
    echo $prompt;
    $canHide = $hidden && function_exists('posix_isatty') && @posix_isatty(STDIN) && DIRECTORY_SEPARATOR === '/';
    if ($canHide) shell_exec('stty -echo');
    $line = fgets(STDIN);
    if ($canHide) { shell_exec('stty echo'); echo "\n"; }
    return rtrim((string)$line, "\r\n");
}
function ask_new_password(): ?string {
    $pw  = ask('New password (at least 10 characters): ', true);
    $pw2 = ask('Type it again to confirm: ', true);
    if (strlen($pw) < 10)  { fwrite(STDERR, "Too short — please use at least 10 characters.\n"); return null; }
    if (strlen($pw) > 72)  { fwrite(STDERR, "Too long — the limit is 72 characters.\n"); return null; }
    if ($pw !== $pw2)      { fwrite(STDERR, "The two passwords did not match. Nothing was saved.\n"); return null; }
    return $pw;
}
try {
    $db = gv_open_db();
} catch (Throwable $e) {
    fwrite(STDERR, 'Could not open the database: ' . $e->getMessage() . "\n");
    exit(1);
}
echo "\nGrammar Vault — teacher accounts\n";
echo "  1) Create a teacher account\n";
echo "  2) Reset a teacher's password\n";
echo "  3) List teachers and the names students type\n";
$choice = trim(ask("Choose 1-3: "));
if ($choice === '1') {
    $username = trim(ask('Username (3-30 letters/numbers . _ -): '));
    $display  = trim(ask('Name students will see (for example Ms. Rivera): '));
    $pw = ask_new_password();
    if ($pw === null) exit(1);
    [$id, $err] = gv_create_teacher($db, $username, $display, $pw);
    if (!$id) { fwrite(STDERR, $err . "\n"); exit(1); }
    $t = gv_teacher_by_id($db, $id);
    echo "Saved. Account '{$t['username']}' created.\nStudents find this teacher by typing: {$t['display_name']}\n";
    echo "Log in at teacher.php with that username and password.\n";
} elseif ($choice === '2') {
    $username = trim(ask('Username of the teacher: '));
    $q = $db->prepare('SELECT id, username FROM teachers WHERE username = ?');
    $q->execute([$username]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (!$row) { fwrite(STDERR, "No teacher with that username. Choose option 3 to see the list.\n"); exit(1); }
    $pw = ask_new_password();
    if ($pw === null) exit(1);
    $db->prepare('UPDATE teachers SET password_hash = ? WHERE id = ?')->execute([password_hash($pw, PASSWORD_DEFAULT), $row['id']]);
    echo "Saved. The password for '{$row['username']}' was changed.\n";
} elseif ($choice === '3') {
    $rows = $db->query('SELECT t.username, t.display_name,
                               (SELECT COUNT(*) FROM players p WHERE p.teacher_id = t.id) AS students
                        FROM teachers t ORDER BY t.id')->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) echo "No teacher accounts yet.\n";
    foreach ($rows as $r) printf("  %-20s %-22s   %d student(s)\n", $r['username'], $r['display_name'], $r['students']);
} else {
    fwrite(STDERR, "Please choose 1, 2 or 3.\n");
    exit(1);
}
