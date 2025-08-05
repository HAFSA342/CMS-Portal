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

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$student_file = realpath(__DIR__ . '/../data/students.json');
if (!$student_file) {
    $student_file = __DIR__ . '/../data/students.json';
}

$student_list = file_exists($student_file) ? json_decode(file_get_contents($student_file), true) : [];
if (json_last_error() !== JSON_ERROR_NONE) {
    $student_list = [];
}

// Assign a new ID and hash the password
$data['id'] = uniqid();
$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
$student_list[] = $data;
file_put_contents($student_file, json_encode($student_list, JSON_PRETTY_PRINT));
echo json_encode(['success' => true, 'message' => 'Student saved']); 