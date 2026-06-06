define(['core/notification'], function(Notification) {
    return {
        attach: function(config) {
            // Un retraso de 1.5 segundos para asegurar que el HTML y el iFrame de Vimeo estén dibujados en la página
            setTimeout(function() {
                // Seleccionar absolutamente TODOS los iFrames que apunten a Vimeo en la página actual
                var iframes = document.querySelectorAll('iframe[src*="vimeo.com"]');
                
                iframes.forEach(function(iframe) {
                    // Evitar duplicados si el filtro se ejecuta dos veces
                    if (iframe.classList.contains('vt-tracked')) return;
                    iframe.classList.add('vt-tracked');

                    // Inicialización nativa directa sobre el elemento iFrame
                    var player = new Vimeo.Player(iframe);

                    player.getVideoId().then(function(id) {
                        // Verificamos si este iFrame corresponde al ID que Moodle está procesando en este bucle
                        if (String(id) === String(config.vimeoId)) {
                            
                            var storageKey = 'vimeores_v' + config.vimeoId + '_c' + config.courseId;
                            var savedTime = localStorage.getItem(storageKey);

                            // Si hay tiempo guardado en este navegador, mover el video ahí de inmediato
                            if (savedTime && parseFloat(savedTime) > 0) {
                                player.setCurrentTime(parseFloat(savedTime)).catch(function(err) {
                                    console.log('Error al posicionar tiempo:', err);
                                });
                            }

                            // Guardar el segundo exacto CADA VEZ que el video avanza (reproducción o adelanto manual)
                            player.on('timeupdate', function(data) {
                                localStorage.setItem(storageKey, data.seconds);
                            });

                            // Control anti-trampas: pausa y reinicio si cambia de pestaña o minimiza
                            document.addEventListener('visibilitychange', function() {
                                if (document.hidden) {
                                    player.pause();
                                    player.setCurrentTime(0).then(function() {
                                        localStorage.setItem(storageKey, 0);
                                        Notification.addNotification({
                                            message: 'El video se reinició automáticamente por salir de la pantalla de estudio.',
                                            type: 'warning'
                                        });
                                    });
                                }
                            });
                        }
                    }).catch(function(error) {
                        // Si un iframe falla o no es compatible, no traba el Moodle
                        console.log('Filtro Vimeo detectó un iframe no inicializado aún:', error);
                    });
                });
            }, 1500);
        }
    };
});
