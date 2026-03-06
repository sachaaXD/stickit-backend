<?php

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
    echo "Unauthorized";
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
    echo "Invalid token";
    exit;
}

$user_id = $user['id'];

/* ======================
   AMBIL ID STIKER
====================== */

$sticker_id = intval($_GET['id'] ?? 0);

if (!$sticker_id) {
    http_response_code(400);
    echo "Sticker id required";
    exit;
}

/* ======================
   CEK APAKAH USER BELI STIKER
====================== */

$stmt = $conn->prepare("
SELECT s.image
FROM order_items oi
JOIN orders o ON oi.order_id = o.id
JOIN stickers s ON oi.sticker_id = s.id
WHERE
o.user_id = ?
AND o.status = 'paid'
AND oi.sticker_id = ?
LIMIT 1
");

$stmt->bind_param("ii", $user_id, $sticker_id);
$stmt->execute();

$sticker = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sticker) {
    http_response_code(403);
    echo "You have not purchased this sticker";
    exit;
}

/* ======================
   FILE PATH
====================== */

$file = "../uploads/stickers/" . $sticker['image'];

if (!file_exists($file)) {
    http_response_code(404);
    echo "File not found";
    exit;
}

/* ======================
   DOWNLOAD FILE
====================== */

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.$sticker['image'].'"');
header('Content-Length: ' . filesize($file));

readfile($file);
exit;