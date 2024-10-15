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
    <title>Sobre Nosotros</title> <!-- Título de la página -->
    
    <link rel="stylesheet" href="../css/nosotros.css"> <!-- Estilo CSS para la página "Nosotros" -->
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
        <h1>Nosotros</h1> <!-- Título de la sección -->
    </div>
    
    <div class="main-content">
        <div class="content-box"> <!-- Cuadro para el contenido -->
            <div class="about-container">
                <img src="../Img/nosotros.png" class="about-image" alt="Imagen sobre nosotros"> <!-- Imagen de la sección -->
                <div class="about-text">
                    <h2>Bienvenidos al Hospital Monte Esperanza</h2> <!-- Subtítulo -->
                    <p>
                        En nuestro hospital, nos dedicamos a proporcionar la mejor atención médica a nuestros pacientes.
                        Nuestro equipo de profesionales está aquí para ayudarle en cada paso del camino hacia su salud.
                        Creemos en un enfoque centrado en el paciente y trabajamos incansablemente para ofrecer servicios de calidad.
                    </p>
                </div>
            </div>
        </div> <!-- Fin del cuadro para el contenido -->
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
