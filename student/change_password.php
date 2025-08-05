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
    
    if (!isset($data['rollNumber']) || !isset($data['currentPassword']) || !isset($data['newPassword'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Roll number, current password, and new password required']);
        exit;
    }

    // Validate new password length
    if (strlen($data['newPassword']) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long']);
        exit;
    }

    $students_file = __DIR__ . '/../data/students.json';
    
    if (!file_exists($students_file)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Students data not found']);
        exit;
    }

    $students = json_decode(file_get_contents($students_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error reading students data');
    }

    // Find student and verify current password
    foreach ($students as $key => $student) {
        if ($student['rollNumber'] === $data['rollNumber']) {
            if (password_verify($data['currentPassword'], $student['password'])) {
                // Update password
                $students[$key]['password'] = password_hash($data['newPassword'], PASSWORD_DEFAULT);
                
                // Save back to file
                $result = file_put_contents($students_file, json_encode($students, JSON_PRETTY_PRINT));
                if ($result === false) {
                    throw new Exception('Failed to save updated password');
                }
                
                echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
                exit;
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit;
            }
        }
    }

    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Student not found']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 