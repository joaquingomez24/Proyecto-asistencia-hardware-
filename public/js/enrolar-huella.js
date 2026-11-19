/* Ficha del docente (secretario): ordena al lector registrar una huella nueva y muestra el avance. */
(function () {
    var panel = document.getElementById('panel-huellas');
    if (!panel) { return; }

    var urlEnrolar = panel.dataset.urlEnrolar;
    var urlEstado = panel.dataset.urlEstado;
    var urlEnLinea = panel.dataset.urlEnLinea;

    var btnEnrolar = document.getElementById('btn-enrolar');
    var campoDedo = document.getElementById('dedo');
    var textoLector = document.getElementById('estado-lector-enrolar');

    var modal = document.getElementById('modal-lector');
    var icono = document.getElementById('modal-lector-icono');
    var titulo = document.getElementById('modal-lector-titulo');
    var texto = document.getElementById('modal-lector-texto');
    var btnCancelar = document.getElementById('modal-lector-cancelar');
    var btnCerrar = document.getElementById('modal-lector-cerrar');

    var lectorEnLinea = false;
    var comandoId = null;
    var detener = null;
    var huboExito = false;

    Lector.vigilarConexion(urlEnLinea, function (enLinea) {
        lectorEnLinea = enLinea;
        textoLector.textContent = enLinea
            ? 'Lector conectado. El registro se hace en el lector de la Raspberry: pedile al docente que esté ahí.'
            : 'Lector desconectado: no se puede registrar una huella ahora.';
        btnEnrolar.disabled = !enLinea || comandoId !== null;
    });

    function mostrar(estado, tituloTexto, cuerpo) {
        modal.hidden = false;
        titulo.textContent = tituloTexto;
        texto.textContent = cuerpo;
        icono.className = 'modal-lector-icono ' + estado;
        icono.innerHTML = estado === 'espera' ? '<span class="spinner"></span>' : (estado === 'exito' ? '✔' : '✖');
        btnCancelar.hidden = estado !== 'espera';
        btnCerrar.hidden = estado === 'espera';
    }

    btnEnrolar.addEventListener('click', async function () {
        btnEnrolar.disabled = true;
        huboExito = false;
        mostrar('espera', 'Registrando huella', 'Preparando el lector…');

        var r;
        try {
            r = await Lector.enviar(urlEnrolar, 'POST', { dedo: campoDedo.value.trim() || null });
        } catch (e) {
            btnEnrolar.disabled = false;
            return mostrar('error', 'No se pudo iniciar', 'No hay conexión con el servidor.');
        }

        if (!r.ok) {
            btnEnrolar.disabled = false;
            var detalle = (r.datos && (r.datos.mensaje || r.datos.message)) || 'No se pudo iniciar el registro.';
            return mostrar('error', 'No se pudo iniciar', detalle);
        }

        comandoId = r.datos.comando_id;

        detener = Lector.seguir(
            urlEstado + '/' + comandoId,
            function (datos) { if (datos.progreso) { texto.textContent = datos.progreso; } },
            function (datos) {
                detener = null;
                comandoId = null;
                huboExito = !!datos.exito;
                mostrar(datos.exito ? 'exito' : 'error',
                    datos.exito ? 'Huella registrada' : 'No se pudo registrar',
                    datos.mensaje || '');
            },
            1000
        );
    });

    btnCancelar.addEventListener('click', function () {
        if (comandoId !== null) {
            Lector.enviar(urlEstado + '/' + comandoId, 'DELETE').catch(function () {});
        }
        if (detener) { detener(); detener = null; }
        comandoId = null;
        modal.hidden = true;
        btnEnrolar.disabled = !lectorEnLinea;
    });

    btnCerrar.addEventListener('click', function () {
        modal.hidden = true;
        if (huboExito) { window.location.reload(); }   // recarga para listar la huella nueva
        btnEnrolar.disabled = !lectorEnLinea;
    });
})();
