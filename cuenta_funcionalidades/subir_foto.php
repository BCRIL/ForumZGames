<?php
session_start();

// Comprobar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    header("Location: /ForumZGames/login/login.php"); // Redirigir a la página de inicio de sesión
    exit();
}

// Verificar si se ha subido una foto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto_perfil'])) {
    // Conectar a la base de datos
    $conn_string = "host=localhost dbname=ForumZGames user=postgres password=root"; // Cambia esto
    $conn = pg_connect($conn_string);

    if (!$conn) {
        die("Error en la conexión a la base de datos.");
    }

    // Ruta donde se guardará la imagen
    $target_dir = "/ForumZGames/imagenes/uploads/"; // Asegúrate de que esta carpeta exista y tenga permisos de escritura
    $target_file = $_SERVER['DOCUMENT_ROOT'] . $target_dir . basename($_FILES["foto_perfil"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Comprobar si el archivo es una imagen
    $check = getimagesize($_FILES["foto_perfil"]["tmp_name"]);
    if ($check === false) {
        echo "El archivo no es una imagen.";
        exit();
    }

    // Mover el archivo subido al directorio de destino
    if (move_uploaded_file($_FILES["foto_perfil"]["tmp_name"], $target_file)) {
        // Actualizar la URL de la foto de perfil en la base de datos
        $username = $_SESSION['username'];
        $target_file = $target_dir . basename($_FILES["foto_perfil"]["name"]);
        // Usar una declaración preparada para evitar inyecciones SQL
        $query = "UPDATE usuario SET url_foto_perfil = $1 WHERE username = $2"; // Usar 'username'
        $result = pg_prepare($conn, "update_profile_pic", $query);
        $result = pg_execute($conn, "update_profile_pic", array($target_file, $username));

        if ($result) {
            echo "La foto de perfil se ha cambiado correctamente.";
            header("Location: /ForumZGames/user/PerfilUser/perfil.php"); // Redirigir a la página de inicio de sesión
        } else {
            echo "Error al actualizar la foto de perfil.";
        }
    } else {
        echo "Lo siento, ocurrió un error al subir la imagen.";
    }

    // Cerrar la conexión
    pg_close($conn);
}
?>
