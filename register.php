<?php
header('Content-Type: application/json');
require 'config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]);
    exit;
}

$name = strtolower(trim($data['name'] ?? ''));
$raw_password = $data['password'] ?? '';

if ($name === '' || $raw_password === '') {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Name and password required'
    ]);
    exit;
}

if (!preg_match('/^[a-z0-9_]{3,20}$/', $name)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid username format'
    ]);
    exit;
}

if (strlen($raw_password) < 6) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Password must be at least 6 characters'
    ]);
    exit;
}

$role = 'user';

$check = $conn->prepare("
    SELECT id
    FROM users
    WHERE name = ?
    LIMIT 1
");

$check->bind_param("s", $name);
$check->execute();
$exists = $check->get_result()->num_rows > 0;
$check->close();

if ($exists) {
    http_response_code(409);
    echo json_encode([
        'status' => 'error',
        'message' => 'Username already exists'
    ]);
    exit;
}

$password = $raw_password;

$stmt = $conn->prepare("
    INSERT INTO users (name, password, role)
    VALUES (?, ?, ?)
");

$stmt->bind_param("sss", $name, $password, $role);

if ($stmt->execute()) {

    http_response_code(201);

    echo json_encode([
        'status' => 'success',
        'message' => 'User registered'
    ]);

} else {

    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => 'Registration failed'
    ]);
}

$stmt->close();
$conn->close();