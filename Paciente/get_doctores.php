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

// Obtener el ID de especialidad de los parámetros GET, validando que sea un entero
$especialidad_id = filter_input(INPUT_GET, 'especialidad', FILTER_VALIDATE_INT);

if (!$especialidad_id) {
    http_response_code(400); // Código de estado 400 Bad Request
    echo json_encode(['error' => 'Especialidad no válida']);
    exit;
}

try {
    // Obtener la conexión a la base de datos
    $db = getDB();

    // Consulta para obtener los doctores junto con su información de usuario
    $stmt = $db->prepare("
        SELECT u.id AS id, u.nombre, u.apellido 
        FROM usuarios u 
        INNER JOIN doctores d ON u.id = d.id_usuario 
        WHERE d.id_especialidad = ? AND u.tipo_usuario = 'doctor'
    ");
    $stmt->bind_param('i', $especialidad_id); // 'i' para indicar que es un entero
    $stmt->execute();
    
    $result = $stmt->get_result();
    $doctores = $result->fetch_all(MYSQLI_ASSOC); // Obtener todos los doctores como un array asociativo

    // Devolver la lista de doctores en formato JSON
    echo json_encode($doctores);
    
} catch (Exception $e) {
    http_response_code(500); // Código de estado 500 Error del servidor
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>
