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

        // Actualizar el estado de la cita a 'completada'
        $stmt = $db->prepare("UPDATE citas SET estado = 'completada' WHERE id = ? AND id_doctor = ?");
        $stmt->bind_param('ii', $cita_id, $_SESSION['user_id']);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Obtener el ID del paciente para notificarlo
            $stmt = $db->prepare("SELECT id_paciente FROM citas WHERE id = ?");
            $stmt->bind_param('i', $cita_id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $cita = $resultado->fetch_assoc();

            if ($cita) {
                $paciente_id = $cita['id_paciente'];

                // Insertar notificación para el paciente
                $mensaje_notificacion = "Su cita ha sido marcada como completada por el doctor.";
                $stmt = $db->prepare("INSERT INTO notificaciones (id_usuario, mensaje, leido, fecha) VALUES (?, ?, 0, NOW())");
                $stmt->bind_param('is', $paciente_id, $mensaje_notificacion);
                $stmt->execute();
            }

            $mensaje = "Cita completada con éxito.";
        } else {
            $mensaje = "Error al completar la cita. Por favor, inténtalo de nuevo.";
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
