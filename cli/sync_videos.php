<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/vimeotracker/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

global $DB;

echo "=== INICIANDO MIGRACIÓN MASIVA DE VIDEOS VIMEO A VIMEOTRACKER ===\n";

// 1. Buscar páginas, etiquetas o secciones que contengan enlaces a vimeo.com o player.vimeo.com
$items = $DB->get_recordset_select('page', "intro LIKE '%vimeo.com%' OR content LIKE '%vimeo.com%'");
$count = 0;

foreach ($items as $item) {
    // Buscar patrones numéricos de Vimeo (IDs de video)
    if (preg_match('/vimeo\.com\/(?:video\/)?([0-9]+)/', $item->content, $matches) || 
        preg_match('/vimeo\.com\/(?:video\/)?([0-9]+)/', $item->intro, $matches)) {
        
        $vimeo_id = $matches[1];
        
        // Verificar en qué curso está esta página para clonar la actividad ahí
        $cm = get_coursemodule_from_instance('page', $item->id);
        if (!$cm) continue;

        // Verificar si ya creamos un Vimeo Tracker idéntico en esta sección para no duplicar
        $exists = $DB->record_exists('vimeotracker', array('course' => $cm->course, 'vimeo_id' => $vimeo_id));
        if ($exists) continue;

        // Crear la nueva instancia de Vimeo Tracker automáticamente
        $data = new stdClass();
        $data->course = $cm->course;
        $data->name = "Vimeo Tracker: " . $item->name;
        $data->intro = $item->intro;
        $data->introformat = 1;
        $data->vimeo_id = $vimeo_id;
        $data->resume_enabled = 1;
        $data->track_time = 1;
        $data->min_watch_percent = 80;
        $data->timecreated = time();
        $data->timemodified = time();

        $vimeotracker_id = $DB->insert_record('vimeotracker', $data);

        // Inyectarlo en la estructura del curso del alumno
        $module = $DB->get_record('modules', array('name' => 'vimeotracker'));
        
        $newcm = new stdClass();
        $newcm->course = $cm->course;
        $newcm->module = $module->id;
        $newcm->instance = $vimeotracker_id;
        $newcm->section = $cm->section;
        $newcm->visible = 1;
        
        $newcm->id = add_course_module($newcm);
        course_add_cm_to_section($newcm->course, $newcm->id, $newcm->section);
        
        echo "✓ Migrado con éxito: '{$item->name}' en Curso ID {$cm->course} (Vimeo ID: {$vimeo_id})\n";
        $count++;
    }
}

$items->close();
rebuild_course_cache(0, true);
echo "=== MIGRACIÓN FINALIZADA. Total de actividades creadas: $count ===\n";
