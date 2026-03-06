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

/* ======================
   VALIDASI USER
====================== */

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

/* ======================
   AMBIL HISTORY
====================== */

$stmt = $conn->prepare("
SELECT 
o.id AS order_id,
o.status,
o.created_at,
s.id AS sticker_id,
s.name,
s.image,
oi.price
FROM order_items oi
JOIN orders o ON oi.order_id = o.id
JOIN stickers s ON oi.sticker_id = s.id
WHERE o.user_id = ?
ORDER BY o.created_at DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$history = [];

while ($row = $result->fetch_assoc()) {

    $history[] = [
        "order_id" => (int)$row['order_id'],
        "status" => $row['status'],
        "date" => $row['created_at'],
        "sticker_id" => (int)$row['sticker_id'],
        "name" => $row['name'],
        "image" => $row['image'],
        "price" => (int)$row['price']
    ];
}

$stmt->close();

echo json_encode([
    "status" => "success",
    "data" => $history
]);

$conn->close();