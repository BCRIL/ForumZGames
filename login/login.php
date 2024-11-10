<?php
session_start();

// Conectar a la base de datos
$conn = pg_connect("host=localhost dbname=ForumZGames user=postgres password=root");

header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (!$conn) {
    die("Error de conexión: " . pg_last_error());
}

// Verificar si ya existe una cookie de "Recuérdame" y no hay sesión activa
if (isset($_COOKIE['rememberme']) && !isset($_SESSION['username'])) {
    $username = $_COOKIE['rememberme'];

    // Buscar al usuario en la base de datos usando la cookie
    $query = "SELECT username FROM usuario WHERE username = $1";
    $result = pg_query_params($conn, $query, array($username));

    if ($result && pg_num_rows($result) == 1) {
        // Restaurar la sesión
        $_SESSION['username'] = $username;
        header("Location: perfil.php"); // Redirigir a la página de perfil
        exit();
    } else {
        // Si algo sale mal, borrar la cookie (por ejemplo, si el usuario fue eliminado)
        setcookie('rememberme', '', time() - 3600, "/");
    }
}

// Verificar si se ha enviado el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Primero revisamos admins
    $query_user = "SELECT * FROM administrador WHERE admin_username = $1";
    $result_user = pg_query_params($conn, $query_user, array($username));
    if (!$result_user) {
        die("Error en la consulta: " . pg_last_error($conn));
    }

    // Verificar si el admin existe
    if (pg_num_rows($result_user) > 0) {
        
        $query = "SELECT admin_password FROM administrador WHERE admin_username = $1";
        $result = pg_query_params($conn, $query, array($username));
        if (!$result) {
            die("Error en la consulta: " . pg_last_error($conn));
        }    

        $row = pg_fetch_assoc($result);
        $hashed_password = $row['admin_password'];

        // Verificar la contraseña
        if (crypt($password, $hashed_password) === $hashed_password) {
            // Guardar información de sesión del admin
            $_SESSION['admin_username'] = $username;

            // Foto de perfil del admin
            $query = "SELECT admin_url_foto_perfil FROM administrador WHERE admin_username = $1";
            $result = pg_query_params($conn, $query, array($username));
            $row = pg_fetch_assoc($result);
            $_SESSION['admin_url_foto_perfil'] = $row['admin_url_foto_perfil'];

            header("Location: /ForumZGames/admin/IndexAdmin/admin_index.php");
            exit();
        } else {
            // Si la contraseña es incorrecta, revisamos la tabla de usuarios normales
            $query = "SELECT password FROM usuario WHERE username = $1";
            $result = pg_query_params($conn, $query, array($username));

            if (pg_num_rows($result) == 1) {
                $row = pg_fetch_assoc($result);
                $hashed_password = $row['password'];

                if (password_verify($password, $hashed_password)) {
                    $_SESSION['username'] = $username;

                    if (isset($_POST['remember'])) {
                        setcookie('rememberme', $username, time() + (86400 * 7), "/");
                    }

                    // Redirigir al origen si existe, o a perfil
                    $redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : '/ForumZGames/user/PerfilUser/perfil.php';
                    header("Location: " . $redirect_url);
                    exit();
                } else {
                    header("Location: login.php");
                    exit();
                }
            } else {
                header("Location: login.php");
                exit();
            }
        }
    } else {
        // Revisamos usuarios normales si no es un admin
        $query = "SELECT password FROM usuario WHERE username = $1";
        $result = pg_query_params($conn, $query, array($username));

        if (pg_num_rows($result) == 1) {
            $row = pg_fetch_assoc($result);
            $hashed_password = $row['password'];

            if (password_verify($password, $hashed_password)) {
                $_SESSION['username'] = $username;

                if (isset($_POST['remember'])) {
                    setcookie('rememberme', $username, time() + (86400 * 7), "/");
                }

                // Redirigir al origen si existe, o a perfil
                $redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : '/ForumZGames/user/PerfilUser/perfil.php';
                header("Location: " . $redirect_url);
                exit();
            } else {
                header("Location: login.php");
                exit();
            }
        } else {
            header("Location: login.php");
            exit();
        }
    }
}

// Cerrar la conexión
pg_close($conn);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="styleLogin.css?v=<?php echo time(); ?>">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="/ForumZGames/imagenes/images/Logo_v1.png" type="image/x-icon">
</head>
<body>

    <div class="wrapper">
      <form action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="POST" autocomplete="off">
        <h1>Iniciar Sesión</h1>
        <div class="input-box">
          <input type="text" name="username" id="username" placeholder="Usuario" required autocomplete="off">
          <i class='bx bxs-user'></i>
        </div>
        <div class="input-box">
          <input type="password" name="password" id="password" placeholder="Contraseña" required autocomplete="off">
          <i class='bx bxs-lock-alt'></i>
        </div>

        <div class="remember-forgot">
          <label><input type="checkbox" name="remember" value="1"> Recuérdame</label>
          <a href="#">¿No puedes iniciar sesión?</a>
        </div>

        <button type="submit" class="btn">Entrar</button>

        <div class="register-link">
          <p>¿No tienes cuenta?<a href="Registrarse.php"> Regístrate</a></p>
        </div>
      </form>
    </div>
</body>
</html>
