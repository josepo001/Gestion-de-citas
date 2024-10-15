<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../Admin/DB.php'; // Asegúrate de que la ruta sea correcta

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirige a login.php en lugar de index.php
    exit;
}

try {
    $db = getDB(); // Obtener conexión a la base de datos

    // Obtener la información del usuario logueado
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Consulta que une la tabla doctores, usuarios y especialidades
    $stmt = $db->prepare("
        SELECT d.id, u.nombre, u.apellido, e.nombre AS especialidad, d.consultorio, d.horario_inicio, d.horario_fin, d.dias_atencion, d.numero_colegiado
        FROM doctores d
        JOIN usuarios u ON d.id_usuario = u.id
        JOIN especialidades e ON d.id_especialidad = e.id
    ");
    $stmt->execute();
    $doctores = $stmt->get_result();

    // Obtener estadísticas generales
    $stats = [];
    $resultCount = $db->query("SELECT COUNT(*) as total FROM usuarios");
    $stats['total_usuarios'] = $resultCount->fetch_assoc()['total'];

    // Total de doctores
    $resultCount = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo_usuario = 'doctor'");
    $stats['total_doctores'] = $resultCount->fetch_assoc()['total'];

    // Total de admin
    $resultCount = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo_usuario = 'admin'");
    $stats['total_Admin'] = $resultCount->fetch_assoc()['total'];

    // Total de pacientes
    $resultCount = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo_usuario = 'paciente'");
    $stats['total_pacientes'] = $resultCount->fetch_assoc()['total'];

    // Citas pendientes
    $resultCount = $db->query("SELECT COUNT(*) as total FROM citas WHERE estado = 'pendiente'");
    $stats['citas_pendientes'] = $resultCount->fetch_assoc()['total'];

    // Citas de hoy
    $resultCount = $db->query("SELECT COUNT(*) as total FROM citas WHERE fecha = CURRENT_DATE");
    $stats['citas_hoy'] = $resultCount->fetch_assoc()['total'];

    // Citas de la semana
    $resultCount = $db->query("SELECT COUNT(*) as total FROM citas WHERE fecha BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)");
    $stats['citas_semana'] = $resultCount->fetch_assoc()['total'];
} catch (Exception $e) {
    error_log($e->getMessage()); // Registrar error en el servidor
    die("Error al obtener la lista de doctores."); // Mensaje genérico
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor Doctores</title>
    <link rel="stylesheet" href="../css/Doctores.css">
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
                <i class="fas fa-user-circle" style="font-size: 24px;"></i>
                <span><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></span>
                <small><?php echo ucfirst($user['tipo_usuario']); ?></small>
            </div>
        </div>
    </header>

    <div style="text-align: left;">
        <h1>Gestión de Doctores</h1>
    </div>

    <!-- estadísticas -->
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Citas Pendientes</h3>
                <div class="number"><?php echo $stats['citas_pendientes']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Citas Hoy</h3>
                <div class="number"><?php echo $stats['citas_hoy']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Citas Esta Semana</h3>
                <div class="number"><?php echo $stats['citas_semana']; ?></div>
            </div>
        </div>
    </div>


    <!-- Tabla para mostrar doctores con sus especialidades -->
    <div class="container">
        <table class="table table-light table-bordered border-secondary table-rounded">
            <thead class="table-dark">
                <tr>
                    <th scope="col" style="width: 50px; vertical-align: middle;">ID</th>
                    <th scope="col" style="width: 120px; vertical-align: middle;">Nombre</th>
                    <th scope="col" style="width: 120px; vertical-align: middle;">Apellido</th>
                    <th scope="col" style="width: 150px; vertical-align: middle;">Especialidad</th>
                    <th scope="col" style="width: 100px; vertical-align: middle;">Consultorio</th>
                    <th scope="col" style="width: 150px; vertical-align: middle;">Horario</th>
                    <th scope="col" style="width: 100px; vertical-align: middle;">Días Atención</th>
                    <th scope="col" style="width: 100px; vertical-align: middle;">Número Colegiado</th>
                    <th scope="col" style="width: 80px; vertical-align: middle;">Editar</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($doctores->num_rows > 0): ?>
                    <?php while ($doctor = $doctores->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($doctor['id']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['especialidad']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['consultorio']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['horario_inicio'] . ' - ' . $doctor['horario_fin']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['dias_atencion']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['numero_colegiado']); ?></td>
                            <td>
                                <a class="btn btn-success btn-sm" href="editarDoc.php?id=<?php echo htmlspecialchars($doctor['id']); ?>">Editar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center;">No se encontraron doctores.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
