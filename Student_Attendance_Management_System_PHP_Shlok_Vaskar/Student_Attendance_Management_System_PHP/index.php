<?php
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

$today = date('Y-m-d');
$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, ['dashboard', 'students', 'attendance', 'reports'], true)) {
    $page = 'dashboard';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_token();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $roll = trim($_POST['roll'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $class = trim($_POST['class'] ?? '');

        if (!$roll || !$name || !$class) {
            msg('error', 'Please fill all fields.');
        } else {
            try {
                mongo_insert('students', [
                    'roll_no' => $roll,
                    'student_name' => $name,
                    'class_name' => $class,
                ]);
                msg('ok', 'Student added.');
            } catch (Throwable $e) {
                if ($e->getCode() === 11000) {
                    msg('error', 'Could not add student. Roll number may already exist.');
                } else {
                    error_log('Could not add student: ' . $e->getMessage());
                    msg('error', 'Could not add student because of a database error.');
                }
            }
        }
        go('?page=students');
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        if (!MongoDB\BSON\ObjectId::isValid($id)) {
            msg('error', 'Invalid student.');
            go('?page=students');
        }

        $studentId = new MongoDB\BSON\ObjectId($id);
        try {
            mongo_delete('attendance', ['student_id' => $studentId], true);
            mongo_delete('students', ['_id' => $studentId]);
            msg('ok', 'Student deleted.');
        } catch (Throwable $e) {
            error_log('Could not delete student: ' . $e->getMessage());
            msg('error', 'Could not delete student because of a database error.');
        }
        go('?page=students');
    }

    if ($action === 'save') {
        $date = $_POST['date'] ?? $today;
        $statuses = $_POST['status'] ?? [];
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            msg('error', 'Invalid date.');
            go('?page=attendance');
        }
        if (!is_array($statuses)) {
            msg('error', 'Invalid attendance data.');
            go('?page=attendance&date=' . urlencode($date));
        }

        try {
            $validStudentIds = [];
            foreach (mongo_find('students', [], ['projection' => ['_id' => 1]]) as $student) {
                $validStudentIds[(string) $student['_id']] = true;
            }

            foreach ($statuses as $id => $status) {
                $id = (string) $id;
                if (
                    MongoDB\BSON\ObjectId::isValid($id)
                    && isset($validStudentIds[$id])
                    && in_array($status, ['Present', 'Absent'], true)
                ) {
                    $studentId = new MongoDB\BSON\ObjectId($id);
                    mongo_update_one(
                        'attendance',
                        ['student_id' => $studentId, 'attendance_date' => $date],
                        ['$set' => ['status' => $status]],
                        ['upsert' => true]
                    );
                }
            }
            msg('ok', 'Attendance saved for ' . $date . '.');
        } catch (Throwable $e) {
            error_log('Could not save attendance: ' . $e->getMessage());
            msg('error', 'Could not save attendance because of a database error.');
        }
        go('?page=attendance&date=' . urlencode($date));
    }
}

$date = $_GET['date'] ?? $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = $today;
}
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$total = mongo_count('students');
$counts = [
    'Present' => mongo_count('attendance', [
        'attendance_date' => $today,
        'status' => 'Present',
    ]),
    'Absent' => mongo_count('attendance', [
        'attendance_date' => $today,
        'status' => 'Absent',
    ]),
];
$marked = $counts['Present'] + $counts['Absent'];
$rate = $marked ? round($counts['Present'] / $marked * 100) : 0;

$students = [];
foreach (mongo_find('students', [], ['sort' => ['roll_no' => 1]]) as $student) {
    $students[] = [
        'id' => (string) $student['_id'],
        'roll_no' => $student['roll_no'],
        'student_name' => $student['student_name'],
        'class_name' => $student['class_name'],
    ];
}

$rows = [];
if ($page === 'attendance' || $page === 'reports') {
    $attendanceByStudent = [];
    foreach (mongo_find('attendance', ['attendance_date' => $date]) as $record) {
        $attendanceByStudent[(string) $record['student_id']] = $record['status'];
    }

    foreach ($students as $student) {
        $student['status'] = $attendanceByStudent[$student['id']] ?? null;
        $rows[] = $student;
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Student Attendance Management</title><link rel="stylesheet" href="assets/style.css"></head><body>
<div class="layout"><aside><div class="logo"><b>SA</b><span>AttendEase<small>Student Portal</small></span></div>
<nav><a class="<?=$page==='dashboard'?'active':''?>" href="index.php">▦ Dashboard</a><a class="<?=$page==='students'?'active':''?>" href="?page=students">♙ Students</a><a class="<?=$page==='attendance'?'active':''?>" href="?page=attendance">✓ Mark Attendance</a><a class="<?=$page==='reports'?'active':''?>" href="?page=reports">▤ Reports</a></nav>
<div class="sidefoot">TYIT Mini Project<br>PHP · MongoDB</div></aside><main>
<header><div><small>STUDENT ATTENDANCE</small><h1><?=ucfirst($page)?></h1></div><div class="user"><b>SV</b><span><strong>Shlok Vaskar</strong><small>Roll No. 19062</small></span></div></header>
<?php if($flash):?><div class="alert <?=h($flash[0])?>"><?=h($flash[1])?></div><?php endif;?>
<?php if($page==='dashboard'):?>
<section class="hero"><small>OVERVIEW</small><h2>Welcome! Here's your attendance overview.</h2><p>Manage student records and track daily attendance.</p><strong><?=date('d M Y')?></strong></section>
<div class="stats"><article><small>Total Students</small><b><?=$total?></b><span>Registered students</span></article><article><small>Present Today</small><b><?=$counts['Present']?></b><span>Marked present</span></article><article><small>Absent Today</small><b><?=$counts['Absent']?></b><span>Marked absent</span></article><article><small>Attendance Rate</small><b><?=$rate?>%</b><span>Of marked attendance</span></article></div>
<section class="panel"><h3>Quick Actions</h3><div class="actions"><a href="?page=students">＋ Add / Manage Students</a><a href="?page=attendance">✓ Mark Attendance</a><a href="?page=reports">▤ View Reports</a></div></section>
<section class="panel"><h3>Student Records</h3><?php if(!$students):?><p class="empty">No students added. Go to Students to add records.</p><?php else:?><div class="tablewrap"><table><tr><th>Roll No.</th><th>Student Name</th><th>Class</th></tr><?php foreach(array_slice($students,0,6) as $s):?><tr><td><?=h($s['roll_no'])?></td><td><?=h($s['student_name'])?></td><td><?=h($s['class_name'])?></td></tr><?php endforeach;?></table></div><?php endif;?></section>
<?php elseif($page==='students'):?>
<section class="panel"><h3>Add New Student</h3><form method="post" class="form"><input type="hidden" name="csrf" value="<?=h(token())?>"><input type="hidden" name="action" value="add"><label>Roll Number<input name="roll" required placeholder="e.g. 19062"></label><label>Student Name<input name="name" required placeholder="Full name"></label><label>Class / Division<input name="class" required value="TYIT"></label><button>Add Student</button></form></section>
<section class="panel"><h3>Student Records (<?=count($students)?>)</h3><?php if(!$students):?><p class="empty">No student records found.</p><?php else:?><div class="tablewrap"><table><tr><th>Roll No.</th><th>Name</th><th>Class</th><th>Action</th></tr><?php foreach($students as $s):?><tr><td><?=h($s['roll_no'])?></td><td><?=h($s['student_name'])?></td><td><?=h($s['class_name'])?></td><td><form method="post" onsubmit="return confirm('Delete this student?')"><input type="hidden" name="csrf" value="<?=h(token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=h($s['id'])?>"><button class="danger">Delete</button></form></td></tr><?php endforeach;?></table></div><?php endif;?></section>
<?php elseif($page==='attendance'):?>
<section class="panel"><h3>Daily Attendance</h3><form method="get" class="dateform"><input type="hidden" name="page" value="attendance"><label>Select Date<input type="date" name="date" value="<?=h($date)?>"></label><button class="secondary">Load Date</button></form>
<?php if(!$rows):?><p class="empty">No students found. Add students first.</p><?php else:?><form method="post"><input type="hidden" name="csrf" value="<?=h(token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="date" value="<?=h($date)?>"><div class="tablewrap"><table><tr><th>Roll No.</th><th>Student Name</th><th>Class</th><th>Status</th></tr><?php foreach($rows as $r):?><tr><td><?=h($r['roll_no'])?></td><td><?=h($r['student_name'])?></td><td><?=h($r['class_name'])?></td><td><select name="status[<?=h($r['id'])?>]"><option value="Present" <?=$r['status']==='Present'?'selected':''?>>Present</option><option value="Absent" <?=$r['status']==='Absent'?'selected':''?>>Absent</option></select></td></tr><?php endforeach;?></table></div><button>Save Attendance</button></form><?php endif;?></section>
<?php else: $p=0;$a=0;foreach($rows as $r){if($r['status']==='Present')$p++;if($r['status']==='Absent')$a++;}$m=$p+$a;$pr=$m?round($p/$m*100,1):0;?>
<section class="panel"><h3>Attendance Report</h3><form method="get" class="dateform"><input type="hidden" name="page" value="reports"><label>Report Date<input type="date" name="date" value="<?=h($date)?>"></label><button class="secondary">Generate</button></form><div class="reportstats"><div>Present<b><?=$p?></b></div><div>Absent<b><?=$a?></b></div><div>Attendance Rate<b><?=$pr?>%</b></div></div>
<?php if(!$rows):?><p class="empty">No student records available.</p><?php else:?><div class="tablewrap"><table><tr><th>Roll No.</th><th>Name</th><th>Class</th><th>Status</th></tr><?php foreach($rows as $r):?><tr><td><?=h($r['roll_no'])?></td><td><?=h($r['student_name'])?></td><td><?=h($r['class_name'])?></td><td><span class="pill <?=$r['status']==='Present'?'present':($r['status']==='Absent'?'absent':'pending')?>"><?=h($r['status']??'Not marked')?></span></td></tr><?php endforeach;?></table></div><?php endif;?></section>
<?php endif;?>
<footer>Student Attendance Management System <span>Shlok Vaskar · Roll No. 19062 · TYIT</span></footer></main></div></body></html>
