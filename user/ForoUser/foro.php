<?php
// Conectar a la base de datos
session_start();
$conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

// Verificar si ya existe una cookie de "Recuérdame" y no hay sesión activa
if (isset($_COOKIE['rememberme']) && !isset($_SESSION['username'])) {
    $username = $_COOKIE['rememberme'];
    $query = "SELECT username FROM usuario WHERE username = $1";
    $result = pg_query_params($conn, $query, array($username));

    if ($result && pg_num_rows($result) == 1) {
        $_SESSION['username'] = $username;
        header("Location: index.php");
        exit();
    } else {
        setcookie('rememberme', '', time() - 3600, "/");
    }
}

$loggedIn = isset($_SESSION['username']);
$username = $loggedIn ? $_SESSION['username'] : null;

// Define pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 3;
$offset = ($page - 1) * $limit;

// Query to count total forums for pagination
$query_total = "SELECT COUNT(*) as total FROM foro";
$result_total = pg_query($conn, $query_total);
$total_foros = pg_fetch_assoc($result_total)['total'];
$total_paginas = ceil($total_foros / $limit);

$query_foros = "
    SELECT f.id_foro, f.titulo, f.fecha, f.id_usuario, f.imagen, u.url_foto_perfil,
           (SELECT MAX(m.fecha) FROM mensaje m WHERE m.id_foro = f.id_foro AND m.mostrar = true) AS fecha_ultimo_mensaje,
           (SELECT COUNT(DISTINCT m.id_usuario) FROM mensaje m WHERE m.id_foro = f.id_foro) AS participantes
    FROM foro f
    JOIN usuario u ON f.id_usuario = u.username
    ORDER BY f.fecha DESC, f.id_foro ASC
    LIMIT $1 OFFSET $2;
";

$result_foros = pg_query_params($conn, $query_foros, array($limit, $offset));
$foros = pg_fetch_all($result_foros);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foros</title>
    <link rel="stylesheet" href="stylesForo.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Press+Start+2P&family=Monoton&family=Lobster&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
    <link rel="icon" href="images/Logo_v1.png" type="image/x-icon">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="buscar.js"></script> <!-- Load buscar.js for search functionality -->
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

    <script>
        function buscarForos() {
            const query = document.getElementById("search-bar").value;
            const resultadosBusqueda = document.getElementById("resultados-busqueda");

            if (query.length === 0) {
                resultadosBusqueda.innerHTML = "";
                return;
            }

            // Realizar la solicitud AJAX a buscar_foros.php
            const xhr = new XMLHttpRequest();
            xhr.open("GET", "buscar_foros.php?q=" + encodeURIComponent(query), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    resultadosBusqueda.innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }
    </script>



    <!-- Barra de búsqueda -->
    <div class="search-wrapper">
        <div class="search-container">
            <input type="text" id="search-bar" placeholder="¿Qué es lo que buscas?" onkeyup="buscarForos()" class="search-input">
            <button class="search-button">
                <i class='bx bx-search-alt-2'></i>
            </button>
            <div id="resultados-busqueda" class="resultados-container"></div>
        </div>
    </div>


    <!-- Boton para añadir nuevas foros -->
    <a href="anyadirForo.php" class="anyadir-button">
        <i class='bx bx-plus'></i> Añadir foro
    </a>

    <!-- Foros list section -->
    <div class="new-list">
        <?php if ($foros): ?>
            <?php foreach ($foros as $foros_ind): ?>
                <div class="foro-item">
                    <div class="image-container">
                        <a href="chatForo.php?id_foro=<?php echo htmlspecialchars($foros_ind['id_foro']); ?>">
                            <div class="blue-square">
                                <div class="texto-cuadro-azul">
                                    <span class="titulo-cuadro-azul">
                                        <?php echo htmlspecialchars($foros_ind['titulo']); ?>
                                    </span><br><br>
                                    Autor: <?php echo htmlspecialchars($foros_ind['id_usuario']); ?><br>
                                    Último Mensaje:
                                    <?php 
                                        echo $foros_ind['fecha_ultimo_mensaje'] 
                                            ? htmlspecialchars(date('Y-m-d H:i', strtotime($foros_ind['fecha_ultimo_mensaje']))) 
                                            : 'No hay mensajes';
                                    ?><br>
                                    Participantes: <?php echo htmlspecialchars($foros_ind['participantes']); ?> Personas<br>
                                </div>
                                <img src="<?php echo htmlspecialchars($foros_ind['url_foto_perfil']); ?>" alt="Imagen del autor" class="avatar-image">
                            </div>
                        </a>
                        <img src="<?php echo htmlspecialchars($foros_ind['imagen']); ?>" alt="Imagen del foro" class="foro-image">
                    </div>
                    
                    <!-- Mostrar el icono de basura solo si el usuario logueado es el creador del foro -->
                    <?php if ($loggedIn && $username === $foros_ind['id_usuario']): ?>
                        <div class="delete-icon">
                            <a href="eliminarForo.php?id_foro=<?php echo htmlspecialchars($foros_ind['id_foro']); ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar este foro?');">
                                <img src="/ForumZGames/uploads/basura.png" alt="Eliminar foro" class="icono-basura">
                            </a>
                        </div>
                    <?php endif; ?>

                    <hr class="full-width-line">
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <p>No se encontraron foros.</p>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="foro.php?page=<?php echo $page - 1; ?>">&laquo; Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="foro.php?page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $total_paginas): ?>
            <a href="foro.php?page=<?php echo $page + 1; ?>">Siguiente &raquo;</a>
        <?php endif; ?>
    </div>

    <footer>
        <p>Forum ZGames</p>
    </footer>
</body>
</html>

<?php
// Close the database connection
pg_close($conn);
?>
