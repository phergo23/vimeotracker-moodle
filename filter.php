<?php
defined('MOODLE_INTERNAL') || die();

class filter_vimeotracker extends moodle_text_filter {
    public function filter($text, array $options = array()) {
        global $PAGE, $DB, $USER;

        // Si no hay menciones a Vimeo, retornar el texto original rápido
        if (stripos($text, 'vimeo.com') === false) {
            return $text;
        }

        // Buscar todos los iFrames o enlaces de Vimeo en el texto
        if (preg_match_all('/vimeo\.com\/(?:video\/)?([0-9]+)/', $text, $matches)) {
            $vimeo_ids = array_unique($matches[1]);
            
            // Detectar de forma segura el ID del curso actual
            $courseid = 0;
            if (isset($options['context'])) {
                $coursecontext = $options['context']->get_course_context(false);
                if ($coursecontext) {
                    $courseid = $coursecontext->instanceid;
                }
            }

            // Inyectar el SDK de Vimeo de forma segura como HTML puro para evitar errores de Moodle
            $text = '<script src="https://player.vimeo.com/api/player.js"></script>' . $text;

            foreach ($vimeo_ids as $vimeo_id) {
                $last_position = 0;
                if (isloggedin()) {
                    $progress = $DB->get_record('filter_vimeotracker_time', array(
                        'userid' => $USER->id, 
                        'vimeo_id' => $vimeo_id,
                        'courseid' => $courseid
                    ), 'last_position');
                    if ($progress) {
                        $last_position = $progress->last_position;
                    }
                }

                // Inyectar el script que controla el reproductor de Vimeo
                $PAGE->requires->js_call_amd('filter_vimeotracker/vimeo_injector', 'attach', array(
                    'vimeoId' => $vimeo_id,
                    'lastPosition' => (float)$last_position,
                    'courseId' => (int)$courseid
                ));
            }
        }

        return $text;
    }
}
