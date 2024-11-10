<?php
session_start();
$conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

if (!$conn) {
    die("Error al conectar con la base de datos.");
}

$loggedIn = isset($_SESSION['username']);
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['texto'])) {
    $id_foro = (int)$_POST['id_foro'];
    $id_usuario = $_SESSION['username'];
    $texto = trim($_POST['texto']);

    if (!empty($texto)) {
        $query_add_message = "
            INSERT INTO mensaje (id_foro, id_usuario, texto, fecha, mostrar) 
            VALUES ($1, $2, $3, NOW(), true)
        ";

        $result = pg_query_params($conn, $query_add_message, array($id_foro, $id_usuario, $texto));

        if ($result) {
            // Redirigir de vuelta al foro después de enviar el mensaje
            header("Location: chatForo.php?id_foro=" . $id_foro);
            exit();
        } else {
            echo "Error al guardar el mensaje: " . pg_last_error($conn);
        }
    } else {
        echo "El mensaje no puede estar vacío.";
    }
} elseif (!$loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<script>alert('Debe iniciar sesión para enviar un mensaje.');</script>";
}

pg_close($conn);
?>
