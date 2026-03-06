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

/* VALIDASI USER */

$stmt = $conn->prepare("
SELECT id, name, role
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

echo json_encode([
    "status" => "success",
    "data" => [
        "id" => (int)$user['id'],
        "name" => $user['name'],
        "role" => $user['role']
    ]
]);

$conn->close();