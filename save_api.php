<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'samesite' => 'Lax',
]);
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST required']);
    exit;
}
const MAX_SLOTS = 3;
const MAX_BODY_BYTES = 60000;
$raw = file_get_contents('php://input');
if (strlen($raw) > MAX_BODY_BYTES) {
    http_response_code(413);
    echo json_encode(['success' => false, 'error' => 'Request too large']);
    exit;
}
$input = json_decode($raw, true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
    exit;
}
if (!isset($_SESSION['gv_saves']) || !is_array($_SESSION['gv_saves'])) {
    $_SESSION['gv_saves'] = array_fill(0, MAX_SLOTS, null);
}
$action = $input['action'] ?? '';
function valid_slot($slot) {
    return is_int($slot) && $slot >= 0 && $slot < MAX_SLOTS;
}
switch ($action) {
    case 'get_all':
        echo json_encode([
            'success' => true,
            'slots' => $_SESSION['gv_saves'],
        ]);
        break;
    case 'save':
        $slot = isset($input['slot']) ? intval($input['slot']) : -1;
        if (!valid_slot($slot)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid slot']);
            break;
        }
        $data = $input['data'] ?? null;
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid save data']);
            break;
        }
        $_SESSION['gv_saves'][$slot] = $data;
        echo json_encode(['success' => true]);
        break;
    case 'delete':
        $slot = isset($input['slot']) ? intval($input['slot']) : -1;
        if (!valid_slot($slot)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid slot']);
            break;
        }
        $_SESSION['gv_saves'][$slot] = null;
        echo json_encode(['success' => true]);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
