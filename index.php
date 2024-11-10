<?php
session_start();

// Conectar a la base de datos
$conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

// Verificar si ya existe una cookie de "Recuérdame" y no hay sesión activa
if (isset($_COOKIE['rememberme']) && !isset($_SESSION['username'])) {
    $username = $_COOKIE['rememberme'];

    // Buscar al usuario en la base de datos usando la cookie
    $query = "SELECT username FROM usuario WHERE username = $1";
    $result = pg_query_params($conn, $query, array($username));

    if ($result && pg_num_rows($result) == 1) {
        // Restaurar la sesión
        $_SESSION['username'] = $username;
        header("Location: index.php"); // Redirigir a la página de perfil
        exit();
    } else {
        // Si la cookie no es válida (usuario no existe), borrarla
        setcookie('rememberme', '', time() - 3600, "/");
    }
}

// Comprobar si el usuario ha iniciado sesión
$loggedIn = isset($_SESSION['username']);
$username = $loggedIn ? $_SESSION['username'] : null;




// Seleccionar las 6 noticias mas recientes
$limit_noticias = 6;
$query_noticias = "
    SELECT 
        n.id_noticia,
        n.titulo, 
        n.url_noticia, 
        n.fechapublicacion,
        n.id_videojuego, 
        n.url_imagen
    FROM 
        noticia n
    ORDER BY 
        n.fechapublicacion DESC, 
        n.id_noticia ASC
    LIMIT $1; -- Limitamos a 6 noticias y aplicamos offset
";
$result_noticias = pg_query_params($conn, $query_noticias, array($limit_noticias));

if ($result_noticias === false) {
    echo "Error en la consulta de noticias: " . pg_last_error($conn);
    exit();
}

$noticias = pg_fetch_all($result_noticias);

// Seleccionar los 8 juegos mejor valorados
$limit_juegos = 8;
$query_juegos = "
    SELECT 
        v.id_videojuego,
        v.nombre AS nombre_videojuego, 
        v.url_imagen,
        COALESCE(AVG(val.puntuacion), 0) AS media_valoracion,
        (SELECT COUNT(*) FROM valoracion WHERE id_videojuego = v.id_videojuego) AS num_valoraciones
    FROM 
        videojuego v
    LEFT JOIN 
        valoracion val ON v.id_videojuego = val.id_videojuego
    GROUP BY 
        v.id_videojuego, v.nombre, v.url_imagen
    ORDER BY 
        media_valoracion DESC, 
        v.id_videojuego ASC
    LIMIT $1; -- Limitamos a 5 juegos y aplicamos offset
";
$result_juegos = pg_query_params($conn, $query_juegos, array($limit_juegos));

if ($result_juegos === false) {
    echo "Error en la consulta de noticias: " . pg_last_error($conn);
    exit();
}

$juegos = pg_fetch_all($result_juegos);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantalla de Inicio</title>
    <link href="styleindex.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Press+Start+2P&family=Monoton&family=Lobster&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
    <link rel="icon" href="/ForumZGames/imagenes/images/Logo_v1.png" type="image/x-icon">
</head>
<body>
    <nav class="nav-bar">
        <ul class="nav-left">
            <li><a href="/ForumZGames/user/JuegoUser/juegos.php">Juegos</a></li>
            <li><a href="/ForumZGames/user/ForoUser/foro.php">Foros</a></li>
        </ul>
    
        <div class="logo">
            <img src="/ForumZGames/imagenes/images/Forum_ZGames.jpeg" alt="Logo" />
        </div>
    
        <ul class="nav-right">
            <li><a href="/ForumZGames/user/NoticiasUser/noticias.php">Noticias</a></li>
            <?php if ($loggedIn): ?>
                <li><a href="/ForumZGames/user/PerfilUser/perfil.php">Perfil (<?php echo htmlspecialchars($username); ?>)</a></li>
            <?php else: ?>
                <li><a href="/ForumZGames/login/login.php">Cuenta</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="gif-container">
        <p class="line1"><strong>Elige tu juego</strong></p>
        <p class="line2"><i>Elige tu camino</i></p>
    </div>
    
    <!-- Sección central con los logos -->
    <div class="logo-container">
        <a href="/ForumZGames/user/JuegoUser/juegos.php?plataforma=3">
            <img src="/ForumZGames/imagenes/images/Xbox.png" alt="Xbox">
        </a>
        <a href="/ForumZGames/user/JuegoUser/juegos.php?plataforma=4">
            <img src="/ForumZGames/imagenes/images/nintendo.png" alt="Nintendo">
        </a>
        <a href="/ForumZGames/user/JuegoUser/juegos.php?plataforma=2">
            <img src="/ForumZGames/imagenes/images/playstation.png" alt="PlayStation">
        </a>
        <a href="/ForumZGames/user/JuegoUser/juegos.php?plataforma=1">
            <img src="/ForumZGames/imagenes/images/pc.png" alt="PC">
        </a>
    </div>

    <!-- Sección noticias en tendencia -->
    <div class="cabecera">
        <p>ÚLTIMAS NOTICIAS</p>
    </div>
    <div class="new-list">
        <?php if ($noticias): ?>
            <?php foreach ($noticias as $noticia_ind): ?>
                <div class="highlighted-new">
                    <div class="background-box"></div> <!-- Caja de fondo negro -->
                    <a href="<?php echo htmlspecialchars($noticia_ind['url_noticia']); ?>" class="foreground-box">
                        <img src="<?php echo htmlspecialchars($noticia_ind['url_imagen']); ?>" alt="<?php echo htmlspecialchars($noticia_ind['titulo']); ?>" class="new-image">
                        <div class="titulo-noticia"><?php echo htmlspecialchars($noticia_ind['titulo']); ?></div>
                    </a>
                    
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No se encontraron noticias en tendencia.</p>
        <?php endif; ?>
    </div>


    <!-- Sección juegos favoritos -->
    <div class="cabecera">
        <p>Favoritos Del Público</p>
    </div>
    <div class="FavoritosdelPublico">
        <?php if ($juegos): ?>
            <?php foreach ($juegos as $juego_ind): ?>
                <div class="topjuego">
                    <a href="/ForumZGames/user/JuegoUser/Juegoseleccionado.php?id_videojuego=<?php echo htmlspecialchars($juego_ind['id_videojuego']); ?>" class="juego-link">
                        <img src="<?php echo htmlspecialchars($juego_ind['url_imagen']); ?>" alt="Imagen de <?php echo htmlspecialchars($juego_ind['nombre_videojuego']); ?>">
                        <p class="titulo-juego"><?php echo htmlspecialchars($juego_ind['nombre_videojuego']); ?></p>
                    </a>
                    
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No se encontraron noticias en tendencia.</p>
        <?php endif; ?>
    </div>

    <!-- Explicacion de la web -->  
    <div class="Explicacion">
        <p>¡Bienvenido a <strong>FORUMGAMES</strong>, el lugar perfecto para descubrir tu próximo juego favorito! 
            Aquí puedes explorar una amplia variedad de géneros, modos de juego y plataformas, 
            con herramientas avanzadas que te ayudarán a encontrar el título perfecto. 
            ¡Empieza tu búsqueda y sumérgete en el mundo de los videojuegos como nunca antes!</p>
    </div>

    <!-- Footer de la pagina -->
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
                    <img src="/ForumZGames/imagenes/images/Instagram.png" alt="Instagram" />
                </a>
                <a href="https://twitter.com" target="_blank">
                    <img src="/ForumZGames/imagenes/images/Twitter.png" alt="Twitter" />
                </a>
                <a href="https://www.youtube.com" target="_blank">
                    <img src="/ForumZGames/imagenes/images/Youtube.png" alt="Youtube" />
                </a>
                <a href="https://www.facebook.com" target="_blank">
                    <img src="/ForumZGames/imagenes/images/Facebook.png" alt="Facebook" />
                </a>
            </div>       
        </div>
    </div>
</body>
</html>
