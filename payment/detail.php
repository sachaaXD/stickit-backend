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

$order_id = intval($_GET['order_id'] ?? 0);

if (!$order_id) {

    http_response_code(400);

    echo json_encode([
        "status" => "error",
        "message" => "Order id required"
    ]);

    exit;
}

/* CEK ORDER */

$stmt = $conn->prepare("
SELECT id, total, status
FROM orders
WHERE id = ?
AND user_id = ?
LIMIT 1
");

$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();

$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {

    http_response_code(404);

    echo json_encode([
        "status" => "error",
        "message" => "Order not found"
    ]);

    exit;
}

/* AMBIL ITEM */

$stmt = $conn->prepare("
SELECT
s.name,
s.image,
oi.price
FROM order_items oi
JOIN stickers s ON oi.sticker_id = s.id
WHERE oi.order_id = ?
");

$stmt->bind_param("i", $order_id);
$stmt->execute();

$result = $stmt->get_result();

$items = [];

while ($row = $result->fetch_assoc()) {

    $items[] = [
        "name" => $row['name'],
        "image" => $row['image'],
        "price" => (int)$row['price']
    ];
}

$stmt->close();

echo json_encode([
    "status" => "success",
    "data" => [
        "order_id" => (int)$order['id'],
        "status" => $order['status'],
        "total" => (int)$order['total'],
        "qris_image" => "payments/qris.png",
        "items" => $items
    ]
]);

$conn->close();