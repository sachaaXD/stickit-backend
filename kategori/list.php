<?php

header('Content-Type: application/json');
require '../config/db.php';

$result = $conn->query("
SELECT id, name
FROM categories
ORDER BY name ASC
");

$categories = [];

while ($row = $result->fetch_assoc()) {

    $categories[] = [
        "id" => (int)$row['id'],
        "name" => $row['name']
    ];
}

echo json_encode([
    "status" => "success",
    "data" => $categories
]);

$conn->close();