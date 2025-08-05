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
    
    if (!isset($data['rollNumber'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Roll number required']);
        exit;
    }

    $data_dir = realpath(__DIR__ . '/../data');
    if ($data_dir === false || !is_dir($data_dir)) {
        throw new Exception('Data directory not found.');
    }
    $students_file = $data_dir . '/students.json';
    
    if (!file_exists($students_file)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Students data not found']);
        exit;
    }

    $students = json_decode(file_get_contents($students_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error reading students data');
    }

    // Find student by roll number
    foreach ($students as $student) {
        if ($student['rollNumber'] === $data['rollNumber']) {
            // Remove password before sending
            unset($student['password']);
            echo json_encode(['success' => true, 'student' => $student]);
            exit;
        }
    }

    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Student not found']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 