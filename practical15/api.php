<?php
header('Content-Type: application/json');
session_start();
require_once '../practical9/db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Check database connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($action === 'toggle_event_reg') {
    $event_id = intval($_POST['event_id'] ?? 0);
    $student_id = intval($_POST['student_id'] ?? ($_SESSION['user_id'] ?? 1));

    if (!$event_id || !$student_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }

    // Check if currently registered
    $check_stmt = $conn->prepare("SELECT reg_id FROM registrations WHERE student_id = ? AND event_id = ?");
    $check_stmt->bind_param("ii", $student_id, $event_id);
    $check_stmt->execute();
    $res = $check_stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        // Unregister
        $del_stmt = $conn->prepare("DELETE FROM registrations WHERE reg_id = ?");
        $del_stmt->bind_param("i", $row['reg_id']);
        $del_stmt->execute();
        $del_stmt->close();
        $registered = false;
        $act_text = "Cancelled registration for Event #$event_id";
    } else {
        // Register
        $ins_stmt = $conn->prepare("INSERT INTO registrations (student_id, event_id) VALUES (?, ?)");
        $ins_stmt->bind_param("ii", $student_id, $event_id);
        $ins_stmt->execute();
        $ins_stmt->close();
        $registered = true;
        $act_text = "Registered for Event #$event_id";
    }
    $check_stmt->close();

    // Log to audit trail
    $log_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)");
    $uid = $_SESSION['user_id'] ?? 1;
    $log_stmt->bind_param("iss", $uid, $act_text, $act_text);
    $log_stmt->execute();
    $log_stmt->close();

    // Fetch updated total registrations for this event
    $count_res = $conn->query("SELECT COUNT(*) as c FROM registrations WHERE event_id = $event_id");
    $total_reg = $count_res->fetch_assoc()['c'] ?? 0;

    // Fetch overall total registrations
    $overall_res = $conn->query("SELECT COUNT(*) as c FROM registrations");
    $overall_reg_count = $overall_res->fetch_assoc()['c'] ?? 0;

    echo json_encode([
        'success' => true,
        'registered' => $registered,
        'total_reg' => $total_reg,
        'overall_reg_count' => $overall_reg_count
    ]);
    exit;
}

if ($action === 'toggle_student_status') {
    $student_id = intval($_POST['student_id'] ?? 0);
    if (!$student_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
        exit;
    }

    $st_res = $conn->prepare("SELECT status, name FROM students WHERE student_id = ?");
    $st_res->bind_param("i", $student_id);
    $st_res->execute();
    $student = $st_res->get_result()->fetch_assoc();
    $st_res->close();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }

    $new_status = ($student['status'] === 'active') ? 'inactive' : 'active';
    $upd = $conn->prepare("UPDATE students SET status = ? WHERE student_id = ?");
    $upd->bind_param("si", $new_status, $student_id);
    $upd->execute();
    $upd->close();

    // Audit log
    $log_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)");
    $uid = $_SESSION['user_id'] ?? 1;
    $act = "Student Status Update";
    $detail = "Changed {$student['name']} (#$student_id) status to $new_status";
    $log_stmt->bind_param("iss", $uid, $act, $detail);
    $log_stmt->execute();
    $log_stmt->close();

    echo json_encode([
        'success' => true,
        'new_status' => $new_status
    ]);
    exit;
}

if ($action === 'health_check') {
    $checks = [
        'database' => !$conn->connect_error,
        'users_table' => ($conn->query("SELECT 1 FROM users LIMIT 1") !== false),
        'students_table' => ($conn->query("SELECT 1 FROM students LIMIT 1") !== false),
        'events_table' => ($conn->query("SELECT 1 FROM events LIMIT 1") !== false),
        'registrations_table' => ($conn->query("SELECT 1 FROM registrations LIMIT 1") !== false),
        'audit_logs_table' => ($conn->query("SELECT 1 FROM audit_logs LIMIT 1") !== false),
        'uploads_writable' => is_writable('../practical12/uploads/'),
        'session_active' => isset($_SESSION['user_id'])
    ];
    echo json_encode(['success' => true, 'checks' => $checks]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
