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
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['student_roll']) || !isset($data['subject_id']) || 
        !isset($data['faculty_email']) || !isset($data['data_type']) || !isset($data['data'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Verify faculty exists and has permission for this subject
    $faculty_file = realpath(__DIR__ . '/../data/faculty.json');
    if (!file_exists($faculty_file)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Faculty data not found']);
        exit;
    }

    $faculty_list = json_decode(file_get_contents($faculty_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error reading faculty data');
    }

    // Find faculty and verify subject assignment
    $faculty = null;
    foreach ($faculty_list as $f) {
        if ($f['email'] === $data['faculty_email']) {
            if (!in_array($data['subject_id'], $f['assigned_subjects'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Faculty not authorized for this subject']);
                exit;
            }
            $faculty = $f;
            break;
        }
    }

    if (!$faculty) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Faculty not found']);
        exit;
    }

    // Get enrollments data
    $enrollments_file = realpath(__DIR__ . '/../data/enrollments.json');
    if (!file_exists($enrollments_file)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Enrollments data not found']);
        exit;
    }

    $enrollments_list = json_decode(file_get_contents($enrollments_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error reading enrollments data');
    }

    // Find the specific enrollment
    $enrollment_found = false;
    foreach ($enrollments_list as &$enrollment) {
        if ($enrollment['student_roll'] === $data['student_roll'] && 
            $enrollment['subject_id'] === $data['subject_id'] &&
            $enrollment['faculty_id'] === $faculty['id']) {
            
            $enrollment_found = true;
            
            // Update the appropriate data type
            switch ($data['data_type']) {
                case 'attendance':
                    if (isset($data['data']['total_classes'])) {
                        $enrollment['attendance']['total_classes'] = $data['data']['total_classes'];
                    }
                    if (isset($data['data']['attended_classes'])) {
                        $enrollment['attendance']['attended_classes'] = $data['data']['attended_classes'];
                    }
                    if (isset($data['data']['percentage'])) {
                        $enrollment['attendance']['percentage'] = $data['data']['percentage'];
                    }
                    break;
                    
                case 'marks':
                    if (isset($data['data']['midterm'])) {
                        $enrollment['marks']['midterm'] = $data['data']['midterm'];
                    }
                    if (isset($data['data']['final'])) {
                        $enrollment['marks']['final'] = $data['data']['final'];
                    }
                    if (isset($data['data']['assignments'])) {
                        $enrollment['marks']['assignments'] = $data['data']['assignments'];
                    }
                    if (isset($data['data']['total'])) {
                        $enrollment['marks']['total'] = $data['data']['total'];
                    }
                    if (isset($data['data']['grade'])) {
                        $enrollment['marks']['grade'] = $data['data']['grade'];
                    }
                    break;
                    
                case 'clos':
                    if (isset($data['data']['clo1'])) {
                        $enrollment['clos']['clo1'] = $data['data']['clo1'];
                    }
                    if (isset($data['data']['clo2'])) {
                        $enrollment['clos']['clo2'] = $data['data']['clo2'];
                    }
                    if (isset($data['data']['clo3'])) {
                        $enrollment['clos']['clo3'] = $data['data']['clo3'];
                    }
                    if (isset($data['data']['clo4'])) {
                        $enrollment['clos']['clo4'] = $data['data']['clo4'];
                    }
                    break;
                    
                case 'plos':
                    if (isset($data['data']['plo1'])) {
                        $enrollment['plos']['plo1'] = $data['data']['plo1'];
                    }
                    if (isset($data['data']['plo2'])) {
                        $enrollment['plos']['plo2'] = $data['data']['plo2'];
                    }
                    if (isset($data['data']['plo3'])) {
                        $enrollment['plos']['plo3'] = $data['data']['plo3'];
                    }
                    if (isset($data['data']['plo4'])) {
                        $enrollment['plos']['plo4'] = $data['data']['plo4'];
                    }
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid data type']);
                    exit;
            }
            break;
        }
    }

    if (!$enrollment_found) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Enrollment not found']);
        exit;
    }

    // Write back to file
    if (file_put_contents($enrollments_file, json_encode($enrollments_list, JSON_PRETTY_PRINT)) === false) {
        throw new Exception('Failed to save enrollment data');
    }

    echo json_encode([
        'success' => true,
        'message' => ucfirst($data['data_type']) . ' updated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
