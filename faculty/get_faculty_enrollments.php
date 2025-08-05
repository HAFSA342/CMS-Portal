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

try {
    $faculty_email = $_GET['email'] ?? null;
    if (!$faculty_email) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Faculty email required']);
        exit;
    }

    // Get faculty data
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

    // Find faculty
    $faculty = null;
    foreach ($faculty_list as $f) {
        if (isset($f['email']) && strtolower($f['email']) === strtolower($faculty_email)) {
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
        echo json_encode([
            'success' => true,
            'faculty' => $faculty,
            'enrollments' => []
        ]);
        exit;
    }

    $enrollments_list = json_decode(file_get_contents($enrollments_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error reading enrollments data');
    }

    // Filter enrollments for this faculty's subjects
    $faculty_enrollments = [];
    foreach ($enrollments_list as $enrollment) {
        if (isset($enrollment['faculty_id']) && $enrollment['faculty_id'] === $faculty['id']) {
            // Attach student and subject info
            $student = null;
            $subject = null;

            // Find student info
            foreach ($faculty_list as $f) {
                if ($f['id'] === $enrollment['faculty_id']) {
                    $student = $f;
                    break;
                }
            }

            // Find subject info
            foreach ($faculty_list as $f) {
                if ($f['id'] === $enrollment['subject_id']) {
                    $subject = $f;
                    break;
                }
            }

            $faculty_enrollments[] = [
                'enrollment' => $enrollment,
                'student' => $student,
                'subject' => $subject
            ];
        }
    }

    // Get students data for additional information
    $students_file = realpath(__DIR__ . '/../data/students.json');
    $students_list = [];
    if (file_exists($students_file)) {
        $students_list = json_decode(file_get_contents($students_file), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $students_list = [];
        }
    }

    // Get subjects data for additional information
    $subjects_file = realpath(__DIR__ . '/../data/subjects.json');
    $subjects_list = [];
    if (file_exists($subjects_file)) {
        $subjects_list = json_decode(file_get_contents($subjects_file), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $subjects_list = [];
        }
    }

    // Enrich enrollment data with student and subject information
    $enriched_enrollments = [];
    foreach ($faculty_enrollments as $enrollment) {
        $student_info = null;
        $subject_info = null;

        // Find student info
        foreach ($students_list as $student) {
            if ($student['rollNumber'] === $enrollment['student_roll']) {
                $student_info = $student;
                break;
            }
        }

        // Find subject info
        foreach ($subjects_list as $subject) {
            if ($subject['id'] === $enrollment['subject_id']) {
                $subject_info = $subject;
                break;
            }
        }

        $enriched_enrollments[] = [
            'enrollment' => $enrollment,
            'student' => $student_info,
            'subject' => $subject_info
        ];
    }

    echo json_encode([
        'success' => true,
        'faculty' => $faculty,
        'enrollments' => $enriched_enrollments
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 