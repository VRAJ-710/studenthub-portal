<?php
require_once 'db.php';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $role = $_POST['role'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
        $msg = '<p class="error">All fields are required.</p>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = '<p class="error">Invalid email format.</p>';
    } elseif (strlen($password) < 6) {
        $msg = '<p class="error">Password must be at least 6 characters.</p>';
    } elseif ($password !== $confirm) {
        $msg = '<p class="error">Passwords do not match.</p>';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $msg = '<p class="error">Email or username already exists.</p>';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $ins->bind_param("ssss", $username, $email, $hashed, $role);
            if ($ins->execute()) {
                $msg = '<p class="success">Registration successful!</p>';
            } else {
                $msg = '<p class="error">Registration failed. Try again.</p>';
            }
            $ins->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Practical 9 - User Registration</title>
    <style>
        body { font-family: Arial; margin: 40px; background: #f5f5f5; }
        h1 { color: #333; }
        form { background: white; padding: 25px; max-width: 400px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 12px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { margin-top: 18px; padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
        button:hover { background: #45a049; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>

<h1>Practical 9: Secure User Registration</h1>
<?= $msg ?>

<form method="POST" onsubmit="return validateForm()">
    <label>Username</label>
    <input type="text" name="username" id="username" required>

    <label>Email</label>
    <input type="email" name="email" id="email" required>

    <label>Password</label>
    <input type="password" name="password" id="password" required minlength="6">

    <label>Confirm Password</label>
    <input type="password" name="confirm_password" id="confirm_password" required>

    <label>Role</label>
    <select name="role">
        <option value="student">Student</option>
        <option value="admin">Admin</option>
    </select>

    <button type="submit">Register</button>
</form>

<script>
function validateForm() {
    var u = document.getElementById('username').value.trim();
    var e = document.getElementById('email').value.trim();
    var p = document.getElementById('password').value;
    var c = document.getElementById('confirm_password').value;

    if (u === '' || e === '' || p === '' || c === '') {
        alert('All fields are required.');
        return false;
    }
    if (p.length < 6) {
        alert('Password must be at least 6 characters.');
        return false;
    }
    if (p !== c) {
        alert('Passwords do not match.');
        return false;
    }
    return true;
}
</script>

</body>
</html>
