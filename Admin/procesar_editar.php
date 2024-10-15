<?php
require_once '../Admin/DB.php'; // Asegúrate de que la ruta sea correcta

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['txtID'];
    $nombre = $_POST['txtNombre'];
    $apellido = $_POST['txtApellido'];
    $email = $_POST['txtEmail'];
    $password = $_POST['txtPassword'];
    $tipo_usuario = $_POST['txtTipoUsuario'];

    $db = getDB(); // Obtener conexión a la base de datos

    // Actualizar solo si la contraseña no está vacía
    if (!empty($password)) {
        $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, password = ?, tipo_usuario = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $nombre, $apellido, $email, password_hash($password, PASSWORD_DEFAULT), $tipo_usuario, $id);
    } else {
        $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, tipo_usuario = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $nombre, $apellido, $email, $tipo_usuario, $id);
    }

    if ($stmt->execute()) {
        // Redirigir con mensaje de éxito
        header('Location: usuarios.php?success=Usuario actualizado correctamente');
        exit;
    } else {
        // Manejar errores
        die("Error al actualizar el usuario: " . $stmt->error);
    }
}
?>
