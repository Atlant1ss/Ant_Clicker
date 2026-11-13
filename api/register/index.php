
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

if (
    !isset($data["username"]) ||
    !isset($data["password"]) ||
    !isset($data["email"])
) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields."
    ]);
    exit();
}

$username = trim($data["username"]);
$email = trim($data["email"]);
$password = $data["password"];

if (
    strlen($username) < 3 ||
    strlen($username) > 20 ||
    !preg_match('/^[a-zA-Z0-9_]+$/', $username)
) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid username."
    ]);
    exit();
}

if (
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($email) > 255
) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid email."
    ]);
    exit();
}

if (!is_string($password) || strlen($password) < 8) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 8 characters long."
    ]);
    exit();
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$ant_types = [
    "worker",
    "queen",
    "soldier",
    "explorer",
    "witch",
    "corporate"
];

try {
    mysqli_begin_transaction($conn);

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO users (username, email, password_hash)
         VALUES (?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "sss",
        $username,
        $email,
        $password_hash
    );

    mysqli_stmt_execute($stmt);

    $user_id = mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO game_states (user_id, cur_ants, score)
         VALUES (?, 0, 0)"
    );

    mysqli_stmt_bind_param($stmt, "i", $user_id);

    mysqli_stmt_execute($stmt);

    $game_state_id = mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO ant_inventory (game_state_id, ant_type, quantity)
         VALUES (?, ?, 0)"
    );

    foreach ($ant_types as $ant_type) {
        mysqli_stmt_bind_param(
            $stmt,
            "is",
            $game_state_id,
            $ant_type
        );

        mysqli_stmt_execute($stmt);
    }

    mysqli_stmt_close($stmt);

    mysqli_commit($conn);

    session_regenerate_id(true);

    $_SESSION["user_id"] = $user_id;
    $_SESSION["username"] = $username;

    echo json_encode([
        "success" => true,
        "message" => "User registered successfully."
    ]);

} catch (Throwable $error) {

    mysqli_rollback($conn);

    error_log($error->getMessage());

    if (
        $error instanceof mysqli_sql_exception &&
        $error->getCode() === 1062
    ) {
        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "Username or email already exists."
        ]);
    } else {
        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "An internal error occurred."
        ]);
    }
}

mysqli_close($conn);

?>
