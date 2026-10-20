<?php
session_start();
require_once '../practical9/db.php';
$msg = '';
$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

function handlePoster($upload_dir) {
    if (!isset($_FILES['poster']) || $_FILES['poster']['error'] === UPLOAD_ERR_NO_FILE) return null;
    $file = $_FILES['poster'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 2 * 1024 * 1024;

    if (!in_array($file['type'], $allowed)) return ['error' => 'Only JPG, PNG, GIF, WEBP allowed.'];
    if ($file['size'] > $max_size) return ['error' => 'File must be under 2MB.'];

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('poster_') . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) return ['file' => $filename];
    return ['error' => 'Upload failed.'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $poster_result = handlePoster($upload_dir);
        if (isset($poster_result['error'])) {
            $msg = '<p class="error">' . $poster_result['error'] . '</p>';
        } else {
            $poster = $poster_result['file'] ?? null;
            $stmt = $conn->prepare("INSERT INTO events (event_name, event_date, location, description, category, status, poster) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $_POST['event_name'], $_POST['event_date'], $_POST['location'], $_POST['description'], $_POST['category'], $_POST['status'], $poster);
            $msg = $stmt->execute() ? '<p class="success">Event added.</p>' : '<p class="error">Failed to add event.</p>';
            $stmt->close();
        }
    }

    if ($action === 'edit') {
        $poster_result = handlePoster($upload_dir);
        if (isset($poster_result['error'])) {
            $msg = '<p class="error">' . $poster_result['error'] . '</p>';
        } else {
            if (isset($poster_result['file'])) {
                $stmt = $conn->prepare("UPDATE events SET event_name=?, event_date=?, location=?, description=?, category=?, status=?, poster=? WHERE event_id=?");
                $stmt->bind_param("sssssssi", $_POST['event_name'], $_POST['event_date'], $_POST['location'], $_POST['description'], $_POST['category'], $_POST['status'], $poster_result['file'], $_POST['event_id']);
            } else {
                $stmt = $conn->prepare("UPDATE events SET event_name=?, event_date=?, location=?, description=?, category=?, status=? WHERE event_id=?");
                $stmt->bind_param("ssssssi", $_POST['event_name'], $_POST['event_date'], $_POST['location'], $_POST['description'], $_POST['category'], $_POST['status'], $_POST['event_id']);
            }
            $msg = $stmt->execute() ? '<p class="success">Event updated.</p>' : '<p class="error">Update failed.</p>';
            $stmt->close();
        }
    }

    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM events WHERE event_id=?");
        $stmt->bind_param("i", $_POST['event_id']);
        $msg = $stmt->execute() ? '<p class="success">Event deleted.</p>' : '<p class="error">Delete failed.</p>';
        $stmt->close();
    }
}

$edit_event = null;
if (isset($_GET['edit_id'])) {
    $es = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
    $es->bind_param("i", $_GET['edit_id']);
    $es->execute();
    $edit_event = $es->get_result()->fetch_assoc();
    $es->close();
}

$filter_cat = $_GET['category'] ?? '';
$filter_status = $_GET['status'] ?? '';
$sql = "SELECT * FROM events WHERE 1=1";
if ($filter_cat !== '') $sql .= " AND category = '" . $conn->real_escape_string($filter_cat) . "'";
if ($filter_status !== '') $sql .= " AND status = '" . $conn->real_escape_string($filter_status) . "'";
$sql .= " ORDER BY event_id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Practical 12 - Event Management</title>
    <style>
        body { font-family: Arial; margin: 30px; background: #f5f5f5; }
        h1, h2 { color: #333; }
        form { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; max-width: 500px; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 7px; margin-top: 3px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        textarea { height: 60px; resize: vertical; }
        button { margin-top: 12px; padding: 8px 18px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #45a049; }
        .btn-danger { background: #f44336; }
        .btn-edit { background: #2196F3; }
        table { border-collapse: collapse; width: 100%; background: white; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        img.poster { max-width: 80px; max-height: 60px; border-radius: 4px; }
        .filter-bar { display: flex; gap: 10px; margin-bottom: 15px; align-items: flex-end; }
        .filter-bar select { width: auto; }
    </style>
</head>
<body>

<h1>Practical 12: Event Management CRUD</h1>
<p><a href="../practical10/admin_dashboard.php">← Back to Dashboard</a></p>
<?= $msg ?>

<h2><?= $edit_event ? 'Edit Event' : 'Add Event' ?></h2>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="<?= $edit_event ? 'edit' : 'add' ?>">
    <?php if ($edit_event): ?>
        <input type="hidden" name="event_id" value="<?= $edit_event['event_id'] ?>">
    <?php endif; ?>
    <label>Event Name</label>
    <input type="text" name="event_name" value="<?= htmlspecialchars($edit_event['event_name'] ?? '') ?>" required>
    <label>Date</label>
    <input type="date" name="event_date" value="<?= $edit_event['event_date'] ?? '' ?>" required>
    <label>Location</label>
    <input type="text" name="location" value="<?= htmlspecialchars($edit_event['location'] ?? '') ?>">
    <label>Description</label>
    <textarea name="description"><?= htmlspecialchars($edit_event['description'] ?? '') ?></textarea>
    <label>Category</label>
    <select name="category">
        <?php foreach (['General','Technical','Workshop','Cultural','Sports'] as $c): ?>
            <option value="<?= $c ?>" <?= ($edit_event['category'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
        <?php endforeach; ?>
    </select>
    <label>Status</label>
    <select name="status">
        <option value="open" <?= ($edit_event['status'] ?? '') === 'open' ? 'selected' : '' ?>>Open</option>
        <option value="closed" <?= ($edit_event['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option>
    </select>
    <label>Poster (JPG/PNG/GIF/WEBP, max 2MB)</label>
    <input type="file" name="poster" accept="image/*">
    <?php if ($edit_event && $edit_event['poster']): ?>
        <p>Current: <img src="uploads/<?= $edit_event['poster'] ?>" class="poster"></p>
    <?php endif; ?>
    <button type="submit"><?= $edit_event ? 'Update' : 'Add Event' ?></button>
    <?php if ($edit_event): ?>
        <a href="index.php"><button type="button" style="background:#999;">Cancel</button></a>
    <?php endif; ?>
</form>

<h2>Events List</h2>
<form method="GET" class="filter-bar">
    <select name="category">
        <option value="">All Categories</option>
        <?php foreach (['General','Technical','Workshop','Cultural','Sports'] as $c): ?>
            <option value="<?= $c ?>" <?= $filter_cat === $c ? 'selected' : '' ?>><?= $c ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status">
        <option value="">All Status</option>
        <option value="open" <?= $filter_status === 'open' ? 'selected' : '' ?>>Open</option>
        <option value="closed" <?= $filter_status === 'closed' ? 'selected' : '' ?>>Closed</option>
    </select>
    <button type="submit">Filter</button>
    <a href="index.php"><button type="button" style="background:#999;">Clear</button></a>
</form>

<table>
<tr><th>ID</th><th>Name</th><th>Date</th><th>Location</th><th>Category</th><th>Status</th><th>Poster</th><th>Actions</th></tr>
<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['event_id'] ?></td>
    <td><?= htmlspecialchars($row['event_name']) ?></td>
    <td><?= $row['event_date'] ?></td>
    <td><?= htmlspecialchars($row['location']) ?></td>
    <td><?= $row['category'] ?></td>
    <td><?= $row['status'] ?></td>
    <td><?= $row['poster'] ? '<img src="uploads/' . $row['poster'] . '" class="poster">' : 'No poster' ?></td>
    <td>
        <a href="?edit_id=<?= $row['event_id'] ?>"><button class="btn-edit" type="button">Edit</button></a>
        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this event?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="event_id" value="<?= $row['event_id'] ?>">
            <button class="btn-danger" type="submit">Delete</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</table>

</body>
</html>
