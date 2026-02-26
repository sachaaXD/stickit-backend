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

$name = trim($data['name'] ?? '');
$raw_password = $data['password'] ?? '';

if ($name === '' || $raw_password === '') {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Name and password required'
    ]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, password, role
    FROM users
    WHERE name = ?
    LIMIT 1
");

$stmt->bind_param("s", $name);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

if (!$user || $raw_password !== $user['password']) {

    http_response_code(401);

    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid credentials'
    ]);

    $conn->close();
    exit;
}

$token = bin2hex(random_bytes(32));

// simpan token
$update = $conn->prepare("
    UPDATE users
    SET token = ?
    WHERE id = ?
");

$update->bind_param("si", $token, $user['id']);
$update->execute();
$update->close();

echo json_encode([
    'status' => 'success',
    'message' => 'Login successful',
    'user_id' => (int)$user['id'],
    'role' => $user['role'],
    'token' => $token
]);

$conn->close();

