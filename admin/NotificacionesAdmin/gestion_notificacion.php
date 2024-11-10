<?php
session_start();
$conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

if (!$conn) {
    die("Error en la conexión: " . pg_last_error());
}

// Comprobar si el usuario ha iniciado sesión
if (!isset($_SESSION['admin_username'])) {
    // Redirigir a la página de inicio de sesión si no hay sesión activa
    header("Location: /ForumZGames/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $mensajeId = $_POST['id'];
    $adminId = $_SESSION['admin_username']; // Asegúrate de tener el ID del administrador

    // Actualiza el estado de la notificación
    $updateNotificationQuery = "
    UPDATE denuncia 
    SET pendiente = FALSE, id_admin = $1 
    WHERE id_mensaje = $2";
    
    // Ejecutar la actualización de la notificación
    pg_query_params($conn, $updateNotificationQuery, array($adminId, $mensajeId));

    if ($action === 'delete') {
        // Si la acción es eliminar, también actualiza el mensaje
        $deleteMessageQuery = "
        UPDATE mensaje 
        SET mostrar = FALSE 
        WHERE id_mensaje = $1";
        pg_query_params($conn, $deleteMessageQuery, array($mensajeId));
    }

    // Cierra la conexión a la base de datos
    pg_close($conn);
    echo "Acción realizada exitosamente.";
}
?>
