<?php
session_start();
require_once '../practical9/db.php';
$msg = '';

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    header("Location: " . ($role === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $msg = '<p class="error">All fields are required.</p>';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['last_activity'] = time();

                $upd = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $upd->bind_param("i", $row['id']);
                $upd->execute();
                $upd->close();

                header("Location: " . ($row['role'] === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php'));
                exit;
            } else {
                $msg = '<p class="error">Invalid password.</p>';
            }
        } else {
            $msg = '<p class="error">User not found.</p>';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Practical 10 - Login</title>
    <style>
        body { font-family: Arial; margin: 40px; background: #f5f5f5; }
        h1 { color: #333; }
        form { background: white; padding: 25px; max-width: 380px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 12px; font-weight: bold; }
        input { width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { margin-top: 18px; padding: 10px 20px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
        button:hover { background: #1976D2; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        a { color: #2196F3; }
    </style>
</head>
<body>

<h1>Practical 10: Secure Login</h1>
<?= $msg ?>

<form method="POST">
    <label>Username</label>
    <input type="text" name="username" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <button type="submit">Login</button>
</form>

<p><a href="../practical9/index.php">Don't have an account? Register here</a></p>

</body>
</html>
