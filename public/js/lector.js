/*
 * Utilidades compartidas para hablar con el lector DY50 a través de Laravel.
 * El navegador nunca habla con la Raspberry directamente: pide una acción a Laravel,
 * el agente de la Raspberry la ejecuta, y acá se consulta el avance.
 */
window.Lector = (function () {
    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    /** fetch con JSON y CSRF. Devuelve { ok, status, datos }. */
    async function enviar(url, metodo, cuerpo) {
        var opciones = {
            method: metodo || 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf()
            },
            credentials: 'same-origin',
            cache: 'no-store'
        };
        if (cuerpo) { opciones.body = JSON.stringify(cuerpo); }

        var respuesta = await fetch(url, opciones);
        var datos = null;
        try { datos = await respuesta.json(); } catch (e) { /* respuesta sin JSON */ }

        return { ok: respuesta.ok, status: respuesta.status, datos: datos };
    }

    /**
     * Consulta el estado de un comando cada `ms` hasta que termina.
     * Llama onProgreso(datos) mientras sigue y onFin(datos) una sola vez al terminar.
     * Devuelve una función para dejar de consultar.
     */
    function seguir(urlEstado, onProgreso, onFin, ms) {
        var activo = true;
        var fallos = 0;

        function terminar(datos) {
            activo = false;
            onFin(datos);
        }

        async function ciclo() {
            if (!activo) { return; }

            try {
                var r = await enviar(urlEstado, 'GET');

                if (r.status === 401 || r.status === 419) {
                    return terminar({ exito: false, mensaje: 'La sesión se cerró. Volvé a iniciar sesión.' });
                }

                if (r.ok && r.datos) {
                    fallos = 0;
                    if (r.datos.terminado) { return terminar(r.datos); }
                    onProgreso(r.datos);
                } else {
                    fallos++;
                }
            } catch (e) {
                fallos++;
            }

            if (fallos >= 5) {
                return terminar({ exito: false, mensaje: 'Se perdió la conexión con el servidor.' });
            }

            if (activo) { setTimeout(ciclo, ms || 1000); }
        }

        ciclo();

        return function detener() { activo = false; };
    }

    /** Avisa periódicamente si el agente de la Raspberry está conectado. */
    function vigilarConexion(urlEnLinea, alCambiar, ms) {
        async function consultar() {
            try {
                var r = await enviar(urlEnLinea, 'GET');
                alCambiar(r.ok && r.datos ? !!r.datos.en_linea : false);
            } catch (e) {
                alCambiar(false);
            }
            setTimeout(consultar, ms || 5000);
        }
        consultar();
    }

    return { enviar: enviar, seguir: seguir, vigilarConexion: vigilarConexion };
})();
