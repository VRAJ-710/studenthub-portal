<?php
require_once '../practical9/db.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="students.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['ID', 'Name', 'Email', 'Course', 'Phone']);

$result = $conn->query("SELECT * FROM students ORDER BY student_id");
while ($row = $result->fetch_assoc()) {
    fputcsv($out, [$row['student_id'], $row['name'], $row['email'], $row['course'], $row['phone']]);
}
fclose($out);
?>
