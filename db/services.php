<?php
defined('MOODLE_INTERNAL') || die();

$functions = array(
    'filter_vimeotracker_save_progress' => array(
        'classname'   => 'filter_vimeotracker\external\save_progress',
        'methodname'  => 'save_progress',
        'classpath'   => 'filter/vimeotracker/classes/external/save_progress.php',
        'description' => 'Guarda el progreso de reproduccion de cualquier video detectado por el filtro',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'moodle/course:view'
    )
);
