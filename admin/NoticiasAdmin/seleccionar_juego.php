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

if (isset($_GET['q'])) {
    $query = $_GET['q'];
    $query = pg_escape_string($query); // Escapar la cadena para evitar SQL Injection

    $sql = "SELECT id_videojuego, nombre FROM videojuego WHERE nombre ILIKE '%$query%' LIMIT 10";
    $result = pg_query($conn, $sql);
    
    $games = array();
    while ($row = pg_fetch_assoc($result)) {
        $games[] = $row; // Agrega cada juego al array
    }
    
    echo json_encode($games); // Devuelve los juegos en formato JSON
}
?>