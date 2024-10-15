<?php
session_start();
include('../Admin/DB.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cita_id'])) {
    try {
        $db = getDB();
        
        // Obtener ID de la cita desde el formulario
        $cita_id = $_POST['cita_id'];

        // Actualizar el estado de la cita a 'confirmada'
        $stmt = $db->prepare("UPDATE citas SET estado = 'confirmada' WHERE id = ?");
        $stmt->bind_param('i', $cita_id);
        
        if ($stmt->execute()) {
            // Obtener el ID del paciente relacionado con la cita
            $stmt = $db->prepare("SELECT id_paciente FROM citas WHERE id = ?");
            $stmt->bind_param("i", $cita_id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $cita = $resultado->fetch_assoc();
            
            if ($cita) {
                $id_paciente = $cita['id_paciente'];

                // Insertar notificación para el paciente
                $mensaje_notificacion = "Su cita ha sido confirmada por el doctor.";
                $stmt = $db->prepare("INSERT INTO notificaciones (id_usuario, mensaje, leido, fecha) VALUES (?, ?, 0, NOW())");
                $stmt->bind_param("is", $id_paciente, $mensaje_notificacion);
                $stmt->execute();
            }

            // Redireccionar de vuelta a la agenda del doctor con un mensaje de éxito
            header('Location: homeDoc.php?mensaje=La cita ha sido confirmada exitosamente.');
            exit;
        } else {
            // Manejo de errores si la actualización falla
            header('Location: homeDoc.php?error=No se pudo confirmar la cita. Intente de nuevo.');
            exit;
        }
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    // Redireccionar si no se recibe el ID de la cita
    header('Location: homeDoc.php?error=Acción no válida.');
    exit;
}
?>
