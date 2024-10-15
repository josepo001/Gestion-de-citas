<?php
session_start();
include('../Admin/DB.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cita_id = $_POST['cita_id'];

    try {
        $db = getDB();

        // Actualizar el estado de la cita a 'cancelada'
        $stmt = $db->prepare("UPDATE citas SET estado = 'cancelada' WHERE id = ? AND id_doctor = ?");
        $stmt->bind_param('ii', $cita_id, $_SESSION['user_id']);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Obtener el id del paciente para la notificación
            $stmt = $db->prepare("SELECT id_paciente FROM citas WHERE id = ?");
            $stmt->bind_param("i", $cita_id);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows > 0) {
                $cita = $resultado->fetch_assoc();
                $paciente_id = $cita['id_paciente'];

                // Insertar notificación para el paciente
                $mensaje_notificacion = "La cita con el doctor ID: " . $_SESSION['user_id'] . " ha sido cancelada.";
                $stmt = $db->prepare("INSERT INTO notificaciones (id_usuario, mensaje, leido, fecha) VALUES (?, ?, 0, NOW())");
                $stmt->bind_param("is", $paciente_id, $mensaje_notificacion);
                $stmt->execute();
            }

            $mensaje = "Cita cancelada con éxito.";
        } else {
            $mensaje = "Error al cancelar la cita. Por favor, inténtalo de nuevo.";
        }

        header("Location: homeDoc.php?mensaje=" . urlencode($mensaje));
        exit;

    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header('Location: homeDoc.php');
    exit;
}

