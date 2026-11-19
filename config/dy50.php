<?php

return [
    // Definí DY50_TOKEN en .env. Nunca escribas el valor real del token en este archivo.
    'token' => env('DY50_TOKEN', ''),

    // Nombre con el que la Raspberry se identifica ante Laravel (--dispositivo en el agente).
    'dispositivo' => env('DY50_DISPOSITIVO', 'raspberry-1'),

    // Cantidad de huellas que se asignan como máximo. Posiciones 1..capacidad.
    // Límite de posiciones asignables; ajustalo a la capacidad real del lector.
    'capacidad' => (int) env('DY50_CAPACIDAD', 150),

    // Si la Raspberry no consultó en estos segundos, se muestra "lector desconectado".
    'segundos_en_linea' => 15,

    // Segundos que tiene el agente para tomar un comando antes de que venza.
    'espera_inicio' => 35,

    // Segundos que espera el lector a que se apoye el dedo.
    'timeout_verificar' => 20,
    'timeout_enrolar' => 90, // Plazo total de la orden; cada apoyo del dedo tiene un límite propio de 15 s.
];
