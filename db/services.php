<?php
defined('MOODLE_INTERNAL') || die();

$functions = array(
    'filter_vimeotracker_save_progress' => array(
        'classname'   => 'filter_vimeotracker\external\save_progress',
        'methodname'  => 'execute',
        'description' => 'Guarda de forma global el progreso del video en el servidor',
        'type'        => 'write',
        'ajax'        => true, // Permitir ejecucion directa por JS sin tokens manuales
        'loginrequired' => true,
    )
);
