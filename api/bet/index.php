
<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

function send_response($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

function get_multiplier($mine_count, $safe_count) {
    $probability = 1.0;

    for ($i = 0; $i < $safe_count; $i++) {
        $probability *= (25 - $mine_count - $i) / (25 - $i);
    }

    if ($probability <= 0) {
        return 0;
    }

    return 0.97 / $probability;
}

function get_prize($bet, $mine_count, $safe_count) {
    $multiplier = get_multiplier($mine_count, $safe_count);

    if ($multiplier <= 0) {
        return 0;
    }

    return (int) floor($bet * $multiplier);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    send_response(405, [
        "success" => false,
        "message" => "Method not allowed."
    ]);
}

if (!isset($_SESSION["user_id"])) {
    send_response(401, [
        "success" => false,
        "message" => "Unauthorized."
    ]);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data) || !isset($data["action"]) || !is_string($data["action"])) {
    send_response(400, [
        "success" => false,
        "message" => "Invalid request."
    ]);
}

$conn = mysqli_connect("localhost", "root", "", "ant_clicker");

if (!$conn) {
    send_response(500, [
        "success" => false,
        "message" => "Database connection failed."
    ]);
}

mysqli_set_charset($conn, "utf8mb4");

$user_id = (int) $_SESSION["user_id"];
$action = $data["action"];

try {
    mysqli_begin_transaction($conn);

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, cur_ants
         FROM game_states
         WHERE user_id = ?
         FOR UPDATE"
    );

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $game_state = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$game_state) {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO game_states (user_id, cur_ants, score)
             VALUES (?, 0, 0)"
        );

        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $balance = 0;
    } else {
        if (!ctype_digit((string) $game_state["cur_ants"]) ||
            (float) $game_state["cur_ants"] > PHP_INT_MAX) {
            throw new Exception("Balance exceeds supported limit.");
        }

        $balance = (int) $game_state["cur_ants"];
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT *
         FROM mines_games
         WHERE user_id = ?
         FOR UPDATE"
    );

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $game = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ($action === "status") {
        mysqli_commit($conn);

        if (!$game || $game["status"] !== "active") {
            send_response(200, [
                "success" => true,
                "balance" => (string) $balance,
                "game" => null
            ]);
        }

        $mine_count = (int) $game["mine_count"];
        $safe_count = (int) $game["safe_count"];

        send_response(200, [
            "success" => true,
            "balance" => (string) $balance,
            "game" => [
                "bet" => (string) $game["bet"],
                "mine_count" => $mine_count,
                "revealed" => json_decode($game["revealed_json"], true),
                "safe_count" => $safe_count,
                "multiplier" => get_multiplier($mine_count, $safe_count),
                "prize" => (string) get_prize(
                    (int) $game["bet"],
                    $mine_count,
                    $safe_count
                )
            ]
        ]);
    }

    if ($action === "start") {
        $bet = $data["bet"] ?? null;
        $mine_count = $data["mine_count"] ?? null;

        if (
            !(is_int($bet) || (is_string($bet) && ctype_digit($bet))) ||
            !is_int($mine_count) ||
            $mine_count < 1 ||
            $mine_count > 24
        ) {
            throw new InvalidArgumentException("Invalid bet or mine count.");
        }

        $bet = filter_var($bet, FILTER_VALIDATE_INT);

        if ($bet === false || $bet <= 0) {
            throw new InvalidArgumentException("Invalid bet amount.");
        }

        if ($game && $game["status"] === "active") {
            mysqli_commit($conn);

            $safe_count = (int) $game["safe_count"];
            $mine_count = (int) $game["mine_count"];

            send_response(200, [
                "success" => true,
                "message" => "Active game restored.",
                "balance" => (string) $balance,
                "game" => [
                    "bet" => (string) $game["bet"],
                    "mine_count" => $mine_count,
                    "revealed" => json_decode($game["revealed_json"], true),
                    "safe_count" => $safe_count,
                    "multiplier" => get_multiplier($mine_count, $safe_count),
                    "prize" => (string) get_prize(
                        (int) $game["bet"],
                        $mine_count,
                        $safe_count
                    )
                ]
            ]);
        }

        if ($bet > $balance) {
            throw new InvalidArgumentException("Not enough ants.");
        }

        $mines = [];

        while (count($mines) < $mine_count) {
            $position = random_int(0, 24);

            if (!in_array($position, $mines, true)) {
                $mines[] = $position;
            }
        }

        $balance -= $bet;
        $balance_string = (string) $balance;

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE game_states
             SET cur_ants = ?
             WHERE user_id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $balance_string,
            $user_id
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $mines_json = json_encode($mines);
        $revealed_json = json_encode([]);
        $bet_string = (string) $bet;

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO mines_games
                (user_id, bet, mine_count, mines_json, revealed_json, safe_count, status)
             VALUES (?, ?, ?, ?, ?, 0, 'active')
             ON DUPLICATE KEY UPDATE
                bet = VALUES(bet),
                mine_count = VALUES(mine_count),
                mines_json = VALUES(mines_json),
                revealed_json = VALUES(revealed_json),
                safe_count = 0,
                status = 'active',
                created_at = CURRENT_TIMESTAMP,
                finished_at = NULL"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "isiss",
            $user_id,
            $bet_string,
            $mine_count,
            $mines_json,
            $revealed_json
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);

        send_response(200, [
            "success" => true,
            "message" => "Game started.",
            "balance" => $balance_string,
            "bet" => $bet_string,
            "mine_count" => $mine_count
        ]);
    }

    if (!$game || $game["status"] !== "active") {
        throw new InvalidArgumentException("No active game.");
    }

    $bet = (int) $game["bet"];
    $mine_count = (int) $game["mine_count"];
    $mines = json_decode($game["mines_json"], true);
    $revealed = json_decode($game["revealed_json"], true);
    $safe_count = (int) $game["safe_count"];

    if (
        !is_array($mines) ||
        !is_array($revealed) ||
        count($mines) !== $mine_count ||
        count($revealed) !== $safe_count
    ) {
        throw new Exception("Invalid game state.");
    }

    if ($action === "reveal") {
        $cell = $data["cell"] ?? null;

        if (!is_int($cell) || $cell < 0 || $cell > 24) {
            throw new InvalidArgumentException("Invalid cell.");
        }

        if (in_array($cell, $revealed, true)) {
            throw new InvalidArgumentException("Cell already revealed.");
        }

        $revealed[] = $cell;
        $revealed_json = json_encode($revealed);

        if (in_array($cell, $mines, true)) {
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE mines_games
                 SET revealed_json = ?,
                     status = 'lost',
                     finished_at = CURRENT_TIMESTAMP
                 WHERE user_id = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "si",
                $revealed_json,
                $user_id
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($conn);

            send_response(200, [
                "success" => true,
                "result" => "lost",
                "message" => "You hit a mine!",
                "balance" => (string) $balance,
                "mines" => $mines,
                "revealed" => $revealed
            ]);
        }

        $safe_count++;

        $multiplier = get_multiplier($mine_count, $safe_count);
        $prize = get_prize($bet, $mine_count, $safe_count);

        if ($safe_count === 25 - $mine_count) {
            if ($prize > PHP_INT_MAX - $balance) {
                throw new InvalidArgumentException("Balance limit reached.");
            }

            $new_balance = $balance + $prize;
            $new_balance_string = (string) $new_balance;

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE game_states
                 SET cur_ants = ?,
                     score = GREATEST(score, ?)
                 WHERE user_id = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssi",
                $new_balance_string,
                $new_balance_string,
                $user_id
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE mines_games
                 SET revealed_json = ?,
                     safe_count = ?,
                     status = 'cashed_out',
                     finished_at = CURRENT_TIMESTAMP
                 WHERE user_id = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sii",
                $revealed_json,
                $safe_count,
                $user_id
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($conn);

            send_response(200, [
                "success" => true,
                "result" => "cashed_out",
                "message" => "All safe cells found!",
                "balance" => $new_balance_string,
                "multiplier" => $multiplier,
                "prize" => (string) $prize,
                "mines" => $mines,
                "revealed" => $revealed
            ]);
        }

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE mines_games
             SET revealed_json = ?,
                 safe_count = ?
             WHERE user_id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "sii",
            $revealed_json,
            $safe_count,
            $user_id
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);

        send_response(200, [
            "success" => true,
            "result" => "safe",
            "message" => "Safe cell!",
            "balance" => (string) $balance,
            "multiplier" => $multiplier,
            "prize" => (string) $prize,
            "safe_count" => $safe_count,
            "revealed" => $revealed
        ]);
    }

    if ($action === "cash_out") {
        if ($safe_count <= 0) {
            throw new InvalidArgumentException(
                "Reveal at least one safe cell before cashing out."
            );
        }

        $prize = get_prize($bet, $mine_count, $safe_count);
        $multiplier = get_multiplier($mine_count, $safe_count);

        if ($prize > PHP_INT_MAX - $balance) {
            throw new InvalidArgumentException("Balance limit reached.");
        }

        $new_balance = $balance + $prize;
        $new_balance_string = (string) $new_balance;

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE game_states
             SET cur_ants = ?,
                 score = GREATEST(score, ?)
             WHERE user_id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssi",
            $new_balance_string,
            $new_balance_string,
            $user_id
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE mines_games
             SET status = 'cashed_out',
                 finished_at = CURRENT_TIMESTAMP
             WHERE user_id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);

        send_response(200, [
            "success" => true,
            "result" => "cashed_out",
            "message" => "Prize collected!",
            "balance" => $new_balance_string,
            "multiplier" => $multiplier,
            "prize" => (string) $prize,
            "mines" => $mines,
            "revealed" => $revealed
        ]);
    }

    throw new InvalidArgumentException("Unknown action.");

} catch (InvalidArgumentException $error) {
    mysqli_rollback($conn);

    send_response(400, [
        "success" => false,
        "message" => $error->getMessage()
    ]);

} catch (Throwable $error) {
    mysqli_rollback($conn);

    error_log($error->getMessage());

    send_response(500, [
        "success" => false,
        "message" => "An internal error occurred."
    ]);
}
