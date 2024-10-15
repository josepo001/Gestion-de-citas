<?php
session_start();

// Limpiar todas las variables de sesión
session_unset();

// Destruir la sesión
session_destroy();

// Redirigir a la página de inicio de sesión o a cualquier otra página después de cerrar sesión
header("Location: index.php");
exit();
?>
