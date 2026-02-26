<?php

function get_bearer_token()
{
    $headers = getallheaders();

    if (!isset($headers['Authorization'])) {
        return null;
    }

    if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
        return $matches[1];
    }

    return null;
}

function get_user_from_token($conn)
{
    $token = get_bearer_token();

    if (!$token) {
        http_response_code(401);

        echo json_encode([
        'status' => 'error',
        'message' => 'Token required'
    ]);
    exit;
    }

    $stmt = $conn->prepare("
        SELECT id, name, role
        FROM users
        WHERE token = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    $user = $result->fetch_assoc();

    $stmt->close();

    if (!$user) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid token'
        ]);
        exit;
    }

    return $user;
}