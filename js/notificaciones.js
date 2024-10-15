function mostrarNotificaciones() {
    var popup = document.getElementById('notificaciones-popup');
    if (popup.style.display === "none" || popup.style.display === "") {
        popup.style.display = "block";

        // Marcar notificaciones como leídas
        var notificaciones = document.querySelectorAll('.notificacion');
        notificaciones.forEach(function(noti) {
            var notiId = noti.getAttribute('data-id');
            marcarComoLeida(notiId); // Marcar como leída en la base de datos
        });

        // Ocultar después de 10 segundos
        setTimeout(function() {
            popup.style.display = "none";
        }, 10000);
    } else {
        popup.style.display = "none";
    }
}

function marcarComoLeida(notiId) {
    // Aquí puedes hacer una llamada AJAX para actualizar la base de datos
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "marcar_leida.php", true); // Asegúrate de que esta ruta sea correcta
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
            // Aquí puedes manejar la respuesta si es necesario
            console.log("Notificación marcada como leída: " + notiId);
        }
    };
    xhr.send("id=" + notiId); // Enviar ID de notificación
}

function toggleVerMas() {
    const cancelButton = document.querySelector('.btn-cancel');
    const toggleButton = document.querySelector('.btn-toggle');

    // Mostrar o esconder el botón "Anular hora"
    if (cancelButton.style.display === "none" || cancelButton.style.display === "") {
        cancelButton.style.display = "inline-block";
        toggleButton.textContent = "Ver menos";
    } else {
        cancelButton.style.display = "none";
        toggleButton.textContent = "Ver más";
    }
}
