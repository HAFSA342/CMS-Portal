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
    
    if (!isset($data['rollNumber']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Roll number and password required']);
        exit;
    }

    $students_file = __DIR__ . '/../data/students.json';
    
    if (!file_exists($students_file)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No students registered']);
        exit;
    }

    $students = json_decode(file_get_contents($students_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error reading students data');
    }

    // Find student by roll number and verify password
    foreach ($students as $student) {
        if ($student['rollNumber'] === $data['rollNumber'] && password_verify($data['password'], $student['password'])) {
            // Remove password before sending
            unset($student['password']);
            echo json_encode(['success' => true, 'student' => $student]);
            exit;
        }
    }

    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid roll number or password']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 