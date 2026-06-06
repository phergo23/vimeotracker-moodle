define(['core/notification'], function(Notification) {
    return {
        attach: function(config) {
            setTimeout(function() {
                // Estrategia agresiva: buscar CUALQUIER iframe que apunte a vimeo
                var iframes = document.querySelectorAll('iframe[src*="vimeo.com"]');
                
                iframes.forEach(function(iframe) {
                    // Si este iframe corresponde al ID que estamos procesando
                    if (iframe.src.indexOf(config.vimeoId) !== -1) {
                        if (iframe.classList.contains('vt-tracked')) return;
                        iframe.classList.add('vt-tracked');

                        var player = new Vimeo.Player(iframe);
                        var storageKey = 'vimeores_v' + config.vimeoId + '_c' + config.courseId;
                        var savedTime = localStorage.getItem(storageKey);

                        player.ready().then(function() {
                            if (savedTime) {
                                player.setCurrentTime(parseFloat(savedTime));
                            }
                        }).catch(function(error) {
                            console.log('Error inicializando reproductor Vimeo:', error);
                        });

                        // Guardar el segundo exacto cada vez que avanza el video
                        player.on('timeupdate', function(data) {
                            localStorage.setItem(storageKey, data.seconds);
                        });

                        // Control estricto de abandono (Trampas)
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
                });
            }, 1500); // 1.5 segundos de espera para dar tiempo a que cargue el iFrame incrustado
        }
    };
});
