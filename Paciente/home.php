<?php
// Iniciar la sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir archivo de conexión a la base de datos
require_once '../Admin/DB.php'; // Asegúrate de que la ruta sea correcta

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirigir al login si no hay sesión
    exit;
}

try {
    // Obtener conexión a la base de datos
    $db = getDB(); 

    // Preparar consulta para obtener datos del usuario
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']); // Usando bind_param para mayor seguridad
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc(); // Obtener los datos del usuario

    // Obtener las notificaciones no leídas
    $stmt = $db->prepare("SELECT * FROM notificaciones WHERE id_usuario = ? AND leido = 0 ORDER BY fecha DESC");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $notificaciones = $stmt->get_result();

    // No es necesario actualizar el estado de todas las notificaciones aquí, solo cuando se muestran
} catch (mysqli_sql_exception $e) {
    // Manejo de excepciones si hay un error en la consulta
    error_log("Error en la consulta: " . $e->getMessage()); // Loguear el error
    die("Error al obtener información del usuario. Inténtalo más tarde.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Monte Esperanza</title>
    <link rel="stylesheet" href="../css/home.css"> <!-- Hoja de estilos -->
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
                                    <p class="notificacion" data-id="<?php echo $notificacion['id']; ?>" onclick="marcarLeida(<?php echo $notificacion['id']; ?>)">
                                        <?php echo htmlspecialchars($notificacion['mensaje']); ?>
                                    </p>
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

    <div style="text-align: center;">
        <h1>Bienvenido</h1> <!-- Mensaje de bienvenida -->
    </div>

    <!-- Alerta de mensajes -->
    <?php if (isset($_GET['mensaje'])): ?>
        <div class="alert">
            <?php echo htmlspecialchars($_GET['mensaje']); ?>
        </div>
    <?php endif; ?>

    <!-- Contenido principal -->
    <div class="main-content">
        <!-- Mis consultas activas (Si es un paciente) -->
        <div class="content-box">
            <?php if ($user['tipo_usuario'] == 'paciente'): ?>
                <h2>Mis consultas activas</h2>
                <?php
                // Consulta modificada para filtrar citas canceladas y limitar a 3
                $stmt = $db->prepare("SELECT c.*, d.nombre as doctor_nombre, d.apellido as doctor_apellido
                                      FROM citas c
                                      INNER JOIN usuarios d ON c.id_doctor = d.id
                                      WHERE c.id_paciente = ? AND c.fecha >= CURRENT_DATE AND c.estado != 'cancelada'
                                      ORDER BY c.fecha ASC, c.hora ASC LIMIT 3"); // Cambiar LIMIT a 3
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $citas = $stmt->get_result();

                if ($citas->num_rows === 0):
                ?>
                    <p>No tienes citas programadas.</p>
                <?php else: ?>
                    <ul>
                        <?php while ($cita = $citas->fetch_assoc()): ?>
                            <li>
                                <?php echo date('d/m/Y H:i', strtotime($cita['fecha'] . ' ' . $cita['hora'])); ?>
                                con Dr. <?php echo htmlspecialchars($cita['doctor_nombre'] . ' ' . $cita['doctor_apellido']); ?>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                    <div class="btn-container">
                        <button class="btn-toggle" onclick="toggleVerMas()">Ver más</button>
                        <a class="btn-cancel" onclick="anularHora()" href="citas.php">Anular hora</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Reserva de horas -->
        <div class="content-box reserve-btn-box">
            <h3 class="section-title">Reserva de horas</h3>
            <a class="reserve-btn" href="reserva_hora.php">Reservar</a> <!-- Botón para reservar -->
        </div>

        <!-- Mi historial de consultas -->
        <div class="content-box">
            <h2>Mi historial de consultas</h2>
            <?php
            // Consulta para obtener el historial de consultas completadas (solo 3)
            $stmt = $db->prepare("SELECT * FROM citas WHERE id_paciente = ? AND estado = 'completada' ORDER BY fecha DESC LIMIT 3"); // Agregar LIMIT 3
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $historial = $stmt->get_result();

            if ($historial->num_rows === 0):
            ?>
                <p>No tienes historial de consultas completadas.</p>
            <?php else: ?>
                <ul>
                    <?php while ($consulta = $historial->fetch_assoc()): ?>
                        <li>
                            <?php echo date('d/m/Y', strtotime($consulta['fecha'])); ?>: 
                            <?php echo htmlspecialchars($consulta['motivo']); ?> - 
                            Estado: <?php echo htmlspecialchars($consulta['estado']); ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>
            <div class="btn-container">
                <a href="historial_completo.php" class="btn-toggle">Ver más</a> <!-- Redirigir a historial completo -->
            </div>
        </div>
    </div>

    <!-- Enlace a archivos de JavaScript externos -->
    <script src="../js/notificaciones.js"></script>
    <script src="../js/citas.js"></script>
    <script>
        function mostrarNotificaciones() {
            var popup = document.getElementById("notificaciones-popup");
            popup.style.display = popup.style.display === "none" ? "block" : "none";

            // Ocultar las notificaciones después de 10 segundos
            setTimeout(() => {
                popup.style.display = "none";
            }, 10000);
        }

        function marcarLeida(id) {
    fetch('../marcar_leida.php?id=' + id)
        .then(response => {
            if (response.ok) {
                console.log('Notificación marcada como leída: ' + id);

                // Actualizar el contador de notificaciones
                var contadorElement = document.getElementById("notificacion-count");
                var currentCount = parseInt(contadorElement.textContent);
                if (currentCount > 0) {
                    contadorElement.textContent = currentCount - 1; // Disminuir el contador en 1
                }

                // Opcional: ocultar la notificación marcada
                var notificacionElement = document.querySelector('.notificacion[data-id="'+id+'"]');
                if (notificacionElement) {
                    notificacionElement.style.textDecoration = 'line-through'; // Ejemplo de cómo marcar visualmente la notificación
                }
            }
        });
}

    </script>
</body>
</html>
