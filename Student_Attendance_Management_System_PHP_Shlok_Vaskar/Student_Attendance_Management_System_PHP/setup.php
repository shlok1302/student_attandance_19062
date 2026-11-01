<?php
require __DIR__ . '/config.php';

$mongoManager->executeCommand('attendance_db', new MongoDB\Driver\Command([
    'createIndexes' => 'students',
    'indexes' => [[
        'key' => ['roll_no' => 1],
        'name' => 'roll_no_unique',
        'unique' => true,
    ]],
]));
$mongoManager->executeCommand('attendance_db', new MongoDB\Driver\Command([
    'createIndexes' => 'attendance',
    'indexes' => [
        [
            'key' => ['student_id' => 1, 'attendance_date' => 1],
            'name' => 'student_date_unique',
            'unique' => true,
        ],
        [
            'key' => ['attendance_date' => 1, 'status' => 1],
            'name' => 'date_status',
        ],
    ],
]));

$sampleStudents = [
    ['roll_no' => '19062', 'student_name' => 'Shlok Vaskar', 'class_name' => 'TYIT'],
    ['roll_no' => '19063', 'student_name' => 'Aarav Shah', 'class_name' => 'TYIT'],
    ['roll_no' => '19064', 'student_name' => 'Riya Patil', 'class_name' => 'TYIT'],
];

foreach ($sampleStudents as $student) {
    mongo_update_one(
        'students',
        ['roll_no' => $student['roll_no']],
        ['$setOnInsert' => $student],
        ['upsert' => true]
    );
}

echo "MongoDB is ready. Sample students are available.\n";
