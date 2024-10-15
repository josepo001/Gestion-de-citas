function cancelarCita(citaId) {
    if (confirm("¿Estás seguro de que deseas cancelar esta cita?")) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "../Paciente/anular_hora.php", true); // Cambia la ruta si es necesario
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    alert("Cita cancelada exitosamente.");
                    location.reload(); // Recargar la página para reflejar los cambios
                } else {
                    alert("Error al cancelar la cita: " + response.error); // Cambiar 'message' por 'error'
                }
            }
        };

        xhr.send("cita_id=" + encodeURIComponent(citaId)); // Aquí estás enviando correctamente el ID
    }
}
