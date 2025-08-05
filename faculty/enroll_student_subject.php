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
    
    if (!isset($data['student_roll']) || !isset($data['subject_id']) || !isset($data['faculty_email'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Student roll number, subject ID, and faculty email required']);
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

    // Verify student exists
    $students_file = realpath(__DIR__ . '/../data/students.json');
    if (!file_exists($students_file)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Students data not found']);
        exit;
    }

    $students_list = json_decode(file_get_contents($students_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error reading students data');
    }

    $student_exists = false;
    foreach ($students_list as $student) {
        if ($student['rollNumber'] === $data['student_roll']) {
            $student_exists = true;
            break;
        }
    }

    if (!$student_exists) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }

    // Verify subject exists
    $subjects_file = realpath(__DIR__ . '/../data/subjects.json');
    if (!file_exists($subjects_file)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Subjects data not found']);
        exit;
    }

    $subjects_list = json_decode(file_get_contents($subjects_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error reading subjects data');
    }

    $subject_exists = false;
    foreach ($subjects_list as $subject) {
        if ($subject['id'] === $data['subject_id']) {
            $subject_exists = true;
            break;
        }
    }

    if (!$subject_exists) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Subject not found']);
        exit;
    }

    // Check if enrollment already exists
    $enrollments_file = realpath(__DIR__ . '/../data/enrollments.json');
    if (!file_exists($enrollments_file)) {
        $enrollments_list = [];
    } else {
        $enrollments_list = json_decode(file_get_contents($enrollments_file), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error reading enrollments data');
        }
    }

    // Check for existing enrollment
    foreach ($enrollments_list as $enrollment) {
        if ($enrollment['student_roll'] === $data['student_roll'] && 
            $enrollment['subject_id'] === $data['subject_id']) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Student already enrolled in this subject']);
            exit;
        }
    }

    // Create new enrollment
    $new_enrollment = [
        'student_roll' => $data['student_roll'],
        'subject_id' => $data['subject_id'],
        'faculty_id' => $faculty['id'],
        'enrollment_date' => date('Y-m-d'),
        'attendance' => [
            'total_classes' => 0,
            'attended_classes' => 0,
            'percentage' => 0
        ],
        'marks' => [
            'midterm' => 0,
            'final' => 0,
            'assignments' => 0,
            'total' => 0,
            'grade' => 'N/A'
        ],
        'clos' => [
            'clo1' => 0,
            'clo2' => 0,
            'clo3' => 0,
            'clo4' => 0
        ],
        'plos' => [
            'plo1' => 0,
            'plo2' => 0,
            'plo3' => 0,
            'plo4' => 0
        ]
    ];

    $enrollments_list[] = $new_enrollment;

    // Write back to file
    if (file_put_contents($enrollments_file, json_encode($enrollments_list, JSON_PRETTY_PRINT)) === false) {
        throw new Exception('Failed to save enrollment data');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Student enrolled successfully',
        'enrollment' => $new_enrollment
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 