<?php
// Conectar a la base de datos
$conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

// Verificar la conexión
if (!$conn) {
    die("Error en la conexión a la base de datos.");
}

// Obtener la consulta de búsqueda desde la URL
$q = isset($_GET['q']) ? $_GET['q'] : '';

// Buscar foros que coincidan con el título o descripción
$query = "
    SELECT 
        f.id_foro,
        f.titulo,
        f.imagen,
        f.fecha,
        u.url_foto_perfil
    FROM 
        foro f
    JOIN 
        usuario u ON f.id_usuario = u.username
    WHERE 
        f.titulo ILIKE $1 OR f.descripcion ILIKE $1
    ORDER BY 
        f.fecha DESC
    LIMIT 5;
";

$result = pg_query_params($conn, $query, array('%' . $q . '%'));

if ($result) {
    $foros = pg_fetch_all($result);
    if ($foros) {
        foreach ($foros as $foro) {
            // Caja para cada resultado de foro
            echo "<div class='search-result'>";
            echo "<a href='chatForo.php?id_foro=" . htmlspecialchars($foro['id_foro']) . "' class='search-link'>";
            echo "<img src='" . htmlspecialchars($foro['imagen']) . "' alt='" . htmlspecialchars($foro['titulo']) . "' class='foro-image-small'>";
            echo "<span class='result-text'>" . htmlspecialchars($foro['titulo']) . "</span>";
            echo "<span class='result-date'>" . htmlspecialchars(date('Y-m-d', strtotime($foro['fecha']))) . "</span>";
            echo "</a>";
            echo "</div>";
        }
    } else {
        echo "<p class='no-results'>No se encontraron resultados.</p>";
    }
} else {
    echo "<p class='no-results'>Error en la búsqueda.</p>";
}

// Cerrar la conexión a la base de datos
pg_close($conn);
?>
