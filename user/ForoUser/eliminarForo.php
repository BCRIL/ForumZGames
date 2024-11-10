<?php
session_start();
$conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

// Verificar si el usuario está logueado y si se recibió el ID del foro
if (!isset($_SESSION['username']) || !isset($_GET['id_foro'])) {
    echo "Acceso no autorizado";
    exit();
}

$id_foro = (int)$_GET['id_foro'];
$username = $_SESSION['username'];

// Verificar que el usuario logueado es el creador del foro
$query_verificar = "SELECT id_usuario FROM foro WHERE id_foro = $1";
$result_verificar = pg_query_params($conn, $query_verificar, array($id_foro));
$foro = pg_fetch_assoc($result_verificar);

if (!$foro || $foro['id_usuario'] !== $username) {
    echo "No tienes permiso para eliminar este foro.";
    exit();
}

// Iniciar una transacción para asegurar que todas las eliminaciones se realicen
pg_query($conn, "BEGIN");

try {
    // 1. Eliminar denuncias asociadas a los mensajes del foro
    $query_eliminar_denuncias = "DELETE FROM denuncia WHERE id_mensaje IN (SELECT id_mensaje FROM mensaje WHERE id_foro = $1)";
    pg_query_params($conn, $query_eliminar_denuncias, array($id_foro));

    // 2. Eliminar mensajes del foro
    $query_eliminar_mensajes = "DELETE FROM mensaje WHERE id_foro = $1";
    pg_query_params($conn, $query_eliminar_mensajes, array($id_foro));

    // 3. Eliminar el foro
    $query_eliminar_foro = "DELETE FROM foro WHERE id_foro = $1";
    pg_query_params($conn, $query_eliminar_foro, array($id_foro));

    // Confirmar la transacción
    pg_query($conn, "COMMIT");
    echo "Foro eliminado con éxito";
} catch (Exception $e) {
    // En caso de error, deshacer la transacción
    pg_query($conn, "ROLLBACK");
    echo "Error al eliminar el foro: " . $e->getMessage();
}

// Cerrar la conexión
pg_close($conn);

// Redireccionar de vuelta a la página principal de foros
header("Location: foro.php");
exit();
?>
