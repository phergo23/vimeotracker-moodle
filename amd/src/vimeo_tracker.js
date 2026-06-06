define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    return {
        init: function(config) {
            var iframe = document.querySelector('#vimeo-player');
            if (!iframe) return;

            var player = new Vimeo.Player(iframe);
            var playerReady = false;
            var videoDuration = 0;

            player.ready().then(function() {
                playerReady = true;
                return player.getDuration();
            }).then(function(duration) {
                videoDuration = duration;
                
                // Reanudar si está activo y tiene progreso previo
                if (config.resumeEnabled && config.lastPosition > 0) {
                    player.setCurrentTime(config.lastPosition);
                }
            });

            // Enviar actualizaciones cada 5 segundos de reproduccion activa
            var lastUpdate = Date.now();
            player.on('timeupdate', function(data) {
                var now = Date.now();
                if (now - lastUpdate >= 5000) {
                    lastUpdate = now;
                    var percent = videoDuration > 0 ? Math.round((data.seconds / videoDuration) * 100) : 0;
                    
                    Ajax.call([{
                        methodname: 'mod_vimeotracker_update_watch_time',
                        args: {
                            vimeotracker_id: config.moduleId,
                            current_time: data.seconds,
                            total_time_watched: data.seconds,
                            percentage_watched: percent,
                            duration: videoDuration,
                            status: 'in_progress'
                        }
                    }]);
                }
            });

            // Forzar guardado al finalizar el video completo
            player.on('ended', function() {
                Ajax.call([{
                    methodname: 'mod_vimeotracker_update_watch_time',
                    args: {
                        vimeotracker_id: config.moduleId,
                        current_time: videoDuration,
                        total_time_watched: videoDuration,
                        percentage_watched: 100,
                        duration: videoDuration,
                        status: 'completed'
                    }
                }])[0].then(function() {
                    Notification.addNotification({
                        message: '¡Excelente! Has completado el requerimiento de visualización.',
                        type: 'success'
                    });
                });
            });

            // Regresar al inicio del video si se detecta abandono o invisibilidad (Foco de pestaña)
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    player.pause();
                    player.setCurrentTime(0).then(function() {
                        Notification.addNotification({
                            message: 'Video pausado y reiniciado por salir de la pantalla activa.',
                            type: 'warning'
                        });
                    });
                }
            });
        }
    };
});
