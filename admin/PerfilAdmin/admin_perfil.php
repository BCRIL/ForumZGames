<?php
session_start();

// Comprobar si el usuario ha iniciado sesión
if (!isset($_SESSION['admin_username'])) {
    header("Location: login.php"); // Redirigir a la página de inicio de sesión
    exit();
}

// Obtener el nombre de usuario
$username = $_SESSION['admin_username'];

// Conectar a la base de datos
$conn_string = "host=localhost dbname=ForumZGames user=postgres password=root"; // Cambia esto
$conn = pg_connect($conn_string);

if (!$conn) {
    die("Error en la conexión a la base de datos.");
}

// Recuperar la URL de la foto de perfil y la fecha de creación del usuario
$query = "SELECT admin_url_foto_perfil, admin_registration_date FROM administrador WHERE admin_username = $1";
$result = pg_prepare($conn, "get_profile_data", $query);
$result = pg_execute($conn, "get_profile_data", array($username));

if ($result === false) {
    // Manejo de errores
    die("Error en la consulta: " . pg_last_error($conn));
}

$foto_perfil = "/ForumZGames/imagenes/images/imagenperfildefault.png"; // Ruta de imagen por defecto
$registration_date = ''; // Variable para almacenar la fecha de creación

if ($row = pg_fetch_assoc($result)) {
    $foto_perfil = $row['admin_url_foto_perfil'];
    $registration_date = $row['admin_registration_date']; // Obtener la fecha de creación
}

// Cerrar la conexión
pg_close($conn);

// Convertir la fecha a un objeto DateTime para formatearla
if ($registration_date) {
    $date = new DateTime($registration_date);
    $mes = $date->format('n'); // Obtiene el número del mes sin ceros
    $dia = $date->format('j'); // Obtiene el día sin ceros
    $anio = $date->format('Y'); // Obtiene el año completo

    // Crear un array para los nombres de los meses
    $nombres_meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];

    // Obtener el nombre del mes en español
    $nombre_mes = $nombres_meses[$mes];
    $fecha_formateada = "$dia de $nombre_mes de $anio"; // Formato: "Día de Mes de Año"
} else {
    $fecha_formateada = 'Fecha no disponible'; // Manejar el caso en que no haya fecha
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil</title>
    <link rel="stylesheet" href="admin_stylePerfil.css?v=<?php echo time(); ?>"> <!-- Asegúrate de tener un estilo CSS adecuado -->
    <link rel="icon" href="/ForumZGames/imagenes/images/Logo_v1.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Press+Start+2P&family=Monoton&family=Lobster&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
</head>
<body>
    
    <nav class="sidebar">
        <ul class="nav-left">
            <a href="/ForumZGames/admin/PerfilAdmin/admin_perfil.php">
            <div class="admin-perfil">
                <img src=<?php echo $foto_perfil; ?> alt="Logo" class="imagen-admin">
                <span class="usr-admin"><?php echo $username; ?></span>
            </div></a>
            <hr class="separator">
            <li><a href="/ForumZGames/admin/JuegoAdmin/admin_juegos.php">Juegos</a></li>
            <li><a href="/ForumZGames/admin/ForoAdmin/admin_foro.php">Foros</a></li>
            <li><a href="/ForumZGames/admin/NoticiasAdmin/admin_noticias.php">Noticias</a></li>
            <hr class="separator-sup">
            <li><a href="/ForumZGames/admin/IndexAdmin/admin_index.php">Inicio</a></li>
            <hr class="separator-inf">
            <li><a href="/ForumZGames/admin/NotificacionesAdmin/admin_notificaciones.php">Notificaciones</a></li>
        </ul>
        <div class="logo">
            <img src="/ForumZGames/imagenes/images/Forum_ZGames.jpeg" alt="Logo" />
        </div>
    </nav>

    <div class="profile-container">
        <div class="profile-header">
            <img src="<?php echo htmlspecialchars($foto_perfil); ?>" class="avatar" alt="Avatar" id="profileImage">
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($username); ?></h1>
                <p>Registrado el: <br><?php echo htmlspecialchars($fecha_formateada); ?></p>
            </div>
            <lu>
                <a href="/ForumZGames/cuenta_funcionalidades/logout.php?logout=true" class="logout-button">Cerrar sesión</a>
            </lu>
        </div>
    </div>
</body>
</html>
