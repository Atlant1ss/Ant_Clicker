<?php

$conn = mysqli_connect("localhost", "root", "", "ant_clicker");


if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

session_start();

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Method not allowed."
    ]);

    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid JSON data."]);
    exit();
}

if (!isset($data['cur_ants']) || !isset($data['ants_count']) || !is_array($data['ants_count'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit();
}

$ant_types = [
    "worker",
    "queen",
    "soldier",
    "explorer",
    "witch",
    "corporate"
];

if (!(is_int($cur_ants) || is_string($cur_ants)) || !preg_match('/^\d+$/', (string) $cur_ants)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid ant balance."
    ]);

    exit;
}

foreach ($ant_types as $type) {
    if (!isset($ants_count[$type]) || !is_int($ants_count[$type]) || $ants_count[$type] < 0) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid ant inventory."
        ]);

        exit;
    }
}

$user_id = $_SESSION['user_id'];

try {
    mysqli_begin_transaction($conn);

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO game_states (user_id, cur_ants, score)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE
            cur_ants = VALUES(cur_ants),
            score = GREATEST(score, VALUES(cur_ants))"
    );
    
    mysqli_stmt_bind_param($stmt, "iss", $user_id, $cur_ants, $cur_ants);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id FROM game_states WHERE user_id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    mysqli_stmt_bind_result($stmt, $game_state_id);
    mysqli_stmt_fetch($stmt);

    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO ant_inventory
            (game_state_id, ant_type, quantity)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)"
    );

    foreach ($ant_types as $type) {
        $quantity = $ants_count[$type];

        mysqli_stmt_bind_param(
            $stmt,
            "isi",
            $game_state_id,
            $type,
            $quantity
        );

        mysqli_stmt_execute($stmt);
    }

    mysqli_stmt_close($stmt);

    mysqli_commit($conn);

    echo json_encode([
        "success" => true,
        "message" => "Game state saved successfully."
    ]);

} catch (Throwable $error) {
    mysqli_rollback($conn);

    error_log($error->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Could not save game."
    ]);
}

mysqli_close($conn);
?>