<?php
// Iniciar sesión y cargar la conexión a la base de datos
session_start();
include('DB.php'); // Asegúrate de incluir tu archivo DB.php

// Obtener la conexión
$conn = getDB(); // Llama a la función para obtener la conexión a la base de datos

// Verificar si se ha pasado un ID a través de la URL
if (!isset($_GET['id'])) {
    die("Error: ID no proporcionado.");
}

$doctorId = $_GET['id'];

// Preparar la consulta SQL para obtener los datos del doctor y la especialidad
$sql = "SELECT d.id, u.nombre AS nombre_usuario, d.consultorio, d.horario_inicio, d.horario_fin, d.dias_atencion, d.numero_colegiado, e.descripcion AS especialidad, d.id_especialidad
        FROM doctores d
        JOIN usuarios u ON d.id_usuario = u.id
        JOIN especialidades e ON d.id_especialidad = e.id
        WHERE d.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $doctorId); // Cambia a 'i' si el ID es un entero
$stmt->execute();
$result = $stmt->get_result();

// Verificar si se obtuvo algún resultado
if ($result->num_rows > 0) {
    $doctor = $result->fetch_assoc();
} else {
    die("Error: No se encontró el doctor.");
}

// Obtener todas las especialidades para el menú desplegable
$especialidades_sql = "SELECT id, descripcion FROM especialidades";
$especialidades_result = $conn->query($especialidades_sql);
$especialidades = [];
if ($especialidades_result->num_rows > 0) {
    while ($row = $especialidades_result->fetch_assoc()) {
        $especialidades[] = $row;
    }
}

// Si el formulario ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capturar y limpiar los datos del formulario
    $nombre = htmlspecialchars($_POST['nombre']);
    $apellido = htmlspecialchars($_POST['apellido']);
    $especialidad = $_POST['especialidad']; // Se almacena el ID de la especialidad
    $consultorio = htmlspecialchars($_POST['consultorio']);
    $horario_inicio = htmlspecialchars($_POST['horario_inicio']);
    $horario_fin = htmlspecialchars($_POST['horario_fin']);
    $dias_atencion = htmlspecialchars($_POST['dias_atencion']);
    $numero_colegiado = htmlspecialchars($_POST['numero_colegiado']);
    
    // Preparar la consulta para actualizar los datos del doctor
    $update_sql = "UPDATE doctores SET 
                    nombre = ?, 
                    apellido = ?, 
                    id_especialidad = ?, 
                    consultorio = ?, 
                    horario_inicio = ?, 
                    horario_fin = ?, 
                    dias_atencion = ?, 
                    numero_colegiado = ? 
                   WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('ssssssssi', $nombre, $apellido, $especialidad, $consultorio, $horario_inicio, $horario_fin, $dias_atencion, $numero_colegiado, $doctorId);
    
    // Ejecutar la consulta de actualización
    if ($update_stmt->execute()) {
        echo "Doctor actualizado correctamente.";
    } else {
        echo "Error al actualizar los datos del doctor.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Doctor</title>
    <link rel="stylesheet" href="../css/editarDoc.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <h1>Editar Doctor</h1>
    <form method="post" class="profile-form">
    <div class="form-group">
        <label>Nombre:</label>
        <input type="text" name="nombre" id="nombre" class="form-control" value="<?php echo htmlspecialchars($doctor['nombre_usuario']); ?>" required>
    </div>

    <div class="form-group">
        <label>Especialidad:</label>
        <select name="especialidad" id="especialidad" class="form-select" required>
            <option value="">Seleccione una especialidad</option>
            <?php foreach ($especialidades as $especialidad): ?>
                <option value="<?php echo $especialidad['id']; ?>" <?php echo ($especialidad['id'] == $doctor['id_especialidad']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($especialidad['descripcion']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Consultorio:</label>
        <input type="text" name="consultorio" id="consultorio" class="form-control" value="<?php echo htmlspecialchars($doctor['consultorio']); ?>" required>
    </div>

    <div class="form-group">
        <label>Horario Inicio:</label>
        <input type="time" name="horario_inicio" id="horario_inicio" class="form-control" value="<?php echo htmlspecialchars($doctor['horario_inicio']); ?>" required>
    </div>

    <div class="form-group">
        <label>Horario Fin:</label>
        <input type="time" name="horario_fin" id="horario_fin" class="form-control" value="<?php echo htmlspecialchars($doctor['horario_fin']); ?>" required>
    </div>

    <div class="form-group">
        <label>Días de Atención:</label>
        <input type="text" name="dias_atencion" id="dias_atencion" class="form-control" value="<?php echo htmlspecialchars($doctor['dias_atencion']); ?>" required>
    </div>

    <div class="form-group">
        <label>Número Colegiado:</label>
        <input type="text" name="numero_colegiado" id="numero_colegiado" class="form-control" value="<?php echo htmlspecialchars($doctor['numero_colegiado']); ?>" required>
    </div>

    <div class="button-container">
        <button type="submit" class="btn btn-actualizar">Actualizar</button>
        <a href="Doctores.php" class="btn btn-cancelar">Cancelar</a>
    </div>

</form>


</body>
</html>
