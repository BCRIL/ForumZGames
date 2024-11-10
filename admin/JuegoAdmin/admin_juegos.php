<?php
session_start();

// Conectar a la base de datos
$conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

// Comprobar si el usuario ha iniciado sesión
if (!isset($_SESSION['admin_username'])) {
    // Redirigir a la página de inicio de sesión si no hay sesión activa
    header("Location: login.php");
    exit();
}

// Si ha iniciado sesión, obtener el nombre de usuario y otros datos del administrador
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

// Comprobar si el administrador ha iniciado sesión (Variable para JS)
$loggedIn = isset($_SESSION['admin_username']);

// Definir la página actual y el número de juegos por página
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Página actual
$limit = 5; // Número de juegos por página
$offset = ($page - 1) * $limit; // Desplazamiento

$filter_genero = isset($_GET['genero']) ? (int)$_GET['genero'] : null;
$filter_plataforma = isset($_GET['plataforma']) ? (int)$_GET['plataforma'] : null;

// Obtener el juego destacado (último lanzado, menor id en caso de empate)
$query_destacado = "
    SELECT 
        v.id_videojuego,
        v.nombre AS nombre_videojuego, 
        v.agno_lanzamiento, 
        STRING_AGG(DISTINCT g.nombre_genero, ', ') AS generos, 
        STRING_AGG(DISTINCT p.nombre_plataforma, ', ') AS plataformas,
        v.url_imagen,
        COALESCE(AVG(val.puntuacion), 0) AS media_valoracion,
        (SELECT COUNT(*) FROM valoracion WHERE id_videojuego = v.id_videojuego) AS num_valoraciones
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
    LEFT JOIN 
        valoracion val ON v.id_videojuego = val.id_videojuego
    WHERE 
        ($1::int IS NULL OR g.id_genero = $1) AND
        ($2::int IS NULL OR p.id_plataforma = $2)
    GROUP BY 
        v.id_videojuego, v.nombre, v.agno_lanzamiento, v.url_imagen
    ORDER BY 
        v.agno_lanzamiento DESC, 
        v.id_videojuego ASC
    LIMIT 1;
";

// Ejecutar la consulta del juego destacado
$result_destacado = pg_query_params($conn, $query_destacado, array($filter_genero, $filter_plataforma));

if ($result_destacado === false) {
    echo "Error en la consulta del juego destacado: " . pg_last_error($conn);
    exit();
}

// Obtener el juego destacado
$juego_destacado = pg_fetch_assoc($result_destacado);
if ($juego_destacado === false) {
    $juego_destacado = null; // Establecer como null si no hay juego destacado
}

// Asegurarse de que no accedemos a elementos de un array nulo
if ($juego_destacado) {
    $juego_destacado['generos'] = $juego_destacado['generos'] ?? 'No disponible';
    $juego_destacado['plataformas'] = $juego_destacado['plataformas'] ?? 'No disponible';
}

$query_otros_juegos = "
    SELECT 
        v.id_videojuego,
        v.nombre AS nombre_videojuego, 
        v.agno_lanzamiento, 
        STRING_AGG(DISTINCT g.nombre_genero, ', ') AS generos, 
        STRING_AGG(DISTINCT p.nombre_plataforma, ', ') AS plataformas,
        v.url_imagen,
        COALESCE(AVG(val.puntuacion), 0) AS media_valoracion,
        (SELECT COUNT(*) FROM valoracion WHERE id_videojuego = v.id_videojuego) AS num_valoraciones
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
    LEFT JOIN 
        valoracion val ON v.id_videojuego = val.id_videojuego
    WHERE 
        ($1::int IS NULL OR v.id_videojuego != $1) AND
        ($2::int IS NULL OR g.id_genero = $2) AND
        ($3::int IS NULL OR p.id_plataforma = $3)
    GROUP BY 
        v.id_videojuego, v.nombre, v.agno_lanzamiento, v.url_imagen
    ORDER BY 
        v.agno_lanzamiento DESC, 
        v.id_videojuego ASC
    LIMIT $4 OFFSET $5;
";

// Ejecutar la consulta para los otros juegos
$result_otros = pg_query_params($conn, $query_otros_juegos, array($juego_destacado['id_videojuego'] ?? null, $filter_genero, $filter_plataforma, $limit, $offset));

// Obtener los otros juegos
$otros_juegos = pg_fetch_all($result_otros);
if ($otros_juegos === false) {
    $otros_juegos = []; // Asegurarse de que sea un array vacío si hay un error
}

// Consulta para contar el total de juegos para la paginación
$query_total = "
    SELECT COUNT(DISTINCT v.id_videojuego) as total
    FROM videojuego v
    LEFT JOIN videojuego_genero vg ON v.id_videojuego = vg.id_videojuego
    LEFT JOIN genero g ON vg.id_genero = g.id_genero
    LEFT JOIN videojuego_plataforma vp ON v.id_videojuego = vp.id_juego
    LEFT JOIN plataforma p ON vp.id_plataforma = p.id_plataforma
    WHERE ($1::int IS NULL OR v.id_videojuego != $1) AND
    ($2::int IS NULL OR g.id_genero = $2) AND
    ($3::int IS NULL OR p.id_plataforma = $3);
";

// Ejecutar la consulta para obtener el total de juegos
$result_total = pg_query_params($conn, $query_total, array($juego_destacado['id_videojuego'] ?? null, $filter_genero, $filter_plataforma));

$total_juegos = ($result_total && pg_num_rows($result_total) > 0) ? pg_fetch_assoc($result_total)['total'] : 0;
$total_paginas = ceil($total_juegos / $limit); // Total de páginas
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Juegos</title>
    <link rel="stylesheet" href="styleadmin_juegos.css?v=<?php echo time(); ?>">
    <link rel="icon" href="images/Logo_v1.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Press+Start+2P&family=Monoton&family=Lobster&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="buscar.js"></script> <!-- Archivo JS para manejar la búsqueda -->
    <script>

        let videojuegoIdToDelete;

        function openDeleteModal(videojuegoId) {
            // Guardar el ID de la noticia que se desea eliminar
            videojuegoIdToDelete = videojuegoId;
            // Mostrar el modal de confirmación
            document.getElementById('deleteModal').style.display = 'block';
        }

        function confirmDelete() {
            // Verificar que haya un ID de noticia
            if (videojuegoIdToDelete) {
                // Realizar la solicitud para eliminar la noticia
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'delete_juego.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        // Si se eliminó exitosamente, refrescar la página
                        location.reload();
                    }
                };
                // Enviar el ID de la noticia al servidor
                xhr.send(`id_videojuego=${videojuegoIdToDelete}`);
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function openVoteModal(gameId) {
            // Verifica si el usuario está logueado
            if (<?php echo json_encode($loggedIn); ?>) {
                document.getElementById('voteModal').style.display = 'block'; // Mostrar el modal de votar
                document.getElementById('id_videojuego').value = gameId; // Rellenar el ID en el formulario
            } else {
                document.getElementById('loginModal').style.display = 'block'; // Mostrar el modal de inicio de sesión
            }
        }

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

        function filterGames() {
            const genero = document.getElementById('filter-genero').value;
            const plataforma = document.getElementById('filter-plataforma').value;
            const searchParams = new URLSearchParams(window.location.search);
            
            // Actualiza los parámetros de búsqueda con los nuevos valores de los filtros
            if (genero) {
                searchParams.set('genero', genero);
            } else {
                searchParams.delete('genero');
            }

            if (plataforma) {
                searchParams.set('plataforma', plataforma);
            } else {
                searchParams.delete('plataforma');
            }

            // Reinicia a la primera página cuando se aplican filtros
            searchParams.set('page', 1);

            // Redirigir a la misma página con los nuevos parámetros
            window.location.href = `?${searchParams.toString()}`;
        }

        function redirectToLogin() {
            const currentUrl = encodeURIComponent(window.location.href);
            window.location.href = 'login.php?redirect=' + currentUrl;
        }
        // Función para abrir el modal de agregar videojuego
        function openAddGameModal() {
            document.getElementById('addGameModal').style.display = 'block';
        }

        // Extender la función de cerrar modal para incluir el modal de agregar juego
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Manejar el envío del formulario de agregar videojuego
        document.getElementById('addGameForm').addEventListener('submit', function(event) {
            // Aquí se puede implementar lógica adicional si se requiere, por ejemplo, validaciones personalizadas.
        });
    </script>
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
            <li><a href="admin_juegos.php">Juegos</a></li>
            <li><a href="/ForumZGames/admin/ForoAdmin/admin_foro.php">Foros</a></li>
            <li><a href="/ForumZGames/admin/NoticiasAdmin/admin_noticias.php">Noticias</a></li>
            <hr class="separator-sup">
            <li><a href="/ForumZGames/admin/IndexAdmin/admin_index.php">Inicio</a></li>
            <hr class="separator-inf">
            <li><a href="/ForumZGames/admin/NotificacionesAdmin/admin_notificaciones.php">Notificaciones</a></li>
        </ul>
        <div class="logo">
            <img src="/ForumZgames/imagenes/images/Forum_ZGames.jpeg" alt="Logo" />
        </div>
    </nav>

    <!-- BUSCADOR -->
    <div class="search-wrapper">
        <div class="search-container">
            <input type="text" id="search-bar" placeholder="¿Qué es lo que buscas?" onkeyup="buscarJuegos()" class="search-input">
            <button class="search-button">
                <i class='bx bx-search-alt-2'></i>
            </button>
            <div id="resultados-busqueda" class="resultados-container"></div>
        </div>

        <!-- Filtro por género -->
        <select id="filter-genero" class="filter-select" onchange="toggleFilters('genero')">
            <option value="">Todos los géneros</option>
            <?php
            $query_generos = "SELECT id_genero, nombre_genero FROM genero ORDER BY nombre_genero ASC";
            $result_generos = pg_query($conn, $query_generos);
            while ($genero = pg_fetch_assoc($result_generos)) {
                $selected = ($filter_genero == $genero['id_genero']) ? 'selected' : '';
                echo '<option value="' . $genero['id_genero'] . '" ' . $selected . '>' . htmlspecialchars($genero['nombre_genero']) . '</option>';
            }
            ?>
        </select>

        <!-- Filtro por plataforma -->
        <select id="filter-plataforma" class="filter-select">
            <option value="">Todas las plataformas</option>
            <?php
            $query_plataformas = "SELECT id_plataforma, nombre_plataforma FROM plataforma ORDER BY nombre_plataforma ASC";
            $result_plataformas = pg_query($conn, $query_plataformas);
            while ($plataforma = pg_fetch_assoc($result_plataformas)) {
                $selected = ($filter_plataforma == $plataforma['id_plataforma']) ? 'selected' : '';
                echo '<option value="' . $plataforma['id_plataforma'] . '" ' . $selected . '>' . htmlspecialchars($plataforma['nombre_plataforma']) . '</option>';
            }
            ?>
        </select>
        <!-- Botón para filtrar -->
        <button class="filter-button" onclick="filterGames()">Filtrar</button>
        
        <!-- Boton para añadir nuevas noticias -->
        <a href="anyadir_juego.php" class="anyadir-button">
            <i class='bx bx-plus'></i> Añadir noticia
        </a>
    </div>

            
    <div class="game-list">
        <?php if ($juego_destacado): ?>
            <div class="highlighted-game" style="background-image: url('<?php echo htmlspecialchars($juego_destacado['url_imagen']); ?>');">
                <div class="game-content">      
                    
                <a href="admin_Juegoseleccionado.php?id_videojuego=<?php echo $juego_destacado['id_videojuego']; ?>" style="text-decoration: none; color: inherit;">
                <img src="<?php echo htmlspecialchars($juego_destacado['url_imagen']); ?>" alt="<?php echo htmlspecialchars($juego_destacado['nombre_videojuego']); ?>" class="game-image">
                </a>
                    <div class="game-details">
                        <a href="admin_Juegoseleccionado.php?id_videojuego=<?php echo $juego_destacado['id_videojuego']; ?>" style="text-decoration: none; color: inherit;">
                            <h3><?php echo htmlspecialchars($juego_destacado['nombre_videojuego']); ?></h3>
                        </a>
                        <p>Género: <?php echo htmlspecialchars($juego_destacado['generos']); ?></p>
                        <p>Plataforma: <?php echo htmlspecialchars($juego_destacado['plataformas']); ?></p>
                        <p>Año de lanzamiento: <?php echo htmlspecialchars($juego_destacado['agno_lanzamiento']); ?></p>
                        <p>Número de votaciones: <?php echo htmlspecialchars($juego_destacado['num_valoraciones']); ?></p>
                        <button class="vote-button" onclick="openVoteModal(<?php echo $juego_destacado['id_videojuego']; ?>)">VOTA</button>
                    </div>
                    <a href="javascript:void(0);" class="delete-button" onclick="openDeleteModal(<?php echo $juego_destacado['id_videojuego']; ?>)">
                            <i class='bx bx-trash'></i>
                    </a>
                    <div class="rating-circle" style="background-color: <?php 
                    
                        $media_valoracion = $juego_destacado['media_valoracion'];
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
                        <?php echo number_format($juego_destacado['media_valoracion'], 1); ?>
                    </div>
                </div>
            </div>

            <!-- Modal para iniciar sesión -->
            <div id="loginModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('loginModal')">&times;</span>
                    <h2>Iniciar Sesión</h2>
                    <p>Necesitas iniciar sesión o registrarte para votar.</p>
                    <div class="button-container">
                        <button class="modal-button" onclick="redirectToLogin()">Iniciar Sesión</button>
                        <button class="modal-button" onclick="window.location.href='Registro.html'">Registrarse</button>
                    </div>
                </div>
            </div>
            <!-- Modal para votar -->
            <div id="voteModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('voteModal')">&times;</span>
                    <h2>Vota por este juego</h2>
                    <form id="voteForm" method="POST" action="procesar_valoracion.php" onsubmit="return validateForm()">
                        <label for="rating">Selecciona tu puntuación (1-10):</label>
                        <div class="rating-container">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <div class="rating-square" onclick="selectRating(<?php echo $i; ?>)">
                                    <?php echo $i; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" id="rating" name="puntuacion" required>
                        <input type="hidden" id="id_videojuego" name="id_videojuego" value=""> <!-- ID del videojuego -->
                        <button type="submit">Enviar Voto</button>
                    </form>
                </div>
            </div>


            <script>
                function validateForm() {
                    const rating = document.getElementById('rating').value;

                    if (!rating) {
                        alert('Por favor, selecciona una puntuación.');
                        return false;
                    }
                    return true;
                }
            </script>

        <?php endif; ?>

        <ul class="otros-juegos">
            <?php if ($otros_juegos): ?>
                <?php foreach ($otros_juegos as $juego): ?>
                    <li class="game-item">
                        <!-- Solo el enlace en la imagen y el título para evitar mover la estructura -->
                        <a href="admin_Juegoseleccionado.php?id_videojuego=<?php echo $juego['id_videojuego']; ?>" style="text-decoration: none; color: inherit;">
                            <img src="<?php echo htmlspecialchars($juego['url_imagen']); ?>" alt="<?php echo htmlspecialchars($juego['nombre_videojuego']); ?>" class="game-image">
                        </a>
                        
                        <div class="game-details">
                            <a href="admin_Juegoseleccionado.php?id_videojuego=<?php echo $juego['id_videojuego']; ?>" style="text-decoration: none; color: inherit;">
                                <h3><?php echo htmlspecialchars($juego['nombre_videojuego']); ?></h3>
                            </a>
                            <p>Género: <?php echo htmlspecialchars($juego['generos']); ?></p>
                            <p>Plataforma: <?php echo htmlspecialchars($juego['plataformas']); ?></p>
                            <p>Año de lanzamiento: <?php echo htmlspecialchars($juego['agno_lanzamiento']); ?></p>
                            <p>Número de votaciones: <?php echo htmlspecialchars($juego['num_valoraciones']); ?></p>
                            <button class="vote-button" onclick="openVoteModal(<?php echo $juego['id_videojuego']; ?>)">VOTA</button>
                        </div>
                        <a href="javascript:void(0);" class="delete-button" onclick="openDeleteModal(<?php echo $juego['id_videojuego']; ?>)">
                            <i class='bx bx-trash'></i>
                        </a>
                        <!-- Mantenemos el círculo de valoración fuera del contenedor de detalles -->
                        <div class="rating-circle" style="background-color: <?php 
                            $media_valoracion = $juego['media_valoracion'];
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
                            <?php echo number_format($juego['media_valoracion'], 1); ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="no-juegos">No se encontraron más juegos...</li>
            <?php endif; ?>
        </ul>
        
        <!-- Modal para borrar un juego -->
        <div id="deleteModal" class="modal" style="display: none;">
            <div class="modal-content">
                <h2>¿Deseas eliminar este juego?</h2>
                <p>Esta acción no se puede deshacer.</p>
                <button class="confirmButton" onclick="confirmDelete()">Sí</button>
                <button class="denyButton" onclick="closeModal('deleteModal')">No</button>
            </div>
        </div>

        <!-- Paginación -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?php echo "admin_juegos.php?page=" . ($page - 1) . 
                            ($filter_genero ? "&genero=" . $filter_genero : "") . 
                            ($filter_plataforma ? "&plataforma=" . $filter_plataforma : ""); ?>">&laquo; Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="<?php echo "admin_juegos.php?page=$i" . 
                                ($filter_genero ? "&genero=$filter_genero" : "") . 
                                ($filter_plataforma ? "&plataforma=$filter_plataforma" : ""); ?>" 
                class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>

            <?php if ($page < $total_paginas): ?>
                <a href="<?php echo "admin_juegos.php?page=" . ($page + 1) . 
                            ($filter_genero ? "&genero=" . $filter_genero : "") . 
                            ($filter_plataforma ? "&plataforma=" . $filter_plataforma : ""); ?>">Siguiente &raquo;</a>
            <?php endif; ?>
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
