<?php
require_once __DIR__ . '/db.php';
require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/router.php';
use Dotenv\Dotenv;

use Slim\Factory\AppFactory;
use Slim\Middleware\BodyParsingMiddleware;
use App\Middleware\CorsMiddleware;

use App\Services\AdvisorService;
use App\repositories\AdvisorRepository;


// $app = AppFactory::create();
// $app->get('/hello', function ($request, $response) {
//     $data = ['message' => 'Hello, World!'];
//     $response->getBody()->write(json_encode($data));
//     return $response->withHeader('Content-Type', 'application/json');
// });

// $app->get('/hello/{name}', function ($request, $response, $args) {
//     $name = $args['name'];
//     $response->getBody()->write("Hello, $name!");
//     return $response;
// });


// $app->post('/person', function ($request, $response) {
//     $data = $request->getParsedBody();
//     $person = json_decode($request->getbody()->getContents(), true);
//     $name = $data['name'] ?? 'Guest';
//     $response->getBody()->write("Hello, ". $person['name'] . "!". " You are " . $person['age'] . " years old.");
//     return $response;
// });


// $app->get('/db-test', function ($request, $response) {
//     $pdo = getPDO();
//     $stmt = $pdo->query("SELECT * FROM test LIMIT 10");
//     $students = $stmt->fetchAll();
//     $response->getBody()->write(json_encode($students));
//     return $response->withHeader('Content-Type', 'application/json');
// });

$dotenv = Dotenv::createImmutable(__DIR__ . '/' ); // adjust path to your project root
$dotenv->load();

$app = AppFactory::create();

$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();
$app->add(new CorsMiddleware());
// $app->addErrorMiddleware(true, true, true);

$app->options('/api/assessments', function($request, $response){
    return $response;
});

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
});

$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

$routes = require __DIR__ . '/src/router.php';
$routes($app); 

$app->get('/api/getAllCourses', function($request, $response) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->query("SELECT * FROM course");
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode([
            "courses" => $courses
        ]));
        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "error" => "Database error",
            "details" => $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/api/assessments', function($request, $response, $args){
    // Get all query parameters
    $queryParams = $request->getQueryParams();
    
    // Extract course_id from query parameters
    $courseId = isset($queryParams['course_id']) ? $queryParams['course_id'] : null;
    
    $pdo = getPDO();
    
    // If course_id is provided, filter the results by course_id
    if ($courseId) {
        $stmt = $pdo->prepare("SELECT * FROM assessment_component WHERE course_id = ?");
        $stmt->execute([$courseId]);
    } else {
        // If no course_id is provided, fetch all assessments
        $stmt = $pdo->query("SELECT * FROM assessment_component");
    }

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($data) {
        $response->getBody()->write(json_encode(['assessment_component' => $data]));
    } else {
        $response->getBody()->write(json_encode(['error' => 'Assessment not found'])); 
    }

    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/assessments', function($request, $response){
    $data = json_decode($request->getBody()->getContents(), true);
    
    if(empty($data['course_id']) || empty($data['name']) || empty($data['type']) || empty($data['weight'])){
        $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
        return $response->withStatus(400)->withHeader('Content-Type','application/json');
    }
    
    // Insert assessment into assessment_component table
    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO assessment_component (course_id, name, weight, type) VALUES (?, ?, ?, ?)");
    $stmt->execute([$data['course_id'], $data['name'], $data['weight'], $data['type']]);
    
    // Get the inserted assessment_id
    $assessment_id = $pdo->lastInsertId();
    
    // Now, insert the record into the student_assessments table for each student in the course
    $stmt = $pdo->prepare("
        SELECT s.id
        FROM students s
        JOIN enrollments e ON s.id = e.student_id
        WHERE e.course_id = ?
    ");
    $stmt->execute([$data['course_id']]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($students)) {
        // Optional: Handle case where no students are enrolled in the course
        // You might want to log this or return a specific message.
        // For now, we'll continue, and the loop will just not run.
        error_log("No students found for course_id: " . $data['course_id'] . " when creating assessment.");
    }

    
    foreach ($students as $student) {
        // Insert a record into student_assessments for each student
        $stmt = $pdo->prepare("INSERT INTO student_assessments (student_id, assessment_id, mark) VALUES (?, ?, ?)");
        $stmt->execute([$student['id'], $assessment_id, 0]); // Default mark is 0
    }

    $response->getBody()->write(json_encode(['message' => 'Assessment and student records created successfully.']));
    return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
});


$app->put('/api/assessments/{id}', function($request, $response, $args){
    $data = json_decode($request->getBody()->getContents(), true);
    $pdo = getPDO();
    $stmt = $pdo->prepare("UPDATE assessment_component SET name = ?, weight = ?, type = ? WHERE id = ?");
    $stmt->execute([$data['name'], $data['weight'], $data['type'], $args['id']]);
    $response->getBody()->write(json_encode(['message' => 'Assessment successful updated']));
    return $response->withHeader('content-type', 'application/json')->withStatus(201);
});

$app->patch('/api/assessments/{id}', function ($request, $response, $args) {
    $data = json_decode($request->getBody()->getContents(), true);

    $pdo = getPDO();
    $stmt = $pdo->prepare("UPDATE assessment_component SET name = ?, weight = ?, type = ? WHERE id = ?");
    $stmt->execute([$data['name'], $data['weight'], $data['type'], $args['id']]);

    $response->getBody()->write(json_encode(['message' => 'Assessment successfully updated']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

// $app->post('/api/student-assessments', function($request, $response) {
//     $data = json_decode($request->getBody()->getContents(), true);

//     if (empty($data['student_id']) || empty($data['assessment_id']) || empty($data['mark'])) {
//         $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
//         return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
//     }

//     $pdo = getPDO();
//     $stmt = $pdo->prepare("INSERT INTO student_assessments (student_id, assessment_id, mark) VALUES (?, ?, ?)");
//     $stmt->execute([$data['student_id'], $data['assessment_id'], $data['mark']]);

//     $response->getBody()->write(json_encode(['message' => 'Marks added successfully']));
//     return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
// });

$app->delete('/api/assessments/{id}', function($request, $response, $args) {
    $assessmentId = $args['id'];

    $pdo = getPDO();
    $stmt = $pdo->prepare("DELETE FROM student_assessments WHERE assessment_id = ?");
    $stmt->execute([$assessmentId]);
    
    $stmt = $pdo->prepare("DELETE FROM assessment_component WHERE id = ?");
    $stmt->execute([$assessmentId]);

    $response->getBody()->write(json_encode(['message' => 'Assessment deleted successfully']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

// Add this new GET route handler in your index.php
$app->get('/api/student-records', function($request, $response){
    $queryParams = $request->getQueryParams();
    $courseId = $queryParams['course_id'] ?? null;
    $assessmentName = $queryParams['assessment_name'] ?? null;

    if (!$courseId || !$assessmentName) {
        $response->getBody()->write(json_encode(['error' => 'Missing course_id or assessment_name.']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $pdo = getPDO();

    try {
        // 1. Find the assessment_id based on course_id and assessment_name
        $stmt = $pdo->prepare("SELECT id FROM assessment_component WHERE course_id = ? AND name = ?");
        $stmt->execute([$courseId, $assessmentName]);
        $assessment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$assessment) {
            $response->getBody()->write(json_encode(['error' => 'Assessment not found for the given course and name.']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $assessmentId = $assessment['id'];

        // 2. Fetch student details and their marks for this specific assessment
        $stmt = $pdo->prepare("
            SELECT
                s.id AS student_id,
                s.name,
                s.matric_number,
                sa.mark
            FROM
                students s
            JOIN
                enrollments e ON s.id = e.student_id
            JOIN
                student_assessments sa ON s.id = sa.student_id
            WHERE
                e.course_id = ?
                AND sa.assessment_id = ?
            ORDER BY
                s.name
        ");
        $stmt->execute([$courseId, $assessmentId]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($students) {
            $response->getBody()->write(json_encode(['students' => $students]));
        } else {
            // Return an empty array if no student records found for this assessment/course,
            // rather than a "not found" error, as it's a valid state.
            $response->getBody()->write(json_encode(['students' => []]));
        }
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "error" => "Database error when fetching student records",
            "details" => $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// New PATCH route for batch updating student marks
$app->patch('/api/student-marks/batch-update', function($request, $response) {
    $data = json_decode($request->getBody()->getContents(), true);
    $courseId = $data['course_id'] ?? null;
    $assessmentName = $data['assessment_name'] ?? null;
    $marksToUpdate = $data['marks'] ?? []; // Array of { student_id, mark }

    if (!$courseId || !$assessmentName || !is_array($marksToUpdate)) {
        $response->getBody()->write(json_encode(['error' => 'Invalid request data. Missing course_id, assessment_name, or marks array.']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $pdo = getPDO();

    try {
        $pdo->beginTransaction();

        // 1. Find the assessment_id based on course_id and assessment_name
        $stmt = $pdo->prepare("SELECT id, weight FROM assessment_component WHERE course_id = ? AND name = ?");
        $stmt->execute([$courseId, $assessmentName]);
        $assessment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$assessment) {
            $pdo->rollBack();
            $response->getBody()->write(json_encode(['error' => 'Assessment not found for the given course and name.']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $assessmentId = $assessment['id'];
        $assessmentWeight = $assessment['weight'];

        // Prepare the update statement for student_assessments
        $updateStmt = $pdo->prepare("UPDATE student_assessments SET mark = ? WHERE student_id = ? AND assessment_id = ?");

        foreach ($marksToUpdate as $markData) {
            $studentId = $markData['student_id'] ?? null;
            $mark = $markData['mark'] ?? null;

            // Basic validation on the server side (more robust validation should also be client-side)
            if ($studentId === null || $mark === null || !is_numeric($mark) || $mark < 0 || $mark > $assessmentWeight) {
                $pdo->rollBack();
                $response->getBody()->write(json_encode([
                    'error' => 'Invalid mark value for student_id: ' . $studentId . '. Mark must be non-negative and not exceed assessment weight (' . $assessmentWeight . ').',
                    'provided_mark' => $mark
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $updateStmt->execute([$mark, $studentId, $assessmentId]);
        }

        $pdo->commit();
        $response->getBody()->write(json_encode(['message' => 'Marks updated successfully.']));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

    } catch (PDOException $e) {
        $pdo->rollBack();
        $response->getBody()->write(json_encode([
            "error" => "Database error during mark update",
            "details" => $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// New POST route for adding a student record
$app->post('/api/student-records/add', function($request, $response) {
    $data = json_decode($request->getBody()->getContents(), true);

    $name = $data['name'] ?? null;
    $matricNumber = $data['matric_number'] ?? null;
    $courseId = $data['course_id'] ?? null; // Current course
    $assessmentName = $data['assessment_name'] ?? null; // Current assessment
    $mark = $data['mark'] ?? null;

    error_log("Attempting to add record for Course ID: " . $courseId . ", Assessment Name: " . $assessmentName);

    // Server-side basic validation
    if (empty($name) || empty($matricNumber) || empty($courseId) || empty($assessmentName) || $mark === null || !is_numeric($mark)) {
        $response->getBody()->write(json_encode(['error' => 'Missing or invalid required fields (name, matric_number, mark, course_id, assessment_name).']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $pdo = getPDO();

    try {
        $pdo->beginTransaction();

        // 1. Check if student already exists by matric_number
        // Check if student already exists by matric_number
$stmt = $pdo->prepare("SELECT id FROM students WHERE matric_number = ?");
$stmt->execute([$matricNumber]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student) {
    // Check if a mark already exists for this student and assessment
    $stmt = $pdo->prepare("
        SELECT sa.id
        FROM student_assessments sa
        JOIN assessment_component ac ON sa.assessment_id = ac.id
        WHERE sa.student_id = ? AND ac.course_id = ? AND ac.name = ?
    ");
    $stmt->execute([$studentId, $courseId, $assessmentName]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        // This is where it should return 409 if a duplicate is found for this assessment
        $pdo->rollBack();
        $response->getBody()->write(json_encode(['error' => 'A record for this student in this assessment already exists. Use the "Edit" function to change marks.']));
        return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
    }
} else {
    // Student does not exist, insert into students table
    $stmt = $pdo->prepare("INSERT INTO students (name, matric_number) VALUES (?, ?)");
    $stmt->execute([$name, $matricNumber]);
    $studentId = $pdo->lastInsertId();
}

        // Validate mark against the specific assessment's weight
        $stmt = $pdo->prepare("SELECT id, weight FROM assessment_component WHERE course_id = ? AND name = ?");
        $stmt->execute([$courseId, $assessmentName]);
        $currentAssessment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentAssessment) {
            $pdo->rollBack();
            $response->getBody()->write(json_encode(['error' => 'Current assessment not found.']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $currentAssessmentId = $currentAssessment['id'];
        $assessmentWeight = $currentAssessment['weight'];

        if ($mark < 0 || $mark > $assessmentWeight) {
            $pdo->rollBack();
            $response->getBody()->write(json_encode([
                'error' => 'Invalid mark. Mark must be between 0 and ' . $assessmentWeight . '.',
                'provided_mark' => $mark
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // 2. Enroll student in ALL courses and initialize all student_assessments for them
        // Get all courses
        $stmtCourses = $pdo->query("SELECT course_id FROM course");
        $allCourses = $stmtCourses->fetchAll(PDO::FETCH_COLUMN);

        foreach ($allCourses as $cId) {
            // Check if student is already enrolled in this course to avoid duplicates
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND course_id = ?");
            $stmt->execute([$studentId, $cId]);
            if ($stmt->fetchColumn() == 0) {
                $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
                $stmt->execute([$studentId, $cId]);
            }

            // Get all assessment components for this course
            $stmtAssessments = $pdo->prepare("SELECT id FROM assessment_component WHERE course_id = ?");
            $stmtAssessments->execute([$cId]);
            $courseAssessments = $stmtAssessments->fetchAll(PDO::FETCH_COLUMN);

            foreach ($courseAssessments as $aId) {
                // Check if student_assessment record already exists for this student and assessment
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_assessments WHERE student_id = ? AND assessment_id = ?");
                $stmt->execute([$studentId, $aId]);
                if ($stmt->fetchColumn() == 0) {
                    $stmt = $pdo->prepare("INSERT INTO student_assessments (student_id, assessment_id, mark) VALUES (?, ?, ?)");
                    $stmt->execute([$studentId, $aId, 0]); // Default mark is 0
                }
            }
        }

        // 3. Update the specific mark for the current assessment component
        // This handles both new students and existing students who just got new assessments added
        $stmt = $pdo->prepare("UPDATE student_assessments SET mark = ? WHERE student_id = ? AND assessment_id = ?");
        $stmt->execute([$mark, $studentId, $currentAssessmentId]);

        $pdo->commit();
        $response->getBody()->write(json_encode(['message' => 'Student record added/updated successfully. Student enrolled in all courses and relevant assessments initialized.']));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

    } catch (PDOException $e) {
    $pdo->rollBack();
    // Log the full error message for server-side debugging
    error_log("PDOException in /api/student-records/add: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");

    // Return the actual PDO error message to the frontend for immediate diagnosis
    $response->getBody()->write(json_encode([
        "error" => "Database error during adding student record",
        "details" => $e->getMessage() // <-- **IMPORTANT: This will show the exact DB error in the frontend**
    ]));
    return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
}
});


// ADVISOR PART
$app->get('/api/advisor/courses', function ($request, $response) {
    $queryParams = $request->getQueryParams();
    $advisorUserId = $queryParams['advisor_user_id'] ?? null;

    if (!$advisorUserId) {
        $response->getBody()->write(json_encode([
            "error" => "Missing advisor_user_id in query parameters."
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    try {
        $pdo = getPDO();

        $stmt = $pdo->prepare("SELECT DISTINCT course_id FROM advisor_student WHERE advisor_user_id = ?");
        $stmt->execute([$advisorUserId]);
        $courseIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($courseIds)) {
            $response->getBody()->write(json_encode([
                "courses" => [],
                "message" => "No courses found for this advisor."
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        }

        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        $stmt = $pdo->prepare("SELECT * FROM course WHERE course_id IN ($placeholders)");
        $stmt->execute($courseIds);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode([
            "courses" => $courses
        ]));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "error" => "Database error",
            "details" => $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});



$app->get('/api/public/advisor/courses/{course_id}/students', function ($request, $response, $args) {
    $advisorId = $request->getQueryParams()['advisor_user_id'] ?? null;
    $courseId = $args['course_id'] ?? null;

    if (!$advisorId || !$courseId) {
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Missing advisor_user_id or course_id']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    try {
        $service = new AdvisorService();
        $students = $service->getAdviseesByCourse($advisorId, $courseId);

        $response->getBody()->write(json_encode([
            'success' => true,
            'advisees' => $students
        ]));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/api/public/advisor/courses/{course_id}/students/{student_id}/details', function ($request, $response, $args) {
    $courseId = $args['course_id'] ?? null;
    $studentId = $args['student_id'] ?? null;

    if (!$courseId || !$studentId) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Missing course_id or student_id'
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    try {
        $service = new \App\Services\AdvisorService();
        $detail = $service->getStudentDetail($studentId, $courseId);

        $response->getBody()->write(json_encode([
            'success' => true,
            'details' => $detail
        ]));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});


$app->get('/api/advisees/{advisor_id}', function ($request, $response, $args) {
    $advisorId = $args['advisor_id'];
    $service = new AdvisorService();
    $stats = $service->getAdviseeStats($advisorId);

    $response->getBody()->write(json_encode($stats));
    return $response->withHeader('Content-Type', 'application/json');
});


$app->get('/api/advisee-performance/{advisor_id}', function ($request, $response, $args) {
    $advisorId = $args['advisor_id'];
    $service = new AdvisorService();
    $data = $service->getAdvisorPerformance($advisorId);

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/advisor/course-wise-stats/{advisor_id}', function ($request, $response, $args) {
    $advisorId = $args['advisor_id'];
    $service = new AdvisorService();
    $data = $service->getCourseWiseStats($advisorId);
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

$app ->run();