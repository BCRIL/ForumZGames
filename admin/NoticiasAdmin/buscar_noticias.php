<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Búsqueda de Juegos</title>
    <link rel="stylesheet" href="stylesbuscar_noticias.css?v=<?php echo time(); ?>">
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

    // Buscar noticias que coincidan con el nombre de un videojuego
    $query = "
    SELECT 
        n.id_noticia,
        n.titulo, 
        n.url_imagen,
        n.url_noticia
    FROM 
        videojuego v 
    JOIN 
        noticia n ON n.id_videojuego = v.id_videojuego
    WHERE 
        v.nombre ILIKE $1
    ORDER BY
        n.fechapublicacion DESC, 
        n.id_noticia ASC
    ;";

    $result = pg_query_params($conn, $query, array('%' . $q . '%'));

    if ($result) {
        $noticias = pg_fetch_all($result);
        if ($noticias) {
            foreach ($noticias as $noticia) {
                // Caja para cada resultado, envuelta en un enlace
                echo "<div class='search-result'>"; // Contenedor flex para cada noticia
                echo "<a href='" . htmlspecialchars($noticia['url_noticia']) . "' class='search-link'>"; // Enlace a la noticia
                echo "<img src='" . htmlspecialchars($noticia['url_imagen']) . "' alt='" . htmlspecialchars($noticia['titulo']) . "' class='new-image2'>"; // Imagen de la noticia
                echo "<span class='result-text'>" . htmlspecialchars($noticia['titulo']) . "</span>"; // Nombre de la noticia
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
