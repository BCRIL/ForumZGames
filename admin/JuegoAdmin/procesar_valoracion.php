<?php
session_start();

// Conectar a la base de datos
$conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

if (!$conn) {
    die("Error en la conexión a la base de datos.");
}

// Obtener los datos del formulario
$id_videojuego = isset($_POST['id_videojuego']) ? (int)$_POST['id_videojuego'] : null;
$puntuacion = isset($_POST['puntuacion']) ? (int)$_POST['puntuacion'] : null;

// Verificar si el usuario está logueado
if (!isset($_SESSION['admin_username'])) {
    echo "Error: Debes estar logueado para votar.";
    exit();
}

// Concatenar "_admin" al nombre de usuario del administrador
$user_name = $_SESSION['admin_username'];
$user_name = $user_name . "_admin";
// Validar que el ID del videojuego y la puntuación no estén vacíos
if (empty($id_videojuego) || empty($puntuacion)) {
    echo "Error: Datos incompletos.";
    exit();
}

// Obtener la fecha actual
$fecha = date('Y-m-d H:i:s'); // Formato de fecha y hora

// Verificar si el usuario ya ha votado para este videojuego
$query_verificar = "SELECT * FROM valoracion WHERE id_videojuego = $1 AND id_usuario = $2";
$result_verificar = pg_query_params($conn, $query_verificar, array($id_videojuego, $user_name));

if ($result_verificar && pg_num_rows($result_verificar) > 0) {
    // Si ya existe una valoración, actualízala
    $query_actualizar = "UPDATE valoracion SET fecha = $1, puntuacion = $2 WHERE id_videojuego = $3 AND id_usuario = $4";
    $result_actualizar = pg_query_params($conn, $query_actualizar, array($fecha, $puntuacion, $id_videojuego, $user_name));

    if ($result_actualizar) {
        echo "Valoración actualizada con éxito.";
    } else {
        echo "Error al actualizar la valoración: " . pg_last_error($conn);
    }
} else {
    // Inserción de la valoración en la base de datos
    $query_insertar = "INSERT INTO valoracion (id_videojuego, id_usuario, fecha, puntuacion) VALUES ($1, $2, $3, $4)";
    $result_insertar = pg_query_params($conn, $query_insertar, array($id_videojuego, $user_name, $fecha, $puntuacion));

    if ($result_insertar) {
        echo "Valoración enviada con éxito.";
    } else {
        echo "Error al enviar la valoración: " . pg_last_error($conn);
    }
}

// Redirigir a la página anterior
$redirect_url = $_SERVER['HTTP_REFERER'] ?? 'index.php'; // Usa 'index.php' como URL de fallback si no hay una referencia
header("Location: $redirect_url");
exit();

// Cerrar la conexión a la base de datos
pg_close($conn);
?>
