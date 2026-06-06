<?php
defined('MOODLE_INTERNAL') || die();

class filter_vimeotracker extends moodle_text_filter {
    public function filter($text, array $options = array()) {
        global $USER, $COURSE, $DB;

        if (stripos($text, 'vimeo.com') === false) {
            return $text;
        }

        if (preg_match_all('/vimeo\.com\/(?:video\/)?([0-9]+)/', $text, $matches)) {
            $vimeo_ids = array_unique($matches[1]);
            
            $userid = isset($USER->id) ? $USER->id : 0;
            $courseid = isset($COURSE->id) ? $COURSE->id : 0;

            $antitrampa_config = get_config('filter_vimeotracker', 'activar_antitrampa');
            $activar_antitrampa = !empty($antitrampa_config) ? 'true' : 'false';

            $video_configs = array();
            foreach ($vimeo_ids as $vimeo_id) {
                $last_position = 0;
                if ($userid > 0) {
                    $progress = $DB->get_record('filter_vimeotracker_time', array(
                        'userid' => $userid,
                        'vimeo_id' => $vimeo_id,
                        'courseid' => $courseid
                    ), 'last_position');
                    if ($progress) {
                        $last_position = $progress->last_position;
                    }
                }
                $video_configs[] = array(
                    'vimeoId' => $vimeo_id,
                    'lastPosition' => (float)$last_position
                );
            }

            $html_script = '
            <script src="https://player.vimeo.com/api/player.js"></script>
            <script>
            (function() {
                var videoConfigs = ' . json_encode($video_configs) . ';
                var courseId = ' . $courseid . ';
                var activarAntitrampa = ' . $activar_antitrampa . ';

                function initVimeoTracker() {
                    var iframes = document.querySelectorAll(\'iframe[src*="vimeo.com"]\');
                    iframes.forEach(function(iframe) {
                        if (iframe.classList.contains(\'vt-ready\')) return;

                        var player = new Vimeo.Player(iframe);
                        player.getVideoId().then(function(id) {
                            var currentConfig = videoConfigs.find(function(c) { return String(c.vimeoId) === String(id); });
                            if (!currentConfig) return;

                            iframe.classList.add(\'vt-ready\');

                            if (currentConfig.lastPosition > 0) {
                                player.setCurrentTime(currentConfig.lastPosition).catch(function(err) {});
                            }

                            var lastSavedTime = -1;

                            // USAR EL MOTOR NATIVO DE MOODLE: Requiere invocar core/ajax de forma segura
                            function guardarProgresoServidor(segundosActuales) {
                                if (typeof window.require !== "undefined") {
                                    window.require([\'core/ajax\'], function(ajax) {
                                        ajax.call([{
                                            methodname: \'filter_vimeotracker_save_progress\',
                                            args: {
                                                vimeoId: String(id),
                                                courseId: parseInt(courseId),
                                                seconds: parseFloat(segundosActuales)
                                            }
                                        }]);
                                    });
                                }
                            }

                            player.on(\'timeupdate\', function(data) {
                                var currentTime = Math.floor(data.seconds);
                                // Guardar cada 4 segundos exactos
                                if (currentTime % 4 === 0 && currentTime !== lastSavedTime) {
                                    lastSavedTime = currentTime;
                                    guardarProgresoServidor(data.seconds);
                                }
                            });

                            if (activarAntitrampa) {
                                document.addEventListener(\'visibilitychange\', function() {
                                    if (document.hidden) {
                                        player.pause();
                                        player.setCurrentTime(0).then(function() {
                                            guardarProgresoServidor(0);
                                            alert("El video se reinició automáticamente por salir de la pantalla de estudio.");
                                        });
                                    }
                                });
                            }
                        }).catch(function(e) {});
                    });
                }

                setTimeout(initVimeoTracker, 1000);
                setTimeout(initVimeoTracker, 3000);
            })();
            </script>';

            $text .= $html_script;
        }

        return $text;
    }
}
