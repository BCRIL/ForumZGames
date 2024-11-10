<?php
session_start(); // Iniciar sesión

// Verificar si existe una cookie "Recuérdame" y no hay una sesión activa
if (isset($_COOKIE['rememberme']) && !isset($_SESSION['username'])) {
    $username = $_COOKIE['rememberme'];

    // Conectar a la base de datos
    $conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

    if ($conn) {
        // Buscar al usuario en la base de datos usando la cookie
        $query = "SELECT username FROM usuario WHERE username = $1";
        $result = pg_query_params($conn, $query, array($username));

        // Restaurar la sesión si el usuario existe
        if ($result && pg_num_rows($result) == 1) {
            $_SESSION['username'] = $username;
            header("Location: index.php"); // Redirigir a la página de inicio
            exit();
        } else {
            // Borrar la cookie si el usuario no existe
            setcookie('rememberme', '', time() - 3600, "/");
        }

        // Cerrar la conexión a la base de datos
        pg_close($conn);
    }
}

// Verificar si el usuario ha iniciado sesión
$loggedIn = isset($_SESSION['username']);
$username = $loggedIn ? $_SESSION['username'] : null;

// Conectar a la base de datos
$conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

if (!$conn) {
    die("Error en la conexión a la base de datos.");
}

// Consulta para obtener los juegos votados por el usuario y sus respectivas valoraciones medias
$query_voted_games = "
    SELECT 
        v.id_videojuego,
        v.nombre AS nombre_videojuego, 
        v.agno_lanzamiento, 
        STRING_AGG(DISTINCT g.nombre_genero, ', ') AS generos, 
        STRING_AGG(DISTINCT p.nombre_plataforma, ', ') AS plataformas,
        v.url_imagen,
        val.puntuacion AS user_votacion,
        COALESCE(AVG(val_global.puntuacion), 0) AS media_valoracion
    FROM 
        videojuego v
    LEFT JOIN 
        videojuego_genero vg ON v.id_videojuego = vg.id_videojuego
    LEFT JOIN 
        genero g ON vg.id_genero = g.id_genero
    LEFT JOIN 
        videojuego_plataforma vp ON v.id_videojuego = vp.id_juego
    LEFT JOIN 
        plataforma p ON vp.id_plataforma = p.id_plataforma
    JOIN 
        valoracion val ON v.id_videojuego = val.id_videojuego AND val.id_usuario = $1
    LEFT JOIN 
        valoracion val_global ON v.id_videojuego = val_global.id_videojuego
    GROUP BY 
        v.id_videojuego, val.puntuacion
    ORDER BY 
        v.agno_lanzamiento DESC, 
        v.id_videojuego ASC;
";

// Ejecutar la consulta para obtener los juegos votados por el usuario
$result_voted_games = pg_query_params($conn, $query_voted_games, array($username));

// Almacenar los resultados en un array
$voted_games = pg_fetch_all($result_voted_games);
if ($voted_games === false) {
    $voted_games = [];
}

// Cerrar la conexión a la base de datos
pg_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Reseñas</title>
    <link rel="stylesheet" href="styleReseñas.css?v=<?php echo time(); ?>"> <!-- Cache busting para el CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Press+Start+2P&family=Monoton&family=Lobster&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

        <script>
        // Función para abrir el modal de votación y asignar el ID del juego
        function openVoteModal(gameId) {
            document.getElementById('voteModal').style.display = 'block';
            document.getElementById('id_videojuego').value = gameId;
        }

        // Función para cerrar el modal especificado
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Función para seleccionar la puntuación del juego
        function selectRating(value) {
            document.getElementById('rating').value = value;

            // Remover la clase 'selected' de todos los cuadrados
            const squares = document.querySelectorAll('.rating-square');
            squares.forEach(square => {
                square.classList.remove('selected');
            });

            // Agregar la clase 'selected' al cuadrado seleccionado
            const selectedSquare = document.querySelector(`.rating-square:nth-child(${value})`);
            selectedSquare.classList.add('selected');
        }

        // Validación del formulario de votación antes de enviar
        function validateForm() {
            const rating = document.getElementById('rating').value;

            if (!rating) {
                alert('Por favor, selecciona una puntuación.');
                return false;
            }
            return true;
        }
    </script>

</head>
<body>
    <!-- Barra de navegación -->
    <nav class="nav-bar">
        <ul class="nav-left">   
            <li><a href="reseñas.php">RESEÑAS</a></li>
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

    <!-- Contenedor del título principal -->
    <div class="title-container">
        <h1 class="title-styled">Juegos que Has Votado</h1>
    </div>

    <!-- Lista de juegos votados -->
    <div class="game-list">
        <?php if ($voted_games): ?>
            <ul class="otros-juegos">
                <?php foreach ($voted_games as $juego): ?>
                    <li class="game-item">
                        <a href="/ForumZGames/User/JuegoUser/Juegoseleccionado.php?id_videojuego=<?php echo $juego['id_videojuego']; ?>" style="text-decoration: none; color: inherit;">
                            <img src="<?php echo htmlspecialchars($juego['url_imagen']); ?>" alt="<?php echo htmlspecialchars($juego['nombre_videojuego']); ?>" class="game-image">
                        </a>
                        
                        <div class="game-details">
                            <a href="/ForumZGames/User/JuegoUser/Juegoseleccionado.php?id_videojuego=<?php echo $juego['id_videojuego']; ?>" style="text-decoration: none; color: inherit;">
                                <h3><?php echo htmlspecialchars($juego['nombre_videojuego']); ?></h3>
                            </a>
                            <p>Género: <?php echo htmlspecialchars($juego['generos']); ?></p>
                            <p>Plataforma: <?php echo htmlspecialchars($juego['plataformas']); ?></p>
                            <p>Año de lanzamiento: <?php echo htmlspecialchars($juego['agno_lanzamiento']); ?></p>
                            <p>Tu votación: <?php echo htmlspecialchars($juego['user_votacion']); ?></p>
                            <button class="vote-button" onclick="openVoteModal(<?php echo $juego['id_videojuego']; ?>)">Cambiar Votación</button>
                        </div>

                        <!-- Mostrar la puntuación media de cada juego -->
                        <div class="rating-circle" style="background-color: <?php 
                            $media_valoracion = $juego['media_valoracion'];
                            if ($media_valoracion < 5) {
                                echo 'red'; // Puntuación baja
                            } elseif ($media_valoracion == 5) {
                                echo 'yellow'; // Puntuación media
                            } elseif ($media_valoracion >= 6 && $media_valoracion < 7) {
                                echo 'lightgreen'; // Puntuación ligeramente buena
                            } elseif ($media_valoracion >= 7 && $media_valoracion < 9) {
                                echo 'darkgreen'; // Puntuación buena
                            } else {
                                echo 'blue'; // Puntuación excelente
                            } 
                        ?>;">
                            <?php echo number_format($media_valoracion, 1); ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No has votado ningún juego todavía.</p>
        <?php endif; ?>
    </div>

    <!-- Modal para cambiar la votación -->
    <div id="voteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('voteModal')">&times;</span>
            <h2>Cambia tu votación para este juego</h2>
            <form id="voteForm" method="POST" action="/ForumZGames/user/JuegoUser/procesar_valoracion.php" onsubmit="return validateForm()">
                <input type="hidden" name="current_url" value="reseñas.php"> <!-- URL de referencia para redirección -->
                <label for="rating">Selecciona tu puntuación (1-10):</label>
                <div class="rating-container">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <div class="rating-square" onclick="selectRating(<?php echo $i; ?>)">
                            <?php echo $i; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                <input type="hidden" id="rating" name="puntuacion" required> <!-- Campo oculto para la puntuación -->
                <input type="hidden" id="id_videojuego" name="id_videojuego" value=""> <!-- ID del videojuego -->
                <button type="submit">Actualizar Votación</button>
            </form>
        </div>
    </div>

    <!-- Pie de página -->
    <div class="footer">
        <h2 class="footer-title">Forum ZGames</h2>
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
