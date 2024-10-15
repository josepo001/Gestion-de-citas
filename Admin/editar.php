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
    $db = getDB(); // Obtener conexión a la base de datos

    // Verificar si se ha pasado un ID por la URL
    if (isset($_GET['id'])) {
        $id = $_GET['id'];

        // Obtener información del usuario a editar
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $user = $resultado->fetch_assoc(); // Cargar los datos del usuario a editar
        } else {
            echo "Usuario no encontrado.";
            exit; // Detener ejecución si el usuario no existe
        }
    } else {
        echo "ID de usuario no especificado.";
        exit; // Detener ejecución si no se especifica el ID
    }
} catch (Exception $e) {
    die("Error al obtener información del usuario: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar</title>
    <link rel="stylesheet" href="../css/editar.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div style="text-align: left;">
    <h1>Editar Usuario</h1>
</div>

<!-- FORMULARIO -->
<form class="profile-form" action="procesar_editar.php" method="post" enctype="multipart/form-data">
    <input type="hidden" value="<?php echo htmlspecialchars($user['id']); ?>" name="txtID">        

    <div class="form-group">
        <label for="inputNombre"><b>NOMBRE</b></label>
        <input id="inputNombre" class="form-control" type="text" value="<?php echo htmlspecialchars($user['nombre']); ?>" name="txtNombre" required>
    </div>

    <div class="form-group">
        <label for="inputApellido"><b>APELLIDO</b></label>
        <input id="inputApellido" class="form-control" type="text" value="<?php echo htmlspecialchars($user['apellido']); ?>" name="txtApellido" required>
    </div>
    
    <div class="form-group">
        <label for="inputEmail"><b>EMAIL</b></label>
        <input id="inputEmail" class="form-control" type="email" value="<?php echo htmlspecialchars($user['email']); ?>" name="txtEmail" required>
    </div>

    <div class="form-group">
        <label for="inputPassword"><b>CONTRASEÑA</b></label>
        <input id="inputPassword" class="form-control" type="password" name="txtPassword" placeholder="dejar vacío si no desea cambiar">
    </div>
    
    <div class="form-group">
        <label for="tipoUsuario"><b>TIPO DE USUARIO</b></label>
        <select name="txtTipoUsuario" id="tipoUsuario">
            <option value="admin" <?php echo $user['tipo_usuario'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
            <option value="doctor" <?php echo $user['tipo_usuario'] == 'doctor' ? 'selected' : ''; ?>>Doctor</option>
            <option value="paciente" <?php echo $user['tipo_usuario'] == 'paciente' ? 'selected' : ''; ?>>Paciente</option>
        </select>
    </div>

    <div class="text-center">
        <button type="submit" name="accion" class="btn-actualizar">Actualizar</button>
        <a class="btn btn-danger btn-cancelar" href="usuarios.php">Cancelar</a>
    </div>
</form>
<br>
<!-- FIN FORMULARIO -->

</body>
</html>
