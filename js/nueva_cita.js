function validarFormulario() {
    const doctor = document.getElementById('doctor');
    const fecha = document.getElementById('fecha');
    const hora = document.querySelector('input[name="hora"]:checked'); // Obtener el radio seleccionado
    const motivo = document.getElementById('motivo');

    let isValid = true;

    // Verificar cada campo
    [doctor, fecha, hora, motivo].forEach(input => {
        if (!input || (input === hora && !input.value)) {
            input.classList.add('error'); // Agregar clase de error
            isValid = false;
        } else {
            input.classList.remove('error'); // Quitar clase de error si está lleno
        }
    });

    if (!isValid) {
        alert('Por favor complete todos los campos');
        return false;
    }

    return confirm('¿Está seguro de agendar esta cita?');
}

document.getElementById('especialidad').addEventListener('change', function() {
    const doctorSelect = document.getElementById('doctor');
    const fechaInput = document.getElementById('fecha');
    const especialidadId = this.value;
    const doctorInfo = document.getElementById('doctorInfo');
    
    doctorSelect.disabled = true;
    fechaInput.disabled = true;
    doctorInfo.innerHTML = '';
    doctorInfo.classList.remove('visible');
    
    if (especialidadId) {
        console.log('Especialidad seleccionada:', especialidadId); // Depuración
        fetch(`get_doctores.php?especialidad=${especialidadId}`)
            .then(response => response.json())
            .then(doctores => {
                console.log('Doctores recibidos:', doctores); // Depuración
                doctorSelect.innerHTML = '<option value="">Seleccione un doctor</option>';
                doctores.forEach(doctor => {
                    doctorSelect.innerHTML += `
                        <option value="${doctor.id}">
                            Dr. ${doctor.nombre} ${doctor.apellido}
                        </option>`;
                });
                doctorSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error al cargar doctores:', error);
                alert('Error al cargar doctores');
            });
    }
});

document.getElementById('doctor').addEventListener('change', function() {
    const doctorId = this.value;
    const fechaInput = document.getElementById('fecha');
    
    if (doctorId) {
        console.log('Doctor seleccionado:', doctorId); // Depuración
        fetch(`get_doctor_info.php?doctor=${doctorId}`)
            .then(response => {
                console.log('Respuesta recibida:', response); // Depuración
                return response.json();
            })
            .then(data => {
                console.log('Datos del doctor:', data); // Depuración
                if (data.success) {
                    const info = `
                        <p><strong>Nombre:</strong> ${data.nombre}</p>
                        <p><strong>Especialidad:</strong> ${data.especialidad}</p>
                        <p><strong>Consultorio:</strong> ${data.consultorio}</p>
                        <p><strong>Horario:</strong> ${data.horario}</p>
                        <p><strong>Días de atención:</strong> ${data.dias_atencion}</p>`;
                    document.getElementById('doctorInfo').innerHTML = info;
                    document.getElementById('doctorInfo').classList.add('visible');
                    fechaInput.disabled = false;
                } else {
                    alert(data.error);
                }
            })
            .catch(error => {
                console.error('Error al cargar información del doctor:', error);
                alert('Error al cargar información del doctor');
            });
    } else {
        document.getElementById('doctorInfo').innerHTML = '';
        document.getElementById('doctorInfo').classList.remove('visible');
        fechaInput.disabled = true;
    }
});

document.getElementById('fecha').addEventListener('change', function() {
    const doctorId = document.getElementById('doctor').value;
    const fecha = this.value;
    const horariosDisponibles = document.getElementById('horariosDisponibles');
    
    if (doctorId && fecha) {
        fetch(`get_horarios.php?doctor=${doctorId}&fecha=${fecha}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(horarios => {
                horariosDisponibles.innerHTML = '';
                if (horarios.length > 0) {
                    horarios.forEach(slot => {
                        const { hora, disponible } = slot;
                        horariosDisponibles.innerHTML += `
                            <div class="horario">
                                <input type="radio" id="hora-${hora}" name="hora" value="${hora}" ${disponible ? '' : 'disabled'}>
                                <label for="hora-${hora}">${hora} ${disponible ? '' : '(No disponible)'}</label>
                            </div>`;
                    });
                } else {
                    horariosDisponibles.innerHTML = '<p>No hay horarios disponibles para esta fecha.</p>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar horarios disponibles');
            });
    }
});

// Agregar validación al formulario
document.getElementById('miFormulario').addEventListener('submit', function(event) {
    if (!validarFormulario()) {
        event.preventDefault(); // Evitar el envío del formulario si no es válido
    }
});
