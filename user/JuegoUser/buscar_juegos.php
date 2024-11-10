<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Búsqueda de Juegos</title>
    <link rel="stylesheet" href="stylesbuscarjuegos.css?v=<?php echo time(); ?>">
</head>
<body>

<div id="resultados-busqueda">
    <?php
    // Conectar a la base de datos
    $conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

    // Verificar la conexión
    if (!$conn) {
        die("Error en la conexión a la base de datos.");
    }

    // Obtener la consulta de búsqueda
    $q = isset($_GET['q']) ? $_GET['q'] : '';

    // Buscar juegos que coincidan con el nombre, incluyendo la valoración promedio
    $query = "
        SELECT 
            v.id_videojuego,
            v.nombre AS nombre_videojuego, 
            v.url_imagen,
            COALESCE(AVG(val.puntuacion), 0) AS puntuacion_promedio
        FROM 
            videojuego v
        LEFT JOIN 
            valoracion val ON v.id_videojuego = val.id_videojuego
        WHERE 
            v.nombre ILIKE $1
        GROUP BY 
            v.id_videojuego
        LIMIT 5;
    ";

    $result = pg_query_params($conn, $query, array('%' . $q . '%'));

    if ($result) {
        $juegos = pg_fetch_all($result);
        if ($juegos) {
            foreach ($juegos as $juego) {
                // Caja para cada resultado, envuelta en un enlace
                echo "<div class='search-result'>"; // Contenedor flex para cada juego
                echo "<a href='Juegoseleccionado.php?id_videojuego=" . htmlspecialchars($juego['id_videojuego']) . "' class='search-link'>"; // Enlace al juego
                echo "<img src='" . htmlspecialchars($juego['url_imagen']) . "' alt='" . htmlspecialchars($juego['nombre_videojuego']) . "' class='game-image'>"; // Imagen del juego
                echo "<span class='result-text'>" . htmlspecialchars($juego['nombre_videojuego']) . "</span>"; // Nombre del juego
                echo "<span class='result-rating'>Valoración promedio: " . round($juego['puntuacion_promedio'], 1) . "/10 ⭐</span>"; // Puntuación promedio
                echo "</a>"; // Cierra el enlace
                echo "</div>";
            }
        } else {
            echo "<p class='no-results'>No se encontraron resultados.</p>"; // Mensaje de no resultados
        }
    } else {
        echo "<p class='no-results'>Error en la búsqueda.</p>"; // Mensaje de error
    }

    // Cerrar la conexión a la base de datos
    pg_close($conn);
    ?>
</div>

</body>
</html>
