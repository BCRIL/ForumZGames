<?php
session_start();

// Conectar a la base de datos
$conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

// Comprobar si el usuario ha iniciado sesión
if (!isset($_SESSION['admin_username'])) {
    // Redirigir a la página de inicio de sesión si no hay sesión activa
    header("Location: /ForumZGames/login.php"); // Cambia 'login.php' por el nombre de tu página de inicio de sesión
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

// Definir la página actual y el número de noticias por página (multiplo de 3)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Página actual
$limit = 12; // Número de noticias por página
$offset = ($page - 1) * $limit; // Desplazamiento

// Consulta para hallar las noticias mas recientes
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
    LIMIT $1 OFFSET $2; -- Limitamos a 12 noticias y aplicamos offset
";

// Ejecutar la consulta para las noticias
$result_noticias = pg_query_params($conn, $query_noticias, array($limit, $offset));

if ($result_noticias === false) {
    echo "Error en la consulta de noticias: " . pg_last_error($conn);
    exit();
}

// Obtener todas las noticias
$noticias = pg_fetch_all($result_noticias);

// Consulta para contar el total de noticias para la paginación
$query_total = "
    SELECT COUNT(*) as total
    FROM noticia n
";

// Ejecutar la consulta para obtener el total de noticias
$result_total = pg_query ($conn, $query_total);

if ($result_total === false) {
    echo "Error en la consulta del total de noticias: " . pg_last_error($conn);
    exit();
}

$total_noticias = pg_fetch_assoc($result_total)['total'];
$total_paginas = ceil($total_noticias / $limit); // Total de páginas
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noticias administrador</title>
    <link rel="stylesheet" href="styleadmin_noticias.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Press+Start+2P&family=Monoton&family=Lobster&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="/ForumZGames/imagenes/images/Logo_v1.png" type="image/x-icon">

    <script src="buscar.js"></script> <!-- Archivo JS para manejar la búsqueda -->
    
    <script>
        let noticiaIdToDelete;

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function openDeleteModal(noticiaId) {
            // Guardar el ID de la noticia que se desea eliminar
            noticiaIdToDelete = noticiaId;
            // Mostrar el modal de confirmación
            document.getElementById('deleteModal').style.display = 'block';
        }

        function confirmDelete() {
            // Verificar que haya un ID de noticia
            if (noticiaIdToDelete) {
                // Realizar la solicitud para eliminar la noticia
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'delete_noticia.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        // Si se eliminó exitosamente, refrescar la página
                        location.reload();
                    }
                };
                // Enviar el ID de la noticia al servidor
                xhr.send(`id_noticia=${noticiaIdToDelete}`);
            }
        }
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
            <li><a href="/ForumZGames/admin/IndexAdmin/admin_index.php">Inicio</a></li>
            <li><a href="/ForumZGames/admin/JuegoAdmin/admin_juegos.php">Juegos</a></li>
            <li><a href="/ForumZGames/admin/ForoAdmin/admin_foro.php">Foros</a></li>
            <hr class="separator-sup">
            <li><a href="admin_noticias.php">Noticias</a></li>
            <hr class="separator-inf">
            <li><a href="/ForumZGames/admin/NotificacionesAdmin/admin_notificaciones.php">Notificaciones</a></li>
        </ul>
        <div class="logo">
            <img src="/ForumZGames/imagenes/images/Forum_ZGames.jpeg" alt="Logo" />
        </div>
    </nav>
        <!-- Barra de búsqueda -->
    <div class="search-wrapper">
        <div class="search-container">
            <input type="text" id="search-bar" placeholder="¿Qué es lo que buscas?" onkeyup="buscarNoticias()" class="search-input">
            <button class="search-button">
                <i class='bx bx-search-alt-2'></i>
            </button>
            <div id="resultados-busqueda" class="resultados-container"></div>
        </div>
    </div>

        <!-- Boton para añadir nuevas noticias -->
    <a href="anyadir_noticia.php" class="anyadir-button">
        <i class='bx bx-plus'></i> Añadir noticia
    </a>


   <!-- Noticias -->
   <div class="new-list">
        <?php if ($noticias): ?>
            <?php foreach ($noticias as $noticia_ind): ?>
                <div class="highlighted-new">
                    <div class="background-box"></div> <!-- Caja de fondo negro -->
                    <a href="<?php echo htmlspecialchars($noticia_ind['url_noticia']); ?>" class="foreground-box">
                        <img src="<?php echo htmlspecialchars($noticia_ind['url_imagen']); ?>" alt="<?php echo htmlspecialchars($noticia_ind['titulo']); ?>" class="new-image">
                        <div class="titulo-noticia"><?php echo htmlspecialchars($noticia_ind['titulo']); ?></div>
                        <a href="javascript:void(0);" class="delete-button" onclick="openDeleteModal(<?php echo $noticia_ind['id_noticia']; ?>)">
                            <i class='bx bx-trash'></i>
                        </a>
                    </a>
                    
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No se encontraron noticias.</p>
        <?php endif; ?>
    </div>

    <!-- Modal para borrar una noticia -->
    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>¿Deseas eliminar esta noticia?</h2>
            <p>Esta acción no se puede deshacer.</p>
            <button class="confirmButton" onclick="confirmDelete()">Sí</button>
            <button class="denyButton" onclick="closeModal('deleteModal')">No</button>
        </div>
    </div>

    <!-- Paginación -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="admin_noticias.php?page=<?php echo $page - 1; ?>">&laquo; Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="admin_noticias.php?page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $total_paginas): ?>
            <a href="admin_noticias.php?page=<?php echo $page + 1; ?>">Siguiente &raquo;</a>
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
</body>
</html>