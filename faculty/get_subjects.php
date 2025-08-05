<?php
header('Content-Type: application/json');

try {
    $data_dir = realpath(__DIR__ . '/../data');
    if ($data_dir === false || !is_dir($data_dir)) {
        throw new Exception('Data directory not found.');
    }
    
    $subjects_file = $data_dir . '/subjects.json';

    if (!file_exists($subjects_file)) {
        throw new Exception('Subjects data file not found.');
    }

    $subjects_data = file_get_contents($subjects_file);
    $subjects = json_decode($subjects_data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error decoding subjects JSON data.');
    }

    echo json_encode(['success' => true, 'subjects' => $subjects]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 