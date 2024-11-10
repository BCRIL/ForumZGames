<?php
session_start();

// Conectar a la base de datos
$conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

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
    <title>Inicio administrador</title>
    <link rel="stylesheet" href="styleadmin_index.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Press+Start+2P&family=Monoton&family=Lobster&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
    <link rel="icon" href="/ForumZGames/imagenes/images/Logo_v1.png" type="image/x-icon">
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
            <li><a href="/ForumZGames/admin/JuegoAdmin/admin_juegos.php">Juegos</a></li>
            <li><a href="/ForumZGames/admin/ForoAdmin/admin_foro.php">Foros</a></li>
            <li><a href="/ForumZGames/admin/NoticiasAdmin/admin_noticias.php">Noticias</a></li>
            <hr class="separator-sup">
            <li><a href="admin_index.php">Inicio</a></li>
            <hr class="separator-inf">
            <li><a href="/ForumZGames/admin/NotificacionesAdmin/admin_notificaciones.php">Notificaciones</a></li>
        </ul>
        <div class="logo">
            <img src="/ForumZGames/imagenes/images/Forum_ZGames.jpeg" alt="Logo" />
        </div>
    </nav>
    <div class="no-sidebar">
        <div class="gif-container">
            <p class="line1"><strong>Elige tu juego</strong></p>
            <p class="line2"><i>Elige tu camino</i></p>
        </div>
        
        <!-- Sección central con los logos -->
        <div class="logo-container">
            <a href="/ForumZGames/admin/JuegoAdmin/admin_juegos.php?plataforma=3">
                <img src="/ForumZGames/imagenes/images/Xbox.png" alt="Xbox">
            </a>
            <a href="/ForumZGames/admin/JuegoAdmin/admin_juegos.php?plataforma=4">
                <img src="/ForumZGames/imagenes/images/nintendo.png" alt="Nintendo">
            </a>
            <a href="/ForumZGames/admin/JuegoAdmin/admin_juegos.php?plataforma=2">
                <img src="/ForumZGames/imagenes/images/playstation.png" alt="PlayStation">
            </a>
            <a href="/ForumZGames/admin/JuegoAdmin/admin_juegos.php?plataforma=1">
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
                        <a href="/ForumZGames/admin/JuegoAdmin/Juegoseleccionado.php?id_videojuego=<?php echo htmlspecialchars($juego_ind['id_videojuego']); ?>" class="juego-link">
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
    </div>
</body>
</html>