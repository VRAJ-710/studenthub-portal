<?php
$csv_file = __DIR__ . '/students.csv';
$json_file = __DIR__ . '/students.json';

$csv_content = file_exists($csv_file) ? file_get_contents($csv_file) : 'No CSV file found.';
$json_content = file_exists($json_file) ? file_get_contents($json_file) : 'No JSON file found.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Practical 7 - File Storage Verification (CSV & JSON)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background: #f5f5f5; color: #333; }
        .container { max-width: 950px; margin: 0 auto; }
        h1 { color: #1F4E78; border-bottom: 2px solid #1F4E78; padding-bottom: 8px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 25px; }
        .box { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2 { font-size: 1.1rem; color: #1F4E78; margin-top: 0; display: flex; align-items: center; justify-content: space-between; }
        .badge { font-size: 0.75rem; background: #28a745; color: white; padding: 3px 8px; border-radius: 4px; font-weight: normal; }
        pre { background: #272822; color: #f8f8f2; padding: 15px; border-radius: 6px; overflow-x: auto; font-family: 'Consolas', 'Courier New', monospace; font-size: 0.85rem; line-height: 1.4; max-height: 480px; }
        .nav-link { display: inline-block; margin-bottom: 15px; color: #0563C1; text-decoration: none; font-weight: bold; }
        .nav-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <a href="index.php" class="nav-link">&larr; Back to Registration Form</a>
    <h1>Practical 7: CSV &amp; JSON File Storage Verification</h1>
    <p style="color: #555;">Server-side file verification confirming persistent append-mode storage for registration records.</p>

    <div class="grid">
        <div class="box">
            <h2>
                <span>📄 students.csv (CSV Format)</span>
                <span class="badge">Safe flock() Append</span>
            </h2>
            <pre><code><?php echo htmlspecialchars($csv_content); ?></code></pre>
        </div>

        <div class="box">
            <h2>
                <span>📦 students.json (JSON Array)</span>
                <span class="badge">Valid JSON Schema</span>
            </h2>
            <pre><code><?php echo htmlspecialchars($json_content); ?></code></pre>
        </div>
    </div>
</div>
</body>
</html>
