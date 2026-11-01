<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Umbrales de las alertas automáticas del recorrido del partido (TIZ-27)
    |--------------------------------------------------------------------------
    */

    'thresholds' => [
        // Requisitos de habilitación por vencer (días de anticipación)
        'requirement_expiring_days' => 30,

        // Cierre de inscripción a competencia con jugadores sin habilitar (días de anticipación)
        'registration_closing_days' => 7,

        // Convocatoria de partido sin publicar (horas de anticipación)
        'callup_pending_hours' => 48,

        // Ausencias consecutivas sin justificar para alerta de abandono
        'consecutive_absences' => 3,

        // Caída de asistencia: asistencia del último mes menor a este porcentaje
        'attendance_drop_current_max' => 50.0,

        // Caída de asistencia: promedio histórico previo mayor a este porcentaje
        'attendance_drop_prior_min' => 75.0,
    ],

];
