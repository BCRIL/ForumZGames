<?php
session_start(); // Iniciar sesión

// Conectar a la base de datos
$conn_string = "host=localhost dbname=ForumZGames user=postgres password=root"; // Cambia esto
$conn = pg_connect($conn_string);

if (!$conn) {
    die("Error en la conexión a la base de datos.");
}

// Comprobar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    header("Location: IniciarSesion.html"); // Redirigir a la página de inicio de sesión
    exit();
}

// Obtener el nombre de usuario
$username = $_SESSION['username'];

// Comprobar si el formulario ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = pg_escape_string(trim($_POST['current_password']));
    $new_password = pg_escape_string(trim($_POST['new_password']));
    $confirm_password = pg_escape_string(trim($_POST['confirm_password']));

    // Comprobar si las nuevas contraseñas coinciden
    if ($new_password !== $confirm_password) {
        echo "Las contraseñas no coinciden.";
        exit();
    }

    // Recuperar la contraseña actual del usuario
    $query = "SELECT password FROM usuario WHERE username = $1";
    $result = pg_prepare($conn, "get_current_password", $query);
    $result = pg_execute($conn, "get_current_password", array($username));

    if ($row = pg_fetch_assoc($result)) {
        $hashed_password = $row['password'];

        // Verificar si la contraseña actual ingresada es correcta
        if (!password_verify($current_password, $hashed_password)) {
            echo "La contraseña actual es incorrecta.";
            exit();
        }

        // Hash de la nueva contraseña
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Actualizar la contraseña en la base de datos
        $update_query = "UPDATE usuario SET password = $1 WHERE username = $2";
        $update_result = pg_prepare($conn, "update_password", $update_query);
        $update_result = pg_execute($conn, "update_password", array($new_hashed_password, $username));

        if ($update_result) {
            echo "Contraseña cambiada con éxito.";
            header("Location: /ForumZGames/user/PerfilUser/perfil.php"); // Redirigir a la página de inicio de sesión
        } else {
            echo "Error al cambiar la contraseña: " . pg_last_error($conn);
        }
    } else {
        echo "Error al recuperar la contraseña actual.";
    }
}

// Cerrar la conexión
pg_close($conn);
?>
