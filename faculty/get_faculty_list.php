<?php
header('Content-Type: application/json');
$faculty_file = __DIR__ . '/../data/faculty.json';
if (!file_exists($faculty_file)) {
    echo json_encode([]);
    exit;
}
$faculty_list = json_decode(file_get_contents($faculty_file), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([]);
    exit;
}
echo json_encode($faculty_list); 