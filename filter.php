<?php
defined('MOODLE_INTERNAL') || die();

class filter_vimeotracker extends moodle_text_filter {
    public function filter($text, array $options = array()) {
        global $PAGE, $DB, $USER;

        // Si no hay menciones a Vimeo, retornar el texto tal cual de inmediato para no consumir recursos
        if (stripos($text, 'vimeo.com') === false) {
            return $text;
        }

        // Buscar todos los iFrames o enlaces de Vimeo insertados por profesores en el texto
        if (preg_match_all('/vimeo\.com\/(?:video\/)?([0-9]+)/', $text, $matches)) {
            $vimeo_ids = array_unique($matches[1]);
            $courseid = isset($options['context']) ? $options['context']->get_course_context(false)->instanceid : 0;

            static $sdk_loaded = false;
            if (!$sdk_loaded) {
                $PAGE->requires->js_by_url('https://player.vimeo.com/api/player.js', true);
                $sdk_loaded = true;
            }

            foreach ($vimeo_ids as $vimeo_id) {
                // Consultar si este usuario específico ya tiene progreso previo en este video
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

                // Inyectar de manera segura el código Javascript AMD que amarra y controla el video de forma transparente
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
