<?php
session_start();

// Borrar la cookie 'rememberme' si existe
if (isset($_COOKIE['rememberme'])) {
    setcookie('rememberme', '', time() - 3600, "/"); // Expira la cookie
}

// Destruir la sesión
session_unset();
session_destroy();

// Redirigir al usuario a la página de inicio de sesión
header("Location: /ForumZGames/index.php");
exit();
?>
