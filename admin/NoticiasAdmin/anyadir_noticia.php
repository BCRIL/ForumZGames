<?php
session_start();

// Conectar a la base de datos
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

// Si ha iniciado sesión, obtener el nombre de usuario
$username = $_SESSION['admin_username'];
$query_admin = "SELECT admin_fullname, admin_url_foto_perfil FROM administrador WHERE admin_username = $1";
$result_admin = pg_query_params($conn, $query_admin, array($username));

if (!$result_admin || pg_num_rows($result_admin) === 0) {
    echo "Error al obtener los datos del administrador.";
    exit();
}

$admin_data = pg_fetch_assoc($result_admin);
$admin_fullname = $admin_data['admin_fullname'];
$admin_url_foto_perfil = $admin_data['admin_url_foto_perfil'];
$show_modal = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Datos del formulario
    $titulo = $_POST['titulo'];
    $url_noticia = $_POST['url_noticia'];
    $juego = $_POST['selected-game-id'];
    $publication_date = date('Y-m-d H:i:s');

    // Subir imagen
    $imagenRuta = '';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $nombreImagen = basename($_FILES['imagen']['name']);
        $imagenRuta = $_SERVER['DOCUMENT_ROOT'] . "/ForumZGames/imagenes/noticias/" . $nombreImagen;

        // Mover la imagen a la carpeta de destino
        move_uploaded_file($_FILES['imagen']['tmp_name'], $imagenRuta);
        $imagenRutaReal ="/ForumZGames/imagenes/noticias/" . $nombreImagen;
    }

    // Insertar noticia en la base de datos
    $query = "INSERT INTO noticia (titulo, url_noticia, fechapublicacion, id_videojuego, url_imagen) VALUES ($1, $2, $3, $4, $5)";
    $result = pg_query_params($conn, $query, array($titulo, $url_noticia, $publication_date, $juego, $imagenRutaReal));

    $modal_message = '';

    if ($result) {
        //echo "Noticia guardada correctamente";
        $modal_message = "Noticia guardada correctamente";
    } else {
        //echo "Error al guardar la noticia";
        $modal_message = "Error al guardar la noticia";
    }
    $show_modal = true;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir noticia</title>
    <link rel="stylesheet" href="styleanyadir_noticia.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Press+Start+2P&family=Monoton&family=Lobster&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="/ForumZGames/imagenes/images/Logo_v1.png" type="image/x-icon">

    <script src="buscar.js"></script> <!-- Archivo JS para manejar la búsqueda -->
</head>
<body>
    <nav class="sidebar">
        <ul class="nav-left">
            <a href="/ForumZGames/admin/PerfilAdmin/admin_perfil.php">
            <div class="admin-perfil">
                <img src=<?php echo $admin_url_foto_perfil; ?> alt="Logo" class="imagen-admin">
                <span class="usr-admin"><?php echo $username; ?></span>
            </div></a>
            <hr class="separator">
            <li><a href="/ForumZGames/admin/IndexAdmin/admin_index.php">Inicio</a></li>
            <li><a href="/ForumZGames/admin/JuegoAdmin/admin_juegos.php">Juegos</a></li>
            <li><a href="/ForumZGames/admin/ForoAdmin/admin_foro.php">Foros</a></li>
            <hr class="separator-sup">
            <li><a href="admin_noticias.php">Noticias</a> -> 
                <a href="anyadir_noticia.php">Añadir</a>
            </li>
            <hr class="separator-inf">
            <li><a href="/ForumZGames/admin/NotificacionesAdmin/admin_notificaciones.php">Notificaciones</a></li>
        </ul>
        <div class="logo">
            <img src="/ForumZGames/imagenes/images/Forum_ZGames.jpeg" alt="Logo" />
        </div>
    </nav>

    <!-- Formulario para introducir datos -->
    <div class="form-container">
        <h2>Insertar Noticia</h2>
        <form id="noticiaForm" action="anyadir_noticia.php" method="POST" enctype="multipart/form-data">
            <label for="titulo">Título de la Noticia:</label>
            <input type="text" id="titulo" name="titulo" required>

            <label for="url">URL de la Noticia:</label>
            <input type="url" id="url_noticia" name="url_noticia" required>

            <label for="imagen">Subir Imagen:</label>
            <input type="file" id="imagen" name="imagen" accept="image/*" required>

            <label for="juego">Seleccionar Juego:</label>
            <div class="autocomplete-box">
                <input type="text" id="search-game" placeholder="Buscar juego..." oninput="seleccionarJuegos()">
                <div id="search-results" class="resultados-container"></div>
                <input type="hidden" id="selected-game-id" name="selected-game-id">
            </div>
            <button class="save-button" type="submit">Guardar Noticia</button>
        </form>
    </div>

    <!-- Modal -->
    <div id="myModal" class="modal">
        <div class="modal-content">
            <p id="modalMessage"><?php echo $modal_message; ?></p>
            <button class="close-btn" onclick="redirectToPage()">Aceptar</button>
        </div>
    </div>

    <script>
        function showModal() {
            document.getElementById("myModal").style.display = "flex";
        }

        function closeModal() {
            document.getElementById("myModal").style.display = "none";
        }

        function redirectToPage() {
            // Redirige a una página específica después de cerrar el modal
            window.location.href = 'admin_noticias.php';
        }

        <?php if (!empty($modal_message)) : ?>
            showModal();
        <?php endif; ?>
    </script>


</body>
</html>
