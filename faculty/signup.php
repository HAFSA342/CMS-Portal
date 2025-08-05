<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
    $data = json_decode($input, true);

    // --- Start Corrected Validation ---
    $missing_fields = [];
    $text_fields = ['name', 'email', 'password', 'department', 'designation', 'phone'];

    foreach ($text_fields as $field) {
        if (empty($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '') {
            $missing_fields[] = $field;
        }
    }

    if (empty($data['assigned_subjects']) || !is_array($data['assigned_subjects'])) {
        $missing_fields[] = 'assigned_subjects';
    }
    // --- End Corrected Validation ---

    if (!empty($missing_fields)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Missing or invalid fields: ' . implode(', ', $missing_fields)
        ]);
        exit;
    }

    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // Validate password strength (minimum 6 characters)
    if (strlen($data['password']) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
        exit;
    }

    // Validate phone number (basic validation)
    if (!preg_match('/^\+?[0-9\s\-\(\)]{10,15}$/', $data['phone'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
        exit;
    }

    // Create faculty data structure
    $faculty = [
        'id' => 'FAC' . time() . rand(100, 999), // Generate unique ID with FAC prefix
        'name' => trim($data['name']),
        'email' => trim($data['email']),
        'password' => password_hash($data['password'], PASSWORD_DEFAULT), // Hash password
        'department' => trim($data['department']),
        'role' => trim($data['designation']), // Use designation as role
        'assigned_subjects' => $data['assigned_subjects'], // Add assigned subjects
        'phone' => trim($data['phone']),
        'registration_date' => date('Y-m-d H:i:s'),
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ];

    // File path for storing faculty data
    $data_dir = realpath(__DIR__ . '/../data');
    if ($data_dir === false) {
        // If the path does not exist, try to create it
        $data_dir_to_create = __DIR__ . '/../data';
        if (!mkdir($data_dir_to_create, 0777, true)) {
            throw new Exception('Failed to create data directory.');
        }
        $data_dir = realpath($data_dir_to_create);
    }
    $faculty_file = $data_dir . '/faculty.json';

    // Explicitly set permissions before writing
    if (file_exists($data_dir)) {
        @chmod($data_dir, 0777);
    }
    if (file_exists($faculty_file)) {
        @chmod($faculty_file, 0777);
    }

    if (!is_writable($data_dir)) {
        throw new Exception('Data directory is not writable. Please check server permissions for the folder: ' . $data_dir);
    }
    if (file_exists($faculty_file) && !is_writable($faculty_file)) {
        throw new Exception('Faculty file exists but is not writable. Please check server permissions for the file: ' . $faculty_file);
    }
    
    // Load existing faculty
    $faculty_list = [];
    if (file_exists($faculty_file)) {
        $file_content = file_get_contents($faculty_file);
        if ($file_content !== false) {
            $faculty_list = json_decode($file_content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error reading existing faculty data');
            }
        }
    }

    // Check if email already exists
    foreach ($faculty_list as $existing_faculty) {
        if ($existing_faculty['email'] === $faculty['email']) {
            http_response_code(409);
            echo json_encode([
                'success' => false, 
                'message' => 'Faculty with this email already exists'
            ]);
            exit;
        }
    }

    // Add new faculty
    $faculty_list[] = $faculty;

    // Save back to file
    $result = file_put_contents($faculty_file, json_encode($faculty_list, JSON_PRETTY_PRINT));
    if ($result === false) {
        throw new Exception('Failed to save faculty data');
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Faculty account created successfully! You can now login.',
        'faculty' => [
            'id' => $faculty['id'],
            'name' => $faculty['name'],
            'email' => $faculty['email'],
            'department' => $faculty['department'],
            'designation' => $faculty['designation']
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