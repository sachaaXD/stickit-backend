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

$order_id = intval($data['order_id'] ?? 0);

if (!$order_id) {

    http_response_code(400);

    echo json_encode([
        "status" => "error",
        "message" => "Order id required"
    ]);

    exit;
}

/* UPDATE STATUS */

$stmt = $conn->prepare("
UPDATE orders
SET status = 'paid'
WHERE id = ?
AND user_id = ?
");

$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$stmt->close();

echo json_encode([
    "status" => "success",
    "message" => "Payment confirmed"
]);

$conn->close();