<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_GET['email'])) {
    echo json_encode(['success' => false, 'message' => 'Email required']);
    exit;
}

$email = $_GET['email'];
$faculty_file = realpath(__DIR__ . '/../data/faculty.json');
if (!$faculty_file || !file_exists($faculty_file)) {
    echo json_encode(['success' => false, 'message' => 'Faculty data not found']);
    exit;
}

$faculty_list = json_decode(file_get_contents($faculty_file), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Error reading faculty data']);
    exit;
}

foreach ($faculty_list as $faculty) {
    if (isset($faculty['email']) && strtolower($faculty['email']) === strtolower($email)) {
        echo json_encode([
            'success' => true,
            'faculty' => $faculty,
            'assigned_subjects' => isset($faculty['assigned_subjects']) ? $faculty['assigned_subjects'] : []
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Faculty not found']);
?> 