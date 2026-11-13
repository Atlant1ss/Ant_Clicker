<?php 

session_start();

header("Content-Type: application/json; charset=UTF-8");

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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed."
    ]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON data."
    ]);
    exit();
}

if (!isset($data["username"]) || !isset($data["password"]) || !is_string($data["username"]) || !is_string($data["password"])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Missing or invalid required fields."
    ]);
    exit();
}

$username = trim($data["username"]);
$password = $data["password"];

try {

    $stmt = mysqli_prepare($conn, "SELECT id, password_hash FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$user || !password_verify($password, $user["password_hash"])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid username or password."
        ]);
        exit();
    }

    session_regenerate_id(true);

    $_SESSION["user_id"] = $user["id"];
    $_SESSION["username"] = $username;

    localStorage.setItem("username", $username);

    echo json_encode([
        "success" => true,
        "message" => "Login successful."
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