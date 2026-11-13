
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

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed."
    ]);
    mysqli_close($conn);
    exit();
}

$user_id = $_SESSION["user_id"];

$ant_types = [
    "worker",
    "queen",
    "soldier",
    "explorer",
    "witch",
    "corporate"
];

$ants_count = array_fill_keys($ant_types, 0);

try {
    // Get player's game state.
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, cur_ants, score
         FROM game_states
         WHERE user_id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    mysqli_stmt_bind_result(
        $stmt,
        $game_state_id,
        $cur_ants,
        $score
    );

    $has_game_state = mysqli_stmt_fetch($stmt);

    mysqli_stmt_close($stmt);

    if ($has_game_state) {
        // Get ant inventory.
        $stmt = mysqli_prepare(
            $conn,
            "SELECT ant_type, quantity
             FROM ant_inventory
             WHERE game_state_id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $game_state_id);
        mysqli_stmt_execute($stmt);

        mysqli_stmt_bind_result(
            $stmt,
            $ant_type,
            $quantity
        );

        while (mysqli_stmt_fetch($stmt)) {
            if (array_key_exists($ant_type, $ants_count)) {
                $ants_count[$ant_type] = (int) $quantity;
            }
        }

        mysqli_stmt_close($stmt);

    } else {
        $cur_ants = 0;
        $score = 0;
    }

    echo json_encode([
        "success" => true,
        "game_state" => [
            "cur_ants" => (string) $cur_ants,
            "score" => (string) $score,
            "ants_count" => $ants_count
        ]
    ]);

} catch (Throwable $error) {
    error_log($error->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Could not load game."
    ]);
}

mysqli_close($conn);

?>
