<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../Admin/DB.php'; // Asegúrate de que la ruta sea correcta

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

try {
    // Obtener conexión a la base de datos
    $db = getDB(); 
    if (!$db) {
        die("Error de conexión a la base de datos.");
    }

    // Obtener información del usuario logueado
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

} catch (Exception $e) {
    die("Error al obtener información del usuario: " . $e->getMessage());
}

// Para la lista de usuarios
$search = isset($_GET['search']) ? $_GET['search'] : ''; // Captura la búsqueda

try {
    // Modifica la consulta SQL para incluir la búsqueda
    $sql = "SELECT * FROM usuarios WHERE 
            nombre LIKE ? OR 
            apellido LIKE ? OR 
            email LIKE ? OR 
            id LIKE ? OR 
            tipo_usuario LIKE ?"; // Añadir búsqueda por tipo de usuario
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $db->error);
    }

    $searchTerm = '%' . $search . '%'; // Agregar comodines para la búsqueda
    $stmt->bind_param("sssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm); // Busca en nombre, apellido, email, id y tipo_usuario
    $stmt->execute();
    $result = $stmt->get_result(); // Obtener resultados

    // Verifica si la consulta devolvió filas
    if ($result->num_rows === 0) {
        $noResultMessage = "No se encontraron usuarios.";
    }

    // Obtener estadísticas generales
    $stats = [];

    // Total de usuarios
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
    die("Error al obtener la lista de usuarios: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor Usuarios</title>
    <link rel="stylesheet" href="../css/usuarios.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        function confirmarEliminar(id) {
            if (confirm("¿Estás seguro de que deseas eliminar este usuario?")) {
                document.getElementById('eliminarForm' + id).submit(); // Envía el formulario de eliminación
            }
        }
    </script>
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
        <h1>Gestion Usuarios</h1>
    </div>

    <!-- Mensaje de Éxito -->
    <?php if (isset($_GET['success'])): ?>
        <script>
            alert('<?php echo htmlspecialchars($_GET['success']); ?>');
        </script>
    <?php endif; ?>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Usuarios</h3>
                <div class="number"><?php echo $stats['total_usuarios']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Doctores</h3>
                <div class="number"><?php echo $stats['total_doctores']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pacientes</h3>
                <div class="number"><?php echo $stats['total_pacientes']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Adminstrador</h3>
                <div class="number"><?php echo $stats['total_Admin']; ?></div>
            </div>
        </div>
    </div>

    <!-- Formulario de Búsqueda -->
    <div class="search-container">
        <form method="GET" action="" id="searchForm">
            <input type="text" name="search" id="searchInput" placeholder="Buscar" required>
            <button id="searchButton">Buscar</button>
            <button type="button" id="clearButton">Restablecer Búsqueda</button> <!-- Botón de limpiar -->
        </form>
    </div>

    <script>
        document.getElementById('clearButton').addEventListener('click', function(event) {
            event.preventDefault(); // Evita que el botón de limpiar envíe el formulario
            // Envía el formulario sin el campo de búsqueda
            const searchForm = document.getElementById('searchForm');
            const inputField = document.getElementById('searchInput');
            inputField.value = ''; // Limpia el campo de búsqueda
            searchForm.submit(); // Envía el formulario para mostrar todos los usuarios
        });
    </script>

    <!-- Contenido -->
    <div class="home_content">
        <br>
        <div class="container table-responsive">
            <table class="table table-light table-bordered border-secondary table-rounded">
                <thead class="table-dark">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">RUT</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Apellido</th>
                        <th scope="col">Email</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Fecha Registro</th>            
                        <th scope="col">Editar</th>
                        <th scope="col">Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($mostrar = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($mostrar['id']); ?></td> 
                                <td><?php echo htmlspecialchars($mostrar['rut']); ?></td>
                                <td><?php echo htmlspecialchars($mostrar['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($mostrar['apellido']); ?></td>
                                <td><?php echo htmlspecialchars($mostrar['email']); ?></td>
                                <td><?php echo htmlspecialchars($mostrar['tipo_usuario']); ?></td>
                                <td><?php echo htmlspecialchars($mostrar['fecha_registro']); ?></td>
                                <td>
                                    <a class="btn btn-success btn-sm" href="editar.php?id=<?php echo htmlspecialchars($mostrar['id']); ?>">Editar</a>
                                </td>
                                <td>
                                    <form id="eliminarForm<?php echo $mostrar['id']; ?>" action="eliminar.php" method="post">
                                        <input type="hidden" value="<?php echo htmlspecialchars($mostrar['id']); ?>" name="txtID">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmarEliminar(<?php echo htmlspecialchars($mostrar['id']); ?>)">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">No se encontraron usuarios.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="button-container">
            <a class="btn_agregar" href="Registrar.php">AGREGAR USUARIO</a>
        </div>
    </div>
</body>
</html>
