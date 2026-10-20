<?php
session_start();

// Generate CSRF token if not present (Advanced Extension)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = '';
$msg_type = '';

$csv_file = __DIR__ . '/students.csv';
$json_file = __DIR__ . '/students.json';

// Initialize files if they don't exist
if (!file_exists($csv_file)) {
    $fp = @fopen($csv_file, 'w');
    if ($fp) {
        fputcsv($fp, ['Full Name', 'Email', 'Mobile', 'Course', 'Message', 'Submitted At']);
        fclose($fp);
    }
}

if (!file_exists($json_file)) {
    @file_put_contents($json_file, json_encode([], JSON_PRETTY_PRINT));
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

    // CSRF verification
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $msg = 'Invalid CSRF token submission.';
        $msg_type = 'error';
    } else {
        // Collect and trim inputs
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $message = trim($_POST['message'] ?? '');

        // 1. Server-side validation
        if (empty($name) || empty($email) || empty($mobile) || empty($course) || empty($message)) {
            $msg = 'All fields are required. Please fill in all form fields.';
            $msg_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = 'Invalid email address format. Please enter a valid email.';
            $msg_type = 'error';
        } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
            $msg = 'Mobile number must be exactly 10 digits.';
            $msg_type = 'error';
        } else {
            // 2. Server-side sanitization
            $name_clean = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $email_clean = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
            $mobile_clean = htmlspecialchars($mobile, ENT_QUOTES, 'UTF-8');
            $course_clean = htmlspecialchars($course, ENT_QUOTES, 'UTF-8');
            $message_clean = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            $submitted_at = date('d-m-Y H:i:s');

            // 3. Safe file writing to CSV with file locking (flock)
            $csv_fp = @fopen($csv_file, 'a');
            $csv_saved = false;
            if ($csv_fp) {
                if (flock($csv_fp, LOCK_EX)) {
                    fputcsv($csv_fp, [$name_clean, $email_clean, $mobile_clean, $course_clean, $message_clean, $submitted_at]);
                    flock($csv_fp, LOCK_UN);
                    $csv_saved = true;
                }
                fclose($csv_fp);
            }

            // 4. Safe file writing to JSON with file locking
            $json_saved = false;
            $json_fp = @fopen($json_file, 'c+');
            if ($json_fp) {
                if (flock($json_fp, LOCK_EX)) {
                    $file_size = filesize($json_file);
                    $existing_data = [];
                    if ($file_size > 0) {
                        $content = fread($json_fp, $file_size);
                        $existing_data = json_decode($content, true) ?: [];
                    }
                    $existing_data[] = [
                        'name' => $name_clean,
                        'email' => $email_clean,
                        'mobile' => $mobile_clean,
                        'course' => $course_clean,
                        'message' => $message_clean,
                        'submitted_at' => $submitted_at
                    ];
                    ftruncate($json_fp, 0);
                    rewind($json_fp);
                    fwrite($json_fp, json_encode($existing_data, JSON_PRETTY_PRINT));
                    flock($json_fp, LOCK_UN);
                    $json_saved = true;
                }
                fclose($json_fp);
            }

            if ($csv_saved && $json_saved) {
                $msg = 'Registration successful! Record safely stored in both CSV and JSON formats.';
                $msg_type = 'success';
                // Regenerate CSRF token after successful submission
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $msg = 'File storage error. Could not write records safely.';
                $msg_type = 'error';
            }
        }
    }
}

// Read stored records for intermediate extension display
$records = [];
if (file_exists($json_file)) {
    $json_content = @file_get_contents($json_file);
    $records = json_decode($json_content, true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Practical 7 - PHP Form Processing with CSV/JSON Storage</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background: #f5f5f5; color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #1F4E78; border-bottom: 2px solid #1F4E78; padding-bottom: 8px; margin-bottom: 20px; }
        h2 { color: #333; margin-top: 30px; font-size: 1.25rem; }
        .card { background: white; padding: 25px 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 25px; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; font-weight: bold; font-size: 0.95rem; }
        input[type="text"], input[type="email"], select, textarea {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 0.95rem;
        }
        textarea { height: 80px; resize: vertical; }
        button[type="submit"] {
            background: #4CAF50; color: white; padding: 12px 24px; border: none; border-radius: 4px; font-size: 1rem; cursor: pointer; font-weight: bold; width: 100%; transition: background 0.2s;
        }
        button[type="submit"]:hover { background: #45a049; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: white; }
        th, td { border: 1px solid #ddd; padding: 10px 12px; text-align: left; font-size: 0.9rem; }
        th { background: #1F4E78; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .badge { display: inline-block; padding: 3px 8px; background: #e2e3e5; color: #383d41; border-radius: 4px; font-size: 0.8rem; }
        .file-info { font-size: 0.85rem; color: #666; margin-top: 8px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Practical 7: PHP Server-Side Form Processing &amp; File Storage</h1>

    <div class="card">
        <h2>Student Registration Form</h2>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-<?php echo $msg_type === 'success' ? 'success' : 'error'; ?>">
                <?php echo ($msg_type === 'success' ? '✅ ' : '❌ ') . $msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" placeholder="e.g. Vraj Patel" value="<?php echo isset($_POST['name']) && $msg_type !== 'success' ? htmlspecialchars($_POST['name']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" placeholder="e.g. 25dce027@charusat.edu.in" value="<?php echo isset($_POST['email']) && $msg_type !== 'success' ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="mobile">Mobile Number (10 Digits) *</label>
                <input type="text" id="mobile" name="mobile" placeholder="e.g. 9876543210" maxlength="10" value="<?php echo isset($_POST['mobile']) && $msg_type !== 'success' ? htmlspecialchars($_POST['mobile']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="course">Course / Department *</label>
                <select id="course" name="course" required>
                    <option value="">-- Select Course --</option>
                    <option value="Computer Engineering" <?php echo (isset($_POST['course']) && $_POST['course'] === 'Computer Engineering') ? 'selected' : ''; ?>>Computer Engineering</option>
                    <option value="Information Technology" <?php echo (isset($_POST['course']) && $_POST['course'] === 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                    <option value="Artificial Intelligence" <?php echo (isset($_POST['course']) && $_POST['course'] === 'Artificial Intelligence') ? 'selected' : ''; ?>>Artificial Intelligence</option>
                    <option value="Electronics & Communication" <?php echo (isset($_POST['course']) && $_POST['course'] === 'Electronics & Communication') ? 'selected' : ''; ?>>Electronics & Communication</option>
                </select>
            </div>

            <div class="form-group">
                <label for="message">Message / Statement of Purpose *</label>
                <textarea id="message" name="message" placeholder="Enter reason for registration or message..." required><?php echo isset($_POST['message']) && $msg_type !== 'success' ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
            </div>

            <button type="submit" name="submit" value="Register">Submit Registration</button>
        </form>
    </div>

    <div class="card">
        <h2>Stored Records (CSV &amp; JSON Storage View)</h2>
        <p class="file-info">Persistently stored in <code>practical7/students.csv</code> and <code>practical7/students.json</code></p>
        <?php if (!empty($records)): ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Course</th>
                        <th>Message</th>
                        <th>Submitted At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $idx => $r): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td><strong><?php echo htmlspecialchars($r['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($r['email']); ?></td>
                            <td><?php echo htmlspecialchars($r['mobile']); ?></td>
                            <td><span class="badge"><?php echo htmlspecialchars($r['course']); ?></span></td>
                            <td><?php echo htmlspecialchars($r['message']); ?></td>
                            <td><small><?php echo htmlspecialchars($r['submitted_at']); ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color:#666; font-style:italic;">No records stored yet. Submit the form above to add your first record.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
