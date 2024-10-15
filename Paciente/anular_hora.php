<?php
    session_start();
    require_once '../Admin/DB.php';

    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }

    $cita_id = $_POST['cita_id'] ?? null; // Cambiar a POST

    if (!$cita_id) {
        echo json_encode(['success' => false, 'error' => 'ID de cita no proporcionado']);
        exit;
    }

    try {
        $db = getDB(); // Asegúrate de que esta función devuelve una conexión MySQLi
        
        // Verificar que la cita existe y obtener el ID del doctor
        $stmt = $db->prepare("SELECT id, estado, id_doctor FROM citas WHERE id = ?");
        $stmt->bind_param("i", $cita_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cita = $result->fetch_assoc();
        
        if (!$cita) {
            echo json_encode(['success' => false, 'error' => 'Cita no encontrada']);
            exit;
        }
        
        if ($cita['estado'] === 'cancelada') {
            echo json_encode(['success' => false, 'error' => 'La cita ya está cancelada']);
            exit;
        }
        
        // Cancelar la cita
        $stmt = $db->prepare("UPDATE citas SET estado = 'cancelada' WHERE id = ?");
        $stmt->bind_param("i", $cita_id);
        $stmt->execute();
        
        // Insertar notificación para el doctor
        $doctor_id = $cita['id_doctor'];
        $mensaje_notificacion = "El paciente ID: " . $_SESSION['user_id'] . " ha cancelado la cita ID: " . $cita_id . ".";
        $stmt = $db->prepare("INSERT INTO notificaciones (id_usuario, mensaje, leido, fecha) VALUES (?, ?, 0, NOW())");
        $stmt->bind_param("is", $doctor_id, $mensaje_notificacion);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
        
    } catch (mysqli_sql_exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
?>