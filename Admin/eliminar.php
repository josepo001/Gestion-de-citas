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
    $db = getDB();

    if (isset($_POST['txtID'])) {
        $id_usuario = $_POST['txtID'];

        // Eliminar registros en la tabla doctores relacionados con el usuario
        $stmt = $db->prepare("DELETE FROM doctores WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();

        // Ahora eliminar el usuario
        $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();

        // Verificar si se eliminó algún usuario
        if ($stmt->affected_rows > 0) {
            header('Location: usuarios.php?success=Usuario eliminado correctamente.');
            exit;
        } else {
            header('Location: usuarios.php?error=Error al eliminar el usuario.');
            exit;
        }
    }
} catch (Exception $e) {
    die("Error al eliminar usuario: " . $e->getMessage());
}
?>
