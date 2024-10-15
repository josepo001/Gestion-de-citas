<?php
session_start();
require_once '../Admin/DB.php'; // Asegúrate de que la ruta de DB.php sea correcta

// Verificar si el usuario está logueado y es paciente
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirigir a login si no hay sesión
    exit;
}

try {
    $db = getDB(); // Obtener conexión a la base de datos

    // Verificar que el usuario actual sea un paciente
    $stmt = $db->prepare("SELECT tipo_usuario FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']); // Bind de parámetro
    $stmt->execute();
    $resultado = $stmt->get_result(); // Obtener el resultado
    $usuario = $resultado->fetch_assoc(); // Fetch como asociativo

    // Comprobar si se obtuvo un resultado
    if (!$usuario || $usuario['tipo_usuario'] !== 'paciente') {
        die("Acceso no autorizado"); // Acceso denegado
    }
    
    // Validar y obtener los datos del formulario
    $doctor_id = filter_input(INPUT_POST, 'doctor', FILTER_VALIDATE_INT);
    $fecha = filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_STRING);
    $hora = filter_input(INPUT_POST, 'hora', FILTER_SANITIZE_STRING);
    $motivo = filter_input(INPUT_POST, 'motivo', FILTER_SANITIZE_STRING);
    
    // Verificación de campos requeridos
    if (!$doctor_id || !$fecha || !$hora || !$motivo) { 
        throw new Exception("Todos los campos son requeridos");
    }
    
    // Validar que la fecha sea futura
    $fecha_cita = new DateTime($fecha . ' ' . $hora);
    $ahora = new DateTime();
    
    if ($fecha_cita <= $ahora) {
        throw new Exception("La fecha de la cita debe ser futura");
    }
    
    // Verificar disponibilidad del horario
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM citas 
        WHERE id_doctor = ? 
        AND fecha = ? 
        AND hora = ? 
        AND estado != 'cancelada'
    ");
    $stmt->bind_param("iss", $doctor_id, $fecha, $hora); // Bind de parámetros
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->fetch_row()[0] > 0) {
        // Agregado: log para depuración
        error_log("Horario no disponible para Doctor ID: $doctor_id, Fecha: $fecha, Hora: $hora");
        throw new Exception("El horario seleccionado ya no está disponible");
    }
    
    // Insertar la nueva cita
    $stmt = $db->prepare("
        INSERT INTO citas (
            id_paciente, 
            id_doctor, 
            fecha, 
            hora, 
            motivo, 
            estado, 
            fecha_registro
        ) VALUES (?, ?, ?, ?, ?, 'pendiente', NOW())
    ");
    
    $stmt->bind_param("iisss", $_SESSION['user_id'], $doctor_id, $fecha, $hora, $motivo); // Bind de parámetros
    $resultado = $stmt->execute();
    
    if ($resultado) {
        // Obtener el nombre del paciente
        $stmt = $db->prepare("SELECT nombre, apellido FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $resultado_paciente = $stmt->get_result();
        $paciente = $resultado_paciente->fetch_assoc();
        
        if (!$paciente) {
            throw new Exception("No se pudo obtener la información del paciente");
        }
        
        // Crear el mensaje con el nombre y apellido del paciente
        $nombre_completo_paciente = $paciente['nombre'] . ' ' . $paciente['apellido'];
        $mensaje_notificacion = "Nueva cita agendada con el paciente: " . $nombre_completo_paciente . " para el " . $fecha . " a las " . $hora . ". Motivo: " . $motivo;
    
        // Insertar la notificación para el doctor
        $stmt = $db->prepare("
            INSERT INTO notificaciones (id_usuario, mensaje, leido, fecha) 
            VALUES (?, ?, 0, NOW())
        ");
        $stmt->bind_param("is", $doctor_id, $mensaje_notificacion); // Bind de parámetros
        $stmt->execute(); // Ejecutar la inserción de la notificación
    
        // Redirigir con mensaje de éxito
        header('Location: home.php?mensaje=' . urlencode("Cita agendada correctamente"));
        exit;
    } else {
        throw new Exception("Error al agendar la cita"); // Error al ejecutar la consulta
    }
      
    
} catch (Exception $e) {
    // Manejo de errores, redireccionar con mensaje
    header('Location: reserva_hora.php?error=' . urlencode($e->getMessage()));
    exit;
}
