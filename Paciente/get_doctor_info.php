<?php
session_start();
require_once '../Admin/DB.php'; // Asegúrate de que la ruta al archivo de conexión sea correcta

header('Content-Type: application/json'); // Establecer el tipo de contenido a JSON

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Código de estado 401 No autorizado
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Obtener el ID del doctor de los parámetros GET, validando que sea un entero
$doctor_id = filter_input(INPUT_GET, 'doctor', FILTER_VALIDATE_INT);

if (!$doctor_id) {
    http_response_code(400); // Código de estado 400 Bad Request
    echo json_encode(['error' => 'ID de doctor no válido']);
    exit;
}

try {
    // Obtener conexión a la base de datos
    $db = getDB();

    // Preparar la consulta para obtener información del doctor
    $stmt = $db->prepare("
        SELECT 
            u.nombre, 
            u.apellido,
            e.nombre AS especialidad,
            d.consultorio,
            d.horario_inicio,
            d.horario_fin,
            d.dias_atencion
        FROM usuarios u
        INNER JOIN doctores d ON d.id_usuario = u.id
        INNER JOIN especialidades e ON d.id_especialidad = e.id
        WHERE u.id = ? AND u.tipo_usuario = 'doctor'
    ");
    
    // Vincular parámetros y ejecutar la consulta
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    
    // Obtener el resultado de la consulta
    $result = $stmt->get_result();
    $doctor = $result->fetch_assoc(); // Obtener el resultado como un array asociativo
    
    if ($doctor) {
        // Formatear los días de atención
        $dias_semana = [
            'L' => 'Lunes',
            'M' => 'Martes',
            'X' => 'Miércoles',
            'J' => 'Jueves',
            'V' => 'Viernes',
            'S' => 'Sábado',
            'D' => 'Domingo'
        ];
        
        $dias_atencion = [];
        foreach (str_split($doctor['dias_atencion']) as $dia) {
            if (isset($dias_semana[$dia])) {
                $dias_atencion[] = $dias_semana[$dia]; // Agregar el día correspondiente al array
            }
        }

        // Enviar la respuesta en formato JSON
        echo json_encode([
            'nombre' => $doctor['nombre'] . ' ' . $doctor['apellido'],
            'especialidad' => $doctor['especialidad'],
            'consultorio' => $doctor['consultorio'],
            'horario' => sprintf(
                '%s a %s', 
                date('H:i', strtotime($doctor['horario_inicio'])),
                date('H:i', strtotime($doctor['horario_fin']))
            ),
            'dias_atencion' => implode(', ', $dias_atencion), // Convertir el array a una cadena
            'success' => true // Indicar que la operación fue exitosa
        ]);
    } else {
        http_response_code(404); // Código de estado 404 Not Found
        echo json_encode([
            'error' => 'Doctor no encontrado',
            'success' => false
        ]);
    }

} catch (Exception $e) {
    http_response_code(500); // Código de estado 500 Error del servidor
    echo json_encode([
        'error' => 'Error del servidor: ' . $e->getMessage(),
        'success' => false
    ]);
}
?>
