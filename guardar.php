<?php
// Indicarle a este archivo que use el motor interno de Moodle para la Base de Datos
define('NO_MOODLE_COOKIES', false); 
require_once(__DIR__ . '/../../config.php');

// Verificar que el alumno tenga la sesión iniciada
if (!isloggedin() || isguestuser()) {
    echo json_encode(array('status' => 'error', 'message' => 'No autenticado'));
    die();
}

// Leer los datos crudos que manda el JavaScript desde el navegador
$raw_data = file_get_contents('php://input');
$data = json_decode($raw_data, true);

if (!$data || !isset($data['vimeoId']) || !isset($data['courseId'])) {
    echo json_encode(array('status' => 'error', 'message' => 'Datos incompletos'));
    die();
}

global $DB, $USER;

$userid = $USER->id;
$vimeoId = clean_param($data['vimeoId'], PARAM_ALPHANUMEXT);
$courseId = clean_param($data['courseId'], PARAM_INT);
$seconds = clean_param($data['seconds'], PARAM_FLOAT);

// Buscar si el registro ya existe en PostgreSQL
$record = $DB->get_record('filter_vimeotracker_time', array(
    'userid' => $userid,
    'vimeo_id' => $vimeoId,
    'courseid' => $courseId
));

if ($record) {
    $record->last_position = $seconds;
    $record->timemodified = time();
    $DB->update_record('filter_vimeotracker_time', $record);
} else {
    $newrecord = new \stdClass();
    $newrecord->userid = $userid;
    $newrecord->vimeo_id = $vimeoId;
    $newrecord->courseid = $courseId;
    $newrecord->last_position = $seconds;
    $newrecord->timemodified = time();
    $DB->insert_record('filter_vimeotracker_time', $newrecord);
}

header('Content-Type: application/json');
echo json_encode(array('status' => 'success'));
