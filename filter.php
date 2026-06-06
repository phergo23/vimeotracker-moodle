<?php
defined('MOODLE_INTERNAL') || die();

class filter_vimeotracker extends moodle_text_filter {
    public function filter($text, array $options = array()) {
        global $USER, $COURSE;

        // Si no hay rastro de vimeo en el texto de la lección, salir de inmediato
        if (stripos($text, 'vimeo.com') === false) {
            return $text;
        }

        // Capturar los IDs de Vimeo incrustados en los iFrames
        if (preg_match_all('/vimeo\.com\/(?:video\/)?([0-9]+)/', $text, $matches)) {
            $vimeo_ids = array_unique($matches[1]);
            
            $userid = isset($USER->id) ? $USER->id : 0;
            $courseid = isset($COURSE->id) ? $COURSE->id : 0;

            // Código JavaScript puro que se ejecutará en el navegador del alumno de forma garantizada
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
                            
                            // Llave única por Usuario, Video y Curso (Soporta miles de usuarios en simultáneo)
                            var storageKey = "vimeores_u" + ' . $userid . ' + "_v" + id + "_c" + ' . $courseid . ';
                            var savedTime = localStorage.getItem(storageKey);

                            // Reanudar el video si hay progreso guardado
                            if (savedTime && parseFloat(savedTime) > 0) {
                                player.setCurrentTime(parseFloat(savedTime)).catch(function(err) {
                                    console.log("Error al reanudar:", err);
                                });
                            }

                            // Guardar el segundo actual cada vez que el video avanza
                            player.on(\'timeupdate\', function(data) {
                                localStorage.setItem(storageKey, data.seconds);
                            });

                            // Control estricto anti-trampas (Pausa y reinicia a 0 si cambia de pestaña)
                            document.addEventListener(\'visibilitychange\', function() {
                                if (document.hidden) {
                                    player.pause();
                                    player.setCurrentTime(0).then(function() {
                                        localStorage.setItem(storageKey, 0);
                                        alert("El video se reinició automáticamente por salir de la pantalla de estudio.");
                                    });
                                }
                            });
                        }).catch(function(e) {});
                    });
                }
                
                // Ejecutar inmediatamente y re-intentar por si el contenido tarda en cargar en Moodle
                setTimeout(initVimeoTracker, 1000);
                setTimeout(initVimeoTracker, 3000);
            })();
            </script>';

            // Inyectar el bloque de código al final del texto del curso
            $text .= $html_script;
        }

        return $text;
    }
}
