<?php
// register.php

session_start(); // Inicia la sesión

// Configuración de la base de datos
$host = "localhost"; // Cambia esto si es necesario
$port = "5432"; // Puerto de PostgreSQL
$dbname = "ForumZGames"; // Nombre de tu base de datos
$user = "postgres"; // Tu usuario de PostgreSQL
$password = "root"; // Tu contraseña de PostgreSQL

// Crear conexión
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

// Verificar conexión
if (!$conn) {
    die("Error en la conexión: " . pg_last_error());
}

// Comprobar si el formulario ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recuperar y sanitizar la entrada del usuario
    $username = pg_escape_string(trim($_POST['username']));
    $fullname = pg_escape_string(trim($_POST['fullname']));
    $email = pg_escape_string(trim($_POST['email']));
    $password = pg_escape_string(trim($_POST['password']));
    $confirm_password = pg_escape_string(trim($_POST['confirm_password']));

    // Comprobar si las contraseñas coinciden
    if ($password !== $confirm_password) {
        echo "Las contraseñas no coinciden.";
        exit;
    }

    // Hash de la contraseña para mayor seguridad
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Obtener la fecha de registro actual
    $registration_date = date('Y-m-d H:i:s');

    // Ruta de la imagen de perfil predeterminada
    $default_profile_pic = '/ForumZGames/imagenes/images/imagenperfildefault.png'; // Cambia esto si la ruta es diferente

    // Insertar datos del usuario en la base de datos
    $sql = "INSERT INTO usuario (username, fullname, email, password, registration_date, url_foto_perfil) 
            VALUES ('$username', '$fullname', '$email', '$hashed_password', '$registration_date', '$default_profile_pic')";

    $result = pg_query($conn, $sql);

    if ($result) {
        $_SESSION['username'] = $username; // Guardar el nombre de usuario en la sesión
        header("Location: perfil.php"); // Redirigir a la página de inicio
        exit();
    } else {
        echo "Error al insertar datos: " . pg_last_error($conn);
    }
}

// Cerrar la conexión
pg_close($conn);
?>
