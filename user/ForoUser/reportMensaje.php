<?php
session_start();
$conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit();
}

if (!isset($_SESSION['username']) || !isset($_POST['message_id'])) {
    echo json_encode(["status" => "error", "message" => "Invalid session or message ID."]);
    exit();
}

$username = $_SESSION['username'];
$message_id = (int)$_POST['message_id'];
$description = $_POST['description'];
$timestamp = date('Y-m-d H:i:s');

$query = "
    INSERT INTO denuncia (descripcion, fecha, pendiente, id_mensaje, id_usuario_denunciante)
    VALUES ($1, $2, 'true', $3, $4)
";

$result = pg_query_params($conn, $query, [$description, $timestamp, $message_id, $username]);

if ($result) {
    echo json_encode(["status" => "success", "message" => "Report submitted successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to submit report."]);
}

pg_close($conn);
?>
