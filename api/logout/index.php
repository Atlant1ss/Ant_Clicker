
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
    "message" => "Logout successful."
]);

?>
