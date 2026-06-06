<?php
namespace filter_vimeotracker\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use stdClass;

class save_progress extends external_api {

    public static function save_progress_parameters() {
        return new external_function_parameters(array(
            'vimeo_id' => new external_value(PARAM_ALPHANUMEXT, 'ID del video Vimeo'),
            'current_time' => new external_value(PARAM_RAW, 'Posicion actual en segundos'),
            'percentage' => new external_value(PARAM_INT, 'Porcentaje visto'),
            'courseid' => new external_value(PARAM_INT, 'ID del curso actual')
        ));
    }

    public static function save_progress($vimeo_id, $current_time, $percentage, $courseid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::save_progress_parameters(), array(
            'vimeo_id' => $vimeo_id,
            'current_time' => $current_time,
            'percentage' => $percentage,
            'courseid' => $courseid
        ));

        if (!isloggedin() || isguestuser()) {
            return array('status' => false);
        }

        $record = $DB->get_record('filter_vimeotracker_time', array(
            'userid' => $USER->id, 
            'vimeo_id' => $params['vimeo_id'],
            'courseid' => $params['courseid']
        ));

        $data = new stdClass();
        $data->userid = $USER->id;
        $data->vimeo_id = $params['vimeo_id'];
        $data->courseid = $params['courseid'];
        $data->last_position = (float)$params['current_time'];
        $data->percentage_watched = (int)$params['percentage'];
        $data->timemodified = time();
        $data->is_completed = ($data->percentage_watched >= 80) ? 1 : 0;

        if ($record) {
            $data->id = $record->id;
            $DB->update_record('filter_vimeotracker_time', $data);
        } else {
            $DB->insert_record('filter_vimeotracker_time', $data);
        }

        return array('status' => true);
    }

    public static function save_progress_returns() {
        return new external_single_structure(array(
            'status' => new external_value(PARAM_BOOL, 'Estado del guardado')
        ));
    }
}
