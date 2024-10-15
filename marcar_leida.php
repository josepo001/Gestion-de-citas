<?php
session_start(); // Inicia la sesión del usuario
include('../Admin/DB.php'); // Incluye el archivo de conexión a la base de datos

// Verifica si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php'); // Redirige a la página de inicio si no está autenticado
    exit;
}

// Verifica si se ha recibido un ID de notificación
if (isset($_POST['notificacion_id'])) {
    $notificacion_id = $_POST['notificacion_id']; // Obtiene el ID de la notificación desde la solicitud POST

    try {
        $db = getDB(); // Obtiene conexión a la base de datos
        
        // Actualiza la notificación a leída
        $stmt = $db->prepare("UPDATE notificaciones SET leido = 1 WHERE id = ? AND id_usuario = ?"); // Prepara la consulta SQL
        $stmt->bind_param("ii", $notificacion_id, $_SESSION['user_id']); // Asocia los parámetros de la consulta
        $stmt->execute(); // Ejecuta la consulta

        // Devuelve una respuesta JSON si deseas manejar esto con AJAX
        echo json_encode(['success' => true]); // Respuesta exitosa en formato JSON
    } catch (Exception $e) {
        // Maneja excepciones y devuelve un mensaje de error
        echo json_encode(['success' => false, 'error' => $e->getMessage()]); // Respuesta de error en formato JSON
    }
} else {
    // Si no se proporciona un ID de notificación
    echo json_encode(['success' => false, 'error' => 'No notification ID provided.']); // Respuesta de error
}
?>
