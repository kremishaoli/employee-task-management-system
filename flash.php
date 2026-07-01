<?php
function flash($name) {
    if (!empty($_SESSION[$name])) {
        $msg = $_SESSION[$name];
        unset($_SESSION[$name]); // remove it after showing
        return $msg;
    }
    return null;
}
?>
