#!/usr/bin/env python3
"""Borra todas las huellas almacenadas en un lector DY50."""
import argparse
import sys

from sensor_dy50 import ErrorSensor, SensorDY50


def main():
    parser = argparse.ArgumentParser(
        description="Borra de forma irreversible todas las huellas guardadas en un DY50."
    )
    parser.add_argument("--puerto", default="/dev/serial0", help="puerto serie del lector (predeterminado: /dev/serial0)")
    parser.add_argument("--baudios", type=int, default=57600, help="velocidad serie (predeterminada: 57600)")
    args = parser.parse_args()

    lector = None
    try:
        lector = SensorDY50.abrir(args.puerto, args.baudios)
        cantidad = lector.cantidad_guardadas()
        capacidad = lector.capacidad()
        print("Lector DY50 conectado: %s" % args.puerto)
        print("Huellas almacenadas: %s de %s." % (cantidad, capacidad))

        if cantidad == 0:
            print("La memoria ya está vacía.")
            return 0

        print("ATENCIÓN: el borrado es irreversible y eliminará todas las huellas del lector.")
        if input("Escribí VACIAR para confirmar: ").strip() != "VACIAR":
            print("Cancelado; no se modificó la memoria.")
            return 1

        lector.vaciar_memoria()
        restantes = lector.cantidad_guardadas()
        if restantes != 0:
            raise ErrorSensor(
                "el lector informó que todavía quedan %s huellas después del borrado" % restantes
            )
        print("Memoria vaciada correctamente; no quedan huellas almacenadas.")
        return 0
    except (ErrorSensor, OSError) as error:
        print("ERROR: %s" % error, file=sys.stderr)
        return 1
    finally:
        if lector is not None and getattr(lector, "con", None) is not None:
            lector.con.close()


if __name__ == "__main__":
    sys.exit(main())
