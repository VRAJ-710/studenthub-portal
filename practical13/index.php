<!DOCTYPE html>
<html>
<head>
    <title>Practical 13 - AJAX CRUD</title>
    <style>
        body { font-family: Arial; margin: 30px; background: #f5f5f5; }
        h1, h2 { color: #333; }
        #form-section { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 450px; margin-bottom: 20px; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, select { width: 100%; padding: 7px; margin-top: 3px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { margin-top: 12px; padding: 8px 18px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { opacity: 0.9; }
        .btn-danger { background: #f44336; }
        .btn-edit { background: #2196F3; }
        .btn-cancel { background: #999; }
        table { border-collapse: collapse; width: 100%; background: white; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        #msg { font-weight: bold; margin: 10px 0; }
        .success { color: green; }
        .error { color: red; }
        #loading { display: none; color: #2196F3; font-weight: bold; }
        .search-box { margin-bottom: 15px; }
        .search-box input { max-width: 300px; }
    </style>
</head>
<body>

<h1>Practical 13: AJAX CRUD (Fetch API)</h1>
<div id="msg"></div>
<div id="loading">Loading...</div>

<div id="form-section">
    <h2 id="form-title">Add Student</h2>
    <input type="hidden" id="student_id">
    <label>Name</label>
    <input type="text" id="name">
    <label>Email</label>
    <input type="email" id="email">
    <label>Course</label>
    <select id="course">
        <option value="BCA">BCA</option>
        <option value="MCA">MCA</option>
        <option value="MSc IT">MSc IT</option>
    </select>
    <label>Phone</label>
    <input type="text" id="phone">
    <button onclick="saveStudent()">Save</button>
    <button class="btn-cancel" onclick="resetForm()" style="display:none;" id="cancelBtn">Cancel</button>
</div>

<h2>Student List</h2>
<div class="search-box">
    <input type="text" id="searchBox" placeholder="Search by name or email..." oninput="loadStudents()">
</div>
<table>
    <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Course</th><th>Phone</th><th>Actions</th></tr></thead>
    <tbody id="studentTable"></tbody>
</table>

<script>
var apiUrl = 'api.php';
var debounceTimer;

function showMsg(text, type) {
    var el = document.getElementById('msg');
    el.textContent = text;
    el.className = type;
    setTimeout(function() { el.textContent = ''; }, 3000);
}

function loadStudents() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function() {
        document.getElementById('loading').style.display = 'block';
        fetch(apiUrl + '?action=list')
            .then(function(r) { return r.json(); })
            .then(function(res) {
                document.getElementById('loading').style.display = 'none';
                var search = document.getElementById('searchBox').value.toLowerCase();
                var tbody = document.getElementById('studentTable');
                tbody.innerHTML = '';
                var data = res.data.filter(function(s) {
                    if (!search) return true;
                    return s.name.toLowerCase().indexOf(search) !== -1 || s.email.toLowerCase().indexOf(search) !== -1;
                });
                data.forEach(function(s) {
                    tbody.innerHTML += '<tr><td>' + s.student_id + '</td><td>' + s.name + '</td><td>' + s.email + '</td><td>' + s.course + '</td><td>' + (s.phone || '') + '</td><td><button class="btn-edit" onclick="editStudent(' + s.student_id + ',\'' + s.name.replace(/'/g, "\\'") + '\',\'' + s.email + '\',\'' + s.course + '\',\'' + (s.phone || '') + '\')">Edit</button> <button class="btn-danger" onclick="deleteStudent(' + s.student_id + ')">Delete</button></td></tr>';
                });
            })
            .catch(function() {
                document.getElementById('loading').style.display = 'none';
                showMsg('Failed to load students.', 'error');
            });
    }, 300);
}

function saveStudent() {
    var id = document.getElementById('student_id').value;
    var data = {
        name: document.getElementById('name').value.trim(),
        email: document.getElementById('email').value.trim(),
        course: document.getElementById('course').value,
        phone: document.getElementById('phone').value.trim()
    };

    if (!data.name || !data.email) {
        showMsg('Name and email are required.', 'error');
        return;
    }

    var action = id ? 'update' : 'add';
    if (id) data.student_id = parseInt(id);

    fetch(apiUrl + '?action=' + action, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        showMsg(res.message, res.status);
        if (res.status === 'success') {
            resetForm();
            loadStudents();
        }
    })
    .catch(function() { showMsg('Request failed.', 'error'); });
}

function editStudent(id, name, email, course, phone) {
    document.getElementById('student_id').value = id;
    document.getElementById('name').value = name;
    document.getElementById('email').value = email;
    document.getElementById('course').value = course;
    document.getElementById('phone').value = phone;
    document.getElementById('form-title').textContent = 'Edit Student';
    document.getElementById('cancelBtn').style.display = 'inline-block';
}

function deleteStudent(id) {
    if (!confirm('Delete this student?')) return;
    fetch(apiUrl + '?action=delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ student_id: id })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        showMsg(res.message, res.status);
        loadStudents();
    })
    .catch(function() { showMsg('Delete failed.', 'error'); });
}

function resetForm() {
    document.getElementById('student_id').value = '';
    document.getElementById('name').value = '';
    document.getElementById('email').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('form-title').textContent = 'Add Student';
    document.getElementById('cancelBtn').style.display = 'none';
}

loadStudents();
</script>

</body>
</html>
