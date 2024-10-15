<?php
// Iniciar la sesión si no se ha iniciado previamente
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir el archivo de conexión a la base de datos
require_once '../Admin/DB.php'; // Asegúrate de que la ruta sea correcta

// Redirigir al usuario al login si no ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
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
    
    // Verificar que el usuario sea un paciente
    if ($user['tipo_usuario'] !== 'paciente') {
        header('Location: home.php'); // Redirigir si no es paciente
        exit;
    }

    // Obtener todas las especialidades de la base de datos
    $stmt = $db->query("SELECT * FROM especialidades ORDER BY nombre");
    $especialidades = $stmt->fetch_all(MYSQLI_ASSOC); // Obtener todas las especialidades como un array
    
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

} catch (Exception $e) {
    // Manejo de excepciones si hay un error al obtener información
    die("Error al obtener información del usuario o especialidades: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva Médica</title> <!-- Título de la página -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"> <!-- Estilo para iconos -->
    <link rel="stylesheet" href="../css/reserva.css"> <!-- Estilo CSS para la página -->
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
    
    <div style="text-align: left;">
        <h1>Nueva Hora</h1> <!-- Título de la sección -->
    </div>
    
    <div class="main-content">
        <div class="form-container">
            <!-- Mensajes de error o éxito -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['mensaje'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['mensaje']); ?>
                </div>
            <?php endif; ?>

            <!-- Formulario para hacer una cita -->
            <form id="citaForm" action="hacer_cita.php" method="POST" onsubmit="return validarFormulario()">
                <div class="form-group">
                    <label for="especialidad">Especialidad</label>
                    <select id="especialidad" name="especialidad" required>
                        <option value="">Seleccione una especialidad</option>
                        <?php foreach ($especialidades as $especialidad): ?>
                            <option value="<?php echo $especialidad['id']; ?>">
                                <?php echo htmlspecialchars($especialidad['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="doctor">Doctor</label>
                    <select id="doctor" name="doctor" required disabled>
                        <option value="">Primero seleccione una especialidad</option>
                    </select>
                    <div id="doctorInfo" class="doctor-info"></div> <!-- Información del doctor seleccionado -->
                </div>
                
                <div class="form-group">
                    <label for="fecha">Fecha</label>
                    <input type="date" id="fecha" name="fecha" required disabled> <!-- Campo de selección de fecha -->
                </div>
                
                <div class="form-group">
                    <label>Horarios Disponibles</label>
                    <div id="horariosDisponibles" class="horarios-disponibles"></div> <!-- Horarios disponibles mostrados aquí -->
                    <input type="button" id="hora" name="hora" required> <!-- Botón para seleccionar la hora -->
                </div>
                
                <div class="form-group">
                    <label for="motivo">Motivo de la consulta</label>
                    <textarea id="motivo" name="motivo" rows="4" required placeholder="Describa brevemente el motivo de su consulta"></textarea>
                </div>
                
                <button type="submit" class="btn-agendar">Agendar hora</button> <!-- Botón para enviar el formulario -->
            </form>
        </div>
    </div>

    <script src="../js/nueva_cita.js"></script> <!-- Enlace al archivo JavaScript para la lógica del formulario -->
    <script src="../js/noti.js"></script>

</body>
</html>
