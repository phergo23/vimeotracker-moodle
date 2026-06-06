<?php
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Crear un interruptor de Sí/No (Checkbox) en la administración del filtro
    $settings->add(new admin_setting_configcheckbox(
        'filter_vimeotracker/activar_antitrampa', // Nombre de la variable interna
        'Activar Sistema Anti-Trampas',          // Título en la pantalla
        'Si se activa, el video se pausará y regresará a cero automáticamente si el alumno cambia de pestaña o minimiza el navegador.', // Descripción corta
        0 // Desactivado por defecto para que entres con cautela con tu jefe
    ));
}
