<?php
require_once 'student_auth.php';
require_once '../practical9/db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <style>
        body { font-family: Arial; margin: 40px; background: #f5f5f5; }
        h1 { color: #333; }
        .info { background: #e8f5e9; padding: 10px 15px; border-radius: 4px; margin: 15px 0; }
        table { border-collapse: collapse; background: white; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #4CAF50; color: white; }
    </style>
</head>
<body>

<h1>Student Dashboard</h1>
<div class="info">Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> (Student) | <a href="logout.php">Logout</a></div>

<h2>Available Events</h2>
<?php
$r = $conn->query("SELECT * FROM events WHERE status = 'open' ORDER BY event_date");
?>
<table>
<tr><th>Event</th><th>Date</th><th>Location</th><th>Category</th></tr>
<?php while ($row = $r->fetch_assoc()): ?>
<tr><td><?= htmlspecialchars($row['event_name']) ?></td><td><?= $row['event_date'] ?></td><td><?= htmlspecialchars($row['location']) ?></td><td><?= $row['category'] ?></td></tr>
<?php endwhile; ?>
</table>

</body>
</html>
