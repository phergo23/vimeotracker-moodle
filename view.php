<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('vimeotracker', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$vimeotracker = $DB->get_record('vimeotracker', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/vimeotracker:view', $context);

$PAGE->set_url('/mod/vimeotracker/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($vimeotracker->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Cargar SDK oficial del reproductor de Vimeo de forma externa y asíncrona
$PAGE->requires->js_by_url('https://player.vimeo.com/api/player.js', true);

$progress = vimeotracker_get_user_progress($vimeotracker->id, $USER->id);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($vimeotracker->name));

if (!empty($vimeotracker->intro)) {
    echo format_module_intro('vimeotracker', $vimeotracker, $cm->id);
}

// Contenedor e iframe del reproductor
?>
<div class="vimeo-container" style="max-width: 800px; margin: 0 auto;">
    <iframe id="vimeo-player" 
            src="https://player.vimeo.com/video/<?php echo p($vimeotracker->vimeo_id); ?>" 
            width="100%" 
            height="450" 
            frameborder="0" 
            allow="autoplay; fullscreen; picture-in-picture" 
            allowfullscreen>
    </iframe>
</div>

<?php
// Inyección controlada y segura del módulo AMD Javascript
$PAGE->requires->js_call_amd('mod_vimeotracker/vimeo_tracker', 'init', array(
    'moduleId' => (int)$vimeotracker->id,
    'lastPosition' => (float)$progress['last_position'],
    'resumeEnabled' => (int)$vimeotracker->resume_enabled,
    'trackTime' => (int)$vimeotracker->track_time,
    'minWatchPercent' => (int)$vimeotracker->min_watch_percent
));

echo $OUTPUT->footer();
