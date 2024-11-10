<?php
session_start();

// Conectar a la base de datos
$conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

if (!$conn) {
    die("Error en la conexión: " . pg_last_error());
}

// Comprobar si el usuario ha iniciado sesión
if (!isset($_SESSION['admin_username'])) {
    header("Location: /ForumZGames/login.php");
    exit();
}

$username = $_SESSION['admin_username'];
$username_img = $_SESSION['admin_url_foto_perfil'];
$show_modal = false;

// Obtener géneros y plataformas de la base de datos
$query_generos = "SELECT id_genero, nombre_genero FROM genero";
$result_generos = pg_query($conn, $query_generos);
$generos = pg_fetch_all($result_generos);

$query_plataformas = "SELECT id_plataforma, nombre_plataforma FROM plataforma";
$result_plataformas = pg_query($conn, $query_plataformas);
$plataformas = pg_fetch_all($result_plataformas);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Obtener datos del formulario
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $desarrollador = $_POST['desarrollador'];
    $agno_lanzamiento = $_POST['agno_lanzamiento'];
    $url_imagen = '';
    
    // Subir imagen principal del juego
    if (isset($_FILES['url_imagen']) && $_FILES['url_imagen']['error'] === 0) {
        $nombreImagen = basename($_FILES['url_imagen']['name']);
        $url_imagen = $_SERVER['DOCUMENT_ROOT'] . "/ForumZGames/imagenes/juegos/" . $nombreImagen;
        move_uploaded_file($_FILES['url_imagen']['tmp_name'], $url_imagen);
        $url_imagen_Real = "/ForumZGames/imagenes/juegos/" . $nombreImagen;
    }

    // Insertar juego en la tabla `videojuego`
    $query_videojuego = "INSERT INTO videojuego (nombre, descripcion, precio, desarrollador, agno_lanzamiento, url_imagen) VALUES ($1, $2, $3, $4, $5, $6) RETURNING id_videojuego";
    $result_videojuego = pg_query_params($conn, $query_videojuego, array($nombre, $descripcion, $precio, $desarrollador, $agno_lanzamiento, $url_imagen_Real));

    $modal_message = '';
    if ($result_videojuego) {
        $id_videojuego = pg_fetch_result($result_videojuego, 0, 'id_videojuego');
        
        // Insertar géneros seleccionados en la tabla intermedia `videojuego_genero`
        if (!empty($_POST['generos'])) {
            foreach ($_POST['generos'] as $id_genero) {
                $query_genero = "INSERT INTO videojuego_genero (id_videojuego, id_genero) VALUES ($1, $2)";
                pg_query_params($conn, $query_genero, array($id_videojuego, $id_genero));
            }
        }
        
        // Insertar plataformas seleccionadas en la tabla intermedia `videojuego_plataforma`
        if (!empty($_POST['plataformas'])) {
            foreach ($_POST['plataformas'] as $id_plataforma) {
                $query_plataforma = "INSERT INTO videojuego_plataforma (id_juego, id_plataforma) VALUES ($1, $2)";
                pg_query_params($conn, $query_plataforma, array($id_videojuego, $id_plataforma));
            }
        }
        
        $juego_carpeta = $_SERVER['DOCUMENT_ROOT'] . "/ForumZGames/imagenes/juegos/" . $id_videojuego;
        if (!is_dir($juego_carpeta)) {
            mkdir($juego_carpeta, 0777, true); // Crear la carpeta con permisos completos
        }

       // Subir imágenes adicionales y URL de video para juegoseleccionado
        $video_url = $_POST['video_url'];
        $imagenes = [];
        for ($i = 1; $i <= 5; $i++) {
            $imagen_key = "img$i";
            if (isset($_FILES[$imagen_key]) && $_FILES[$imagen_key]['error'] === 0) {
                $nombreImagen = basename($_FILES[$imagen_key]['name']);
                $rutaImagen = "/ForumZGames/imagenes/juegos/" . $id_videojuego . "/" . $nombreImagen;
                move_uploaded_file($_FILES[$imagen_key]['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . $rutaImagen);
                $imagenes[] = $rutaImagen;
            } else {
                $imagenes[] = null; // Añade null si no se sube una imagen para esta posición
            }
        }

        $query_juegoseleccionado = "INSERT INTO juegoseleccionado (id_juego, img1, img2, img3, img4, img5, video1) VALUES ($1, $2, $3, $4, $5, $6, $7)";
        $result_juegoseleccionado = pg_query_params($conn, $query_juegoseleccionado, array_merge([$id_videojuego], $imagenes, [$video_url]));

        $modal_message = $result_juegoseleccionado ? "Juego guardado correctamente" : "Error al guardar las imágenes y video: " . pg_last_error($conn);
    } else {
        $modal_message = "Error al guardar el juego: " . pg_last_error($conn);
    }
    $show_modal = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Juego</title>
    <link rel="stylesheet" href="styleanyadir_juego.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Press+Start+2P&family=Monoton&family=Lobster&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="/ForumZGames/imagenes/images/Logo_v1.png" type="image/x-icon">
</head>
<body>
    <nav class="sidebar">
        <ul class="nav-left">
            <a href="/ForumZGames/admin/PerfilAdmin/admin_perfil.php">
            <div class="admin-perfil">
                <img src=<?php echo $username_img; ?> alt="Logo" class="imagen-admin">
                <span class="usr-admin"><?php echo $username; ?></span>
            </div></a>
            <li><a href="/ForumZGames/admin/IndexAdmin/admin_index.php">Inicio</a></li>
            <li><a href="admin_juegos.php">Juegos</a> -> 
                <a href="anyadir_juego.php">Añadir Juego</a>
            </li>
            <li><a href="/ForumZGames/admin/NotificacionesAdmin/admin_notificaciones.php">Notificaciones</a></li>
        </ul>
    </nav>

    <div class="form-container">
        <h2>Insertar Juego</h2>
        <form id="juegoForm" action="anyadir_juego.php" method="POST" enctype="multipart/form-data">
            <label for="nombre">Nombre del Juego:</label>
            <input type="text" id="nombre" name="nombre" required>

            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" name="descripcion" required></textarea>

            <label for="precio">Precio:</label>
            <input type="number" id="precio" name="precio" required>

            <label for="desarrollador">Desarrollador:</label>
            <input type="text" id="desarrollador" name="desarrollador" required>

            <label for="agno_lanzamiento">Año de Lanzamiento:</label>
            <input type="number" id="agno_lanzamiento" name="agno_lanzamiento" required>

            <!-- Géneros con casillas de verificación y retroalimentación visual -->
            <label for="generos">Géneros:</label>
            <div id="generos" class="checkbox-group">
                <?php foreach ($generos as $genero): ?>
                    <label>
                        <input type="checkbox" name="generos[]" value="<?php echo $genero['id_genero']; ?>" onclick="updateCheckboxSelection('generos', 'selectedGeneros')">
                        <?php echo $genero['nombre_genero']; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <div id="selectedGeneros" class="selection-display"></div> <!-- Muestra la selección de géneros -->

            <!-- Plataformas con casillas de verificación y retroalimentación visual -->
            <label for="plataformas">Plataformas:</label>
            <div id="plataformas" class="checkbox-group">
                <?php foreach ($plataformas as $plataforma): ?>
                    <label>
                        <input type="checkbox" name="plataformas[]" value="<?php echo $plataforma['id_plataforma']; ?>" onclick="updateCheckboxSelection('plataformas', 'selectedPlataformas')">
                        <?php echo $plataforma['nombre_plataforma']; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <div id="selectedPlataformas" class="selection-display"></div> <!-- Muestra la selección de plataformas -->

            <label for="url_imagen">Imagen Principal:</label>
            <input type="file" id="url_imagen" name="url_imagen" accept="image/*" required>

            <label for="video_url">URL del Video:</label>
            <input type="url" id="video_url" name="video_url">

            <label>Imágenes Adicionales:</label>
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <input type="file" id="img<?php echo $i; ?>" name="img<?php echo $i; ?>" accept="image/*">
            <?php endfor; ?>

            <button class="save-button" type="submit">Guardar Juego</button>
        </form>
    </div>

    <div id="myModal" class="modal">
        <div class="modal-content">
            <p id="modalMessage"><?php echo $modal_message; ?></p>
            <button class="close-btn" onclick="redirectToPage()">Aceptar</button>
        </div>
    </div>

    <script>
        // Función para actualizar las selecciones de casillas de verificación
        function updateCheckboxSelection(groupName, displayId) {
            const checkboxes = document.querySelectorAll(`input[name="${groupName}[]"]:checked`);
            const display = document.getElementById(displayId);
            const selectedOptions = Array.from(checkboxes).map(checkbox => checkbox.parentElement.textContent.trim());
            display.innerHTML = selectedOptions.length > 0 ? 'Seleccionado(s): ' + selectedOptions.join(', ') : 'Ninguna opción seleccionada';
        }

        function showModal() {
            document.getElementById("myModal").style.display = "flex";
        }

        function closeModal() {
            document.getElementById("myModal").style.display = "none";
        }

        function redirectToPage() {
            window.location.href = 'admin_juegos.php';
        }

        <?php if (!empty($modal_message)) : ?>
            showModal();
        <?php endif; ?>
    </script>
</body>
</html>