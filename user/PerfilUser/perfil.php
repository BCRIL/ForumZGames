<?php
session_start();

// Comprobar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Redirigir a la página de inicio de sesión si no está autenticado
    exit();
}

// Obtener el nombre de usuario de la sesión
$username = $_SESSION['username'];

// Conectar a la base de datos
$conn_string = "host=localhost dbname=ForumZGames user=postgres password=root";
$conn = pg_connect($conn_string);

if (!$conn) {
    die("Error en la conexión a la base de datos.");
}

// Consultar la URL de la foto de perfil y la fecha de registro del usuario
$query = "SELECT url_foto_perfil, registration_date FROM usuario WHERE username = $1";
$result = pg_prepare($conn, "get_profile_data", $query);
$result = pg_execute($conn, "get_profile_data", array($username));

if ($result === false) {
    die("Error en la consulta: " . pg_last_error($conn));
}

// Establecer valores predeterminados
$foto_perfil = "/ForumZGames/imagenes/images/imagenperfildefault.png"; // Foto de perfil por defecto
$registration_date = ''; // Inicializar la fecha de registro

// Asignar datos obtenidos de la consulta
if ($row = pg_fetch_assoc($result)) {
    $foto_perfil = $row['url_foto_perfil'] ?: $foto_perfil; // Asignar la foto de perfil si está disponible
    $registration_date = $row['registration_date'] ?: 'Fecha no disponible'; // Asignar la fecha de registro si está disponible
}

// Cerrar la conexión
pg_close($conn);

// Formatear la fecha de registro
if ($registration_date && $registration_date !== 'Fecha no disponible') {
    $date = new DateTime($registration_date);
    $mes = $date->format('n');
    $dia = $date->format('j');
    $anio = $date->format('Y');
    
    // Nombres de los meses en español
    $nombres_meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];

    $nombre_mes = $nombres_meses[$mes];
    $fecha_formateada = "$dia de $nombre_mes de $anio"; // Formato de fecha: "Día de Mes de Año"
} else {
    $fecha_formateada = 'Fecha no disponible'; // Manejar el caso de no tener fecha
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil</title>
    <link rel="stylesheet" href="stylePerfil.css?v=<?php echo time(); ?>"> <!-- Cache busting para estilos -->
    <link rel="icon" href="/ForumZGames/imagenes/images/Logo_v1.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Press+Start+2P&family=Monoton&family=Lobster&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="nav-bar">
        <ul class="nav-left">
            <li><a href="perfil.php">Cuenta</a></li>
        </ul>

        <div class="logo">
            <img src="/ForumZGames/imagenes/images/Forum_ZGames.jpeg" alt="Logo" />
        </div>

        <ul class="nav-right">
            <li><a href="/ForumZGames/index.php">Inicio</a></li>
            <li><a href="/ForumZGames/user/JuegoUser/juegos.php">Juegos</a></li>
            <li><a href="/ForumZGames/user/ForoUser/foro.php">Foros</a></li>
            <li><a href="/ForumZGames/user/NoticiasUser/noticias.php">Noticias</a></li>
        </ul>
    </nav>

    <!-- Contenedor principal del perfil -->
    <div class="profile-container">
        <div class="profile-header">
            <!-- Imagen del perfil con posibilidad de cambiarla -->
            <img src="<?php echo htmlspecialchars($foto_perfil); ?>" class="avatar" alt="Avatar" id="profileImage" style="cursor: pointer;">
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($username); ?></h1>
                <p>Registrado el: <br><?php echo htmlspecialchars($fecha_formateada); ?></p>
            </div>
            <!-- Botón para cambiar la contraseña y cerrar sesión -->
            <div class="profile-actions">
                <button class="btn-change-password">Cambiar Contraseña</button>
                <a href="/ForumZGames/cuenta_funcionalidades/logout.php?logout=true" class="logout-button">Cerrar sesión</a>
            </div>
        </div>

        <!-- Secciones del perfil: Foros activos y reseñas -->
        <div class="profile-sections">
            <div class="profile-section">
                <a href="forosactivos.php">
                    <img src="/ForumZGames/imagenes/images/forums-icon.jpg" alt="Foros">
                    <h2>Foros Activos</h2>
                </a>
            </div>
            <div class="profile-section">
                <a href="/ForumZGames/user/PerfilUser/reseñas.php">
                    <img src="/ForumZGames/imagenes/images/reviews-icon.png" alt="Reseñas">
                    <h2>Reseñas</h2>
                </a>
            </div>
        </div>
    </div>

    <!-- Modal para cambiar la foto de perfil -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeProfileModal">&times;</span>
            <h2>Cambiar Foto de Perfil</h2>
            <form action="/ForumZGames/cuenta_funcionalidades/subir_foto.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="foto_perfil" accept="image/*" required>
                <button type="submit">Actualizar Foto</button>
            </form>
        </div>
    </div>

    <!-- Modal para cambiar la contraseña -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeChangePasswordModal">&times;</span>
            <h2>Cambiar Contraseña</h2>
            <form action="/ForumZGames/cuenta_funcionalidades/cambiar_contraseña.php" method="POST">
                <label for="current_password">Contraseña Actual:</label>
                <input type="password" name="current_password" required>
                <br>
                <label for="new_password">Nueva Contraseña:</label>
                <input type="password" name="new_password" required>
                <br>
                <label for="confirm_password">Confirmar Nueva Contraseña:</label>
                <input type="password" name="confirm_password" required>
                <br>
                <button type="submit">Cambiar Contraseña</button>
            </form>
        </div>
    </div>

    <script>
        // Manejo del modal de cambiar foto de perfil
        const profileModal = document.getElementById("profileModal");
        const profileImage = document.getElementById("profileImage");
        const closeProfileModal = document.getElementById("closeProfileModal");

        profileImage.onclick = () => profileModal.style.display = "block";
        closeProfileModal.onclick = () => profileModal.style.display = "none";

        // Manejo del modal de cambiar contraseña
        const changePasswordModal = document.getElementById("changePasswordModal");
        const changePasswordButton = document.querySelector(".btn-change-password");
        const closeChangePasswordModal = document.getElementById("closeChangePasswordModal");

        changePasswordButton.onclick = () => changePasswordModal.style.display = "block";
        closeChangePasswordModal.onclick = () => changePasswordModal.style.display = "none";

        // Cerrar modal cuando se hace clic fuera del mismo
        window.onclick = (event) => {
            if (event.target === profileModal) profileModal.style.display = "none";
            if (event.target === changePasswordModal) changePasswordModal.style.display = "none";
        }
    </script>
</body>
</html>
