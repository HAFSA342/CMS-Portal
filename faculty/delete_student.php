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
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id']) || empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Student ID is required']);
        exit;
    }

    $studentId = $input['id'];
    $data_dir = realpath(__DIR__ . '/../data');

    if ($data_dir === false || !is_dir($data_dir)) {
        throw new Exception('Data directory not found.');
    }
    $students_file = $data_dir . '/students.json';

    if (!file_exists($students_file) || !is_writable($students_file)) {
        throw new Exception('Student data file is not accessible or not writable.');
    }

    $students = json_decode(file_get_contents($students_file), true);
    if (!is_array($students)) {
        throw new Exception('Error reading students data');
    }

    // Find and remove the student
    $student_found = false;
    $deleted_student = null;
    
    foreach ($students as $key => $student) {
        if ($student['id'] === $studentId) {
            $deleted_student = $student;
            unset($students[$key]);
            $student_found = true;
            break;
        }
    }

    if (!$student_found) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }

    // Re-index array after deletion
    $students = array_values($students);

    // Save updated data
    $result = file_put_contents($students_file, json_encode($students, JSON_PRETTY_PRINT));
    if ($result === false) {
        throw new Exception('Failed to save updated students data');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Student deleted successfully!',
        'deleted_student' => [
            'name' => $deleted_student['name'],
            'rollNumber' => $deleted_student['rollNumber']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?> 