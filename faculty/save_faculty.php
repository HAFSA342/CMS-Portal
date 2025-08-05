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

$faculty_file = realpath(__DIR__ . '/../data/faculty.json');
if (!$faculty_file) {
    $faculty_file = __DIR__ . '/../data/faculty.json';
}

$faculty_list = file_exists($faculty_file) ? json_decode(file_get_contents($faculty_file), true) : [];
if (json_last_error() !== JSON_ERROR_NONE) {
    $faculty_list = [];
}

// Check for duplicate email
foreach ($faculty_list as $faculty) {
    if (isset($faculty['email']) && strtolower($faculty['email']) === strtolower($data['email'])) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'A faculty with this email already exists.']);
        exit;
    }
}

// Assign a new ID
$data['id'] = uniqid();
if (!isset($data['assigned_subjects'])) {
    $data['assigned_subjects'] = [];
}
$faculty_list[] = $data;
$result = file_put_contents($faculty_file, json_encode($faculty_list, JSON_PRETTY_PRINT));
if ($result === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save faculty data. Check file permissions.']);
    exit;
}
echo json_encode(['success' => true, 'message' => 'Faculty saved']); 