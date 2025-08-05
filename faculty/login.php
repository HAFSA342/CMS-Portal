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

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email and password required']);
        exit;
    }
    $faculty_file = __DIR__ . '/../data/faculty.json';
    if (!file_exists($faculty_file)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No faculty registered']);
        exit;
    }
    $faculty_list = json_decode(file_get_contents($faculty_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error reading faculty data');
    }
    foreach ($faculty_list as $faculty) {
        if ($faculty['email'] === $data['email'] && $faculty['password'] === $data['password']) {
            // Remove password before sending
            unset($faculty['password']);
            echo json_encode(['success' => true, 'faculty' => $faculty]);
            exit;
        }
    }
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 