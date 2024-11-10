<?php
session_start();
$conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

if (!$conn) {
    die("Error al conectar con la base de datos.");
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['username'])) {
    header("Location: /ForumZGames/login/login.php"); // Redirige a la página de login si no está autenticado
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreForo = trim($_POST['nombre_foro']);
    $directrices = trim($_POST['directrices']);
    $tags = trim($_POST['tags']);
    $imagenRuta = null; // Inicializamos $imagenRuta como null
    
    // Manejar la subida de la imagen si se ha seleccionado un archivo
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        // Generar un nombre de archivo único para evitar duplicados
        $nombreArchivo = uniqid() . "_" . basename($_FILES['imagen']['name']);
        $rutaDestino = $_SERVER['DOCUMENT_ROOT'] . "/ForumZGames/imagenes/foros/" . $nombreArchivo; // Ruta de destino en el servidor
        $rutaParaBaseDeDatos = "/ForumZGames/imagenes/foros/" . $nombreArchivo; // Ruta para guardar en la base de datos

        
        // Mover el archivo subido a la carpeta "imagenes/foros"
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
            $imagenRuta = $rutaParaBaseDeDatos; // Guardar la ruta para almacenarla en la base de datos
        } else {
            echo "<script>alert('Error al mover el archivo al destino');</script>";
        }
    } elseif (isset($_FILES['imagen']['error']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Mostrar un mensaje de error detallado si la subida falló
        echo "<script>alert('Error en la subida de la imagen: " . $_FILES['imagen']['error'] . "');</script>";
    }

    // Insertar el foro en la base de datos si los campos obligatorios están llenos
    if (!empty($nombreForo) && !empty($directrices)) {
        $query = "INSERT INTO foro (titulo, descripcion, fecha, id_usuario, imagen) VALUES ($1, $2, NOW(), $3, $4)";
        $result = pg_query_params($conn, $query, array($nombreForo, $directrices, $_SESSION['username'], $imagenRuta));

        if ($result) {
            echo "<script>alert('Foro creado exitosamente');</script>";
            header("Location: /ForumZGames/user/ForoUser/foro.php"); // Redirige a la página de login si no está autenticado
        } else {
            echo "<script>alert('Error al crear el foro');</script>";
        }
    } else {
        echo "<script>alert('Por favor, complete todos los campos requeridos');</script>";
    }
}

// Comprobar si el usuario ha iniciado sesión
$loggedIn = isset($_SESSION['username']);
$username = $loggedIn ? $_SESSION['username'] : null;

pg_close($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Foro</title>
    <link rel="stylesheet" href="stylesAnyadirForo.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Press+Start+2P&family=Monoton&family=Lobster&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="nav-bar">
        <ul class="nav-left">
            <li><a href="foro.php">Foros</a></li>
        </ul>

        <div class="logo">
            <img src="/ForumZGames/imagenes/images/Forum_ZGames.jpeg" alt="Logo" />
        </div>

        <ul class="nav-right">
            <li><a href="/ForumZGames/index.php">Inicio</a></li>
            <li><a href="/ForumZGames/user/NoticiasUser/noticias.php">Noticias</a></li>
            <?php if ($loggedIn): ?>
                <li><a href="/ForumZGames/user/PerfilUser/perfil.php">Perfil (<?php echo htmlspecialchars($username); ?>)</a></li>
            <?php else: ?>
                <li><a href="/ForumZGames/login/login.php">Cuenta</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Contenedor Principal -->
    <div class="main-container">
        <div class="content">
            <form action="anyadirForo.php" method="POST" enctype="multipart/form-data">
                <label for="nombre_foro">Nombre Del Foro</label>
                <input type="text" id="nombre_foro" name="nombre_foro" required>

                <label for="directrices">Directrices</label>
                <textarea id="directrices" name="directrices" required></textarea>

                <label for="imagen">Imagen de Portada</label>
                <div class="image-upload">
                    <input type="file" id="imagen" name="imagen" accept="image/*">
                    <label for="imagen">
                        <img src="images/upload-icon.png" alt="Upload Icon">
                    </label>
                </div>

                <button type="submit" class="publish-button">Publicar</button>
            </form>
        </div>
    </div>

    <footer>
        <p>Forum ZGames</p>
        <div class="social-icons">
            <a href="#"><img src="images/instagram-icon.png" alt="Instagram"></a>
            <a href="#"><img src="images/youtube-icon.png" alt="YouTube"></a>
            <a href="#"><img src="images/discord-icon.png" alt="Discord"></a>
        </div>
    </footer>
</body>
</html>
