<?php
session_start(); // Inicia la sesión del usuario
include('../Admin/DB.php'); // Incluye el archivo de conexión a la base de datos

// Verifica si el usuario ha iniciado sesión y es un doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: login.php'); // Redirige a la página de inicio de sesión si no está autenticado
    exit;
}

try {
    $db = getDB(); // Obtener conexión a la base de datos
    
    // Verificar que el usuario sea un doctor
    $stmt = $db->prepare("
        SELECT u.*, d.id AS doctor_id 
        FROM usuarios u
        INNER JOIN doctores d ON d.id_usuario = u.id
        WHERE u.id = ? AND u.tipo_usuario = 'doctor'
    ");
    $stmt->bind_param('s', $_SESSION['user_id']); // Asocia el parámetro de la consulta
    $stmt->execute(); // Ejecuta la consulta
    $result = $stmt->get_result(); // Obtiene el resultado

    $doctor = $result->fetch_assoc(); // Almacena los datos del doctor

    // Redirige si no se encuentra el doctor
    if (!$doctor) {
        header('Location: ../Paciente/dashboard.php'); // Redirige a la página del paciente
        exit;
    }
    
    // Obtener las citas del doctor con estado 'pendiente' o 'confirmada'
    $stmt = $db->prepare("
        SELECT 
            c.*,
            u.nombre AS paciente_nombre,
            u.apellido AS paciente_apellido
        FROM citas c
        INNER JOIN usuarios u ON c.id_paciente = u.id
        WHERE c.id_doctor = ? 
        AND c.fecha >= CURRENT_DATE
        AND c.estado IN ('pendiente', 'confirmada')  -- Filtra por estado
        ORDER BY c.fecha ASC, c.hora ASC
    ");
    $stmt->bind_param('i', $doctor['doctor_id']); // Asocia el ID del doctor
    $stmt->execute(); // Ejecuta la consulta
    $result = $stmt->get_result(); // Obtiene el resultado

    $citas = $result->fetch_all(MYSQLI_ASSOC); // Almacena todas las citas en un arreglo
    
    // Agrupar citas por fecha
    $citas_por_fecha = []; // Inicializa el arreglo para agrupar citas
    foreach ($citas as $cita) {
        $fecha = $cita['fecha']; // Obtiene la fecha de la cita
        if (!isset($citas_por_fecha[$fecha])) {
            $citas_por_fecha[$fecha] = []; // Inicializa el arreglo para la fecha si no existe
        }
        $citas_por_fecha[$fecha][] = $cita; // Agrupa la cita por fecha
    }

    // Obtener las notificaciones
    $stmt = $db->prepare("SELECT `id`, `id_usuario`, `mensaje`, `leido`, `fecha` FROM `notificaciones` WHERE id_usuario = ? AND leido = 0 ORDER BY fecha DESC");
    $stmt->bind_param('i', $_SESSION['user_id']); // Asocia el ID del usuario
    $stmt->execute(); // Ejecuta la consulta
    $notificaciones = $stmt->get_result(); // Obtiene el resultado de las notificaciones

    // Actualizar el estado de las notificaciones a leídas
    if ($notificaciones->num_rows > 0) {
        // Actualizar solo las notificaciones no leídas
        $stmt = $db->prepare("UPDATE notificaciones SET leido = 1 WHERE id_usuario = ? AND leido = 0");
        $stmt->bind_param("i", $_SESSION['user_id']); // Asocia el ID del usuario
        $stmt->execute(); // Ejecuta la consulta para marcar como leídas
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage()); // Maneja excepciones y muestra el error
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Agenda</title>
    <link rel="stylesheet" href="../css/homeDoc.css"> <!-- Enlace a la hoja de estilo -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"> <!-- Enlace a los íconos de Font Awesome -->
    <script>
        // Función para ocultar mensajes después de 5 segundos
        setTimeout(function() {
            var mensaje = document.getElementById('mensaje');
            if (mensaje) {
                mensaje.style.display = 'none'; // Oculta el mensaje
            }
        }, 5000);

        // Función de confirmación
        function confirmAction(action) {
            return confirm('¿Estás seguro de que deseas ' + action + ' esta cita?'); // Confirma la acción del usuario
        }

        // Mostrar/ocultar notificaciones
        function mostrarNotificaciones() {
            var popup = document.getElementById('notificaciones-popup');
            popup.style.display = (popup.style.display === 'none' || popup.style.display === '') ? 'block' : 'none'; // Alterna la visibilidad del popup
        }

        // Marcar notificación como leída
        function marcarLeida(notificacionId) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../marcar_leida.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Ocultar la notificación marcada como leída
                        document.querySelector(`.notificacion[data-id="${notificacionId}"]`).style.display = 'none'; // Oculta la notificación
                        // Actualizar el conteo de notificaciones
                        var count = document.getElementById('notificacion-count');
                        count.textContent = parseInt(count.textContent) - 1; // Restar 1 del contador
                    } else {
                        console.error("Error al marcar la notificación como leída:", response.error); // Maneja errores
                    }
                }
            };
            xhr.send("notificacion_id=" + notificacionId); // Envía la solicitud para marcar la notificación
        }
    </script>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h2>Hospital Monte Esperanza</h2> <!-- Nombre del hospital -->
            </div>
            <nav class="nav-menu">
                <ul>
                    <li><a href="homeDoc.php"><i class="fas fa-home"></i> Inicio</a></li> <!-- Enlace a la página de inicio -->
                    <li><a href="perfilDoc.php"><i class="fas fa-user"></i> Mi Perfil</a></li> <!-- Enlace al perfil del doctor -->
                    <li><a href="../cerrar-sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li> <!-- Enlace para cerrar sesión -->
                    <li style="position: relative;">
                        <i class="fas fa-bell" id="notificaciones-icon" onclick="mostrarNotificaciones()"></i> <!-- Icono de notificaciones -->
                        <span id="notificacion-count"><?php echo $notificaciones->num_rows; ?></span> <!-- Contador de notificaciones -->
                        <div id="notificaciones-popup" style="display: none;"> <!-- Popup de notificaciones -->
                            <?php if ($notificaciones->num_rows > 0): ?>
                                <?php while ($notificacion = $notificaciones->fetch_assoc()): ?>
                                    <p class="notificacion" data-id="<?php echo $notificacion['id']; ?>" onclick="marcarLeida(<?php echo $notificacion['id']; ?>)">
                                        <?php echo htmlspecialchars($notificacion['mensaje']); ?> <!-- Mensaje de la notificación -->
                                    </p>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>No tienes nuevas notificaciones</p> <!-- Mensaje si no hay notificaciones -->
                            <?php endif; ?>
                        </div>
                    </li>
                </ul>
            </nav>
            <div class="user-info">
                <i class="fas fa-user-circle" style="font-size: 24px; margin-right: 8px;"></i> <!-- Icono de usuario -->
                <span><?php echo htmlspecialchars($doctor['nombre'] . ' ' . $doctor['apellido']); ?></span> <!-- Nombre del doctor -->
                <br>
                <small><?php echo ucfirst($doctor['tipo_usuario']); ?></small> <!-- Tipo de usuario -->
            </div>
        </div>
    </header>

    <div>
        <h1>Mi Agenda Médica</h1> <!-- Título de la sección -->
    </div>
    <div class="main-content">
        <div class="agenda-container">
            <?php if (isset($_GET['mensaje'])): ?>
                <div id="mensaje" class="alert">
                    <?php echo htmlspecialchars($_GET['mensaje']); ?> <!-- Mensaje de alerta -->
                </div>
            <?php endif; ?>

            <?php if (empty($citas_por_fecha)): ?>
                <div class="alert">
                    No tienes citas programadas próximamente. <!-- Mensaje si no hay citas -->
                </div>
            <?php else: ?>
                <?php foreach ($citas_por_fecha as $fecha => $citas_dia): ?>
                    <div class="cita-fecha"><?php echo date('d/m/Y', strtotime($fecha)); ?></div> <!-- Fecha de la cita -->
                    <?php foreach ($citas_dia as $cita): ?>
                        <div class="cita-item">
                            <div class="cita-detalles">
                                <div class="cita-info">
                                    <strong>Hora: <?php echo date('H:i', strtotime($cita['hora'])); ?></strong> <!-- Hora de la cita -->
                                    <br>Paciente: <?php echo htmlspecialchars($cita['paciente_nombre'] . ' ' . $cita['paciente_apellido']); ?> <!-- Nombre del paciente -->
                                    <br>
                                    <small>Motivo: <?php echo htmlspecialchars($cita['motivo']); ?></small> <!-- Motivo de la cita -->
                                </div>
                                <div class="cita-estado">
                                    <span class="estado-badge estado-<?php echo $cita['estado']; ?>">
                                        <?php echo ucfirst($cita['estado']); ?> <!-- Estado de la cita -->
                                    </span>
                                    <br>
                                    <div class="boton-container" style="margin-top: var(--espacio);">
                                        <?php if ($cita['estado'] === 'pendiente'): ?>
                                            <!-- Botón de Confirmar -->
                                            <form action="confirmarCita.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="cita_id" value="<?php echo $cita['id']; ?>">
                                                <button type="submit" class="btn btn-confirmar" onclick="return confirmAction('confirmar');">Confirmar</button>
                                            </form>
                                            <!-- Botón de Completar -->
                                            <form action="completarCita.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="cita_id" value="<?php echo $cita['id']; ?>">
                                                <button type="submit" class="btn btn-completar" onclick="return confirmAction('completar');">Completar</button>
                                            </form>
                                            <!-- Botón de Cancelar -->
                                            <form action="cancelarCita.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="cita_id" value="<?php echo $cita['id']; ?>">
                                                <button type="submit" class="btn btn-cancelar" onclick="return confirmAction('cancelar');">Cancelar</button>
                                            </form>
                                        <?php elseif ($cita['estado'] === 'confirmada'): ?>
                                            <!-- Botón de Completar -->
                                            <form action="completarCita.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="cita_id" value="<?php echo $cita['id']; ?>">
                                                <button type="submit" class="btn btn-completar" onclick="return confirmAction('completar');">Completar</button>
                                            </form>
                                            <!-- Botón de Cancelar -->
                                            <form action="cancelarCita.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="cita_id" value="<?php echo $cita['id']; ?>">
                                                <button type="submit" class="btn btn-cancelar" onclick="return confirmAction('cancelar');">Cancelar</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
