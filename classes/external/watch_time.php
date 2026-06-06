<?php
namespace mod_vimeotracker\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use stdClass;

class watch_time extends external_api {

    public static function update_watch_time_parameters() {
        return new external_function_parameters(array(
            'vimeotracker_id' => new external_value(PARAM_INT, 'ID de la actividad'),
            'current_time' => new external_value(PARAM_RAW, 'Posicion actual'),
            'total_time_watched' => new external_value(PARAM_RAW, 'Tiempo acumulado'),
            'percentage_watched' => new external_value(PARAM_INT, 'Porcentaje'),
            'duration' => new external_value(PARAM_RAW, 'Duracion total del video'),
            'status' => new external_value(PARAM_ALPHAEXT, 'Estado de la visualizacion')
        ));
    }

    public static function update_watch_time($vimeotracker_id, $current_time, $total_time_watched, $percentage_watched, $duration, $status) {
        global $DB, $USER;

        $params = self::validate_parameters(self::update_watch_time_parameters(), array(
            'vimeotracker_id' => $vimeotracker_id,
            'current_time' => $current_time,
            'total_time_watched' => $total_time_watched,
            'percentage_watched' => $percentage_watched,
            'duration' => $duration,
            'status' => $status
        ));

        $vimeotracker = $DB->get_record('vimeotracker', array('id' => $params['vimeotracker_id']), '*', MUST_EXIST);
        $cm = $DB->get_record_sql("SELECT cm.id FROM {course_modules} cm JOIN {modules} m ON m.id = cm.module WHERE m.name = 'vimeotracker' AND cm.instance = ?", array($vimeotracker->id), MUST_EXIST);
        
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        $record = $DB->get_record('vimeotracker_watch_time', array('vimeotracker_id' => $vimeotracker->id, 'userid' => $USER->id));

        $data = new stdClass();
        $data->vimeotracker_id = $vimeotracker->id;
        $data->userid = $USER->id;
        $data->last_position = (float)$params['current_time'];
        $data->total_time_watched = (float)$params['total_time_watched'];
        $data->percentage_watched = (int)$params['percentage_watched'];
        $data->video_duration = (float)$params['duration'];
        $data->status = $params['status'];
        $data->timemodified = time();
        $data->is_completed = ($data->percentage_watched >= $vimeotracker->min_watch_percent) ? 1 : 0;

        if ($record) {
            $data->id = $record->id;
            $DB->update_record('vimeotracker_watch_time', $data);
        } else {
            $data->timecreated = time();
            $DB->insert_record('vimeotracker_watch_time', $data);
        }

        return array('status' => true);
    }

    public static function update_watch_time_returns() {
        return new external_single_structure(array(
            'status' => new external_value(PARAM_BOOL, 'Resultado de la operacion')
        ));
    }
}
