<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'Admin/DB.php'; // Asegúrate de que esta función conecte correctamente a la base de datos

// Si la solicitud es POST, intentamos iniciar sesión
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rut = trim($_POST['rut'] ?? ''); // Capturamos el RUT y eliminamos espacios
    $password = $_POST['password'] ?? ''; // Capturamos la contraseña

    try {
        // Obtener la conexión a la base de datos
        $db = getDB();
        
        // Preparamos una consulta para encontrar al usuario según el RUT
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE rut = ?");
        $stmt->bind_param("s", $rut); // Vinculamos el parámetro
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc(); // Obtenemos el resultado como un array asociativo

        // Si el usuario existe y la contraseña es correcta (usando password_verify)
        if ($user && password_verify($password, $user['password'])) {
            // Guardar la información relevante en la sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['tipo_usuario'];
            $_SESSION['user_name'] = $user['nombre'] . ' ' . $user['apellido'];

            // Si es doctor, obtenemos el id de la tabla 'doctores'
            if ($user['tipo_usuario'] === 'doctor') {
                $stmt = $db->prepare("SELECT id FROM doctores WHERE id_usuario = ?");
                $stmt->bind_param("i", $user['id']); // Asegúrate de que el tipo de dato es correcto
                $stmt->execute();
                $result = $stmt->get_result();
                $doctor = $result->fetch_assoc();
                if ($doctor) {
                    $_SESSION['doctor_id'] = $doctor['id'];
                }
            }

            // Redirigir al usuario según su tipo
            switch ($user['tipo_usuario']) {
                case 'admin':
                    header('Location: Admin/homeAdmin.php');
                    break;
                case 'doctor':
                    header('Location: Doctor/homeDoc.php');
                    break;
                case 'paciente':
                    header('Location: Paciente/home.php');
                    break;
                default:
                    $_SESSION['error'] = "Tipo de usuario no reconocido.";
                    header('Location: login.php');
            }
            exit;
        } else {
            // Si las credenciales son inválidas
            $_SESSION['error'] = "RUT o contraseña inválidos";
            header('Location: login.php');
            exit;
        }
    } catch (Exception $e) {
        // Manejo de errores de la base de datos
        $_SESSION['error'] = "Error en el sistema: " . $e->getMessage();
        header('Location: adios.php');
        exit;
    }
} else {
    // Si la solicitud no es POST, redirigimos al formulario de inicio de sesión
    header('Location: index.php');
    exit;
}
?>
