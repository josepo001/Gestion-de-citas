<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Define el conjunto de caracteres utilizado en el documento -->
    <meta charset="UTF-8">
    <!-- Hace que el diseño sea receptivo para diferentes tamaños de pantalla -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Enlace al archivo de estilos CSS para la página de inicio de sesión -->
    <link rel="stylesheet" href="./css/index.css">
    <title>Login</title> <!-- Título que se muestra en la pestaña del navegador -->
</head>
<body>
    <!-- Formulario de inicio de sesión que se envía a 'validar.php' mediante el método POST -->
    <form action="validar.php" method="post">
        <div class="body"></div> <!-- Sección de fondo del formulario -->
        <div class="grad"></div> <!-- Gradiente de fondo para el diseño -->
        <div class="header">
            <div>Hospital Monte Esperanza</div> <!-- Título del formulario -->
        </div>
        <br>
        <div class="login"> <!-- Contenedor para los campos de entrada -->
            <!-- Campo de entrada para el RUT del usuario -->
            <input type="text" placeholder="Ingrese RUT" name="rut" required><br>
            <!-- Campo de entrada para la contraseña del usuario -->
            <input type="password" placeholder="Ingrese su contraseña" name="password" required><br>
            <!-- Botón de envío para iniciar sesión -->
            <input type="submit" value="Ingresar">
        </div>
    </form>
</body>
</html>
