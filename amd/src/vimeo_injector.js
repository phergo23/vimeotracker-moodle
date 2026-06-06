define(['core/notification'], function(Notification) {
    return {
        attach: function(config) {
            setTimeout(function() {
                var iframes = document.querySelectorAll('iframe[src*="' + config.vimeoId + '"]');
                
                iframes.forEach(function(iframe) {
                    if (iframe.classList.contains('vt-tracked')) return;
                    iframe.classList.add('vt-tracked');

                    var player = new Vimeo.Player(iframe);
                    
                    // Recuperar el tiempo guardado en el navegador de este alumno
                    var storageKey = 'vimeo_res_user_' + config.vimeoId + '_c' + config.courseId;
                    var savedTime = localStorage.getItem(storageKey);

                    player.ready().then(function() {
                        if (savedTime) {
                            player.setCurrentTime(parseFloat(savedTime));
                        }
                    });

                    // Guardar el tiempo en el navegador cada segundo que avanza
                    player.on('timeupdate', function(data) {
                        localStorage.setItem(storageKey, data.seconds);
                    });

                    // Control estricto de trampa (Si cambia de pestaña o minimiza)
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
                });
            }, 1000);
        }
    };
});
