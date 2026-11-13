
<?php

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

try {
    $sql = "
        SELECT
            users.username,
            COALESCE(game_states.score, 0) AS score
        FROM users
        LEFT JOIN game_states
            ON users.id = game_states.user_id
        ORDER BY score DESC, users.username ASC
        LIMIT 10
    ";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        throw new Exception(mysqli_error($conn));
    }

    $leaderboard = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $leaderboard[] = [
            "username" => $row["username"],
            "score" => (int) $row["score"]
        ];
    }

    echo json_encode([
        "success" => true,
        "leaderboard" => $leaderboard
    ]);

    mysqli_free_result($result);

} catch (Throwable $error) {
    error_log($error->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Could not load leaderboard."
    ]);
}

mysqli_close($conn);

?>
