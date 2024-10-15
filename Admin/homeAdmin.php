<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../Admin/DB.php'; // Asegúrate de que la ruta sea correcta

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $db = getDB(); // Obtener conexión a la base de datos

    // Obtener la información del usuario logueado
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']); // Usando bind_param para mayor seguridad
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc(); // Obtener los datos del usuario
    
    // Verificar si el usuario es administrador
    if ($user['tipo_usuario'] !== 'admin') {
        header('Location: login.php');
        exit;
    }

    // Obtener estadísticas generales
    $stats = [];

    // Total de usuarios
    $result = $db->query("SELECT COUNT(*) as total FROM usuarios");
    $stats['total_usuarios'] = $result->fetch_assoc()['total'];

    // Total de doctores
    $result = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo_usuario = 'doctor'");
    $stats['total_doctores'] = $result->fetch_assoc()['total'];

    // Total de pacientes
    $result = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo_usuario = 'paciente'");
    $stats['total_pacientes'] = $result->fetch_assoc()['total'];

    // Citas pendientes
    $result = $db->query("SELECT COUNT(*) as total FROM citas WHERE estado = 'pendiente'");
    $stats['citas_pendientes'] = $result->fetch_assoc()['total'];

    // Citas de hoy
    $result = $db->query("SELECT COUNT(*) as total FROM citas WHERE fecha = CURRENT_DATE");
    $stats['citas_hoy'] = $result->fetch_assoc()['total'];

    // Citas de la semana
    $result = $db->query("SELECT COUNT(*) as total FROM citas WHERE fecha BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)");
    $stats['citas_semana'] = $result->fetch_assoc()['total'];

    // Obtener últimas citas
    $ultimasCitas = $db->query("
        SELECT c.*, 
            p.nombre as paciente_nombre, p.apellido as paciente_apellido,
            d.nombre as doctor_nombre, d.apellido as doctor_apellido
        FROM citas c
        JOIN usuarios p ON c.id_paciente = p.id
        JOIN usuarios d ON c.id_doctor = d.id
        ORDER BY c.fecha_registro DESC
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);

    // Obtener doctores más activos
    $doctoresActivos = $db->query("
        SELECT d.nombre, d.apellido, COUNT(c.id) as total_citas
        FROM usuarios d
        LEFT JOIN citas c ON d.id = c.id_doctor
        WHERE d.tipo_usuario = 'doctor'
        GROUP BY d.id
        ORDER BY total_citas DESC
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    die("Error al obtener información del usuario: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="../css/homeAdmin.css">
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
                    <li><a href="homeAdmin.php"><i class="fas fa-home"></i> Inicio</a></li>
                    <?php if ($user['tipo_usuario'] == 'admin'): ?>
                        <li><a href="usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
                        <li><a href="Doctores.php"><i class="fas fa-stethoscope"></i> Doctores</a></li>
                    <?php endif; ?>
                    <li><a href="perfilAdmin.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                    <li><a href="../cerrar-sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                </ul>
            </nav>
            <div class="user-info">
                <i class="fas fa-user-circle" style="font-size: 24px; margin-right: 8px;"></i>
                <span><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></span>
            </div>
        </div>
    </header>

    <div class="main-content">
        <div style="text-align: left;">
            <h1>Bienvenido administrador</h1>
        </div>
        
        <!-- Sección de tablas -->
<div class="content-box">
    <h2>
        Últimas Citas Registradas
    </h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Paciente</th>
                        <th>Doctor</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimasCitas as $cita): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></td>
                            <td><?php echo htmlspecialchars($cita['paciente_nombre'] . ' ' . $cita['paciente_apellido']); ?></td>
                            <td><?php echo htmlspecialchars($cita['doctor_nombre'] . ' ' . $cita['doctor_apellido']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo htmlspecialchars($cita['estado']); ?>">
                                <?php echo ucfirst(htmlspecialchars($cita['estado'])); ?>
                             </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
</div>


</body>
</html>