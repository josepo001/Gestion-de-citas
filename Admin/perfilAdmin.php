<?php
session_start();
require_once '../Admin/DB.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

try {
    $db = getDB(); // Obtener conexión a la base de datos
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']); // Usando bind_param para mayor seguridad
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc(); // Obtener los datos del usuario

    if ($user === null) {
        // Manejo de error: usuario no encontrado
        $_SESSION['mensaje'] = "Usuario no encontrado.";
        header('Location: index.php'); // Redirige a la página de inicio o a otra página
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nombre = $_POST['nombre'] ?? '';
        $apellido = $_POST['apellido'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($password) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, password = ? WHERE id = ?");
            $stmt->execute([$nombre, $apellido, $email, $password_hash, $_SESSION['user_id']]);
        } else {
            $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ? WHERE id = ?");
            $stmt->execute([$nombre, $apellido, $email, $_SESSION['user_id']]);
        }

        $_SESSION['mensaje'] = "Perfil actualizado correctamente";
        header('Location: perfil.php');
        exit;
    }
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <link rel="stylesheet" href="../css/perfil.css">
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
                    <?php if ($user['tipo_usuario'] == 'doctor'): ?>
                        <li><a href="agenda.php"><i class="fas fa-calendar-alt"></i> Mi Agenda</a></li>
                        <li><a href="horarios.php"><i class="fas fa-clock"></i> Mis Horarios</a></li>
                    <?php endif; ?>
                    <?php if ($user['tipo_usuario'] == 'admin'): ?>
                        <li><a href="usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
                        <li><a href="Doctores.php"><i class="fas fa-stethoscope"></i> Doctores</a></li>
                    <?php endif; ?>
                    <li><a href="perfilAdmin.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
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
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="mensaje">
                    <?php 
                    echo $_SESSION['mensaje'];
                    unset($_SESSION['mensaje']);
                    ?>
                </div>
            <?php endif; ?>
            
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
                
                <button type="submit" class="btn-actualizar">Actualizar Perfil</button>
            </form>
        </div>
    </div>
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('hidden');
        }
    </script>
</body>
</html>
