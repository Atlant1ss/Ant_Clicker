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

if (!is_array($data) || !isset($data["password"]) || !is_string($data["password"])) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Missing or invalid required fields."
    ]);

    exit();
}

$password = $data["password"];
$user_id = $_SESSION["user_id"];

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

    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");

    mysqli_stmt_bind_param($stmt, "i", $user_id);

    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) != 1) {
        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Failed to delete account."
        ]);

        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        exit();
    }

    mysqli_stmt_close($stmt);

    session_unset();

    $cookie_params = session_get_cookie_params();

    setcookie(session_name(), "", [
        "expires" => time() - 3600,
        "path" => $cookie_params["path"],
        "domain" => $cookie_params["domain"],
        "secure" => $cookie_params["secure"],
        "httponly" => $cookie_params["httponly"],
        "samesite" => $cookie_params["samesite"]
    ]);

    session_destroy();

    echo json_encode([
        "success" => true,
        "message" => "Account deleted successfully."
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