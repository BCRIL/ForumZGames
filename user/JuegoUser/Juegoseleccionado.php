<?php
session_start(); // Iniciar la sesión para manejar la autenticación del usuario

// Conexión a la base de datos
$conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

// Verificar si ya existe una cookie de "Recuérdame" y no hay sesión activa
if (isset($_COOKIE['rememberme']) && !isset($_SESSION['username'])) {
    $username = $_COOKIE['rememberme'];

    // Buscar al usuario en la base de datos usando la cookie
    $query = "SELECT username FROM usuario WHERE username = $1";
    $result = pg_query_params($conn, $query, array($username));

    if ($result && pg_num_rows($result) == 1) {
        // Restaurar la sesión si la cookie es válida
        $_SESSION['username'] = $username;
        header("Location: index.php"); // Redirigir a la página de inicio después de restaurar la sesión
        exit();
    } else {
        // Si la cookie no es válida (usuario no existe), borrarla
        setcookie('rememberme', '', time() - 3600, "/");
    }
}

// Comprobar si el usuario ha iniciado sesión
$loggedIn = isset($_SESSION['username']);
$username = $loggedIn ? $_SESSION['username'] : null;

// Verificar si se pasó el ID del videojuego por la URL
if (isset($_GET['id_videojuego'])) {
    $id_videojuego = intval($_GET['id_videojuego']);

    // Consulta para obtener los detalles del videojuego
    $query_juego = "
        SELECT 
            nombre AS nombre_videojuego, 
            descripcion, 
            precio, 
            desarrollador, 
            agno_lanzamiento, 
            url_imagen 
        FROM 
            videojuego 
        WHERE 
            id_videojuego = $1
    ";
    $result_juego = pg_query_params($conn, $query_juego, array($id_videojuego));

    // Verificar si se obtuvo un resultado del videojuego
    if ($result_juego && pg_num_rows($result_juego) > 0) {
        $juego = pg_fetch_assoc($result_juego);
        
        // Consulta para obtener las imágenes y el video del juego de la tabla juegoseleccionado
        $query_seleccionado = "
            SELECT 
                img1, img2, img3, img4, img5, video1 
            FROM 
                juegoseleccionado 
            WHERE 
                id_juego = $1
        ";
        $result_seleccionado = pg_query_params($conn, $query_seleccionado, array($id_videojuego));

        // Verificar si se obtuvieron imágenes y video
        if ($result_seleccionado && pg_num_rows($result_seleccionado) > 0) {
            $seleccionado_data = pg_fetch_assoc($result_seleccionado);
            
            // Agregar las imágenes y el video al array del juego
            $juego['img1'] = $seleccionado_data['img1'];
            $juego['img2'] = $seleccionado_data['img2'];
            $juego['img3'] = $seleccionado_data['img3'];
            $juego['img4'] = $seleccionado_data['img4'];
            $juego['img5'] = $seleccionado_data['img5'];
            $juego['video1'] = $seleccionado_data['video1'];
        } else {
            // Si no se encontraron imágenes y video, asignar valores nulos
            $juego['img1'] = $juego['img2'] = $juego['img3'] = $juego['img4'] = $juego['img5'] = null;
            $juego['video1'] = null;
        }
    } else {
        echo "No se encontró el juego en la tabla 'videojuego'.";
    }
} else {
    echo "No se especificó un videojuego.";
}

// Consulta para obtener la valoración media y el número total de votos del juego
$query_rating = "
    SELECT COALESCE(AVG(puntuacion), 0) AS media_valoracion, COUNT(*) AS num_valoraciones
    FROM valoracion
    WHERE id_videojuego = $1
";
$result_rating = pg_query_params($conn, $query_rating, array($id_videojuego));

if ($result_rating && pg_num_rows($result_rating) > 0) {
    $rating_data = pg_fetch_assoc($result_rating);
    $media_valoracion = number_format($rating_data['media_valoracion'], 1); // Formato con un decimal
    $num_valoraciones = $rating_data['num_valoraciones'];
} else {
    $media_valoracion = "0.0";
    $num_valoraciones = 0;
}

// Lógica para añadir un nuevo comentario
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text'])) {
    $comment_text = trim($_POST['comment_text']);
    if (!empty($comment_text)) {
        // Insertar comentario en la base de datos
        $query_add_comment = "
            INSERT INTO comentarios (id_videojuego, id_usuario, texto, fecha) 
            VALUES ($1, $2, $3, NOW())
        ";
        pg_query_params($conn, $query_add_comment, array($id_videojuego, $_SESSION['username'], $comment_text));
        header("Location: Juegoseleccionado.php?id_videojuego=" . $id_videojuego);
        exit();
    }
} elseif (!$loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mostrar el modal de inicio de sesión si intenta comentar sin estar logueado
    echo "<script>document.getElementById('loginModal').style.display = 'block';</script>";
}

// Lógica para eliminar un comentario
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment_id'])) {
    $comment_id = $_POST['delete_comment_id'];
    // Eliminar comentario si el usuario es el autor
    $query_delete_comment = "
        DELETE FROM comentarios 
        WHERE id_comentario = $1 AND id_usuario = $2
    ";
    pg_query_params($conn, $query_delete_comment, array($comment_id, $_SESSION['username']));
    header("Location: Juegoseleccionado.php?id_videojuego=" . $id_videojuego);
    exit();
}

// Consulta para obtener los comentarios de un videojuego específico
$query_comments = "
    SELECT 
        comentarios.id_comentario, 
        comentarios.id_usuario, 
        comentarios.texto, 
        TO_CHAR(comentarios.fecha, 'DD/MM/YYYY HH24:MI:SS') AS fecha,
        usuario.url_foto_perfil AS url_foto_perfil
    FROM 
        comentarios 
    JOIN 
        usuario ON comentarios.id_usuario = usuario.username
    WHERE 
        comentarios.id_videojuego = $1 
    ORDER BY 
        comentarios.fecha ASC
";
$result_comments = pg_query_params($conn, $query_comments, array($id_videojuego));

// Cerrar la conexión a la base de datos
pg_close($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Meta y enlaces a archivos CSS y fuentes -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Juegos</title>
    <link rel="stylesheet" href="stylejuegoseleccionado.css?v=<?php echo time(); ?>">
    <link rel="icon" href="images/Logo_v1.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Press+Start+2P&family=Monoton&family=Lobster&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="buscar.js"></script>
</head>
<body>
    <!-- JavaScript para manejar eventos en la página -->
    <script>
        function openImage(src) {
            // Abrir una imagen en un modal (usando overlay)
            const largeImage = document.getElementById('largeImage');
            largeImage.src = src;
            const overlay = document.getElementById('overlay');
            overlay.style.display = 'flex';
        }

        function closeImage() {
            // Cerrar el modal de imagen
            const overlay = document.getElementById('overlay');
            overlay.style.display = 'none';
        }

        function validateForm() {
            // Validar si el usuario está logueado antes de enviar el formulario de votación
            const loggedIn = <?php echo json_encode($loggedIn); ?>;
            const rating = document.getElementById('rating').value;

            if (!loggedIn) {
                // Mostrar el modal de inicio de sesión si no está logueado
                document.getElementById('loginModal').style.display = 'block';
                return false;
            }

            if (!rating) {
                alert('Por favor, selecciona una puntuación.');
                return false;
            }
            return true;
        }

        function openDeleteModal(commentId) {
            // Mostrar modal de confirmación para eliminar un comentario
            document.getElementById('deleteCommentId').value = commentId;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            // Cerrar el modal de eliminación
            document.getElementById('deleteModal').style.display = 'none';
        }

        function closeLoginModal() {
            // Cerrar el modal de inicio de sesión
            document.getElementById('loginModal').style.display = 'none';
        }

        function selectRating(value) {
            // Seleccionar una puntuación en el sistema de votación
            document.getElementById('rating').value = value;
            const squares = document.querySelectorAll('.rating-square');
            squares.forEach(square => square.classList.remove('selected'));
            document.querySelector(`.rating-square:nth-child(${value})`).classList.add('selected');
        }

        function reportComment(commentId) {
            // Reportar un comentario (simulado)
            alert("Comentario " + commentId + " reportado.");
        }
        function redirectToLogin() {
        // Obtener la URL actual y codificarla
        const currentUrl = encodeURIComponent(window.location.href);
        // Redirigir al usuario a la página de login con la URL actual como parámetro
        window.location.href = '/ForumZGames/login/login.php?redirect=' + currentUrl;
        }

        // Actualizar el enlace de "Inicia sesión" en la sección de comentarios
        document.addEventListener("DOMContentLoaded", function() {
            const loginLinks = document.querySelectorAll(".comment-login-prompt a");
            const currentUrl = encodeURIComponent(window.location.href);
            loginLinks.forEach(link => {
                link.href = "/ForumZGames/login/login.php?redirect=" + currentUrl;
            });
        });
    </script>

    <!-- Barra de navegación -->
    <nav class="nav-bar">
        <ul class="nav-left">
            <li><a href="juegos.php">Juegos</a></li>
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

    <!-- Contenido principal del juego -->
    <div class="background-container" style="background-image: url('<?php echo htmlspecialchars($juego['url_imagen'] ?? 'placeholder.jpg'); ?>'); background-size: cover; background-position: center;">
        <div class="search-wrapper">
            <div class="search-container">
                <input type="text" id="search-bar" placeholder="¿Qué es lo que buscas?" onkeyup="buscarJuegos()" class="search-input">
                <button class="search-button"><i class='bx bx-search-alt-2'></i></button>
                <div id="resultados-busqueda" class="resultados-container"></div>
            </div>
        </div>
        <h1 class="game-title"><?php echo htmlspecialchars($juego['nombre_videojuego'] ?? 'Juego no encontrado'); ?></h1>
    </div>

    <!-- Información del juego -->
    <div class="game-info">
        <!-- Detalles y descripción del juego -->
        <div class="game-description">
            <img src="<?php echo htmlspecialchars($juego['url_imagen'] ?? 'placeholder.jpg'); ?>" alt="Portada del juego" class="game-cover">
            <p><strong>Descripción:</strong> <?php echo htmlspecialchars($juego['descripcion'] ?? 'No disponible'); ?></p>
            <p><strong>Precio:</strong> <?php echo htmlspecialchars($juego['precio'] ?? 'No disponible'); ?></p>
            <p><strong>Desarrollador:</strong> <?php echo htmlspecialchars($juego['desarrollador'] ?? 'No disponible'); ?></p>
            <p><strong>Año de lanzamiento:</strong> <?php echo htmlspecialchars($juego['agno_lanzamiento'] ?? 'No disponible'); ?></p>
        </div>

        <!-- Galería de imágenes y video del juego -->
        <div class="game-gallery">
            <div class="main-image">
                <?php if (!empty($juego['video1'])): ?>
                    <!-- Embebido del video de YouTube -->
                    <?php 
                        preg_match('/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $juego['video1'], $matches);
                        $videoId = $matches[1] ?? '';
                    ?>
                    <iframe width="560" height="315" src="https://www.youtube.com/embed/<?php echo htmlspecialchars($videoId); ?>" frameborder="0" allowfullscreen style="border-radius: 8px; margin-top: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);"></iframe>
                <?php else: ?>
                    <img src="placeholder.jpg" alt="No hay trailer disponible">
                <?php endif; ?>
            </div>

            <!-- Miniaturas de imágenes del juego -->
            <div class="thumbnail-gallery">
                <img src="<?php echo htmlspecialchars($juego['img1'] ?? 'placeholder.jpg'); ?>" alt="Miniatura 1" onclick="openImage(this.src)">
                <div class="thumbnail-grid">
                    <img src="<?php echo htmlspecialchars($juego['img2'] ?? 'placeholder.jpg'); ?>" alt="Miniatura 2" onclick="openImage(this.src)">
                    <img src="<?php echo htmlspecialchars($juego['img3'] ?? 'placeholder.jpg'); ?>" alt="Miniatura 3" onclick="openImage(this.src)">
                    <img src="<?php echo htmlspecialchars($juego['img4'] ?? 'placeholder.jpg'); ?>" alt="Miniatura 4" onclick="openImage(this.src)">
                    <img src="<?php echo htmlspecialchars($juego['img5'] ?? 'placeholder.jpg'); ?>" alt="Miniatura 5" onclick="openImage(this.src)">
                </div>
            </div>
        </div>

        <!-- Modal para imagen ampliada -->
        <div id="overlay" class="overlay" onclick="closeImage()">
            <span class="close-btn">&times;</span>
            <img id="largeImage" class="large-image">
        </div>
    </div>

    <!-- Modal de inicio de sesión -->
    <div id="loginModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeLoginModal()">&times;</span>
            <h2>¡Hola!</h2>
            <p>Para votar, necesitas iniciar sesión. ¿Deseas hacerlo ahora?</p>
            <!-- Modificado para pasar la URL actual como parámetro -->
            <button class="login-button" onclick="redirectToLogin()">Iniciar Sesión</button>
            <button class="cancel-button" onclick="closeLoginModal()">Cancelar</button>
        </div>
    </div>

    <!-- Sección de votación del juego -->
    <div class="vote-section" style="background-color: #001f3f; padding: 20px; color: white; border-radius: 8px; margin: 20px;">
        <h2>Vota por este juego</h2>
        <p>Tu opinión es importante. ¡Dale una puntuación y deja un comentario!</p>
        <form id="voteForm" method="POST" action="procesar_valoracion.php" onsubmit="return validateForm()">
            <!-- Campo oculto para pasar la URL actual -->
            <input type="hidden" id="current_url" name="current_url" value="">
            
            <label for="rating">Selecciona tu puntuación (1-10):</label>
            <div class="rating-container">
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <div class="rating-square" onclick="selectRating(<?php echo $i; ?>)"><?php echo $i; ?></div>
                <?php endfor; ?>
                <!-- Mostrar rating y número de votos -->
                <div class="rating-display">
                    <div class="rating-circle" style="background-color: <?php 
                        if ($media_valoracion < 5) {
                            echo 'red';
                        } elseif ($media_valoracion == 5) {
                            echo 'yellow';
                        } elseif ($media_valoracion >= 6 && $media_valoracion < 7) {
                            echo 'lightgreen';
                        } elseif ($media_valoracion >= 7 && $media_valoracion < 9) {
                            echo 'darkgreen';
                        } else {
                            echo 'blue';
                        } 
                    ?>;">
                        <?php echo $media_valoracion; ?>
                    </div>
                    <p class="votes-text">Número de votaciones: <?php echo $num_valoraciones; ?></p>
                </div>
            </div>
            <input type="hidden" id="rating" name="puntuacion" required>
            <input type="hidden" id="id_videojuego" name="id_videojuego" value="<?php echo htmlspecialchars($id_videojuego); ?>">
            <button type="submit" class="vote-submit-button">Enviar Voto</button>
        </form>
    </div>

    <script>
        // Rellenar el campo oculto con la URL actual para permitir la redirección después de votar
        document.getElementById('current_url').value = window.location.href;
    </script>


    <!-- Sección para comentar sobre el juego -->
    <div class="comments-section">
        <h3>Comentarios</h3>
        <?php if (pg_num_rows($result_comments) > 0): ?>
            <?php while ($row = pg_fetch_assoc($result_comments)): ?>
                <?php
                    // Verificar si el comentario pertenece al usuario logueado
                    $isCurrentUser = $loggedIn && $row['id_usuario'] == $_SESSION['username'];
                    $commentClass = $isCurrentUser ? 'comment-right' : 'comment-left';
                    $profilePicture = !empty($row['url_foto_perfil']) ? htmlspecialchars($row['url_foto_perfil']) : 'images/default-avatar.png';
                ?>
                <div class="comment <?php echo $commentClass; ?>">
                    <div class="comment-actions">
                    </div>
                    <div class="comment-header">
                        <img class="user-avatar" src="<?php echo $profilePicture; ?>" alt="Foto de perfil">
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($row['id_usuario']); ?></span>
                            <small class="message-date"><?php echo $row['fecha']; ?></small>
                        </div>
                    </div>
                    
                    <div class="comment-content">
                        <p><?php echo htmlspecialchars($row['texto']); ?></p>
                        <?php if ($isCurrentUser): ?>
                            <!-- Mostrar el botón "Eliminar" si el comentario es del usuario logueado -->
                            <form method="POST" action="">
                                <input type="hidden" name="delete_comment_id" value="<?php echo $row['id_comentario']; ?>">
                                <button type="button" class="delete-button" onclick="openDeleteModal(<?php echo $row['id_comentario']; ?>)">Eliminar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color: white;">No hay comentarios aún. ¡Sé el primero en comentar!</p>
        <?php endif; ?>

        <?php if ($loggedIn): ?>
            <!-- Formulario para añadir un comentario si el usuario está logueado -->
            <form method="POST" action="">
                <textarea name="comment_text" required placeholder="Escribe tu comentario aquí..."></textarea>
                <button type="submit">Añadir comentario</button>
            </form>
        <?php else: ?>
            <p class="comment-login-prompt">
                <a href="/ForumZGames/login/login.php">Inicia sesión</a> para añadir un comentario.
            </p>
        <?php endif; ?>
    </div>  

    <!-- Modal de confirmación de eliminación -->
    <div id="deleteModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <h2>Confirmación</h2>
            <p>¿Estás seguro de que quieres eliminar este comentario?</p>
            <form method="POST" action="">
                <input type="hidden" id="deleteCommentId" name="delete_comment_id">
                <button type="submit" name="confirm_delete" class="delete-confirm-button">Eliminar</button>
                <button type="button" class="cancel-button" onclick="closeDeleteModal()">Cancelar</button>
            </form>
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
