<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
if (preg_match('#(^|/)data(/|$)#i', $path) || preg_match('#\.(sqlite|db|sqlite-wal|sqlite-shm)$#i', $path)) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}
return false;
