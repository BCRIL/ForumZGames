<?php
session_start(); // Inicia la sesión

// Comprobar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Redirige al login si no está autenticado
    exit();
}

// Obtener el nombre de usuario del usuario logueado
$username = $_SESSION['username'];

// Conectar a la base de datos PostgreSQL
$conn_string = "host=localhost dbname=ForumZGames user=postgres password=root";
$conn = pg_connect($conn_string);

if (!$conn) {
    die("Error en la conexión a la base de datos."); // Muestra mensaje de error si falla la conexión
}

// Consulta para obtener los foros donde el usuario ha creado o participado
$query = "
    SELECT DISTINCT f.id_foro, f.titulo AS nombre, f.descripcion, 
           CASE WHEN f.id_usuario = $1 THEN 'creador' ELSE 'participante' END AS rol
    FROM foro f
    LEFT JOIN mensaje m ON m.id_foro = f.id_foro
    WHERE f.id_usuario = $1 OR m.id_usuario = $1
    ORDER BY f.titulo ASC
";

// Preparar y ejecutar la consulta con el nombre de usuario
$result = pg_prepare($conn, "get_active_forums", $query);
$result = pg_execute($conn, "get_active_forums", array($username));

if ($result === false) {
    die("Error en la consulta: " . pg_last_error($conn)); // Muestra mensaje si ocurre un error en la consulta
}

// Cerrar la conexión a la base de datos
pg_close($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foros Activos</title>
    <link rel="stylesheet" href="styleForosActivos.css?v=<?php echo time(); ?>">
    <link rel="icon" href="/ForumZGames/imagenes/images/Logo_v1.png" type="image/x-icon">
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="nav-bar">
        <ul class="nav-left">
            <li><a href="perfil.php">Cuenta</a></li>
        </ul>

        <!-- Logo centrado -->
        <div class="logo">
            <img src="/ForumZGames/imagenes/images/Forum_ZGames.jpeg" alt="Logo" />
        </div>

        <!-- Elementos de navegación derecha -->
        <ul class="nav-right">
            <li><a href="/ForumZGames/index.php">Inicio</a></li>
            <li><a href="/ForumZGames/user/JuegoUser/juegos.php">Juegos</a></li>
            <li><a href="/ForumZGames/user/ForoUser/foro.php">Foros</a></li>
            <li><a href="/ForumZGames/user/NoticiasUser/noticias.php">Noticias</a></li>
        </ul>
    </nav>

    <!-- Contenedor de foros activos -->
    <div class="foros-activos-container">
        <div class="title-container">
            <h1 class="title-styled">Juegos que Has Votado</h1>
        </div>
        <div class="foros-list">
            <?php if (pg_num_rows($result) > 0): ?>
                <?php while ($row = pg_fetch_assoc($result)): ?>
                    <div class="foro-item">
                        <h2><?php echo htmlspecialchars($row['nombre']); ?></h2>
                        <p><?php echo htmlspecialchars($row['descripcion']); ?></p>
                        <p class="rol">
                            <?php echo ($row['rol'] === 'creador') ? 'Creado por ti' : 'Participando'; ?>
                        </p>
                        <a href="/ForumZGames/user/ForoUser/chatForo.php?id_foro=<?php echo urlencode($row['id_foro']); ?>">Ir al foro</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No has participado en ningún foro todavía.</p> <!-- Mensaje si no hay participación en foros -->
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <h2 class="footer-title">Forum Games</h2>
        <div class="footer-content">
            <div class="footer-column">
                <h3>Tienda</h3>
                <p><a href="#" class="small-link">Steam</a></p>
                <p><a href="#" class="small-link">Epic Games</a></p>
                <p><a href="#" class="small-link">Instant Gaming</a></p>
                <p><a href="#" class="small-link">G2A</a></p>
            </div>
            <div class="footer-column">
                <h3>Llámanos</h3>
                <p>+34 612 443 809</p>
            </div>
            <div class="footer-column">
                <h3>Enlaces de interés</h3>
                <p><a href="#" class="small-link">Guías y Tutoriales</a></p>
                <p><a href="#" class="small-link">FAQs y Soporte</a></p>
            </div>            
            <div class="footer-column">
                <h3>Redes Sociales</h3>
                <a href="https://www.instagram.com" target="_blank">
                    <img src="images/Instagram.png" alt="Instagram" />
                </a>
                <a href="https://twitter.com" target="_blank">
                    <img src="images/Twitter.png" alt="Twitter" />
                </a>
                <a href="https://www.youtube.com" target="_blank">
                    <img src="images/Youtube.png" alt="Youtube" />
                </a>
                <a href="https://www.facebook.com" target="_blank">
                    <img src="images/Facebook.png" alt="Facebook" />
                </a>
            </div>
        </div>       
    </div>
</body>
</html>
