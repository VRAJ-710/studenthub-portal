<?php
session_start();
require_once '../practical9/db.php';
$timeout = 1800;

if (!isset($_SESSION['user_id'])) {
    header("Location: ../practical10/index.php");
    exit;
}
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset(); session_destroy();
    header("Location: ../practical10/index.php?msg=timeout");
    exit;
}
$_SESSION['last_activity'] = time();
$role = $_SESSION['role'];

if ($role !== 'admin') {
    header("Location: ../practical10/student_dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $new_status = $_POST['new_status'];
    $uid = $_POST['user_id'];
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $uid);
    $stmt->execute();
    $stmt->close();

    $log = $conn->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)");
    $action_text = "Changed user #$uid role to $new_status";
    $log->bind_param("iss", $_SESSION['user_id'], $action_text, $action_text);
    $log->execute();
    $log->close();
}

$user_count = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$student_count = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
$event_count = $conn->query("SELECT COUNT(*) as c FROM events")->fetch_assoc()['c'];
$reg_count = $conn->query("SELECT COUNT(*) as c FROM registrations")->fetch_assoc()['c'];

$recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$all_users = $conn->query("SELECT * FROM users ORDER BY id");
$audit_logs = $conn->query("SELECT al.*, u.username FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Practical 14 - Admin Dashboard</title>
    <style>
        body { font-family: Arial; margin: 0; background: #f5f5f5; }
        .wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 220px; background: #263238; color: white; padding: 20px; }
        .sidebar h2 { margin-top: 0; font-size: 18px; border-bottom: 1px solid #455a64; padding-bottom: 10px; }
        .sidebar a { display: block; color: #b0bec5; text-decoration: none; padding: 8px 0; }
        .sidebar a:hover { color: white; }
        .main { flex: 1; padding: 30px; }
        h1 { color: #333; margin-top: 0; }
        .cards { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 25px; }
        .card { background: white; padding: 20px 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; min-width: 140px; }
        .card h2 { margin: 0; color: #2196F3; font-size: 28px; }
        .card p { margin: 5px 0 0; color: #666; }
        table { border-collapse: collapse; width: 100%; background: white; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        h2.section { color: #333; margin-top: 30px; }
        .info-bar { background: #e3f2fd; padding: 10px 15px; border-radius: 4px; margin-bottom: 20px; }
        button { padding: 5px 12px; border: none; border-radius: 4px; cursor: pointer; color: white; }
        .btn-sm { background: #ff9800; font-size: 12px; }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="sidebar">
        <h2>StudentHub</h2>
        <a href="index.php">📊 Dashboard</a>
        <?php if ($role === 'admin'): ?>
            <a href="../practical11/index.php">👨‍🎓 Manage Students</a>
            <a href="../practical12/index.php">📅 Manage Events</a>
            <a href="../practical13/index.php">🔌 API CRUD</a>
        <?php endif; ?>
        <a href="../practical10/logout.php">🚪 Logout</a>
    </div>

    <div class="main">
        <div class="info-bar">Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> (<?= ucfirst($role) ?>) | Role-based menu is active</div>

        <h1>Practical 14: Admin Dashboard</h1>

        <div class="cards">
            <div class="card"><h2><?= $user_count ?></h2><p>Users</p></div>
            <div class="card"><h2><?= $student_count ?></h2><p>Students</p></div>
            <div class="card"><h2><?= $event_count ?></h2><p>Events</p></div>
            <div class="card"><h2><?= $reg_count ?></h2><p>Registrations</p></div>
        </div>

        <h2 class="section">Latest Registered Users</h2>
        <table>
        <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Last Login</th><th>Registered</th></tr>
        <?php while ($row = $recent_users->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= $row['role'] ?></td>
            <td><?= $row['last_login'] ?? 'Never' ?></td>
            <td><?= $row['created_at'] ?></td>
        </tr>
        <?php endwhile; ?>
        </table>

        <h2 class="section">All Users (Role Toggle)</h2>
        <table>
        <tr><th>ID</th><th>Username</th><th>Role</th><th>Action</th></tr>
        <?php while ($row = $all_users->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= $row['role'] ?></td>
            <td>
                <?php if ($row['id'] != $_SESSION['user_id']): ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="toggle_status" value="1">
                    <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="new_status" value="<?= $row['role'] === 'admin' ? 'student' : 'admin' ?>">
                    <button class="btn-sm" type="submit">Make <?= $row['role'] === 'admin' ? 'Student' : 'Admin' ?></button>
                </form>
                <?php else: ?>
                    (You)
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </table>

        <h2 class="section">Audit Log (Recent Actions)</h2>
        <table>
        <tr><th>ID</th><th>User</th><th>Action</th><th>Time</th></tr>
        <?php while ($row = $audit_logs->fetch_assoc()): ?>
        <tr>
            <td><?= $row['log_id'] ?></td>
            <td><?= htmlspecialchars($row['username'] ?? 'System') ?></td>
            <td><?= htmlspecialchars($row['action']) ?></td>
            <td><?= $row['created_at'] ?></td>
        </tr>
        <?php endwhile; ?>
        <?php if ($audit_logs->num_rows === 0): ?>
        <tr><td colspan="4">No audit logs yet.</td></tr>
        <?php endif; ?>
        </table>
    </div>
</div>

</body>
</html>
