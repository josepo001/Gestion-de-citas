<?php
// Iniciar la sesión
session_start();
// Incluir el archivo de conexión a la base de datos
require_once '../Admin/DB.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php'); // Redirigir al inicio si no hay sesión
    exit;
}

try {
    // Obtener conexión a la base de datos
    $db = getDB(); 
    // Preparar una consulta para obtener la información del usuario
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']); // Usar bind_param para mayor seguridad
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc(); // Obtener los datos del usuario

    if ($user === null) {
        // Manejo de error: usuario no encontrado
        $_SESSION['mensaje'] = "Usuario no encontrado.";
        header('Location: index.php'); // Redirige a la página de inicio
        exit;
    }

    // Procesar el formulario al enviarlo
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nombre = $_POST['nombre'] ?? '';
        $apellido = $_POST['apellido'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Actualizar el perfil del usuario
        if ($password) {
            // Si se proporciona una nueva contraseña, se encripta y se actualiza
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, password = ? WHERE id = ?");
            $stmt->execute([$nombre, $apellido, $email, $password_hash, $_SESSION['user_id']]);
        } else {
            // Si no se proporciona una nueva contraseña, solo se actualizan otros campos
            $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ? WHERE id = ?");
            $stmt->execute([$nombre, $apellido, $email, $_SESSION['user_id']]);
        }

        $_SESSION['mensaje'] = "Perfil actualizado correctamente"; // Mensaje de éxito
        header('Location: perfil.php'); // Redirigir a la página del perfil
        exit;
    }

    // Obtener las notificaciones no leídas
    $stmt = $db->prepare("SELECT * FROM notificaciones WHERE id_usuario = ? AND leido = 0 ORDER BY fecha DESC");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $notificaciones = $stmt->get_result();

    // Actualizar el estado de las notificaciones a leídas
    if ($notificaciones->num_rows > 0) {
        $stmt = $db->prepare("UPDATE notificaciones SET leido = 1 WHERE id_usuario = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
    }
} catch(PDOException $e) {
    // Manejo de excepciones si hay un error en la consulta
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title> <!-- Título de la página -->
    <link rel="stylesheet" href="../css/perfil.css"> <!-- Estilo CSS para la página de perfil -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"> <!-- Estilo para iconos -->
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
                    <li><a href="home.php"><i class="fas fa-home"></i> Inicio</a></li>
                    <?php if ($user['tipo_usuario'] == 'doctor'): ?>
                        <li><a href="agenda.php"><i class="fas fa-calendar-alt"></i> Mi Agenda</a></li>
                        <li><a href="horarios.php"><i class="fas fa-clock"></i> Mis Horarios</a></li>
                    <?php endif; ?>
                    <?php if ($user['tipo_usuario'] == 'admin'): ?>
                        <li><a href="usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
                        <li><a href="especialidades.php"><i class="fas fa-stethoscope"></i> Especialidades</a></li>
                    <?php endif; ?>
                    <li><a href="./nosotros.php"><i class="fas fa-users"></i> Nosotros</a></li>
                    <li><a href="perfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                    <li><a href="../cerrar-sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                    <li style="position: relative;">
                        <i class="fas fa-bell" id="notificaciones-icon" onclick="mostrarNotificaciones()"></i>
                        <span id="notificacion-count"><?php echo $notificaciones->num_rows; ?></span>
                        
                        <div id="notificaciones-popup" style="display: none;">
                            <?php if ($notificaciones->num_rows > 0): ?>
                                <?php while ($notificacion = $notificaciones->fetch_assoc()): ?>
                                    <p><?php echo htmlspecialchars($notificacion['mensaje']); ?></p>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>No tienes nuevas notificaciones</p>
                            <?php endif; ?>
                        </div>
                    </li>
                </ul>
            </nav>
            <div class="user-info">
                <i class="fas fa-user-circle" style="font-size: 24px; margin-right: 8px;"></i>
                <span><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></span> <!-- Mostrar nombre completo del usuario -->
                <br>
                <small><?php echo ucfirst($user['tipo_usuario']); ?></small> <!-- Mostrar tipo de usuario -->
            </div>
        </div>
    </header>
    
    <div>
        <h1>Mi Perfil</h1> <!-- Título de la sección -->
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
                           value="<?php echo htmlspecialchars($user['nombre']); ?>" required> <!-- Campo de nombre -->
                </div>
                
                <div class="form-group">
                    <label for="apellido">Apellido</label>
                    <input type="text" id="apellido" name="apellido" 
                           value="<?php echo htmlspecialchars($user['apellido']); ?>" required> <!-- Campo de apellido -->
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required> <!-- Campo de email -->
                </div>
                
                <div class="form-group">
                    <label for="password">Nueva Contraseña (dejar en blanco para mantener la actual)</label>
                    <input type="password" id="password" name="password"> <!-- Campo de nueva contraseña -->
                </div>
                
                <button type="submit" class="btn-actualizar">Actualizar Perfil</button> <!-- Botón para enviar el formulario -->
            </form>
        </div>
    </div>

    <script>
        // Función para alternar la visibilidad de la barra lateral (no se usa en este código, pero puede ser útil para futuras implementaciones)
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('hidden');
        }
    </script>
    <script src="../js/noti.js"></script>
</body>
</html>
