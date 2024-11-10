
<?php
session_start();
include 'conexion.php';

// Conectar a la base de datos
$conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

if (!$conn) {
    die("Error en la conexión a la base de datos.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_noticia'])) {
    $id_noticia = $_POST['id_noticia'];

    // Realizar la consulta para eliminar la noticia
    $query = "DELETE FROM noticia WHERE id_noticia = $1";
    $result = pg_query_params($conn, $query, array($id_noticia));

    if ($result) {
        echo "Noticia eliminada exitosamente";
    } else {
        echo "Error al eliminar la noticia";
    }
}
?>
