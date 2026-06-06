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

            // Variable que acumulará las configuraciones de cada video cargado en esta pagina
            $video_configs = array();

            foreach ($vimeo_ids as $vimeo_id) {
                $last_position = 0;
                
                // EXTRAER DEL SERVIDOR: Buscar el progreso guardado real en la base de datos global
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
                            
                            // Encontrar la configuracion de tiempo asignada por el servidor para este video
                            var currentConfig = videoConfigs.find(function(c) { return String(c.vimeoId) === String(id); });
                            if (!currentConfig) return;

                            iframe.classList.add(\'vt-ready\');

                            // 1. REANUDACIÓN GLOBAL: El servidor nos dice en que segundo ponerlo
                            if (currentConfig.lastPosition > 0) {
                                player.setCurrentTime(currentConfig.lastPosition).catch(function(err) {});
                            }

                            // Variable para controlar ráfagas y no saturar tu servidor (guarda cada 4 segundos)
                            var lastSavedTime = 0;

                            // 2. GUARDADO EN EL SERVIDOR VIA AJAX NATIVO DE MOODLE
                            player.on(\'timeupdate\', function(data) {
                                var currentTime = Math.floor(data.seconds);
                                
                                // Guardar solo si ha avanzado 4 segundos desde el ultimo registro para cuidar el rendimiento
                                if (currentTime % 4 === 0 && currentTime !== lastSavedTime) {
                                    lastSavedTime = currentTime;

                                    // Llamada directa al motor AJAX de tu Moodle
                                    var wsUrl = M.cfg.wwwroot + \'/lib/ajax/service.php?sesskey=\' + M.cfg.sesskey;
                                    var payload = [{
                                        index: 0,
                                        methodname: \'filter_vimeotracker_save_progress\',
                                        args: {
                                            vimeoId: String(id),
                                            courseId: parseInt(courseId),
                                            seconds: parseFloat(data.seconds)
                                        }
                                    }];

                                    fetch(wsUrl, {
                                        method: \'POST\',
                                        headers: { \'Content-Type\': \'application/json\' },
                                        body: json_encode_moodle_payload(payload)
                                    }).catch(function(e) {});
                                }
                            });

                            if (activarAntitrampa) {
                                document.addEventListener(\'visibilitychange\', function() {
                                    if (document.hidden) {
                                        player.pause();
                                        player.setCurrentTime(0).then(function() {
                                            // Si hace trampa, avisar al servidor de inmediato que su tiempo volvio a 0
                                            var wsUrl = M.cfg.wwwroot + \'/lib/ajax/service.php?sesskey=\' + M.cfg.sesskey;
                                            var payload = [{
                                                index: 0,
                                                methodname: \'filter_vimeotracker_save_progress\',
                                                args: { vimeoId: String(id), courseId: parseInt(courseId), seconds: 0 }
                                            }];
                                            fetch(wsUrl, { method: \'POST\', headers: { \'Content-Type\': \'application/json\' }, body: json_encode_moodle_payload(payload) });
                                            alert("El video se reinició automáticamente por salir de la pantalla de estudio.");
                                        });
                                    }
                                });
                            }
                        }).catch(function(e) {});
                    });
                }

                function json_encode_moodle_payload(obj) { return JSON.stringify(obj); }

                setTimeout(initVimeoTracker, 1000);
                setTimeout(initVimeoTracker, 3000);
            })();
            </script>';

            $text .= $html_script;
        }

        return $text;
    }
}
