/* Pantalla del docente: elegir materia -> pedir lectura al DY50 -> mostrar resultado -> cerrar sesión. */
(function () {
    var raiz = document.getElementById('panel');
    var urlIniciar = raiz.dataset.urlIniciar;
    var urlEstado = raiz.dataset.urlEstado;
    var urlEnLinea = raiz.dataset.urlEnLinea;
    var urlLogin = raiz.dataset.loginUrl;

    var SEGUNDOS_RESULTADO = 5;     // cuánto se muestra el resultado antes de volver al login
    var SEGUNDOS_OCIO = 120;        // sin tocar nada, se cierra la sesión

    var estadoLector = document.getElementById('estado-lector');
    var barra = document.getElementById('barra-accion');
    var textoSeleccion = document.getElementById('seleccion-texto');
    var btnRegistrar = document.getElementById('btn-registrar');

    var sup = document.getElementById('superposicion');
    var supIcono = document.getElementById('sup-icono');
    var supTitulo = document.getElementById('sup-titulo');
    var supTexto = document.getElementById('sup-texto');
    var supCuenta = document.getElementById('sup-cuenta');
    var btnCancelar = document.getElementById('sup-cancelar');
    var btnReintentar = document.getElementById('sup-reintentar');
    var btnCerrar = document.getElementById('sup-cerrar');

    var lectorEnLinea = false;
    var horarioId = null;
    var comandoId = null;
    var detenerSeguimiento = null;
    var temporizadorOcio = null;

    // ── Cierre por inactividad ───────────────────────────────────────────────
    function reiniciarOcio() {
        clearTimeout(temporizadorOcio);
        if (comandoId !== null) { return; }   // hay una lectura en curso
        temporizadorOcio = setTimeout(function () {
            document.getElementById('form-logout').submit();
        }, SEGUNDOS_OCIO * 1000);
    }
    ['click', 'touchstart', 'keydown'].forEach(function (e) {
        document.addEventListener(e, reiniciarOcio, { passive: true });
    });
    reiniciarOcio();

    // ── Estado del lector ────────────────────────────────────────────────────
    Lector.vigilarConexion(urlEnLinea, function (enLinea) {
        lectorEnLinea = enLinea;
        estadoLector.textContent = enLinea
            ? 'Lector conectado'
            : 'Lector desconectado: verificá que la Raspberry y su programa estén encendidos.';
        estadoLector.className = 'estado-lector ' + (enLinea ? 'ok' : 'error');
        actualizarBoton();
    });

    // ── Selección de materia ─────────────────────────────────────────────────
    function actualizarBoton() {
        btnRegistrar.disabled = !(horarioId && lectorEnLinea);
    }

    document.querySelectorAll('.horario').forEach(function (tarjeta) {
        tarjeta.addEventListener('click', function () {
            document.querySelectorAll('.horario').forEach(function (t) { t.classList.remove('seleccionado'); });
            tarjeta.classList.add('seleccionado');
            horarioId = tarjeta.dataset.id;
            textoSeleccion.textContent = tarjeta.dataset.nombre;
            barra.hidden = false;
            actualizarBoton();
        });
    });

    // ── Ventana de lectura ───────────────────────────────────────────────────
    function mostrar(estado, titulo, texto) {
        sup.hidden = false;
        supTitulo.textContent = titulo;
        supTexto.textContent = texto;
        supCuenta.textContent = '';
        supIcono.className = 'sup-icono ' + estado;
        supIcono.innerHTML = estado === 'espera' ? '<span class="spinner"></span>'
            : estado === 'exito' ? '✔' : '✖';
        btnCancelar.hidden = estado !== 'espera';
        btnReintentar.hidden = estado !== 'error';
        btnCerrar.hidden = estado !== 'error';
    }

    function cerrarVentana() {
        sup.hidden = true;
        comandoId = null;
        if (detenerSeguimiento) { detenerSeguimiento(); detenerSeguimiento = null; }
        reiniciarOcio();
    }

    function volverAlLogin(segundos) {
        var restantes = segundos;
        supCuenta.textContent = 'Cerrando sesión en ' + restantes + ' s…';
        var reloj = setInterval(function () {
            restantes--;
            if (restantes <= 0) {
                clearInterval(reloj);
                window.location.href = urlLogin;
            } else {
                supCuenta.textContent = 'Cerrando sesión en ' + restantes + ' s…';
            }
        }, 1000);
    }

    async function iniciar() {
        if (!horarioId) { return; }

        mostrar('espera', 'Preparando el lector', 'Un momento…');
        clearTimeout(temporizadorOcio);

        var r;
        try {
            r = await Lector.enviar(urlIniciar, 'POST', { horario_id: Number(horarioId) });
        } catch (e) {
            return mostrar('error', 'No se pudo iniciar', 'No hay conexión con el servidor.');
        }

        if (!r.ok) {
            var mensaje = (r.datos && (r.datos.mensaje || r.datos.message)) || 'No se pudo iniciar el registro.';
            return mostrar('error', 'No se pudo iniciar', mensaje);
        }

        comandoId = r.datos.comando_id;
        mostrar('espera', 'Apoyá tu dedo en el lector', 'Esperando al lector…');

        detenerSeguimiento = Lector.seguir(
            urlEstado + '/' + comandoId,
            function (datos) {
                if (datos.progreso) { supTexto.textContent = datos.progreso; }
            },
            function (datos) {
                detenerSeguimiento = null;
                comandoId = null;

                if (datos.exito) {
                    mostrar('exito', '¡Asistencia registrada!', datos.mensaje || '');
                    volverAlLogin(SEGUNDOS_RESULTADO);   // el servidor ya cerró la sesión
                } else {
                    mostrar('error', 'No se registró la asistencia', datos.mensaje || 'Ocurrió un error.');
                }
            },
            1000
        );
    }

    btnRegistrar.addEventListener('click', iniciar);
    btnReintentar.addEventListener('click', iniciar);
    btnCerrar.addEventListener('click', cerrarVentana);

    btnCancelar.addEventListener('click', function () {
        if (comandoId !== null) {
            Lector.enviar(urlEstado + '/' + comandoId, 'DELETE').catch(function () {});
        }
        cerrarVentana();
    });
})();
