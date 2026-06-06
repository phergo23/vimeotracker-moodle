<?php
defined('MOODLE_INTERNAL') || die();

function vimeotracker_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO: return true;
        case FEATURE_SHOW_DESCRIPTION: return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES: return true;
        default: return null;
    }
}

function vimeotracker_add_instance($data, $mform = null) {
    global $DB;
    $data->timecreated = time();
    $data->timemodified = time();
    return $DB->insert_record('vimeotracker', $data);
}

function vimeotracker_update_instance($data, $mform = null) {
    global $DB;
    $data->timemodified = time();
    $data->id = $data->instance;
    return $DB->update_record('vimeotracker', $data);
}

function vimeotracker_delete_instance($id) {
    global $DB;
    if (!$vimeotracker = $DB->get_record('vimeotracker', array('id' => $id))) {
        return false;
    }
    $watchtimes = $DB->get_records('vimeotracker_watch_time', array('vimeotracker_id' => $id), '', 'id');
    if (!empty($watchtimes)) {
        list($insql, $inparams) = $DB->get_in_or_equal(array_keys($watchtimes));
        $DB->delete_records_select('vimeotracker_events', "watch_time_id $insql", $inparams);
    }
    $DB->delete_records('vimeotracker_watch_time', array('vimeotracker_id' => $id));
    $DB->delete_records('vimeotracker', array('id' => $id));
    return true;
}

function vimeotracker_get_user_progress($vimeotracker_id, $userid) {
    global $DB;
    $progress = $DB->get_record('vimeotracker_watch_time', array('vimeotracker_id' => $vimeotracker_id, 'userid' => $userid));
    if (!$progress) {
        return [
            'last_position' => 0,
            'percentage_watched' => 0,
            'is_completed' => 0,
            'total_time_watched' => 0,
            'status' => 'not_started'
        ];
    }
    return (array)$progress;
}
