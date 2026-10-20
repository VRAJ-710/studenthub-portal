<?php
session_start();
require_once '../practical9/db.php';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO students (name, email, course, phone) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $_POST['name'], $_POST['email'], $_POST['course'], $_POST['phone']);
        $msg = $stmt->execute() ? '<p class="success">Student added successfully.</p>' : '<p class="error">Failed to add. Email may already exist.</p>';
        $stmt->close();
    }

    if ($action === 'edit') {
        $stmt = $conn->prepare("UPDATE students SET name=?, email=?, course=?, phone=? WHERE student_id=?");
        $stmt->bind_param("ssssi", $_POST['name'], $_POST['email'], $_POST['course'], $_POST['phone'], $_POST['student_id']);
        $msg = $stmt->execute() ? '<p class="success">Student updated successfully.</p>' : '<p class="error">Update failed.</p>';
        $stmt->close();
    }

    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM students WHERE student_id=?");
        $stmt->bind_param("i", $_POST['student_id']);
        $msg = $stmt->execute() ? '<p class="success">Student deleted.</p>' : '<p class="error">Delete failed.</p>';
        $stmt->close();
    }
}

$search = $_GET['search'] ?? '';
$filter_course = $_GET['course'] ?? '';
$where = "WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $where .= " AND (name LIKE ? OR email LIKE ?)";
    $s = "%$search%";
    $params[] = &$s;
    $params[] = &$s;
    $types .= 'ss';
}
if ($filter_course !== '') {
    $where .= " AND course = ?";
    $params[] = &$filter_course;
    $types .= 's';
}

$sql = "SELECT * FROM students $where ORDER BY student_id DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$edit_student = null;
if (isset($_GET['edit_id'])) {
    $es = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $es->bind_param("i", $_GET['edit_id']);
    $es->execute();
    $edit_student = $es->get_result()->fetch_assoc();
    $es->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Practical 11 - Student Management</title>
    <style>
        body { font-family: Arial; margin: 30px; background: #f5f5f5; }
        h1, h2 { color: #333; }
        form { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; max-width: 500px; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, select { width: 100%; padding: 7px; margin-top: 3px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { margin-top: 12px; padding: 8px 18px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #45a049; }
        .btn-danger { background: #f44336; }
        .btn-danger:hover { background: #d32f2f; }
        .btn-edit { background: #2196F3; }
        table { border-collapse: collapse; width: 100%; background: white; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .search-bar { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 15px; }
        .search-bar input, .search-bar select { width: auto; }
    </style>
</head>
<body>

<h1>Practical 11: Student Management CRUD</h1>
<p><a href="../practical10/admin_dashboard.php">← Back to Dashboard</a></p>
<?= $msg ?>

<h2><?= $edit_student ? 'Edit Student' : 'Add Student' ?></h2>
<form method="POST">
    <input type="hidden" name="action" value="<?= $edit_student ? 'edit' : 'add' ?>">
    <?php if ($edit_student): ?>
        <input type="hidden" name="student_id" value="<?= $edit_student['student_id'] ?>">
    <?php endif; ?>
    <label>Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($edit_student['name'] ?? '') ?>" required>
    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($edit_student['email'] ?? '') ?>" required>
    <label>Course</label>
    <select name="course">
        <?php foreach (['BCA','MCA','MSc IT'] as $c): ?>
            <option value="<?= $c ?>" <?= ($edit_student['course'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
        <?php endforeach; ?>
    </select>
    <label>Phone</label>
    <input type="text" name="phone" value="<?= htmlspecialchars($edit_student['phone'] ?? '') ?>">
    <button type="submit"><?= $edit_student ? 'Update' : 'Add Student' ?></button>
    <?php if ($edit_student): ?>
        <a href="index.php"><button type="button" style="background:#999;">Cancel</button></a>
    <?php endif; ?>
</form>

<h2>Student List</h2>
<form method="GET" class="search-bar">
    <input type="text" name="search" placeholder="Search name or email..." value="<?= htmlspecialchars($search) ?>">
    <select name="course">
        <option value="">All Courses</option>
        <?php foreach (['BCA','MCA','MSc IT'] as $c): ?>
            <option value="<?= $c ?>" <?= $filter_course === $c ? 'selected' : '' ?>><?= $c ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Search</button>
    <a href="index.php"><button type="button" style="background:#999;">Clear</button></a>
</form>

<table>
<tr><th>ID</th><th>Name</th><th>Email</th><th>Course</th><th>Phone</th><th>Actions</th></tr>
<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['student_id'] ?></td>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td><?= $row['course'] ?></td>
    <td><?= htmlspecialchars($row['phone']) ?></td>
    <td>
        <a href="?edit_id=<?= $row['student_id'] ?>"><button class="btn-edit" type="button">Edit</button></a>
        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this student?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="student_id" value="<?= $row['student_id'] ?>">
            <button class="btn-danger" type="submit">Delete</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</table>

<h3>Export</h3>
<a href="export_csv.php"><button>Export to CSV</button></a>

</body>
</html>
