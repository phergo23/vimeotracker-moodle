<?php
defined('MOODLE_INTERNAL') || die();

class filter_vimeotracker extends moodle_text_filter {
    public function filter($text, array $options = array()) {
        global $PAGE;

        // Si no hay rastro de vimeo, saltar de inmediato
        if (stripos($text, 'vimeo.com') === false) {
            return $text;
        }

        // Buscar IDs de vimeo incrustados
        if (preg_match_all('/vimeo\.com\/(?:video\/)?([0-9]+)/', $text, $matches)) {
            $vimeo_ids = array_unique($matches[1]);
            
            $courseid = 0;
            if (isset($options['context'])) {
                $coursecontext = $options['context']->get_course_context(false);
                if ($coursecontext) {
                    $courseid = $coursecontext->instanceid;
                }
            }

            // Forzar la carga del script oficial de Vimeo en el navegador
            $text = '<script src="https://player.vimeo.com/api/player.js"></script>' . $text;

            foreach ($vimeo_ids as $vimeo_id) {
                // Llamar al inyector JavaScript
                $PAGE->requires->js_call_amd('filter_vimeotracker/vimeo_injector', 'attach', array(
                    'vimeoId' => $vimeo_id,
                    'courseId' => (int)$courseid
                ));
            }
        }

        return $text;
    }
}
