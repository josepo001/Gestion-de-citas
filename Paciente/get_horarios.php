<?php
session_start();
require_once '../Admin/DB.php'; // Asegúrate de que la ruta sea correcta

header('Content-Type: application/json'); // Establecer el tipo de contenido a JSON

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Código de estado 401 No autorizado
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Obtener los parámetros GET (doctor y fecha)
$doctor_id = $_GET['doctor'] ?? null;
$fecha = $_GET['fecha'] ?? null;

if (!$doctor_id || !$fecha) {
    http_response_code(400); // Código de estado 400 Bad Request
    echo json_encode(['error' => 'Faltan parámetros requeridos']);
    exit;
}

try {
    // Obtener la conexión a la base de datos
    $db = getDB();

    // Obtener el día de la semana en español
    $dia_semana = date('l', strtotime($fecha)); // Obtener el día en inglés
    $dia_semana_esp = [
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes',
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado',
        'Sunday' => 'Domingo'
    ][$dia_semana]; // Traducir a español
    
    // Consulta para obtener el horario del doctor en un día específico
    $query = "SELECT hora_inicio, hora_fin 
              FROM horarios 
              WHERE id_doctor = ? AND dia_semana = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("is", $doctor_id, $dia_semana_esp); // Usar bind_param para mayor seguridad
    $stmt->execute();
    $result = $stmt->get_result();
    $horario = $result->fetch_assoc(); // Obtener el resultado como un array asociativo
    
    if (!$horario) {
        echo json_encode([]); // Si no hay horarios, devolver un array vacío
        exit;
    }
    
    // Generar intervalos de 30 minutos entre hora_inicio y hora_fin
    $hora_actual = strtotime($horario['hora_inicio']);
    $hora_fin = strtotime($horario['hora_fin']);
    $slots = []; // Array para almacenar los slots de tiempo
    
    while ($hora_actual < $hora_fin) {
        $hora = date('H:i', $hora_actual);
        
        // Verificar si el slot de la hora actual está disponible
        $query = "SELECT id FROM citas 
                  WHERE id_doctor = ? AND fecha = ? AND hora = ? 
                  AND estado != 'cancelada'";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("iss", $doctor_id, $fecha, $hora);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Añadir el slot al array con su disponibilidad
        $slots[] = [
            'hora' => $hora,
            'disponible' => $result->num_rows == 0 // Disponible si no hay citas
        ];
        
        // Incrementar en 30 minutos
        $hora_actual = strtotime('+30 minutes', $hora_actual);
    }
    
    // Devolver los slots en formato JSON
    echo json_encode($slots);

} catch (Exception $e) {
    http_response_code(500); // Código de estado 500 Error del servidor
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>
