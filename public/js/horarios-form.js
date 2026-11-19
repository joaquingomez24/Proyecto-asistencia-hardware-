/* Formulario de docentes: agrega y quita filas de horarios (se envían como horarios[n][campo]). */
(function () {
    var contenedor = document.getElementById('contenedor-horarios');
    var molde = document.getElementById('plantilla-horario');
    var btnAgregar = document.getElementById('btn-agregar');
    if (!contenedor || !molde) { return; }

    var filas = [];
    try { filas = JSON.parse(contenedor.dataset.filas || '[]'); } catch (e) { filas = []; }
    if (!Array.isArray(filas)) { filas = Object.keys(filas).map(function (k) { return filas[k]; }); }

    var indice = 0;

    function agregar(datos) {
        datos = datos || {};
        var fila = molde.content.firstElementChild.cloneNode(true);
        var n = indice++;

        fila.querySelectorAll('[data-campo]').forEach(function (campo) {
            var nombre = campo.dataset.campo;
            campo.name = 'horarios[' + n + '][' + nombre + ']';
            if (datos[nombre] !== undefined && datos[nombre] !== null) {
                campo.value = datos[nombre];
            }
        });

        fila.querySelector('[data-quitar]').addEventListener('click', function () { fila.remove(); });
        contenedor.appendChild(fila);
    }

    (filas.length ? filas : [{}]).forEach(agregar);
    btnAgregar.addEventListener('click', function () { agregar(); });
})();
