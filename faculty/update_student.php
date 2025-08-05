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

    // Validate required fields
    $required_fields = ['id', 'name', 'rollNumber', 'email', 'phone', 'department', 'semester'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
        ]);
        exit;
    }

    // Validate email format
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // Validate phone number
    if (!preg_match('/^\+?[0-9\s\-\(\)]{10,15}$/', $input['phone'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
        exit;
    }

    // Validate semester
    if (!is_numeric($input['semester']) || $input['semester'] < 1 || $input['semester'] > 8) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Semester must be between 1 and 8']);
        exit;
    }

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

    // Find and update the student
    $student_found = false;
    foreach ($students as $key => $student) {
        if ($student['id'] === $input['id']) {
            // Check if roll number or email already exists for other students
            foreach ($students as $other_student) {
                if ($other_student['id'] !== $input['id']) {
                    if ($other_student['rollNumber'] === $input['rollNumber']) {
                        http_response_code(409);
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Roll number already exists for another student'
                        ]);
                        exit;
                    }
                    if ($other_student['email'] === $input['email']) {
                        http_response_code(409);
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Email already exists for another student'
                        ]);
                        exit;
                    }
                }
            }

            // Update student data
            $students[$key]['name'] = trim($input['name']);
            $students[$key]['rollNumber'] = trim($input['rollNumber']);
            $students[$key]['email'] = trim($input['email']);
            $students[$key]['phone'] = trim($input['phone']);
            $students[$key]['department'] = trim($input['department']);
            $students[$key]['semester'] = (int)$input['semester'];
            $students[$key]['updated_at'] = date('Y-m-d H:i:s');

            $student_found = true;
            break;
        }
    }

    if (!$student_found) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }

    // Save updated data
    $result = file_put_contents($students_file, json_encode($students, JSON_PRETTY_PRINT));
    if ($result === false) {
        throw new Exception('Failed to save updated student data');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Student updated successfully!'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?> 