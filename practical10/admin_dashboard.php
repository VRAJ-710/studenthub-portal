<?php
require_once 'auth.php';
require_once '../practical9/db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial; margin: 40px; background: #f5f5f5; }
        h1 { color: #333; }
        .card { display: inline-block; background: white; padding: 20px 30px; margin: 10px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; min-width: 150px; }
        .card h2 { margin: 0; color: #2196F3; }
        .card p { margin: 5px 0 0; color: #666; }
        nav a { margin-right: 15px; color: #2196F3; text-decoration: none; }
        nav a:hover { text-decoration: underline; }
        .info { background: #e3f2fd; padding: 10px 15px; border-radius: 4px; margin: 15px 0; }
    </style>
</head>
<body>

<h1>Admin Dashboard</h1>
<div class="info">Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> (Admin) | <a href="logout.php">Logout</a></div>

<nav>
    <a href="../practical11/index.php">Manage Students</a>
    <a href="../practical12/index.php">Manage Events</a>
</nav>

<div style="margin-top: 20px;">
<?php
$r = $conn->query("SELECT COUNT(*) as c FROM users");
$users = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) as c FROM students");
$students = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) as c FROM events");
$events = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) as c FROM registrations");
$regs = $r->fetch_assoc()['c'];
?>
<div class="card"><h2><?= $users ?></h2><p>Total Users</p></div>
<div class="card"><h2><?= $students ?></h2><p>Total Students</p></div>
<div class="card"><h2><?= $events ?></h2><p>Total Events</p></div>
<div class="card"><h2><?= $regs ?></h2><p>Registrations</p></div>
</div>

<h2>Recent Registrations</h2>
<?php
$r = $conn->query("SELECT u.username, u.email, u.role, u.created_at FROM users ORDER BY created_at DESC LIMIT 5");
?>
<table border="1" cellpadding="8" cellspacing="0" style="background:white; border-collapse:collapse;">
<tr style="background:#4CAF50; color:white;"><th>Username</th><th>Email</th><th>Role</th><th>Registered</th></tr>
<?php while ($row = $r->fetch_assoc()): ?>
<tr><td><?= htmlspecialchars($row['username']) ?></td><td><?= htmlspecialchars($row['email']) ?></td><td><?= $row['role'] ?></td><td><?= $row['created_at'] ?></td></tr>
<?php endwhile; ?>
</table>

</body>
</html>
