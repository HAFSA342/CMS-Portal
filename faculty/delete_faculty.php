<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email required']);
    exit;
}

$faculty_file = __DIR__ . '/../data/faculty.json';
if (!file_exists($faculty_file)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Faculty data file not found']);
    exit;
}

$faculty_list = json_decode(file_get_contents($faculty_file), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error reading faculty data']);
    exit;
}

$new_list = array_filter($faculty_list, function($f) use ($data) {
    return $f['email'] !== $data['email'];
});

file_put_contents($faculty_file, json_encode(array_values($new_list), JSON_PRETTY_PRINT));
echo json_encode(['success' => true, 'message' => 'Faculty deleted']); 