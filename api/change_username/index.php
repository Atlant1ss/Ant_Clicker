<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Method not allowed."
    ]);

    exit();
}


if (!isset($_SESSION["user_id"])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "User not logged in."
    ]);

    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data) || !isset($data["password"]) || !isset($data["new_username"]) || !is_string($data["password"]) || !is_string($data["new_username"])) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Missing or invalid required fields."
    ]);

    exit();
}

$password = $data["password"];
$new_username = trim($data["new_username"]);
$user_id = $_SESSION["user_id"];

if (empty($new_username)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Username cannot be empty."
    ]);
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "ant_clicker");

if (!$conn) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);

    exit();
}

mysqli_set_charset($conn, "utf8mb4");

try {

    $stmt = mysqli_prepare($conn, "SELECT password_hash FROM users WHERE id = ?");

    mysqli_stmt_bind_param($stmt, "i", $user_id);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$user || !password_verify($password, $user["password_hash"])) {
        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Incorrect password."
        ]);

        mysqli_close($conn);
        exit();
    }

    $stmt = mysqli_prepare($conn, "UPDATE users SET username = ? WHERE id = ?");

    mysqli_stmt_bind_param($stmt, "si", $new_username, $user_id);

    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) < 0) {
        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Failed to change username."
        ]);

        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        exit();
    }

    mysqli_stmt_close($stmt);

    echo json_encode([
        "success" => true,
        "message" => "Username changed!.",
        "new_username" => $new_username
    ]);

} catch (Throwable $error) {

    error_log($error->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "An internal error occurred."
    ]);
}

mysqli_close($conn);

?>