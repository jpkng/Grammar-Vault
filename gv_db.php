<?php
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath((string)$_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    http_response_code(404);
    exit;
}
const GV_DATA_DIR    = __DIR__ . '/data';
const GV_DB_PATH     = GV_DATA_DIR . '/grammar_vault.sqlite';
const GV_CONFIG_PATH = __DIR__ . '/teacher_config.php';
const GV_CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
const GV_CODE_LEN      = 6;
function gv_is_local(): bool {
    return in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
}
function gv_config(): array {
    if (is_file(GV_CONFIG_PATH)) {
        $cfg = include GV_CONFIG_PATH;
        if (is_array($cfg)) return $cfg;
    }
    return [];
}
function gv_legacy_hash(): string {
    $env = getenv('GV_TEACHER_PASSWORD_HASH');
    if (is_string($env) && $env !== '') return $env;
    $cfg = gv_config();
    return !empty($cfg['password_hash']) ? (string)$cfg['password_hash'] : '';
}
function gv_open_db(): PDO {
    if (!is_dir(GV_DATA_DIR)) mkdir(GV_DATA_DIR, 0750, true);
    if (!file_exists(GV_DATA_DIR . '/.htaccess')) file_put_contents(GV_DATA_DIR . '/.htaccess', "Require all denied\nDeny from all\n");
    if (!file_exists(GV_DATA_DIR . '/index.html')) file_put_contents(GV_DATA_DIR . '/index.html', '');
    $db = new PDO('sqlite:' . GV_DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_TIMEOUT, 10);
    try { $db->exec('PRAGMA journal_mode = DELETE'); } catch (Throwable $e) {  }
    $db->exec('PRAGMA foreign_keys = ON');
    $db->exec("
        CREATE TABLE IF NOT EXISTS teachers (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            username      TEXT    NOT NULL UNIQUE COLLATE NOCASE,
            display_name  TEXT    NOT NULL,
            password_hash TEXT    NOT NULL,
            class_code    TEXT    NOT NULL UNIQUE,
            created_at    TEXT    NOT NULL
        );
        CREATE TABLE IF NOT EXISTS players (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            teacher_id  INTEGER NOT NULL DEFAULT 0,
            name        TEXT    NOT NULL COLLATE NOCASE,
            grade       INTEGER NOT NULL DEFAULT 0,
            created_at  TEXT    NOT NULL,
            last_seen   TEXT    NOT NULL,
            UNIQUE (teacher_id, name, grade)
        );
        CREATE TABLE IF NOT EXISTS runs (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            run_uuid     TEXT    NOT NULL UNIQUE,
            player_id    INTEGER NOT NULL REFERENCES players(id),
            started_at   TEXT    NOT NULL,
            finished_at  TEXT,
            final_score  INTEGER,
            final_total  INTEGER
        );
        CREATE TABLE IF NOT EXISTS answers (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id     TEXT    NOT NULL UNIQUE,
            run_id       INTEGER NOT NULL REFERENCES runs(id),
            level        INTEGER NOT NULL,
            category     TEXT    NOT NULL,
            question     TEXT    NOT NULL,
            qtype        TEXT    NOT NULL,
            given        TEXT    NOT NULL,
            correct      INTEGER NOT NULL,
            answered_at  TEXT    NOT NULL
        );
        CREATE TABLE IF NOT EXISTS level_results (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            run_id        INTEGER NOT NULL REFERENCES runs(id),
            level         INTEGER NOT NULL,
            correct       INTEGER NOT NULL,
            total         INTEGER NOT NULL,
            stars         INTEGER NOT NULL,
            completed_at  TEXT    NOT NULL,
            UNIQUE (run_id, level)
        );
        CREATE TABLE IF NOT EXISTS school_roster (
            student_no TEXT PRIMARY KEY,
            full_name  TEXT NOT NULL,
            grade      INTEGER NOT NULL DEFAULT 0
        );
        CREATE TABLE IF NOT EXISTS reset_requests (
            at    INTEGER NOT NULL,
            ip    TEXT    NOT NULL,
            uname TEXT    NOT NULL
        );
        CREATE TABLE IF NOT EXISTS gv_settings (
            name  TEXT PRIMARY KEY,
            value TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS students (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            teacher_id    INTEGER NOT NULL,
            student_no    TEXT    NOT NULL UNIQUE,
            full_name     TEXT    NOT NULL,
            username      TEXT    UNIQUE COLLATE NOCASE,
            grade         INTEGER NOT NULL DEFAULT 0,
            registered    INTEGER NOT NULL DEFAULT 0,
            created_at    TEXT    NOT NULL,
            registered_at TEXT,
            last_login    TEXT
        );
        CREATE TABLE IF NOT EXISTS teacher_login_attempts (ip TEXT NOT NULL, at INTEGER NOT NULL);
        CREATE TABLE IF NOT EXISTS class_code_failures    (ip TEXT NOT NULL, at INTEGER NOT NULL);
    ");
    gv_migrate($db);
    $db->exec('CREATE INDEX IF NOT EXISTS idx_answers_run ON answers(run_id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_runs_player ON runs(player_id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_players_teacher ON players(teacher_id)');
    $db->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_players_student ON players(student_id) WHERE student_id IS NOT NULL');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_students_teacher ON students(teacher_id)');
    return $db;
}
function gv_players_has_teacher_column(PDO $db): bool {
    foreach ($db->query('PRAGMA table_info(players)')->fetchAll(PDO::FETCH_ASSOC) as $c) {
        if ($c['name'] === 'teacher_id') return true;
    }
    return false;
}
function gv_has_column(PDO $db, string $table, string $col): bool {
    foreach ($db->query('PRAGMA table_info(' . $table . ')')->fetchAll(PDO::FETCH_ASSOC) as $c) {
        if ($c['name'] === $col) return true;
    }
    return false;
}
function gv_migrate(PDO $db): void {
    if (!gv_players_has_teacher_column($db)) {
        $db->exec('PRAGMA foreign_keys = OFF');
        $db->exec('BEGIN IMMEDIATE');
        try {
            if (!gv_players_has_teacher_column($db)) {
                $db->exec('CREATE TABLE players_new (
                    id          INTEGER PRIMARY KEY AUTOINCREMENT,
                    teacher_id  INTEGER NOT NULL DEFAULT 0,
                    name        TEXT    NOT NULL COLLATE NOCASE,
                    grade       INTEGER NOT NULL DEFAULT 0,
                    created_at  TEXT    NOT NULL,
                    last_seen   TEXT    NOT NULL,
                    UNIQUE (teacher_id, name, grade)
                )');
                $db->exec('INSERT INTO players_new (id, teacher_id, name, grade, created_at, last_seen)
                           SELECT id, 0, name, grade, created_at, last_seen FROM players');
                $db->exec('DROP TABLE players');
                $db->exec('ALTER TABLE players_new RENAME TO players');
            }
            $db->exec('COMMIT');
        } catch (Throwable $e) {
            $db->exec('ROLLBACK');
            $db->exec('PRAGMA foreign_keys = ON');
            throw $e;
        }
        $db->exec('PRAGMA foreign_keys = ON');
    }
    if (!gv_has_column($db, 'players', 'student_id')) $db->exec('ALTER TABLE players ADD COLUMN student_id INTEGER');
    if (!gv_has_column($db, 'teachers', 'email'))    $db->exec("ALTER TABLE teachers ADD COLUMN email TEXT NOT NULL DEFAULT ''");
    if (!gv_has_column($db, 'teachers', 'pw_temp'))  $db->exec('ALTER TABLE teachers ADD COLUMN pw_temp INTEGER NOT NULL DEFAULT 0');
    if (!gv_has_column($db, 'students', 'password_hash')) $db->exec('ALTER TABLE students ADD COLUMN password_hash TEXT');
    if (!gv_has_column($db, 'answers', 'seconds'))    $db->exec('ALTER TABLE answers ADD COLUMN seconds REAL');
    if (!gv_has_column($db, 'answers', 'timed_out'))  $db->exec('ALTER TABLE answers ADD COLUMN timed_out INTEGER NOT NULL DEFAULT 0');
    $none = (int)$db->query('SELECT COUNT(*) FROM teachers')->fetchColumn() === 0;
    $legacy = $none ? gv_legacy_hash() : '';
    if ($legacy !== '') {
        $db->exec('BEGIN IMMEDIATE');
        try {
            if ((int)$db->query('SELECT COUNT(*) FROM teachers')->fetchColumn() === 0) {
                gv_insert_teacher($db, 'teacher', 'Teacher', $legacy);
            }
            $db->exec('COMMIT');
        } catch (Throwable $e) {
            $db->exec('ROLLBACK');
            throw $e;
        }
    }
}
function gv_new_class_code(PDO $db): string {
    $max = strlen(GV_CODE_ALPHABET) - 1;
    $q = $db->prepare('SELECT 1 FROM teachers WHERE class_code = ?');
    for ($try = 0; $try < 50; $try++) {
        $code = '';
        for ($i = 0; $i < GV_CODE_LEN; $i++) $code .= GV_CODE_ALPHABET[random_int(0, $max)];
        $q->execute([$code]);
        if (!$q->fetchColumn()) return $code;
    }
    throw new RuntimeException('Could not make a unique class code');
}
function gv_insert_teacher(PDO $db, string $username, string $display, string $hash, string $email = ''): int {
    $first = (int)$db->query('SELECT COUNT(*) FROM teachers')->fetchColumn() === 0;
    $db->prepare('INSERT INTO teachers (username, display_name, password_hash, class_code, created_at, email) VALUES (?,?,?,?,?,?)')
       ->execute([$username, $display, $hash, gv_new_class_code($db), gmdate('Y-m-d H:i:s'), $email]);
    $id = (int)$db->lastInsertId();
    if ($first) $db->prepare('UPDATE players SET teacher_id = ? WHERE teacher_id = 0')->execute([$id]);
    return $id;
}
function gv_teacher_key($v): string {
    $v = is_scalar($v) ? (string)$v : '';
    $v = function_exists('mb_strtolower') ? mb_strtolower($v, 'UTF-8') : strtolower($v);
    $v = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $v) ?? '';
    $v = trim($v);
    $v = preg_replace('/^(mr|mrs|ms|miss|mx|dr|teacher)\s+/u', '', $v) ?? $v;
    return str_replace(' ', '', $v);
}
function gv_teachers_matching(PDO $db, string $typed): array {
    $key = gv_teacher_key($typed);
    if ($key === '' || strlen($key) > 60) return [];
    $out = [];
    foreach ($db->query('SELECT id, username, display_name FROM teachers')->fetchAll(PDO::FETCH_ASSOC) as $t) {
        if (gv_teacher_key($t['display_name']) === $key) $out[] = $t;
    }
    return $out;
}
function gv_valid_username(string $u): bool {
    return (bool)preg_match('/^[A-Za-z0-9._-]{3,30}$/', $u);
}
function gv_create_teacher(PDO $db, string $username, string $display, string $password, string $email = ''): array {
    $username = trim($username);
    $display  = trim(preg_replace('/[\x00-\x1F\x7F]/u', ' ', $display) ?? '');
    if (!gv_valid_username($username))  return [null, 'Usernames use 3–30 letters, numbers, dots, dashes or underscores.'];
    if ($display === '' || (function_exists('mb_strlen') ? mb_strlen($display) : strlen($display)) > 30)
        return [null, 'Please enter the name students will see (up to 30 characters), for example "Ms. Rivera".'];
    if (strlen($password) < 10)         return [null, 'Please use a password of at least 10 characters.'];
    if (strlen($password) > 72)         return [null, 'That password is too long (limit 72 characters).'];
    $email = gv_clean_email($email);
    if ($email !== '' && !gv_valid_email($email)) return [null, 'Please enter a valid email address.'];
    try {
        $db->exec('BEGIN IMMEDIATE');
        $q = $db->prepare('SELECT 1 FROM teachers WHERE username = ?');
        $q->execute([$username]);
        if ($q->fetchColumn()) { $db->exec('ROLLBACK'); return [null, 'That username is already taken.']; }
        if (gv_teachers_matching($db, $display)) { $db->exec('ROLLBACK'); return [null, 'Another teacher already uses that name. Students type this name to find you, so please add something to tell you apart, for example "Ms. Rivera (Room 5)".']; }
        $id = gv_insert_teacher($db, $username, $display, password_hash($password, PASSWORD_DEFAULT), $email);
        $db->exec('COMMIT');
        return [$id, ''];
    } catch (Throwable $e) {
        try { $db->exec('ROLLBACK'); } catch (Throwable $e2) {}
        error_log('Grammar Vault gv_create_teacher: ' . $e->getMessage());
        return [null, 'Could not create the account. Please try again.'];
    }
}
function gv_teacher_by_id(PDO $db, int $id): ?array {
    $q = $db->prepare('SELECT id, username, display_name, class_code, password_hash, email, pw_temp FROM teachers WHERE id = ?');
    $q->execute([$id]);
    $r = $q->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}
function gv_teacher_by_code(PDO $db, string $code): ?array {
    $q = $db->prepare('SELECT id, display_name, class_code FROM teachers WHERE class_code = ?');
    $q->execute([$code]);
    $r = $q->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}
function gv_teacher_suggestions(PDO $db, string $typed, int $limit = 5): array {
    $key = gv_teacher_key($typed);
    if (strlen($key) < 2 || strlen($key) > 60) return [];
    $out = [];
    foreach ($db->query('SELECT display_name FROM teachers ORDER BY display_name')->fetchAll(PDO::FETCH_COLUMN) as $dn) {
        if (strpos(gv_teacher_key($dn), $key) === 0) $out[] = $dn;
        if (count($out) >= $limit) break;
    }
    return $out;
}
function gv_teacher_by_name(PDO $db, string $typed): ?array {
    $m = gv_teachers_matching($db, $typed);
    return count($m) === 1 ? ['id' => (int)$m[0]['id'], 'display_name' => $m[0]['display_name']] : null;
}
function gv_clean_code($v): string {
    $v = is_scalar($v) ? strtoupper((string)$v) : '';
    return substr(preg_replace('/[^A-Z0-9]/', '', $v) ?? '', 0, GV_CODE_LEN);
}
const GV_GRADES = [2, 3, 4];
function gv_norm_student_no($v): string {
    $v = is_scalar($v) ? trim((string)$v) : '';
    if (preg_match('/^(\d{2})[\s.\-]*(\d{4})[\s.\-]*(\d{3})$/', $v, $m)) return $m[1] . '-' . $m[2] . '-' . $m[3];
    return '';
}
function gv_clean_full_name($v): string {
    $v = is_scalar($v) ? (string)$v : '';
    $v = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $v) ?? '';
    return trim(preg_replace('/\s+/u', ' ', $v) ?? '');
}
function gv_valid_full_name(string $n): bool {
    return (bool)preg_match("/^\p{L}[\p{L}\p{M}\s.,'’\-]{1,59}$/u", $n) && count(preg_split('/\s+/u', $n)) >= 2;
}
function gv_name_key(string $n): string {
    $n = function_exists('mb_strtolower') ? mb_strtolower($n, 'UTF-8') : strtolower($n);
    $parts = preg_split('/[^\p{L}\p{N}]+/u', $n, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    sort($parts);
    return implode(' ', $parts);
}
function gv_first_name(string $full): string {
    if (strpos($full, ',') !== false) { $after = trim(substr($full, strpos($full, ',') + 1)); if ($after !== '') $full = $after; }
    $p = preg_split('/\s+/u', trim($full)) ?: [];
    $f = $p[0] ?? $full;
    return function_exists('mb_substr') ? mb_substr($f, 0, 20) : substr($f, 0, 20);
}
function gv_valid_student_username(string $u): bool {
    return gv_valid_username($u) && gv_norm_student_no($u) === '' && !preg_match('/^\d+$/', $u);
}
function gv_student_by_no(PDO $db, string $no): ?array {
    $q = $db->prepare('SELECT * FROM students WHERE student_no = ?');
    $q->execute([$no]);
    return $q->fetch(PDO::FETCH_ASSOC) ?: null;
}
function gv_student_by_id(PDO $db, int $id): ?array {
    $q = $db->prepare('SELECT * FROM students WHERE id = ?');
    $q->execute([$id]);
    return $q->fetch(PDO::FETCH_ASSOC) ?: null;
}
function gv_student_find(PDO $db, string $ident): ?array {
    $ident = trim($ident);
    $no = gv_norm_student_no($ident);
    if ($no !== '') return gv_student_by_no($db, $no);
    if (!gv_valid_username($ident)) return null;
    $q = $db->prepare('SELECT * FROM students WHERE username = ?');
    $q->execute([$ident]);
    return $q->fetch(PDO::FETCH_ASSOC) ?: null;
}
function gv_student_public(PDO $db, array $s): array {
    $t = gv_teacher_by_id($db, (int)$s['teacher_id']);
    return [
        'student_no' => $s['student_no'],
        'full_name'  => $s['full_name'],
        'first_name' => gv_first_name($s['full_name']),
        'username'   => $s['username'] ?? '',
        'grade'      => (int)$s['grade'],
        'teacher'    => $t ? $t['display_name'] : '',
    ];
}
const GV_PW_MIN = 4;
const GV_TOKEN_DAYS = 30;
function gv_valid_password($p): bool {
    return is_string($p) && strlen($p) >= GV_PW_MIN && strlen($p) <= 72 && trim($p) !== '';
}
function gv_password_error(): string { return 'Your password needs at least ' . GV_PW_MIN . ' letters or numbers.'; }
function gv_secret(PDO $db): string {
    $q = $db->prepare("SELECT value FROM gv_settings WHERE name = 'token_secret'");
    $q->execute();
    $v = $q->fetchColumn();
    if (is_string($v) && strlen($v) >= 32) return $v;
    $v = bin2hex(random_bytes(32));
    $db->prepare("INSERT OR IGNORE INTO gv_settings (name, value) VALUES ('token_secret', ?)")->execute([$v]);
    $q->execute();
    return (string)$q->fetchColumn();
}
function gv_student_token(PDO $db, array $s): string {
    $payload = $s['id'] . '|' . (time() + GV_TOKEN_DAYS * 86400) . '|' . substr(hash('sha256', (string)($s['password_hash'] ?? '')), 0, 12);
    return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=') . '.' . hash_hmac('sha256', $payload, gv_secret($db));
}
function gv_student_from_token(PDO $db, $token): ?array {
    if (!is_string($token) || strlen($token) > 300 || substr_count($token, '.') !== 1) return null;
    [$p64, $sig] = explode('.', $token);
    $payload = base64_decode(strtr($p64, '-_', '+/'), true);
    if ($payload === false || !hash_equals(hash_hmac('sha256', $payload, gv_secret($db)), $sig)) return null;
    $parts = explode('|', $payload);
    if (count($parts) !== 3 || (int)$parts[1] < time()) return null;
    $s = gv_student_by_id($db, (int)$parts[0]);
    if (!$s || (int)$s['registered'] !== 1 || empty($s['password_hash'])) return null;
    if (!hash_equals(substr(hash('sha256', (string)$s['password_hash']), 0, 12), $parts[2])) return null;
    return $s;
}
function gv_student_register(PDO $db, $noRaw, $nameRaw, $teacherTyped, $gradeRaw, $usernameRaw, $password = ''): array {
    $no   = gv_norm_student_no($noRaw);
    $name = gv_clean_full_name($nameRaw);
    $user = is_scalar($usernameRaw) ? trim((string)$usernameRaw) : '';
    if ($no === '')                 return [null, 'Please type your Student ID like this: 12-3456-789.'];
    if (!gv_valid_full_name($name)) return [null, 'Please type your full name (first and last name).'];
    if (!gv_valid_password($password)) return [null, gv_password_error()];
    if ($password === $no || $password === str_replace('-', '', $no) || ($user !== '' && strcasecmp($password, $user) === 0))
        return [null, 'Please choose a password that is different from your Student ID and username.'];
    if ($user !== '' && !gv_valid_student_username($user))
        return [null, 'A username uses 3–30 letters, numbers, dots, dashes or underscores (and cannot look like a Student ID).'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    try {
        $db->exec('BEGIN IMMEDIATE');
        $ex = gv_student_by_no($db, $no);
        if ($user !== '') {
            $q = $db->prepare('SELECT id FROM students WHERE username = ?');
            $q->execute([$user]);
            $taken = $q->fetchColumn();
            if ($taken && (!$ex || (int)$taken !== (int)$ex['id'])) { $db->exec('ROLLBACK'); return [null, 'That username is already taken. Please pick another one.']; }
        }
        $ts = gmdate('Y-m-d H:i:s');
        if ($ex) {
            if ((int)$ex['registered'] === 1) { $db->exec('ROLLBACK'); return [null, 'This Student ID is already registered. Please log in instead.']; }
            if (gv_name_key($ex['full_name']) !== gv_name_key($name)) {
                $db->exec('ROLLBACK');
                return [null, 'The name does not match what your teacher entered for this Student ID. Please type your full name exactly as your teacher wrote it.'];
            }
            $db->prepare('UPDATE students SET registered = 1, registered_at = ?, last_login = ?, username = ?, password_hash = ? WHERE id = ?')
               ->execute([$ts, $ts, $user !== '' ? $user : null, $hash, $ex['id']]);
            $id = (int)$ex['id'];
        } else {
            $t = gv_teacher_by_name($db, is_scalar($teacherTyped) ? (string)$teacherTyped : '');
            if (!$t) { $db->exec('ROLLBACK'); return [null, "We couldn't find that teacher. Please type your teacher's name the way they told you (for example Ms. Rivera)."]; }
            $grade = (int)$gradeRaw;
            if (!in_array($grade, GV_GRADES, true)) { $db->exec('ROLLBACK'); return [null, 'Please choose your grade (2, 3 or 4).']; }
            $db->prepare('INSERT INTO students (teacher_id, student_no, full_name, username, grade, registered, created_at, registered_at, last_login, password_hash)
                          VALUES (?,?,?,?,?,1,?,?,?,?)')
               ->execute([$t['id'], $no, $name, $user !== '' ? $user : null, $grade, $ts, $ts, $ts, $hash]);
            $id = (int)$db->lastInsertId();
        }
        $db->exec('COMMIT');
        return [gv_student_by_id($db, $id), ''];
    } catch (Throwable $e) {
        try { $db->exec('ROLLBACK'); } catch (Throwable $e2) {}
        error_log('Grammar Vault gv_student_register: ' . $e->getMessage());
        return [null, 'Could not register. Please try again.'];
    }
}
const GV_LOGIN_FAIL = 'Wrong Student ID, username or password. (If your teacher just added you, tap Register first.)';
function gv_student_login(PDO $db, $ident, $password = ''): array {
    $ident = is_scalar($ident) ? trim((string)$ident) : '';
    if ($ident === '') return [null, 'Please type your Student ID or username.'];
    $s = gv_student_find($db, $ident);
    if ($s && (int)$s['registered'] === 1 && empty($s['password_hash'])) return [null, 'NEEDPW'];
    if (!is_string($password) || $password === '') return [null, 'Please type your password.'];
    $ok = password_verify($password, $s && !empty($s['password_hash']) ? $s['password_hash'] : '$2y$10$xiF3xACazoBwWy58bsqtIO9vza6I1ByjDxwZXkVZ8KBNBEemQ.xeG');
    if (!$s || (int)$s['registered'] !== 1 || !$ok) return [null, GV_LOGIN_FAIL];
    if (password_needs_rehash($s['password_hash'], PASSWORD_DEFAULT)) $db->prepare('UPDATE students SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $s['id']]);
    $db->prepare('UPDATE students SET last_login = ? WHERE id = ?')->execute([gmdate('Y-m-d H:i:s'), $s['id']]);
    return [gv_student_by_id($db, (int)$s['id']), ''];
}
function gv_student_set_first_password(PDO $db, $ident, $nameRaw, $password): array {
    $s = gv_student_find($db, is_scalar($ident) ? trim((string)$ident) : '');
    if (!gv_valid_password($password)) return [null, gv_password_error()];
    if (!$s || (int)$s['registered'] !== 1 || !empty($s['password_hash']) || gv_name_key($s['full_name']) !== gv_name_key(gv_clean_full_name($nameRaw)))
        return [null, 'We could not set a password. Check your Student ID and type your full name exactly as it is on your account, or ask your teacher.'];
    if ($password === $s['student_no'] || $password === str_replace('-', '', $s['student_no'])) return [null, 'Please choose a password that is different from your Student ID.'];
    $db->prepare('UPDATE students SET password_hash = ?, last_login = ? WHERE id = ? AND password_hash IS NULL')->execute([password_hash($password, PASSWORD_DEFAULT), gmdate('Y-m-d H:i:s'), $s['id']]);
    return [gv_student_by_id($db, (int)$s['id']), ''];
}
function gv_teacher_set_student_password(PDO $db, int $teacherId, int $studentId, $password): bool {
    $s = gv_student_by_id($db, $studentId);
    if (!$s || (int)$s['teacher_id'] !== $teacherId || (int)$s['registered'] !== 1 || !gv_valid_password($password)) return false;
    $db->prepare('UPDATE students SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $studentId]);
    return true;
}
function gv_teacher_add_student(PDO $db, int $teacherId, $noRaw, $nameRaw, $gradeRaw): array {
    $no = gv_norm_student_no($noRaw);
    $name = gv_clean_full_name($nameRaw);
    $grade = (int)$gradeRaw;
    if ($no === '')                 return [null, 'Student ID must look like 12-3456-789.'];
    if (!gv_valid_full_name($name)) return [null, 'Please enter the full name (first and last name).'];
    if (!in_array($grade, GV_GRADES, true)) return [null, 'Grade must be 2, 3 or 4.'];
    try {
        $db->exec('BEGIN IMMEDIATE');
        $ex = gv_student_by_no($db, $no);
        if ($ex && (int)$ex['teacher_id'] === 0) {
            $db->prepare('UPDATE students SET teacher_id = ?, grade = ? WHERE id = ?')->execute([$teacherId, $grade, $ex['id']]);
            gv_move_student_player($db, (int)$ex['id'], $teacherId, $grade, (string)$ex['full_name'], (string)$ex['student_no']);
            $db->exec('COMMIT');
            return [(int)$ex['id'], '', true];
        }
        if ($ex) {
            $db->exec('ROLLBACK');
            return [null, (int)$ex['teacher_id'] === $teacherId ? 'Already in your class.' : 'This Student ID already belongs to another teacher\'s class.'];
        }
        $db->prepare('INSERT INTO students (teacher_id, student_no, full_name, grade, registered, created_at) VALUES (?,?,?,?,0,?)')
           ->execute([$teacherId, $no, $name, $grade, gmdate('Y-m-d H:i:s')]);
        $id = (int)$db->lastInsertId();
        $db->exec('COMMIT');
        return [$id, ''];
    } catch (Throwable $e) {
        try { $db->exec('ROLLBACK'); } catch (Throwable $e2) {}
        error_log('Grammar Vault gv_teacher_add_student: ' . $e->getMessage());
        return [null, 'Could not add the student. Please try again.'];
    }
}
function gv_move_student_player(PDO $db, int $studentId, int $teacherId, int $grade, string $name, string $no): void {
    $q = $db->prepare('SELECT id FROM players WHERE student_id = ?');
    $q->execute([$studentId]);
    $pid = $q->fetchColumn();
    if (!$pid) return;
    $c = $db->prepare('SELECT 1 FROM players WHERE teacher_id = ? AND name = ? AND grade = ? AND id <> ?');
    $c->execute([$teacherId, $name, $grade, $pid]);
    if ($c->fetchColumn()) $name .= ' (' . $no . ')';
    $db->prepare('UPDATE players SET teacher_id = ?, grade = ?, name = ? WHERE id = ?')->execute([$teacherId, $grade, $name, $pid]);
}
function gv_teacher_remove_student(PDO $db, int $teacherId, int $studentId): bool {
    $s = gv_student_by_id($db, $studentId);
    if (!$s || (int)$s['teacher_id'] !== $teacherId) return false;
    try {
        $db->exec('BEGIN IMMEDIATE');
        if ((int)$s['registered'] === 1) {
            $db->prepare('UPDATE students SET teacher_id = 0 WHERE id = ?')->execute([$studentId]);
            gv_move_student_player($db, $studentId, 0, (int)$s['grade'], (string)$s['full_name'], (string)$s['student_no']);
        } else {
            $db->prepare('DELETE FROM students WHERE id = ?')->execute([$studentId]);
        }
        $db->exec('COMMIT');
        return true;
    } catch (Throwable $e) {
        try { $db->exec('ROLLBACK'); } catch (Throwable $e2) {}
        error_log('Grammar Vault gv_teacher_remove_student: ' . $e->getMessage());
        return false;
    }
}
function gv_student_player_id(PDO $db, array $s): int {
    $q = $db->prepare('SELECT id FROM players WHERE student_id = ?');
    $q->execute([$s['id']]);
    $id = $q->fetchColumn();
    $ts = gmdate('Y-m-d H:i:s');
    if ($id) {
        $db->prepare('UPDATE players SET last_seen = ?, grade = ?, teacher_id = ? WHERE id = ?')->execute([$ts, $s['grade'], $s['teacher_id'], $id]);
        return (int)$id;
    }
    $name = $s['full_name'];
    $q = $db->prepare('SELECT id FROM players WHERE teacher_id = ? AND name = ? AND grade = ? AND student_id IS NULL');
    $q->execute([$s['teacher_id'], $name, $s['grade']]);
    $old = $q->fetchColumn();
    if ($old) {
        $db->prepare('UPDATE players SET student_id = ?, last_seen = ? WHERE id = ?')->execute([$s['id'], $ts, $old]);
        return (int)$old;
    }
    $q = $db->prepare('SELECT 1 FROM players WHERE teacher_id = ? AND name = ? AND grade = ?');
    $q->execute([$s['teacher_id'], $name, $s['grade']]);
    if ($q->fetchColumn()) $name .= ' (' . $s['student_no'] . ')';
    $db->prepare('INSERT INTO players (teacher_id, name, grade, created_at, last_seen, student_id) VALUES (?,?,?,?,?,?)')
       ->execute([$s['teacher_id'], $name, $s['grade'], $ts, $ts, $s['id']]);
    return (int)$db->lastInsertId();
}
function gv_teacher_remove_legacy_player(PDO $db, int $teacherId, int $playerId): bool {
    $q = $db->prepare('SELECT id FROM players WHERE id = ? AND teacher_id = ? AND student_id IS NULL');
    $q->execute([$playerId, $teacherId]);
    if (!$q->fetchColumn()) return false;
    try {
        $db->exec('BEGIN IMMEDIATE');
        $db->prepare('DELETE FROM answers       WHERE run_id IN (SELECT id FROM runs WHERE player_id = ?)')->execute([$playerId]);
        $db->prepare('DELETE FROM level_results WHERE run_id IN (SELECT id FROM runs WHERE player_id = ?)')->execute([$playerId]);
        $db->prepare('DELETE FROM runs    WHERE player_id = ?')->execute([$playerId]);
        $db->prepare('DELETE FROM players WHERE id = ?')->execute([$playerId]);
        $db->exec('COMMIT');
        return true;
    } catch (Throwable $e) {
        try { $db->exec('ROLLBACK'); } catch (Throwable $e2) {}
        return false;
    }
}
function gv_teacher_bulk_add(PDO $db, int $teacherId, string $text, int $defaultGrade): array {
    $added = 0; $skipped = [];
    foreach (preg_split('/\R/u', $text) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $parts = array_values(array_filter(array_map('trim', preg_split('/[,;\t]+/u', $line) ?: []), fn($p) => $p !== ''));
        $no = ''; $name = ''; $grade = $defaultGrade;
        foreach ($parts as $p) {
            if ($no === '' && gv_norm_student_no($p) !== '') $no = $p;
            elseif (preg_match('/^[2-4]$/', $p)) $grade = (int)$p;
            elseif ($name === '') $name = $p;
        }
        [$id, $err] = gv_teacher_add_student($db, $teacherId, $no, $name, $grade);
        if ($id) $added++; else $skipped[] = [$line, $err];
    }
    return [$added, $skipped];
}
function gv_clean_email($v): string {
    $v = is_scalar($v) ? trim((string)$v) : '';
    return function_exists('mb_strtolower') ? mb_strtolower($v, 'UTF-8') : strtolower($v);
}
function gv_valid_email(string $e): bool {
    return strlen($e) <= 120 && (bool)filter_var($e, FILTER_VALIDATE_EMAIL) && !preg_match('/[\r\n,;<>\s]/', $e);
}
function gv_generate_password(int $len = 12): string {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $max = strlen($alphabet) - 1; $out = '';
    for ($i = 0; $i < $len; $i++) $out .= $alphabet[random_int(0, $max)];
    return $out;
}
function gv_teacher_for_reset(PDO $db, string $username, string $email): ?array {
    $email = gv_clean_email($email);
    $q = $db->prepare('SELECT id, username, display_name, email, password_hash FROM teachers WHERE username = ?');
    $q->execute([trim($username)]);
    $t = $q->fetch(PDO::FETCH_ASSOC);
    if (!$t || $t['email'] === '' || $email === '' || !hash_equals((string)$t['email'], $email)) return null;
    return $t;
}
function gv_set_teacher_email(PDO $db, int $teacherId, string $email): bool {
    $email = gv_clean_email($email);
    if (!gv_valid_email($email)) return false;
    $db->prepare('UPDATE teachers SET email = ? WHERE id = ?')->execute([$email, $teacherId]);
    return true;
}
function gv_school_list_add(PDO $db, string $text, int $defaultGrade = 0): array {
    $added = 0; $skipped = 0; $seen = 0;
    $st = $db->prepare('INSERT INTO school_roster (student_no, full_name, grade) VALUES (?,?,?) ON CONFLICT(student_no) DO UPDATE SET full_name = excluded.full_name, grade = excluded.grade');
    $db->exec('BEGIN IMMEDIATE');
    try {
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || ++$seen > 5000) continue;
            $parts = array_values(array_filter(array_map('trim', preg_split('/[,;\t]+/u', $line) ?: []), fn($p) => $p !== ''));
            $no = ''; $name = ''; $grade = $defaultGrade;
            foreach ($parts as $p) {
                $n = gv_norm_student_no($p);
                if ($no === '' && $n !== '') $no = $n;
                elseif (preg_match('/^[2-4]$/', $p)) $grade = (int)$p;
                elseif ($name === '') $name = gv_clean_full_name($p);
            }
            if ($no === '' || !gv_valid_full_name($name)) { $skipped++; continue; }
            $st->execute([$no, $name, in_array($grade, GV_GRADES, true) ? $grade : 0]);
            $added++;
        }
        $db->exec('COMMIT');
    } catch (Throwable $e) {
        try { $db->exec('ROLLBACK'); } catch (Throwable $e2) {}
        error_log('Grammar Vault gv_school_list_add: ' . $e->getMessage());
        return [0, $skipped];
    }
    return [$added, $skipped];
}
function gv_school_suggestions(PDO $db): array {
    return $db->query('SELECT no, name, grade FROM (
                           SELECT student_no AS no, full_name AS name, grade FROM students WHERE teacher_id = 0
                           UNION ALL
                           SELECT r.student_no, r.full_name, r.grade FROM school_roster r
                           WHERE NOT EXISTS (SELECT 1 FROM students s WHERE s.student_no = r.student_no)
                       ) ORDER BY name COLLATE NOCASE LIMIT 4000')->fetchAll(PDO::FETCH_ASSOC);
}
