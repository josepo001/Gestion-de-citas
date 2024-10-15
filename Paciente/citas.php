<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../Admin/DB.php'; // Asegúrate de que la ruta sea correcta

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $db = getDB(); // Asegúrate de que esta función devuelve una conexión MySQLi

    // Obtener los datos del usuario
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']); // Usando bind_param para mayor seguridad
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc(); // Obtener los datos del usuario

    // Obtener todas las citas del usuario que son pendientes o confirmadas
    $query = "SELECT c.*, 
              d.nombre AS doctor_nombre, d.apellido AS doctor_apellido,
              e.nombre AS especialidad
              FROM citas c
              INNER JOIN doctores doc ON c.id_doctor = doc.id
              INNER JOIN usuarios d ON doc.id_usuario = d.id
              INNER JOIN especialidades e ON doc.id_especialidad = e.id
              WHERE c.id_paciente = ? AND c.estado IN ('pendiente', 'confirmada')
              ORDER BY c.fecha ASC, c.hora DESC";

    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']); // Vincula el parámetro
    $stmt->execute();
    $result = $stmt->get_result();
    $citas = $result->fetch_all(MYSQLI_ASSOC); // Obtiene todas las citas como un array asociativo

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

    $stmt->close(); // Cierra la declaración

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Citas</title>
    <link rel="stylesheet" href="../css/citas.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        <h1>Mis Citas</h1>
    </div>
    
    <div class="main-content">
        <div class="citas-container">
            <?php if (empty($citas)): ?>
                <p>No tienes citas programadas.</p>
            <?php else: ?>
                <!-- Mostrar citas -->
                <?php foreach ($citas as $cita): ?>
                    <div class="cita-card">
                        <div class="cita-header">
                            <span class="cita-fecha">
                                <i class="fas fa-calendar"></i> 
                                <?php echo date('d/m/Y', strtotime($cita['fecha'])); ?>
                                <i class="fas fa-clock"></i> 
                                <?php echo date('H:i', strtotime($cita['hora'])); ?>
                            </span>
                            <span class="cita-estado estado-<?php echo $cita['estado']; ?>">
                                <?php echo ucfirst($cita['estado']); ?>
                            </span>
                        </div>
                        
                        <div class="cita-info">
                            <p>
                                <i class="fas fa-user-md"></i> 
                                Dr. <?php echo htmlspecialchars($cita['doctor_nombre'] . ' ' . $cita['doctor_apellido']); ?>
                            </p>
                            <p>
                                <i class="fas fa-stethoscope"></i> 
                                <?php echo htmlspecialchars($cita['especialidad']); ?>
                            </p>
                            <?php if ($cita['motivo']): ?>
                                <p>
                                    <i class="fas fa-comment"></i> 
                                    <?php echo htmlspecialchars($cita['motivo']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($cita['estado'] == 'pendiente' || $cita['estado'] == 'confirmada'): ?>
                            <button class="btn-cancelar" onclick="cancelarCita(<?php echo $cita['id']; ?>)">
                                Cancelar Cita
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function cancelarCita(idCita) {
            if (confirm("¿Estás seguro de que deseas cancelar esta cita?")) {
                $.ajax({
                    type: "POST",
                    url: 'anular_hora.php', // Asegúrate de que esta ruta sea correcta
                    data: { cita_id: idCita },
                    dataType: 'json', // Espera una respuesta JSON
                    success: function(response) {
                        if (response.success) {
                            alert("Cita cancelada con éxito.");
                            location.reload(); // Recargar la página para reflejar los cambios
                        } else {
                            alert(response.error); // Mostrar error
                        }
                    },
                    error: function() {
                        alert("Ocurrió un error al procesar la solicitud.");
                    }
                });
            }
        }
    </script>

    <script src="../js/noti.js"></script>

</body>
</html>
