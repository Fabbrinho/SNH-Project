<?php
if (!isset($_SESSION['token_csrf'])) {
    $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
}
function newToken(){
    unset( $_SESSION['token_csrf']);
    $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
    return;
}
function getToken() {
    return $_SESSION['token_csrf'];
}

function verifyToken($token) {
    return hash_equals($_SESSION['token_csrf'], $token);
}
?>
