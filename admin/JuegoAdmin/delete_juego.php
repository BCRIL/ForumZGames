<?php
session_start();
include 'conexion.php';

// Conectar a la base de datos
$conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

if (!$conn) {
    die("Error en la conexión a la base de datos.");
}

// Verificar si el método de la solicitud es POST y si se ha enviado el ID del videojuego
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_videojuego'])) {
    $id_videojuego = $_POST['id_videojuego'];

    // Iniciar una transacción
    pg_query($conn, "BEGIN");

    try {
        // Eliminar registros en las tablas relacionadas con el videojuego
        $queries = [
            "DELETE FROM valoracion WHERE id_videojuego = $1",
            "DELETE FROM videojuego_genero WHERE id_videojuego = $1",
            "DELETE FROM videojuego_plataforma WHERE id_juego = $1",
            "DELETE FROM comentarios WHERE id_videojuego = $1",
            "DELETE FROM juegoseleccionado WHERE id_juego = $1",
            "DELETE FROM videojuego WHERE id_videojuego = $1"
        ];

        // Ejecutar cada consulta
        foreach ($queries as $query) {
            $result = pg_query_params($conn, $query, array($id_videojuego));
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($conn));
            }
        }

        // Confirmar la transacción si todas las eliminaciones fueron exitosas
        pg_query($conn, "COMMIT");
        echo "Videojuego y datos relacionados eliminados exitosamente.";

    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        pg_query($conn, "ROLLBACK");
        echo "Error al eliminar el videojuego: " . $e->getMessage();
    } finally {
        // Cerrar la conexión
        pg_close($conn);
    }
}
?>
