<?php
defined('MOODLE_INTERNAL') || die();

$functions = array(
    'mod_vimeotracker_get_course_report' => array(
        'classname'   => 'mod_vimeotracker\external\course_report',
        'methodname'  => 'get_course_report',
        'classpath'   => 'mod/vimeotracker/classes/external/course_report.php',
        'description' => 'Obtiene el reporte completo de visualizacion de videos por curso',
        'type'        => 'read',
        'capabilities'=> 'mod/vimeotracker:viewreport',
    ),
    'mod_vimeotracker_update_watch_time' => array(
        'classname'   => 'mod_vimeotracker\external\watch_time',
        'methodname'  => 'update_watch_time',
        'classpath'   => 'mod/vimeotracker/classes/external/watch_time.php',
        'description' => 'Actualiza el progreso del estudiante en tiempo real via AJAX',
        'type'        => 'write',
        'capabilities'=> 'mod/vimeotracker:view',
    )
);
