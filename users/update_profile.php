<?php

header('Content-Type: application/json');
require '../config/db.php';

function getBearerToken() {

    $headers = getallheaders();

    if (!isset($headers['Authorization'])) {
        return null;
    }

    if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
        return $matches[1];
    }

    return null;
}

$token = getBearerToken();

if (!$token) {

    http_response_code(401);

    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized"
    ]);

    exit;
}

/* CEK USER */

$stmt = $conn->prepare("
SELECT id
FROM users
WHERE token = ?
LIMIT 1
");

$stmt->bind_param("s", $token);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {

    http_response_code(401);

    echo json_encode([
        "status" => "error",
        "message" => "Invalid token"
    ]);

    exit;
}

$user_id = $user['id'];

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data['name'] ?? '');
$password = $data['password'] ?? '';

if ($name === '' || $password === '') {

    http_response_code(400);

    echo json_encode([
        "status" => "error",
        "message" => "Name and password required"
    ]);

    exit;
}

$stmt = $conn->prepare("
UPDATE users
SET name = ?, password = ?
WHERE id = ?
");

$stmt->bind_param("ssi", $name, $password, $user_id);
$stmt->execute();
$stmt->close();

echo json_encode([
    "status" => "success",
    "message" => "Profile updated"
]);

$conn->close();