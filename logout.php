<?php

header("Content-Type: application/json");

require_once "../config/database.php";
require_once "../middleware/auth.php";

$user = get_user_from_token($conn);

$stmt = $conn->prepare("
    UPDATE users
    SET token = NULL
    WHERE id = ?
");

$stmt->bind_param("i", $user['id']);
$stmt->execute();

$stmt->close();

echo json_encode([
    "status" => "success",
    "message" => "Logout berhasil"
]);