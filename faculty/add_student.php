<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get POST data
    $input = file_get_contents('php://input');
    if ($input === false) {
        throw new Exception('Failed to read input data');
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    // Log the received data for debugging
    error_log("Received data: " . print_r($data, true));

    // Validate required fields
    $required_fields = ['name', 'rollNumber', 'email', 'phone', 'password', 'department', 'facultyEmail'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
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
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // Validate phone number (basic validation)
    if (!preg_match('/^\+?[0-9\s\-\(\)]{10,15}$/', $data['phone'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
        exit;
    }

    // Create student data structure
    $student = [
        'id' => uniqid(),
        'name' => trim($data['name']),
        'rollNumber' => trim($data['rollNumber']),
        'email' => trim($data['email']),
        'phone' => trim($data['phone']),
        'password' => password_hash($data['password'], PASSWORD_DEFAULT), // Hash password
        'department' => trim($data['department']),
        'facultyEmail' => trim($data['facultyEmail']),
        'semester' => 1,
        'cgpa' => 0.0,
        'attendance' => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'status' => 'active',
        'subjects' => []
    ];

    // File path for storing students data
    $data_dir = realpath(__DIR__ . '/../data');
    if ($data_dir === false) {
        // If the path does not exist, try to create it
        $data_dir_to_create = __DIR__ . '/../data';
        error_log("Data directory does not exist. Attempting to create: " . $data_dir_to_create);
        if (!mkdir($data_dir_to_create, 0777, true)) {
            throw new Exception('Failed to create data directory: ' . $data_dir_to_create);
        }
        $data_dir = realpath($data_dir_to_create);
    }
    
    $students_file = $data_dir . '/students.json';

    error_log("Normalized Data directory: " . $data_dir);
    error_log("Normalized Students file: " . $students_file);

    // Create data directory if it doesn't exist (double check after realpath)
    if (!is_dir($data_dir)) {
        throw new Exception('Data directory could not be confirmed or created: ' . $data_dir);
    }

    // Check if directory is writable
    if (!is_writable($data_dir)) {
        throw new Exception('Data directory is not writable: ' . $data_dir);
    }

    // Load existing students
    $students = [];
    if (file_exists($students_file)) {
        $file_content = file_get_contents($students_file);
        if ($file_content !== false) {
            $students = json_decode($file_content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // If JSON is invalid, start with empty array
                $students = [];
                error_log("Invalid JSON in students file, starting with empty array");
            }
        }
    }

    // Ensure students is an array
    if (!is_array($students)) {
        $students = [];
    }

    // Check for duplicate roll number
    foreach ($students as $existing_student) {
        if (isset($existing_student['rollNumber']) && strtolower($existing_student['rollNumber']) === strtolower($student['rollNumber'])) {
            http_response_code(409);
            echo json_encode([
                'success' => false, 
                'message' => 'A student with this roll number already exists.'
            ]);
            exit;
        }
    }

    // Add new student
    $students[] = $student;

    // Save back to file
    $json_data = json_encode($students, JSON_PRETTY_PRINT);
    if ($json_data === false) {
        throw new Exception('Failed to encode student data to JSON: ' . json_last_error_msg());
    }

    $result = file_put_contents($students_file, $json_data);
    if ($result === false) {
        throw new Exception('Failed to save student data to file: ' . $students_file);
    }

    error_log("Student added successfully: " . $student['id']);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Student enrolled successfully!',
        'student' => [
            'id' => $student['id'],
            'name' => $student['name'],
            'rollNumber' => $student['rollNumber'],
            'email' => $student['email'],
            'department' => $student['department']
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in add_student.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?> 