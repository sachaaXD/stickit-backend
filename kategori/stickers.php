<?php

header('Content-Type: application/json');
require '../config/db.php';

$category_id = intval($_GET['id'] ?? 0);

if (!$category_id) {

    http_response_code(400);

    echo json_encode([
        "status" => "error",
        "message" => "Category id required"
    ]);

    exit;
}

$stmt = $conn->prepare("
SELECT
id,
name,
image,
price
FROM stickers
WHERE category_id = ?
ORDER BY id DESC
");

$stmt->bind_param("i", $category_id);
$stmt->execute();

$result = $stmt->get_result();

$stickers = [];

while ($row = $result->fetch_assoc()) {

    $stickers[] = [
        "id" => (int)$row['id'],
        "name" => $row['name'],
        "image" => $row['image'],
        "price" => (int)$row['price']
    ];
}

$stmt->close();

echo json_encode([
    "status" => "success",
    "data" => $stickers
]);

$conn->close();