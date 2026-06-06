<?php
defined('MOODLE_INTERNAL') || die();

class filter_vimeotracker extends moodle_text_filter {
    public function filter($text, array $options = array()) {
        global $USER, $COURSE;

        if (stripos($text, 'vimeo.com') === false) {
            return $text;
        }

        if (preg_match_all('/vimeo\.com\/(?:video\/)?([0-9]+)/', $text, $matches)) {
            $vimeo_ids = array_unique($matches[1]);
            
            $userid = isset($USER->id) ? $USER->id : 0;
            $courseid = isset($COURSE->id) ? $COURSE->id : 0;

            // Leer la configuración del interruptor de Moodle (retorna true o false)
            $antitrampa_config = get_config('filter_vimeotracker', 'activar_antitrampa');
            $activar_antitrampa = !empty($antitrampa_config) ? 'true' : 'false';

            $html_script = '
            <script src="https://player.vimeo.com/api/player.js"></script>
            <script>
            (function() {
                function initVimeoTracker() {
                    var iframes = document.querySelectorAll(\'iframe[src*="vimeo.com"]\');
                    iframes.forEach(function(iframe) {
                        if (iframe.classList.contains(\'vt-ready\')) return;
                        
                        var player = new Vimeo.Player(iframe);
                        player.getVideoId().then(function(id) {
                            iframe.classList.add(\'vt-ready\');
                            
                            var storageKey = "vimeores_u" + ' . $userid . ' + "_v" + id + "_c" + ' . $courseid . ';
                            var savedTime = localStorage.getItem(storageKey);

                            if (savedTime && parseFloat(savedTime) > 0) {
                                player.setCurrentTime(parseFloat(savedTime)).catch(function(err) {});
                            }

                            player.on(\'timeupdate\', function(data) {
                                localStorage.setItem(storageKey, data.seconds);
                            });

                            // Evaluar dinámicamente el interruptor que configuró Jael
                            var antitrampaActivo = ' . $activar_antitrampa . ';
                            
                            if (antitrampaActivo) {
                                document.addEventListener(\'visibilitychange\', function() {
                                    if (document.hidden) {
                                        player.pause();
                                        player.setCurrentTime(0).then(function() {
                                            localStorage.setItem(storageKey, 0);
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
