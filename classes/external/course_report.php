<?php
namespace mod_vimeotracker\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;

class course_report extends external_api {

    public static function get_course_report_parameters() {
        return new external_function_parameters(array(
            'courseid' => new external_value(PARAM_INT, 'ID del curso Moodle')
        ));
    }

    public static function get_course_report($courseid) {
        global $DB;

        $params = self::validate_parameters(self::get_course_report_parameters(), array(
            'courseid' => $courseid
        ));

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);

        // Obtener todas las instancias de Vimeo Tracker en este curso
        $videos = $DB->get_records('vimeotracker', array('course' => $params['courseid']));
        
        // Obtener todos los alumnos matriculados
        $students = get_enrolled_users($context, 'mod/vimeotracker:view', 0, 'u.id, u.firstname, u.lastname, u.email');

        $reportdata = array();

        foreach ($videos as $video) {
            foreach ($students as $student) {
                $progress = $DB->get_record('vimeotracker_watch_time', array(
                    'vimeotracker_id' => $video->id,
                    'userid' => $student->id
                ));

                $reportdata[] = array(
                    'student_id'   => (int)$student->id,
                    'fullname'     => $student->firstname . ' ' . $student->lastname,
                    'video_name'   => $video->name,
                    'vimeo_id'     => $video->vimeo_id,
                    'time_watched' => $progress ? (float)$progress->total_time_watched : 0.0,
                    'percentage'   => $progress ? (int)$progress->percentage_watched : 0,
                    'completed'    => $progress ? (int)$progress->is_completed : 0
                );
            }
        }

        return array('data' => $reportdata);
    }

    public static function get_course_report_returns() {
        return new external_single_structure(array(
            'data' => new external_multiple_structure(
                new external_single_structure(array(
                    'student_id'   => new external_value(PARAM_INT, 'ID del estudiante'),
                    'fullname'     => new external_value(PARAM_TEXT, 'Nombre completo'),
                    'video_name'   => new external_value(PARAM_TEXT, 'Nombre del Video'),
                    'vimeo_id'     => new external_value(PARAM_TEXT, 'Vimeo ID'),
                    'time_watched' => new external_value(PARAM_FLOAT, 'Segundos reproducidos'),
                    'percentage'   => new external_value(PARAM_INT, 'Porcentaje completado'),
                    'completed'    => new external_value(PARAM_INT, 'Estado completado (1 o 0)')
                ))
            )
        ));
    }
}
