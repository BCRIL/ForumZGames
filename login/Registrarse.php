<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="styleRegistrarse.css?v=<?php echo time(); ?>">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="/ForumZGames/imagenes/images/Logo_v1.png" type="image/x-icon">
</head>
<body>

    <div class="wrapper">
      <form action="register.php" method="POST"> <!-- Cambiar la acción al script PHP -->
        <h1>Regístrate</h1>
        <div class="input-box">
          <input type="text" name="username" placeholder="Nombre de usuario" required>
          <i class='bx bxs-user'></i>
        </div>

        <div class="input-box">
          <input type="text" name="fullname" placeholder="Nombre y apellidos" required>
          <i class='bx bxs-user'></i>
        </div>

        <div class="input-box">
            <input type="email" name="email" placeholder="Dirección de e-mail" required> <!-- Cambiar tipo a email -->
            <i class='bx bxs-user'></i>
        </div>

        <div class="input-box">
          <input type="password" name="password" placeholder="Contraseña" required>
          <i class='bx bxs-lock-alt' ></i>
        </div>

        <div class="input-box">
          <input type="password" name="confirm_password" placeholder="Repite Contraseña" required>
          <i class='bx bxs-lock-alt' ></i>
        </div>

        <div class="remember-forgot">
          <a href="login.php">¿Ya tienes una cuenta?</a>
        </div>

        <button type="submit" class="btn">Entrar</button> <!-- Corregir el tipo del botón -->
      </form>
    </div>
</body>
</html>
