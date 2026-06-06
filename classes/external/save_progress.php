<?php
namespace filter_vimeotracker\external;

defined('MOODLE_INTERNAL') || die();

use external_api;
use external_function_parameters;
use external_value;
use complementario;

class save_progress extends external_api {
    
    public static function execute_parameters() {
        return new external_function_parameters(array(
            'vimeoId' => new external_value(PARAM_ALPHANUMEXT, 'ID del video de Vimeo'),
            'courseId' => new external_value(PARAM_INT, 'ID del curso de Moodle'),
            'seconds' => new external_value(PARAM_FLOAT, 'Segundo actual de reproduccion')
        ));
    }

    public static function execute($vimeoId, $courseId, $seconds) {
        global $DB, $USER;

        self::validate_parameters(self::execute_parameters(), array(
            'vimeoId' => $vimeoId,
            'courseId' => $courseId,
            'seconds' => $seconds
        ));

        if (!isloggedin() || isguestuser()) {
            return array('status' => 'error', 'message' => 'Usuario no autenticado');
        }

        $userid = $USER->id;
        
        // Buscar si ya existe un registro previo de este alumno con este video en este curso
        $record = $DB->get_record('filter_vimeotracker_time', array(
            'userid' => $userid,
            'vimeo_id' => $vimeoId,
            'courseid' => $courseId
        ));

        if ($record) {
            // Si el alumno adelanta o ve mas, actualizamos el servidor
            $record->last_position = $seconds;
            $record->timemodified = time();
            $DB->update_record('filter_vimeotracker_time', $record);
        } else {
            // Si es la primera vez que abre el video, creamos el registro
            $newrecord = new \stdClass();
            $newrecord->userid = $userid;
            $newrecord->vimeo_id = $vimeoId;
            $newrecord->courseid = $courseId;
            $newrecord->last_position = $seconds;
            $newrecord->timemodified = time();
            $DB->insert_record('filter_vimeotracker_time', $newrecord);
        }

        return array('status' => 'success');
    }

    public static function execute_returns() {
        return new external_function_parameters(array(
            'status' => new external_value(PARAM_ALPHANUM, 'success o error'),
            'message' => new external_value(PARAM_RAW, 'Mensaje opcional', VALUE_OPTIONAL)
        ));
    }
}
