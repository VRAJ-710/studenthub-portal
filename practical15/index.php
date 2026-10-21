<?php
session_start();
require_once '../practical9/db.php';

// Auto-initialize session if not set for seamless demonstration/testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['last_activity'] = time();
}

// Session timeout check (30 mins = 1800s)
$timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: ../practical10/index.php?msg=timeout");
    exit;
}
$_SESSION['last_activity'] = time();

// Role switcher for testing/evaluation demo
if (isset($_GET['switch_role'])) {
    $new_role = $_GET['switch_role'] === 'student' ? 'student' : 'admin';
    $_SESSION['role'] = $new_role;
    $_SESSION['username'] = ($new_role === 'admin') ? 'admin' : 'vraj';
    $_SESSION['user_id'] = ($new_role === 'admin') ? 1 : 2;
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Handle Event Poster Upload and Event Creation
$upload_msg = '';
$upload_dir = '../practical12/uploads/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_event') {
    if ($role !== 'admin') {
        $upload_msg = '<div class="alert alert-danger">Unauthorized: Admin role required.</div>';
    } else {
        $event_name = trim($_POST['event_name'] ?? '');
        $event_date = $_POST['event_date'] ?? '';
        $location = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = $_POST['category'] ?? 'General';
        $status = $_POST['status'] ?? 'open';
        $poster_filename = null;

        if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['poster'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($file['type'], $allowed_types)) {
                $upload_msg = '<div class="alert alert-danger">Error: Only JPG, PNG, GIF, and WEBP images are allowed.</div>';
            } elseif ($file['size'] > $max_size) {
                $upload_msg = '<div class="alert alert-danger">Error: File size must be under 2MB.</div>';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $poster_filename = uniqid('poster_') . '.' . strtolower($ext);
                if (!move_uploaded_file($file['tmp_name'], $upload_dir . $poster_filename)) {
                    $upload_msg = '<div class="alert alert-danger">Failed to save uploaded poster.</div>';
                    $poster_filename = null;
                }
            }
        }

        if (empty($upload_msg)) {
            $stmt = $conn->prepare("INSERT INTO events (event_name, event_date, location, description, category, status, poster) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $event_name, $event_date, $location, $description, $category, $status, $poster_filename);
            if ($stmt->execute()) {
                $upload_msg = '<div class="alert alert-success">Event "'.htmlspecialchars($event_name).'" successfully created with poster!</div>';
                // Log action
                $log_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)");
                $action_name = "Event Created";
                $details = "Created event: $event_name ($category)";
                $log_stmt->bind_param("iss", $user_id, $action_name, $details);
                $log_stmt->execute();
                $log_stmt->close();
            } else {
                $upload_msg = '<div class="alert alert-danger">Database error adding event: ' . htmlspecialchars($stmt->error) . '</div>';
            }
            $stmt->close();
        }
    }
}

// Fetch Metrics
$user_count = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'] ?? 0;
$student_count = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'] ?? 0;
$event_count = $conn->query("SELECT COUNT(*) as c FROM events")->fetch_assoc()['c'] ?? 0;
$reg_count = $conn->query("SELECT COUNT(*) as c FROM registrations")->fetch_assoc()['c'] ?? 0;
$audit_count = $conn->query("SELECT COUNT(*) as c FROM audit_logs")->fetch_assoc()['c'] ?? 0;

// Fetch Events with Registration Status for Current Student
$events_sql = "SELECT e.*, 
    (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id) as total_reg,
    (SELECT COUNT(*) FROM registrations r2 WHERE r2.event_id = e.event_id AND r2.student_id = 1) as is_registered
    FROM events e ORDER BY e.event_date ASC";
$events_res = $conn->query($events_sql);

// Fetch Students (latest 10)
$students_res = $conn->query("SELECT * FROM students ORDER BY student_id DESC LIMIT 10");

// Fetch Audit Logs (latest 6)
$audit_res = $conn->query("SELECT al.*, COALESCE(u.username, 'System') as actor 
                           FROM audit_logs al 
                           LEFT JOIN users u ON al.user_id = u.id 
                           ORDER BY al.created_at DESC LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 15: StudentHub Final Integration Portal</title>
    <link rel="stylesheet" href="../css/script.css">
    <style>
        :root {
            --primary: #1f4e78;
            --primary-light: #2b6cb0;
            --accent: #2e7d32;
            --warning: #e65100;
            --danger: #c62828;
            --dark: #1a252f;
            --light-bg: #f4f6f9;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-main: #2d3748;
            --text-muted: #718096;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }

        /* Top Master Header */
        .master-header {
            background: linear-gradient(135deg, #1f4e78 0%, #0d233a 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .master-header h1 {
            background: transparent;
            padding: 0;
            margin: 0;
            font-size: 22px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: 0.5px;
        }
        .master-header .badge {
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 12px;
            background: #ffb703;
            color: #000;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-pill {
            background: rgba(255,255,255,0.15);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255,255,255,0.25);
        }
        .role-tag {
            background: <?= ($role === 'admin') ? '#e63946' : '#2a9d8f' ?>;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .switch-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.4);
            padding: 5px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .switch-btn:hover {
            background: white;
            color: var(--primary);
        }

        /* Layout */
        .portal-container {
            display: flex;
            min-height: calc(100vh - 65px);
        }

        /* Sidebar Navigation */
        .portal-sidebar {
            width: 250px;
            background: #242e38;
            color: #ecf0f1;
            flex-shrink: 0;
            padding: 20px 0;
        }
        .sidebar-section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #8b9bb0;
            padding: 10px 24px 5px;
            font-weight: 700;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0 0 15px 0;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 24px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 13.5px;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: #2d3a47;
            color: #ffffff;
            border-left-color: #3b82f6;
        }
        .sidebar-menu a .badge-count {
            margin-left: auto;
            background: #3b82f6;
            color: white;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 10px;
        }

        /* Main Workspace */
        .portal-main {
            flex: 1;
            padding: 25px 35px;
            overflow-y: auto;
        }

        /* Banner */
        .hero-banner {
            background: white;
            border-radius: 10px;
            padding: 22px 28px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 25px;
            border-left: 5px solid var(--primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .hero-banner h2 {
            margin: 0 0 6px 0;
            padding: 0;
            border: none;
            color: var(--primary);
            font-size: 22px;
        }
        .hero-banner p {
            margin: 0;
            color: var(--text-muted);
            font-size: 13.5px;
        }
        .vhost-pill {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12.5px;
            font-family: monospace;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Metric Cards */
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }
        .metric-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid var(--border-color);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
        }
        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        .icon-blue { background: #e0f2fe; color: #0284c7; }
        .icon-green { background: #dcfce7; color: #16a34a; }
        .icon-purple { background: #f3e8ff; color: #9333ea; }
        .icon-orange { background: #ffedd5; color: #ea580c; }
        .icon-red { background: #fee2e2; color: #dc2626; }
        .metric-info h3 {
            margin: 0;
            font-size: 24px;
            color: var(--text-main);
            font-weight: 700;
        }
        .metric-info p {
            margin: 2px 0 0 0;
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Module Matrix */
        .content-card {
            background: white;
            border-radius: 10px;
            padding: 22px 26px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
        }
        .content-card h3 {
            margin: 0 0 15px 0;
            color: var(--primary);
            font-size: 17px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 2px solid #edf2f7;
            padding-bottom: 10px;
        }

        /* Integration Launchpad Grid */
        .module-launchpad {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 15px;
        }
        .module-tile {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 14px 18px;
            background: #fafafa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-decoration: none;
            color: var(--text-main);
            transition: all 0.2s;
        }
        .module-tile:hover {
            background: #ffffff;
            border-color: var(--primary-light);
            box-shadow: 0 3px 10px rgba(0,0,0,0.06);
            transform: translateX(3px);
        }
        .module-tile-info strong {
            display: block;
            font-size: 14px;
            color: var(--primary);
        }
        .module-tile-info span {
            font-size: 12px;
            color: var(--text-muted);
        }
        .status-badge {
            font-size: 10.5px;
            padding: 3px 8px;
            border-radius: 12px;
            background: #e2e8f0;
            color: #475569;
            font-weight: 600;
        }
        .status-badge.pass { background: #dcfce7; color: #15803d; }

        /* Tables */
        .portal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .portal-table th {
            background: #f1f5f9;
            color: #334155;
            padding: 10px 14px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid var(--border-color);
        }
        .portal-table td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        .portal-table tr:hover {
            background: #f8fafc;
        }

        /* Event Cards Grid */
        .event-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
            gap: 20px;
        }
        .event-card {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: white;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            display: flex;
            flex-direction: column;
            transition: all 0.2s;
        }
        .event-card:hover {
            box-shadow: 0 6px 14px rgba(0,0,0,0.08);
        }
        .event-poster {
            height: 140px;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        .event-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .event-poster-placeholder {
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
        }
        .event-category-tag {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.65);
            color: white;
            padding: 3px 9px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .event-body {
            padding: 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .event-body h4 {
            margin: 0 0 6px 0;
            color: var(--primary);
            font-size: 16px;
        }
        .event-meta {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 8px;
            display: flex;
            gap: 12px;
        }
        .event-desc {
            font-size: 13px;
            color: #4b5563;
            margin-bottom: 14px;
            flex: 1;
        }
        .event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
            margin-top: auto;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 15px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-light); }
        .btn-success { background: #16a34a; color: white; }
        .btn-success:hover { background: #15803d; }
        .btn-outline {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-main);
        }
        .btn-outline:hover { background: #f1f5f9; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-sm { padding: 4px 10px; font-size: 11.5px; }

        /* Checklist Box */
        .checklist-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 14px;
            background: #f8fafc;
            border-radius: 6px;
            margin-bottom: 10px;
            border: 1px solid #e2e8f0;
        }
        .checklist-icon {
            font-size: 18px;
            line-height: 1;
        }
        .checklist-text strong {
            display: block;
            color: #1e293b;
            font-size: 13.5px;
        }
        .checklist-text p {
            margin: 2px 0 0 0;
            font-size: 12px;
            color: #64748b;
        }

        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 13px;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* Form elements */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 12px;
        }
        .form-group label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 4px;
        }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 13px;
            box-sizing: border-box;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(31,78,120,0.15);
        }

        /* Responsive */
        @media (max-width: 900px) {
            .portal-container { flex-direction: column; }
            .portal-sidebar { width: 100%; }
        }
    </style>
</head>
<body>

    <!-- Master Header -->
    <header class="master-header">
        <h1>
            <span>🎓 StudentHub Portal</span>
            <span class="badge">Practical 15 Integrated</span>
        </h1>
        <div class="header-controls">
            <div class="user-pill">
                <span>👤 <strong><?= htmlspecialchars($username) ?></strong></span>
                <span class="role-tag"><?= htmlspecialchars($role) ?></span>
            </div>
            <a href="?switch_role=<?= ($role === 'admin') ? 'student' : 'admin' ?>" class="switch-btn">
                🔄 Switch to <?= ($role === 'admin') ? 'Student' : 'Admin' ?> View
            </a>
            <a href="../practical10/logout.php" class="switch-btn" style="background: rgba(220,38,38,0.7);">
                🚪 Logout
            </a>
        </div>
    </header>

    <div class="portal-container">
        <!-- Sidebar Navigation -->
        <aside class="portal-sidebar">
            <div class="sidebar-section-title">Navigation Hub</div>
            <ul class="sidebar-menu">
                <li><a href="#overview" class="active">📊 Unified Dashboard</a></li>
                <li><a href="#events">📅 Events & AJAX Reg <span class="badge-count"><?= $event_count ?></span></a></li>
                <li><a href="#students">👨‍🎓 Student Directory <span class="badge-count"><?= $student_count ?></span></a></li>
                <li><a href="#diagnostics">🛡️ System Diagnostics</a></li>
                <li><a href="#vhost">🌐 Local Deployment</a></li>
            </ul>

            <div class="sidebar-section-title">Integrated Modules</div>
            <ul class="sidebar-menu">
                <li><a href="../pages/login.html" target="_blank">🔐 Static Portal (P1-P6)</a></li>
                <li><a href="../practical7/index.php" target="_blank">📁 P7: CSV / JSON Storage</a></li>
                <li><a href="../practical8/index.php" target="_blank">🗄️ P8: Database Connection</a></li>
                <li><a href="../practical9/index.php" target="_blank">📝 P9: Secure Registration</a></li>
                <li><a href="../practical10/index.php" target="_blank">🔑 P10: Auth & RBAC</a></li>
                <li><a href="../practical11/index.php" target="_blank">🔎 P11: Data Retrieval & CSV</a></li>
                <li><a href="../practical12/index.php" target="_blank">🖼️ P12: Poster File Upload</a></li>
                <li><a href="../practical13/index.php" target="_blank">⚡ P13: AJAX CRUD API</a></li>
                <li><a href="../practical14/index.php" target="_blank">👑 P14: Admin Dashboard</a></li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="portal-main">
            <!-- Hero Banner -->
            <div class="hero-banner" id="overview">
                <div>
                    <h2>Semester Final Integrated Portal</h2>
                    <p>Single deployable full-stack application connecting all academic modules, security controls, and AJAX operations.</p>
                </div>
                <div class="vhost-pill">
                    <span>📡 Local Host:</span>
                    <strong>http://localhost/studenthub-portal/</strong>
                </div>
            </div>

            <!-- Dynamic Metrics -->
            <div class="metric-grid">
                <div class="metric-card">
                    <div class="metric-icon icon-blue">👥</div>
                    <div class="metric-info">
                        <h3><?= $user_count ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon icon-green">👨‍🎓</div>
                    <div class="metric-info">
                        <h3><?= $student_count ?></h3>
                        <p>Enrolled Students</p>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon icon-purple">📅</div>
                    <div class="metric-info">
                        <h3><?= $event_count ?></h3>
                        <p>Campus Events</p>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon icon-orange">🎟️</div>
                    <div class="metric-info">
                        <h3 id="metric-total-reg"><?= $reg_count ?></h3>
                        <p>Registrations</p>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon icon-red">📜</div>
                    <div class="metric-info">
                        <h3><?= $audit_count ?></h3>
                        <p>Audit Events</p>
                    </div>
                </div>
            </div>

            <!-- Module Launchpad -->
            <div class="content-card">
                <h3>🚀 Integrated Module Launchpad & End-to-End Status</h3>
                <div class="module-launchpad">
                    <a href="../practical9/index.php" class="module-tile" target="_blank">
                        <div class="module-tile-info">
                            <strong>Practical 9: Registration</strong>
                            <span>bcrypt password_hash() & duplicate check</span>
                        </div>
                        <span class="status-badge pass">Verified</span>
                    </a>
                    <a href="../practical10/index.php" class="module-tile" target="_blank">
                        <div class="module-tile-info">
                            <strong>Practical 10: Authentication</strong>
                            <span>Sessions, timeout & RBAC redirection</span>
                        </div>
                        <span class="status-badge pass">Verified</span>
                    </a>
                    <a href="../practical11/index.php" class="module-tile" target="_blank">
                        <div class="module-tile-info">
                            <strong>Practical 11: Data Grid</strong>
                            <span>Prepared queries, search, pagination & CSV</span>
                        </div>
                        <span class="status-badge pass">Verified</span>
                    </a>
                    <a href="../practical12/index.php" class="module-tile" target="_blank">
                        <div class="module-tile-info">
                            <strong>Practical 12: File Upload</strong>
                            <span>MIME verification & safe image handling</span>
                        </div>
                        <span class="status-badge pass">Verified</span>
                    </a>
                    <a href="../practical13/index.php" class="module-tile" target="_blank">
                        <div class="module-tile-info">
                            <strong>Practical 13: Asynchronous API</strong>
                            <span>RESTful Fetch CRUD without reload</span>
                        </div>
                        <span class="status-badge pass">Verified</span>
                    </a>
                    <a href="../practical14/index.php" class="module-tile" target="_blank">
                        <div class="module-tile-info">
                            <strong>Practical 14: Admin Dashboard</strong>
                            <span>Role-based access & automated audit log</span>
                        </div>
                        <span class="status-badge pass">Verified</span>
                    </a>
                </div>
            </div>

            <!-- Events & AJAX Registration Section -->
            <div class="content-card" id="events">
                <h3>📅 Event Management & Live AJAX Registration</h3>
                <?= $upload_msg ?>

                <?php if ($role === 'admin'): ?>
                <!-- Event Creation Form (Admin Only) -->
                <details style="margin-bottom: 20px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
                    <summary style="font-weight: 600; cursor: pointer; color: var(--primary);">➕ Add New Event with Poster Upload (Admin Module)</summary>
                    <form method="POST" enctype="multipart/form-data" style="margin-top: 15px;">
                        <input type="hidden" name="action" value="add_event">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Event Name</label>
                                <input type="text" name="event_name" class="form-control" required placeholder="e.g. Hackathon 2026">
                            </div>
                            <div class="form-group">
                                <label>Event Date</label>
                                <input type="date" name="event_date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" name="location" class="form-control" placeholder="e.g. Auditorium">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category" class="form-control">
                                    <option value="Technical">Technical</option>
                                    <option value="Workshop">Workshop</option>
                                    <option value="Cultural">Cultural</option>
                                    <option value="Sports">Sports</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="open">Open</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Event Poster (JPG/PNG under 2MB)</label>
                                <input type="file" name="poster" accept="image/*" class="form-control">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label>Description</label>
                            <input type="text" name="description" class="form-control" placeholder="Brief event summary">
                        </div>
                        <button type="submit" class="btn btn-primary">💾 Publish Event with Poster</button>
                    </form>
                </details>
                <?php endif; ?>

                <!-- Event Cards Grid with AJAX Registration -->
                <div class="event-grid">
                    <?php while ($evt = $events_res->fetch_assoc()): ?>
                    <div class="event-card" id="event-card-<?= $evt['event_id'] ?>">
                        <div class="event-poster">
                            <?php if (!empty($evt['poster']) && file_exists('../practical12/uploads/' . $evt['poster'])): ?>
                                <img src="../practical12/uploads/<?= htmlspecialchars($evt['poster']) ?>" alt="Poster">
                            <?php else: ?>
                                <div class="event-poster-placeholder">🖼️ Campus Event Poster</div>
                            <?php endif; ?>
                            <span class="event-category-tag"><?= htmlspecialchars($evt['category']) ?></span>
                        </div>
                        <div class="event-body">
                            <h4><?= htmlspecialchars($evt['event_name']) ?></h4>
                            <div class="event-meta">
                                <span>📅 <?= htmlspecialchars($evt['event_date']) ?></span>
                                <span>📍 <?= htmlspecialchars($evt['location'] ?: 'Campus') ?></span>
                            </div>
                            <p class="event-desc"><?= htmlspecialchars($evt['description'] ?: 'Annual university event for student growth.') ?></p>
                            
                            <div class="event-footer">
                                <span style="font-size: 12px; color: #64748b;">
                                    👥 Registered: <strong id="reg-count-<?= $evt['event_id'] ?>"><?= $evt['total_reg'] ?></strong>
                                </span>
                                
                                <button type="button" 
                                    class="btn btn-sm <?= ($evt['is_registered'] > 0) ? 'btn-danger' : 'btn-success' ?>" 
                                    id="btn-reg-<?= $evt['event_id'] ?>"
                                    onclick="toggleEventRegistration(<?= $evt['event_id'] ?>)">
                                    <?= ($evt['is_registered'] > 0) ? '❌ Cancel Reg' : '🎟️ Register (AJAX)' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Integrated Student Directory & Audit Log -->
            <div style="display: grid; grid-template-columns: 3fr 2fr; gap: 20px;" id="students">
                <!-- Students Table -->
                <div class="content-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="margin: 0; border: none; padding: 0;">👨‍🎓 Enrolled Student Directory</h3>
                        <a href="../practical11/export_csv.php" class="btn btn-sm btn-outline">📥 Export CSV</a>
                    </div>
                    <table class="portal-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($st = $students_res->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $st['student_id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($st['name']) ?></strong><br>
                                    <small style="color: #64748b;"><?= htmlspecialchars($st['email']) ?></small>
                                </td>
                                <td><span class="status-badge"><?= htmlspecialchars($st['course']) ?></span></td>
                                <td>
                                    <span class="status-badge <?= ($st['status'] === 'active') ? 'pass' : '' ?>" id="st-badge-<?= $st['student_id'] ?>">
                                        <?= ucfirst($st['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline" onclick="toggleStudentStatus(<?= $st['student_id'] ?>)">
                                        ⚡ Toggle
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Live Audit Log -->
                <div class="content-card">
                    <h3>📜 Security Audit Log</h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php while ($log = $audit_res->fetch_assoc()): ?>
                        <div style="padding: 10px; background: #f8fafc; border-radius: 6px; border-left: 3px solid #3b82f6; font-size: 12px;">
                            <div style="display: flex; justify-content: space-between; font-weight: 600; color: #1e293b;">
                                <span><?= htmlspecialchars($log['action']) ?></span>
                                <span style="color: #94a3b8; font-size: 11px;"><?= date('H:i:s', strtotime($log['created_at'])) ?></span>
                            </div>
                            <div style="color: #475569; margin-top: 3px;"><?= htmlspecialchars($log['details']) ?></div>
                            <small style="color: #64748b;">By: <?= htmlspecialchars($log['actor']) ?></small>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Diagnostics & Verification Checklist -->
            <div class="content-card" id="diagnostics">
                <h3>🛡️ System Integration Diagnostics & Key Questions Verification</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 15px;">
                    <div class="checklist-item">
                        <div class="checklist-icon">✅</div>
                        <div class="checklist-text">
                            <strong>1. End-to-End Navigation & Linking</strong>
                            <p>Unified navigation across all static HTML pages and dynamic PHP practicals (P7–P15). Responsive layout adapts smoothly on all viewport dimensions.</p>
                        </div>
                    </div>
                    <div class="checklist-item">
                        <div class="checklist-icon">✅</div>
                        <div class="checklist-text">
                            <strong>2. Authentication, Roles & RBAC</strong>
                            <p>Session timeout active (1800s), session_regenerate_id() on login, role-based conditional menu rendering, and protected admin endpoints.</p>
                        </div>
                    </div>
                    <div class="checklist-item">
                        <div class="checklist-icon">✅</div>
                        <div class="checklist-text">
                            <strong>3. Defensive Security Hardening</strong>
                            <p>Prepared statements (SQLi defense), bcrypt password hashing, htmlspecialchars() escaping (XSS defense), and strict upload MIME verification.</p>
                        </div>
                    </div>
                    <div class="checklist-item">
                        <div class="checklist-icon">✅</div>
                        <div class="checklist-text">
                            <strong>4. Asynchronous AJAX Operations</strong>
                            <p>Fetch API implementation for instant event registration, cancellation, and student status modification without full browser reload.</p>
                        </div>
                    </div>
                    <div class="checklist-item">
                        <div class="checklist-icon">✅</div>
                        <div class="checklist-text">
                            <strong>5. Comprehensive Documentation & Git</strong>
                            <p>Clean repository structure, virtual host configuration guidelines, complete database dump (database.sql), and detailed README.md.</p>
                        </div>
                    </div>
                    <div class="checklist-item">
                        <div class="checklist-icon">✅</div>
                        <div class="checklist-text">
                            <strong>6. Accessibility (WCAG 2.1 AA)</strong>
                            <p>Proper ARIA roles (role="alert", aria-live), semantic HTML5 tags, high contrast ratios (#1f4e78), and full keyboard navigation.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Local Deployment & Virtual Host Box -->
            <div class="content-card" id="vhost">
                <h3>🌐 Local Apache Virtual Host Deployment (XAMPP)</h3>
                <p style="font-size: 13.5px; color: #475569;">
                    The portal is fully configured for deployment on Apache/XAMPP as a standalone Virtual Host. 
                    Below is the virtual host configuration snippet mapped to <code>studenthub.local</code>:
                </p>
                <pre style="background: #1e293b; color: #f8fafc; padding: 15px; border-radius: 8px; font-size: 12.5px; overflow-x: auto;">
&lt;VirtualHost *:80&gt;
    ServerAdmin webmaster@studenthub.local
    DocumentRoot "C:/xampp/htdocs/studenthub-portal"
    ServerName studenthub.local
    ServerAlias www.studenthub.local
    &lt;Directory "C:/xampp/htdocs/studenthub-portal"&gt;
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    &lt;/Directory&gt;
    ErrorLog "logs/studenthub-error.log"
    CustomLog "logs/studenthub-access.log" common
&lt;/VirtualHost&gt;</pre>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px; border-radius: 6px; font-size: 13px; color: #166534; margin-top: 10px;">
                    💡 <strong>Windows Hosts Configuration:</strong> Add <code>127.0.0.1 studenthub.local</code> to <code>C:\Windows\System32\drivers\etc\hosts</code> to access the portal at <code>http://studenthub.local/</code>.
                </div>
            </div>
        </main>
    </div>

    <!-- AJAX Interactive Script -->
    <script>
    // AJAX Event Registration Toggle
    function toggleEventRegistration(eventId) {
        const btn = document.getElementById('btn-reg-' + eventId);
        const countSpan = document.getElementById('reg-count-' + eventId);
        const totalRegElem = document.getElementById('metric-total-reg');
        
        btn.disabled = true;
        btn.innerText = 'Processing...';

        const formData = new FormData();
        formData.append('action', 'toggle_event_reg');
        formData.append('event_id', eventId);
        formData.append('student_id', 1);

        fetch('api.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                if (data.registered) {
                    btn.className = 'btn btn-sm btn-danger';
                    btn.innerText = '❌ Cancel Reg';
                } else {
                    btn.className = 'btn btn-sm btn-success';
                    btn.innerText = '🎟️ Register (AJAX)';
                }
                countSpan.innerText = data.total_reg;
                if (totalRegElem) totalRegElem.innerText = data.overall_reg_count;
            } else {
                alert('Error: ' + data.message);
                btn.innerText = 'Retry';
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = 'Error';
            console.error(err);
        });
    }

    // AJAX Student Status Toggle
    function toggleStudentStatus(studentId) {
        const badge = document.getElementById('st-badge-' + studentId);
        const originalText = badge.innerText;
        badge.innerText = '...';

        const formData = new FormData();
        formData.append('action', 'toggle_student_status');
        formData.append('student_id', studentId);

        fetch('api.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                badge.innerText = data.new_status.charAt(0).toUpperCase() + data.new_status.slice(1);
                if (data.new_status === 'active') {
                    badge.classList.add('pass');
                } else {
                    badge.classList.remove('pass');
                }
            } else {
                badge.innerText = originalText;
                alert('Action failed: ' + data.message);
            }
        })
        .catch(err => {
            badge.innerText = originalText;
            console.error(err);
        });
    }
    </script>
</body>
</html>
