<?php
// Iniciar la sesión
session_start();

// Incluir archivo de conexión a la base de datos
require_once '../Admin/DB.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php'); // Redirigir a la página de inicio si no hay sesión
    exit;
}

try {
    $db = getDB(); // Obtener conexión a la base de datos

    // Preparar consulta para obtener datos del usuario
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']); // Usando bind_param para mayor seguridad
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc(); // Obtener los datos del usuario

    // Manejo de error: usuario no encontrado
    if ($user === null) {
        $_SESSION['mensaje'] = "Usuario no encontrado.";
        header('Location: index.php'); // Redirige a la página de inicio o a otra página
        exit;
    }

    // Procesar el formulario de actualización del perfil
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Obtener los datos del formulario
        $nombre = $_POST['nombre'] ?? '';
        $apellido = $_POST['apellido'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Actualizar los datos del usuario
        if ($password) {
            // Si se proporciona una nueva contraseña, se hash antes de guardar
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, password = ? WHERE id = ?");
            $stmt->execute([$nombre, $apellido, $email, $password_hash, $_SESSION['user_id']]);
        } else {
            // Si no se proporciona contraseña, actualizar solo nombre, apellido y email
            $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ? WHERE id = ?");
            $stmt->execute([$nombre, $apellido, $email, $_SESSION['user_id']]);
        }

        // Mensaje de éxito y redirección
        $_SESSION['mensaje'] = "Perfil actualizado correctamente";
        header('Location: perfil.php');
        exit;
    }
} catch(PDOException $e) {
    die("Error: " . $e->getMessage()); // Manejo de excepciones si hay un error en la consulta
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <link rel="stylesheet" href="../css/perfil.css"> <!-- Hoja de estilos -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"> <!-- Iconos -->
</head>
<body>
     <!-- Header -->
     <header class="header">
        <div class="header-content">
            <div class="logo">
                <h2>Hospital Monte Esperanza</h2>
            </div>
            <nav class="nav-menu">
                <ul>
                    <li><a href="homeDoc.php"><i class="fas fa-home"></i> Inicio</a></li>
                    <?php if ($user['tipo_usuario'] == 'doctor'): ?>
                    <?php endif; ?>
                    <?php if ($user['tipo_usuario'] == 'admin'): ?>
                        <li><a href="usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
                        <li><a href="especialidades.php"><i class="fas fa-stethoscope"></i> Especialidades</a></li>
                    <?php endif; ?>
                    <li><a href="perfilDoc.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                    <li><a href="../cerrar-sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                </ul>
            </nav>
            <div class="user-info">
                <i class="fas fa-user-circle" style="font-size: 24px; margin-right: 8px;"></i> <!-- Ícono de usuario -->
                <span><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></span>
                <br>
                <small><?php echo ucfirst($user['tipo_usuario']); ?></small>
            </div>
        </div>
    </header>
    
    <div >
        <h1>Mi Perfil</h1>
    </div>
    
    <div class="main-content">
        <div class="profile-container">
            <!-- Mostrar mensajes de éxito o error -->
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="mensaje">
                    <?php 
                    echo $_SESSION['mensaje'];
                    unset($_SESSION['mensaje']); // Limpiar el mensaje después de mostrarlo
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulario para actualizar el perfil -->
            <form class="profile-form" method="POST">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" 
                           value="<?php echo htmlspecialchars($user['nombre']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="apellido">Apellido</label>
                    <input type="text" id="apellido" name="apellido" 
                           value="<?php echo htmlspecialchars($user['apellido']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Nueva Contraseña (dejar en blanco para mantener la actual)</label>
                    <input type="password" id="password" name="password">
                </div>
                
                <button type="submit" class="btn-actualizar">Actualizar Perfil</button> <!-- Botón para enviar el formulario -->
            </form>
        </div>
    </div>
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('hidden'); // Alternar la visibilidad de la barra lateral
        }
    </script>
</body>
</html>
