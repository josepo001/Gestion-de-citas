<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'DB.php'; // Asegúrate de que la ruta sea correcta

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener conexión a la base de datos
    $db = getDB();

    // Capturar los datos del formulario
    $rut = $_POST['rut'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $tipo_usuario = $_POST['tipo_usuario'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Encriptar la contraseña

    try {
        // Validar si el email ya existe
        $checkEmailStmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $checkEmailStmt->bind_param("s", $email);
        $checkEmailStmt->execute();
        $checkEmailStmt->bind_result($count);
        $checkEmailStmt->fetch();
        $checkEmailStmt->close();

        if ($count > 0) {
            throw new Exception("El email ya está en uso.");
        }

        // Validar si el RUT ya existe
        $checkRUTStmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE rut = ?");
        $checkRUTStmt->bind_param("s", $rut);
        $checkRUTStmt->execute();
        $checkRUTStmt->bind_result($countRUT);
        $checkRUTStmt->fetch();
        $checkRUTStmt->close();

        if ($countRUT > 0) {
            throw new Exception("El RUT ya está en uso.");
        }

        // Insertar nuevo usuario
        $stmt = $db->prepare("INSERT INTO usuarios (rut, nombre, apellido, email, tipo_usuario, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $rut, $nombre, $apellido, $email, $tipo_usuario, $password);
        $stmt->execute();

        // Redirigir con mensaje de éxito
        header('Location: usuarios.php?success=Usuario registrado correctamente');
        exit;

    } catch (Exception $e) {
        $mensaje = "Error al registrar usuario: " . htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Usuario</title>
    <link rel="stylesheet" href="../css/registrar.css"> <!-- Asegúrate de tener un CSS para esta página -->
</head>
<body>
    <header class="header">
        <h1>Registrar Nuevo Usuario</h1>
    </header>

    <div class="position-absolute top-0 start-0 p-3">
        <a href="usuarios.php" class="btn btn-secondary blanco">Volver</a>
    </div>

    <?php if ($mensaje): ?>
        <div class="error-message">
            <p><?php echo $mensaje; ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div>
            <label for="rut">RUT:</label>
            <input type="text" name="rut" id="rut" required>
        </div>
        <div>
            <label for="nombre">Nombre:</label>
            <input type="text" name="nombre" id="nombre" required>
        </div>
        <div>
            <label for="apellido">Apellido:</label>
            <input type="text" name="apellido" id="apellido" required>
        </div>
        <div>
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>
        </div>
        <div>
            <label for="tipo_usuario">Tipo de Usuario:</label>
            <select name="tipo_usuario" id="tipo_usuario" required>
                <option value="admin">Admin</option>
                <option value="doctor">Doctor</option>
                <option value="paciente">Paciente</option>
            </select>
        </div>
        <div>
            <label for="password">Contraseña:</label>
            <input type="password" name="password" id="password" required>
        </div>
        <button type="submit">Registrar Usuario</button>
    </form>
</body>
</html>
