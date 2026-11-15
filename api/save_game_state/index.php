
<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

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

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized."
    ]);
    mysqli_close($conn);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed."
    ]);
    mysqli_close($conn);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON data."
    ]);
    mysqli_close($conn);
    exit();
}

$cur_ants = $data["cur_ants"] ?? null;
$ants_count = $data["ants_count"] ?? null;

if (
    !(is_int($cur_ants) || (is_string($cur_ants) && ctype_digit($cur_ants))) ||
    !is_array($ants_count)
) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Missing or invalid required fields."
    ]);
    mysqli_close($conn);
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

foreach ($ant_types as $type) {
    if (
        !isset($ants_count[$type]) ||
        !is_int($ants_count[$type]) ||
        $ants_count[$type] < 0
    ) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid ant inventory."
        ]);
        mysqli_close($conn);
        exit();
    }
}

$user_id = $_SESSION["user_id"];

try {
    mysqli_begin_transaction($conn);

    // Save current balance and update personal record.
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO game_states (user_id, cur_ants, score)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE
            cur_ants = VALUES(cur_ants),
            score = GREATEST(score, VALUES(cur_ants))"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "iss",
        $user_id,
        $cur_ants,
        $cur_ants
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Retrieve game state ID.
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id FROM game_states WHERE user_id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    mysqli_stmt_bind_result($stmt, $game_state_id);
    mysqli_stmt_fetch($stmt);

    mysqli_stmt_close($stmt);

    if (!$game_state_id) {
        throw new Exception("Game state not found.");
    }

    // Save each ant quantity.
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO ant_inventory
            (game_state_id, ant_type, quantity)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE
            quantity = VALUES(quantity)"
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
