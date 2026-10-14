<!DOCTYPE html>
<html>
<head>
    <title>Practical 8 - StudentHub Database</title>
    <style>
        body { font-family: Arial; margin: 30px; background: #f5f5f5; }
        h1 { color: #333; }
        h2 { color: #555; margin-top: 30px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        table { border-collapse: collapse; width: 80%; margin-top: 10px; background: white; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #4CAF50; color: white; }
        tr:nth-child(even) { background: #f2f2f2; }
    </style>
</head>
<body>

<h1>Practical 8: StudentHub Database</h1>

<?php
require_once 'db.php';

echo '<p class="success">✅ Database connected successfully!</p>';

echo "<h2>All Students</h2>";
$stmt = $conn->prepare("SELECT * FROM students");
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Course</th></tr>";
foreach ($students as $row) {
    echo "<tr>";
    echo "<td>" . $row['student_id'] . "</td>";
    echo "<td>" . $row['name'] . "</td>";
    echo "<td>" . $row['email'] . "</td>";
    echo "<td>" . $row['course'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>All Events</h2>";
$stmt = $conn->prepare("SELECT * FROM events");
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>ID</th><th>Event Name</th><th>Date</th><th>Location</th></tr>";
foreach ($events as $row) {
    echo "<tr>";
    echo "<td>" . $row['event_id'] . "</td>";
    echo "<td>" . $row['event_name'] . "</td>";
    echo "<td>" . $row['event_date'] . "</td>";
    echo "<td>" . $row['location'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Registrations</h2>";
$stmt = $conn->prepare("SELECT s.name, e.event_name, r.reg_date 
                         FROM registrations r 
                         JOIN students s ON r.student_id = s.student_id 
                         JOIN events e ON r.event_id = e.event_id");
$stmt->execute();
$regs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>Student Name</th><th>Event Name</th><th>Registration Date</th></tr>";
foreach ($regs as $row) {
    echo "<tr>";
    echo "<td>" . $row['name'] . "</td>";
    echo "<td>" . $row['event_name'] . "</td>";
    echo "<td>" . $row['reg_date'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Search: BCA Students</h2>";
$course = "BCA";
$stmt = $conn->prepare("SELECT * FROM students WHERE course = :course");
$stmt->bindParam(':course', $course);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Course</th></tr>";
foreach ($results as $row) {
    echo "<tr>";
    echo "<td>" . $row['student_id'] . "</td>";
    echo "<td>" . $row['name'] . "</td>";
    echo "<td>" . $row['email'] . "</td>";
    echo "<td>" . $row['course'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>

</body>
</html>
