<?php
require_once '../practical9/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'list') {
    $result = $conn->query("SELECT * FROM students ORDER BY student_id DESC");
    $students = [];
    while ($row = $result->fetch_assoc()) $students[] = $row;
    echo json_encode(['status' => 'success', 'data' => $students]);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'add') {
        if (empty($data['name']) || empty($data['email'])) {
            echo json_encode(['status' => 'error', 'message' => 'Name and email are required.']);
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO students (name, email, course, phone) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $data['name'], $data['email'], $data['course'], $data['phone']);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Student added.', 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Insert failed. Email may exist.']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'update') {
        $stmt = $conn->prepare("UPDATE students SET name=?, email=?, course=?, phone=? WHERE student_id=?");
        $stmt->bind_param("ssssi", $data['name'], $data['email'], $data['course'], $data['phone'], $data['student_id']);
        echo json_encode($stmt->execute() ? ['status' => 'success', 'message' => 'Updated.'] : ['status' => 'error', 'message' => 'Update failed.']);
        $stmt->close();
        exit;
    }

    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM students WHERE student_id=?");
        $stmt->bind_param("i", $data['student_id']);
        echo json_encode($stmt->execute() ? ['status' => 'success', 'message' => 'Deleted.'] : ['status' => 'error', 'message' => 'Delete failed.']);
        $stmt->close();
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
?>
