<?php
function gv_mail_config(): array {
    $d = ['method' => 'auto', 'host' => '', 'port' => 587, 'secure' => 'tls', 'username' => '', 'password' => '', 'from_email' => '', 'from_name' => 'Grammar Vault'];
    $f = __DIR__ . '/mail_config.php';
    if (is_file($f)) { $c = include $f; if (is_array($c)) $d = array_merge($d, $c); }
    return $d;
}
function gv_mail_ready(): bool {
    $c = gv_mail_config();
    if ($c['method'] === 'smtp' || ($c['method'] === 'auto' && $c['host'] !== '')) {
        return $c['host'] !== '' && $c['from_email'] !== '';
    }
    return function_exists('mail') && $c['from_email'] !== '';
}
function gv_smtp_read($fp): array {
    $code = ''; $text = '';
    while (($line = fgets($fp, 1024)) !== false) {
        $text .= $line;
        $code = substr($line, 0, 3);
        if (strlen($line) < 4 || $line[3] !== '-') break;
    }
    return [(int)$code, $text];
}
function gv_smtp_cmd($fp, string $cmd, array $ok): bool {
    fwrite($fp, $cmd . "\r\n");
    [$code] = gv_smtp_read($fp);
    return in_array($code, $ok, true);
}
function gv_send_mail(string $to, string $subject, string $body): bool {
    $c = gv_mail_config();
    if (!gv_valid_email($to) || $c['from_email'] === '' || !gv_valid_email(strtolower($c['from_email']))) return false;
    $fromName = preg_replace('/[\r\n"<>]/', '', (string)$c['from_name']);
    $subject = preg_replace('/[\r\n]/', ' ', $subject);
    $encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = "From: \"$fromName\" <{$c['from_email']}>\r\n"
             . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";
    $useSmtp = $c['method'] === 'smtp' || ($c['method'] === 'auto' && $c['host'] !== '');
    if (!$useSmtp) {
        return function_exists('mail') && @mail($to, $encSubject, str_replace("\n.", "\n..", $body), $headers);
    }
    $secure = strtolower((string)$c['secure']);
    $port = (int)$c['port'];
    $fp = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : '') . $c['host'] . ':' . $port, $en, $es, 15);
    if (!$fp) return false;
    stream_set_timeout($fp, 15);
    try {
        [$code] = gv_smtp_read($fp);
        if ($code !== 220) return false;
        $me = 'localhost';
        if (!gv_smtp_cmd($fp, "EHLO $me", [250])) return false;
        if ($secure === 'tls') {
            if (!gv_smtp_cmd($fp, 'STARTTLS', [220])) return false;
            if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) return false;
            if (!gv_smtp_cmd($fp, "EHLO $me", [250])) return false;
        }
        if ($c['username'] !== '') {
            if (!gv_smtp_cmd($fp, 'AUTH LOGIN', [334])) return false;
            if (!gv_smtp_cmd($fp, base64_encode($c['username']), [334])) return false;
            if (!gv_smtp_cmd($fp, base64_encode($c['password']), [235])) return false;
        }
        if (!gv_smtp_cmd($fp, 'MAIL FROM:<' . $c['from_email'] . '>', [250])) return false;
        if (!gv_smtp_cmd($fp, 'RCPT TO:<' . $to . '>', [250, 251])) return false;
        if (!gv_smtp_cmd($fp, 'DATA', [354])) return false;
        $msg = "To: <$to>\r\nSubject: $encSubject\r\nDate: " . date('r') . "\r\n" . $headers . "\r\n"
             . preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $body));
        $msg = str_replace("\n", "\r\n", str_replace("\r\n", "\n", $msg));
        fwrite($fp, $msg . "\r\n.\r\n");
        [$code] = gv_smtp_read($fp);
        $ok = $code === 250;
        @gv_smtp_cmd($fp, 'QUIT', [221]);
        return $ok;
    } finally {
        @fclose($fp);
    }
}
