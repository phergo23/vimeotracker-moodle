define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    return {
        attach: function(config) {
            // Esperar un momento a que el DOM cargue iFrames dinámicos de editores Atto/TinyMCE
            setTimeout(function() {
                // Localizar cualquier iFrame que contenga el ID de Vimeo actual
                var iframes = document.querySelectorAll('iframe[src*="' + config.vimeoId + '"]');
                
                iframes.forEach(function(iframe) {
                    if (iframe.classList.contains('vt-tracked')) return;
                    iframe.classList.add('vt-tracked');
                    iframe.id = 'vimeo-iframe-' + config.vimeoId;

                    var player = new Vimeo.Player(iframe);
                    var videoDuration = 0;

                    player.ready().then(function() {
                        return player.getDuration();
                    }).then(function(duration) {
                        videoDuration = duration;
                        if (config.lastPosition > 0) {
                            player.setCurrentTime(config.lastPosition);
                        }
                    });

                    // Guardado inteligente cada 5 segundos
                    var lastUpdate = Date.now();
                    player.on('timeupdate', function(data) {
                        var now = Date.now();
                        if (now - lastUpdate >= 5000) {
                            lastUpdate = now;
                            var percent = videoDuration > 0 ? Math.round((data.seconds / videoDuration) * 100) : 0;
                            
                            Ajax.call([{
                                methodname: 'filter_vimeotracker_save_progress',
                                args: {
                                    vimeo_id: config.vimeoId,
                                    current_time: data.seconds,
                                    percentage: percent,
                                    courseid: config.courseId
                                }
                            }]);
                        }
                    });

                    // Control estricto de abandono de pestaña activa (Cero trampas)
                    document.addEventListener('visibilitychange', function() {
                        if (document.hidden) {
                            player.pause();
                            player.setCurrentTime(0).then(function() {
                                Notification.addNotification({
                                    message: 'El video se reinició automáticamente por salir de la pantalla de estudio.',
                                    type: 'warning'
                                });
                            });
                        }
                    });
                });
            }, 1000);
        }
    };
});
