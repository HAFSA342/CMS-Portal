<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data_dir = realpath(__DIR__ . '/../data');
    if ($data_dir === false || !is_dir($data_dir)) {
        // If data directory doesn't exist, there are no students.
        echo json_encode(['success' => true, 'students' => []]);
        exit;
    }

    $students_file = $data_dir . '/students.json';
    
    error_log("Loading students from: " . $students_file);
    
    // Check if students file exists
    if (!file_exists($students_file)) {
        error_log("Students file does not exist: " . $students_file);
        echo json_encode([
            'success' => true,
            'students' => [],
            'message' => 'No students enrolled yet'
        ]);
        exit;
    }

    // Check if file is readable
    if (!is_readable($students_file)) {
        throw new Exception('Students file is not readable: ' . $students_file);
    }

    // Read students data
    $file_content = file_get_contents($students_file);
    if ($file_content === false) {
        throw new Exception('Failed to read students data from file: ' . $students_file);
    }

    error_log("File content length: " . strlen($file_content));

    $students = json_decode($file_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error parsing students data: ' . json_last_error_msg());
    }

    error_log("Successfully loaded " . count($students) . " students");

    // Filter out sensitive information (passwords) before sending
    $safe_students = array_map(function($student) {
        return [
            'id' => $student['id'],
            'name' => $student['name'],
            'rollNumber' => $student['rollNumber'],
            'email' => $student['email'],
            'phone' => $student['phone'],
            'department' => $student['department'] ?? 'N/A',
            'semester' => $student['semester'] ?? 'N/A',
            'cgpa' => $student['cgpa'] ?? 0.0,
            'attendance' => $student['attendance'] ?? 0,
            'created_at' => $student['created_at'] ?? $student['enrollment_date'] ?? date('Y-m-d H:i:s'),
            'updated_at' => $student['updated_at'] ?? date('Y-m-d H:i:s'),
            'status' => $student['status'] ?? 'active'
        ];
    }, $students);

    // Sort by creation date (newest first)
    usort($safe_students, function($a, $b) {
        $dateA = $a['created_at'] ?? $a['enrollment_date'] ?? '';
        $dateB = $b['created_at'] ?? $b['enrollment_date'] ?? '';
        return strtotime($dateB) - strtotime($dateA);
    });

    error_log("Returning " . count($safe_students) . " safe students");

    echo json_encode([
        'success' => true,
        'students' => $safe_students,
        'total' => count($safe_students),
        'message' => count($safe_students) > 0 ? 'Students loaded successfully' : 'No students enrolled yet'
    ]);

} catch (Exception $e) {
    error_log("Error in get_students.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?> 