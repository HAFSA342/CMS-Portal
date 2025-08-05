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
    
    if (!isset($data['email'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email required']);
        exit;
    }

    $faculty_file = __DIR__ . '/../data/faculty_data.json';
    
    if (!file_exists($faculty_file)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Faculty data not found']);
        exit;
    }

    $faculty_list = json_decode(file_get_contents($faculty_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error reading faculty data');
    }

    // Find faculty by email
    foreach ($faculty_list as $faculty) {
        if ($faculty['email'] === $data['email']) {
            // Remove password before sending
            unset($faculty['password']);
            echo json_encode(['success' => true, 'faculty' => $faculty]);
            exit;
        }
    }

    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Faculty not found']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 