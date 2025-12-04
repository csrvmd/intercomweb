# intercomweb
Servidor Intercom Web local con audio OPUS
/var/www/html/intercom/
│
├─ config.txt               # Configuración principal
│
├─ logo.svg                 # Icono de empresa (200px ancho)
├─ icono.png                # Favicon (64x64)
├─ altavoz.png              # Icono altavoz
├─ conf.png                 # Icono configuración
│
├─ backups/                 # Copias de seguridad de config.txt
│    └─ config_2025-12-03_1300.txt
│    └─ ...
│
├─ index.php                # Página principal (intercom/sala)
├─ config.php               # Página y lógica de configuración
├─ salas.php                # Gestión y cambio de salas
├─ signaling.php            # Señalización (WebRTC, solo control)
├─ audio.js                 # Lógica del audio y WebRTC P2P
├─ style.css                # Estilos, diseño minimalista, responsive
└─ utils.php                # Funciones auxiliares (leer configuración, gestión IP, recargas, etc.)
===========================================
Permisos sugeridos y estructura de backups
# Crear directorio y dar permisos suficientes a www-data
sudo mkdir -p /var/www/html/intercom/backups
sudo chown www-data:www-data /var/www/html/intercom/backups
sudo chmod 755 /var/www/html/intercom/backups
# Igual para /signals si usas el signaling básico
sudo mkdir -p /var/www/html/intercom/signals
sudo chown www-data:www-data /var/www/html/intercom/signals
sudo chmod 700 /var/www/html/intercom/signals
==============================================
Da permisos de escritura a www-data en backups y signals:
sudo chown -R www-data:www-data /var/www/html/intercom/backups
sudo chown -R www-data:www-data /var/www/html/intercom/signals
sudo chmod -R 755 /var/www/html/intercom/backups
sudo chmod -R 700 /var/www/html/intercom/signals
===============================================
Acceso

Desde un ordenador registrado (IP fija en el botón!), accede a http://SERVIDOR/intercom/ y deberías ver la interfaz de sala/botones.
Accede como administrador a http://SERVIDOR/intercom/config.php e ingresa tu clave para editar configuración desde web.
Prueba restaurar un backup previo y verifica que la configuración cambia correctamente.
Prueba de intercom

Accede con otro ordenador/ficha (IP registrada) y entra a la misma sala. Los botones para usuarios conectados deben estar en verde.
Prueba pulsación breve/larga entre dos usuarios:
Pulsa un botón poco tiempo → inicia llamada, termina en breve.
Pulsa largo → mantiene llamada mientras dura la pulsación.
Prueba botón conferencia: iniciar llamada a todos los usuarios conectados en la sala actual.
Configuración audio

Pulsa el icono de altavoz, selecciona entrada/salida de audio.
Recarga la página y verifica que tu selección permanece.
No registrado

Intenta acceder desde IP no registrada y verifica que sale el cartel de denegado.
