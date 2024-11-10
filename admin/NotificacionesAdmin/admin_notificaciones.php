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


// Definir la página actual y el número de denuncias por página
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Página actual
$limit = 12; // Número de denuncias por página
$offset = ($page - 1) * $limit; // Desplazamiento

// Consulta para hallar las denuncias mas antiguas que siguen pendientes de resolver
$query_notificaciones = "
    SELECT 
        d.id_denuncia,
        d.descripcion, 
        d.fecha, 
        d.pendiente,
        d.id_admin, 
        d.id_mensaje,
        d.id_usuario_denunciante,
        d.id_admin
    FROM 
        denuncia d
    WHERE
        d.pendiente = TRUE
    ORDER BY 
        d.fecha ASC, 
        d.id_denuncia ASC
    LIMIT $1 OFFSET $2; -- Limitamos a 12 denuncias y aplicamos offset
";

// Ejecutar la consulta para las denuncias
$result_notificaciones = pg_query_params($conn, $query_notificaciones, array($limit, $offset));

if ($result_notificaciones === false) {
    echo "Error en la consulta de notificaciones: " . pg_last_error($conn);
    exit();
}

// Obtener todas las notificaciones
$notificaciones = pg_fetch_all($result_notificaciones);

// Consulta para contar el total de notificaciones para la paginación
$query_total = "
    SELECT COUNT(*) as total
    FROM denuncia d
    WHERE pendiente = true
";

// Ejecutar la consulta para obtener el total de notificaciones
$result_total = pg_query ($conn, $query_total);

if ($result_total === false) {
    echo "Error en la consulta del total de notificaciones: " . pg_last_error($conn);
    exit();
}

$total_notificaciones = pg_fetch_assoc($result_total)['total'];
$total_paginas = max(1, ceil($total_notificaciones / $limit)); // Total de páginas, mínimo de 1

// Consulta para hallar la foto de perfil de un usuario
$query_fotoperfil = "
    SELECT 
        u.username,
        u.url_foto_perfil
    FROM 
        usuario u
    WHERE
        u.username = $1
";

// Consulta para encontrar el foro en el que se encuentra dicho mensaje
$query_foromensaje = "
    SELECT 
        m.id_foro,
        m.texto
    FROM 
        mensaje m
    WHERE
        m.id_mensaje = $1
";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones admin</title>
    <link rel="stylesheet" href="styleadmin_notificaciones.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Press+Start+2P&family=Monoton&family=Lobster&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="/ForumZGames/imagenes/images/Logo_v1.png" type="image/x-icon">

    <script>
        let currentNotificationId; // Para almacenar el ID de la notificación actual
        let currentMensajeId;

        function closeModal() {
            document.getElementById('actionModal').style.display = "none"; // Cierra el modal
        }

        function openModal(notificationId, mensajeId) {
            currentNotificationId = notificationId; // Almacena el ID de la notificación
            currentMensajeId = mensajeId;
            document.getElementById('actionModal').style.display = "block"; // Muestra el modal
        }

        function handleAction(action) {
            if(currentMensajeId) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "gestion_notificacion.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                // Define los datos que se enviarán según la acción elegida
                const params = action === 'delete' 
                    ? `action=delete&id=${currentMensajeId}` 
                    : `action=doNothing&id=${currentMensajeId}`;

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        // Aquí puedes manejar la respuesta del servidor si es necesario
                        closeModal(); // Cierra el modal después de la acción
                        location.reload(); // Recarga la página para ver los cambios
                    }
                };
                xhr.send(params); // Envía los parámetros al servidor
            }
        }

    </script>


</head>
<body>
    <!-- Barra lateral -->
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
            <li><a href="/ForumZGames/admin/NoticiasAdmin/admin_noticias.php">Noticias</a></li>
            <li><a href="/ForumZGames/admin/ForoAdmin/admin_foro.php">Foros</a></li>
            <hr class="separator-sup">
            <li><a href="admin_notificaciones.php">Notificaciones</a></li>
            <hr class="separator-inf">
            
        </ul>
        <div class="logo">
            <img src="/ForumZGames/imagenes/images/Forum_ZGames.jpeg" alt="Logo" />
        </div>
    </nav>

    <div class="cabecera">
        <p>Notificaciones</p>
    </div>

    <!-- Notificacones -->
    <div class="notification-list">
        <?php if ($notificaciones): ?>
            <?php foreach ($notificaciones as $notificaciones_ind):
                // foto de perfil del denunciante
                $result_usr = pg_query_params($conn, $query_fotoperfil, array($notificaciones_ind['id_usuario_denunciante']));
                $user_data = pg_fetch_assoc($result_usr);

                // foro en el que se encuentra el mensaje denunciado
                $result_foro = pg_query_params($conn, $query_foromensaje, array($notificaciones_ind['id_mensaje']));
                $foro_data = pg_fetch_assoc($result_foro);
            ?>
                <div class="highlighted-notification">
                    <div class="usr-perfil">
                        <!-- Usuario denunciante -->
                        <img src=<?php echo htmlspecialchars($user_data['url_foto_perfil']); ?> alt="Logo" class="imagen-usr">
                        <a href="" class="usr-name"> 
                            <?php echo htmlspecialchars($user_data['username']); ?>
                        </a>
                    </div>
                    <p>Mensaje:</p>
                    <p class="texto"><?php echo htmlspecialchars($foro_data['texto']); ?></p>
                    
                    <!-- Boton para ir al foro donde se encuentra el mensaje -->
                    <a href="/ForumZGames/admin/ForoAdmin/chatForo.php?id_foro=<?php echo urlencode($foro_data['id_foro']); ?>" class="foro-button">Ver foro</a>

                    <!-- Modal para tomar una decisión sobre la denuncia -->
                    <a href="javascript:void(0);" class="denuncia-button" onclick="openModal(<?php echo $notificaciones_ind['id_denuncia']; ?>, '<?php echo $notificaciones_ind['id_mensaje']; ?>')">Acciones</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No se encontraron noticias.</p>
        <?php endif; ?>
    </div>

    <!-- Modal para borrar una noticia -->
    <div id="actionModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>¿Qué acción desea realizar?</h2>
            <button class="deletebutton" id="deleteMessageButton" onclick="handleAction('delete')">Eliminar mensaje</button>
            <button class="correctbutton" id="doNothingButton" onclick="handleAction('doNothing')">Mensaje correcto</button>
        </div>
    </div>

    <!-- Paginación -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="admin_notificaciones.php?page=<?php echo $page - 1; ?>">&laquo; Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="admin_notificaciones.php?page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $total_paginas): ?>
            <a href="admin_notificaciones.php?page=<?php echo $page + 1; ?>">Siguiente &raquo;</a>
        <?php endif; ?>
    </div>
</body>
</html>
