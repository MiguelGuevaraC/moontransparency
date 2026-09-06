<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ubicación en GeoBosques</title>
    <style>
        html, body { margin: 0; min-height: 100%; font-family: Arial, sans-serif; }
        main { padding: 1rem; }
        #map-frame { width: 100%; height: 500px; border: 0; }
        .message { padding: 0.9rem 1rem; border-radius: 0.35rem; background: #f1f5f9; color: #334155; }
        .message[hidden], #map-frame[hidden] { display: none; }
    </style>
</head>
<body>
<main>
    <p id="map-message" class="message" @if ($map['available']) hidden @endif>
        {{ $map['message'] }}
    </p>

    @if ($map['available'])
        <iframe
            id="map-frame"
            title="Ubicación guardada en el visor GeoBosques"
            data-viewer-url="{{ $map['viewer_url'] }}"
            referrerpolicy="no-referrer-when-downgrade"
            hidden
        ></iframe>

        <noscript>
            <p class="message">Se necesita JavaScript para comprobar la conexión antes de cargar el visor.</p>
        </noscript>

        <script>
            (() => {
                const frame = document.getElementById('map-frame');
                const message = document.getElementById('map-message');
                const viewerUrl = frame.dataset.viewerUrl;

                const showOfflineMessage = () => {
                    frame.hidden = true;
                    frame.removeAttribute('src');
                    message.textContent = 'Sin conexión: el visor GeoBosques no está disponible. Los demás datos de la participación se pueden consultar normalmente.';
                    message.hidden = false;
                };

                const loadMapWhenOnline = () => {
                    if (! navigator.onLine) {
                        showOfflineMessage();

                        return;
                    }

                    if (! frame.src) {
                        frame.src = viewerUrl;
                    }

                    message.hidden = true;
                    frame.hidden = false;
                };

                window.addEventListener('online', loadMapWhenOnline);
                window.addEventListener('offline', showOfflineMessage);
                loadMapWhenOnline();
            })();
        </script>
    @endif
</main>
</body>
</html>
