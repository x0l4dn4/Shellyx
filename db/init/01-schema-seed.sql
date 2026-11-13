-- Esquema compartido tienda + foro, y datos sembrados.
-- Se ejecuta automáticamente al inicializar MariaDB con el data dir vacío.
-- (Para re-sembrar: docker compose down -v && docker compose up -d --build)

SET NAMES utf8mb4;

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  price DECIMAL(10,2),
  description TEXT
);

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) UNIQUE,
  password_md5 CHAR(32)
);

-- Foro: hilos (posts) y respuestas (replies). La RFI vive en posts.embed_url,
-- que se incluye en post.php. Las respuestas no tienen embed_url.
CREATE TABLE posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  title VARCHAR(255),
  body TEXT,
  embed_url VARCHAR(512),
  category VARCHAR(32) NOT NULL DEFAULT 'general',
  product_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_posts_product (product_id)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE replies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT,
  user_id INT,
  body TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) DEFAULT CHARSET=utf8mb4;

-- Catálogo Shellyx: gadgets de hardware hacking (gama de entrada < 100 EUR y
-- gama avanzada). Editable a gusto; el buscador sigue siendo UNION-vulnerable.
INSERT INTO products (name, price, description) VALUES
  ('Shellyx One',            79.00,  'Gadget multiherramienta programable: WiFi, BLE y sub-GHz en un bolsillo. Ideal para empezar.'),
  ('Shellyx Mini',           39.00,  'Dispositivo de entrada por USB para automatizar pulsaciones y cargar tus primeros scripts.'),
  ('Shellyx Pro',            149.00,  'Gama avanzada: SDR integrado, GPIO expuesto y firmware abierto para pentesters.'),
  ('Modulo WiFi Recon',      24.00,  'Placa de expansion para captura y analisis de redes 2.4GHz. Compatible con Shellyx One/Pro.'),
  ('Modulo Sub-GHz',         29.00,  'Transceptor 315/433/868MHz para analizar mandos y telemetria. Solo uso legal y etico.'),
  ('Antena de alta ganancia', 15.00, 'Antena SMA desmontable para ampliar el alcance de tus modulos de radio.'),
  ('Carcasa translucida',      9.00, 'Carcasa de policarbonato con guia de luz para el LED de estado. Verde Shellyx.'),
  ('Kit Educativo B2B',      299.00,  'Pack para aulas: 4 dispositivos, modulos y acceso a los retos mensuales del foro.'),
  ('Pack Firmwares Oficiales', 19.00, 'Licencia de pago unico de la coleccion de firmwares y scripts oficiales. Mas barato junto al gadget.');

-- Usuarios del foro: contraseñas del top 1000 de rockyou, guardadas como MD5.
INSERT INTO users (username, password_md5) VALUES
  ('admin',     MD5('letmein')),
  ('moderador', MD5('password')),
  ('jsanchez',  MD5('iloveyou')),
  ('mlopez',    MD5('qwerty')),
  ('agarcia',   MD5('sunshine'));

-- Ids de usuario (la cadena no depende del AUTO_INCREMENT exacto).
SET @admin := (SELECT id FROM users WHERE username = 'admin');
SET @mod   := (SELECT id FROM users WHERE username = 'moderador');
SET @js    := (SELECT id FROM users WHERE username = 'jsanchez');
SET @ml    := (SELECT id FROM users WHERE username = 'mlopez');
SET @ag    := (SELECT id FROM users WHERE username = 'agarcia');

-- Hilos sembrados (foro "vivo"): gadgets de hardware hacking inventados.
-- embed_url vacío: ningún RFI accidental en páginas públicas; el vector lo mete
-- un atacante autenticado vía new_post.php.
INSERT INTO posts (id, user_id, title, body, embed_url, category, created_at) VALUES
(1, @admin,
 '[FIRMWARE] Shellyx Nibbler v2.3 — soporte 915 MHz y lector NFC mejorado',
 'Ya está disponible la v2.3 del firmware del Nibbler. Esta release toca sobre todo el stack sub-GHz y el lector NFC.\n\nChangelog:\n- Sub-GHz: soporte de 915 MHz además de 433/868. Replay más estable en ASK/OOK.\n- NFC: lectura de Mifare Classic 1K más fiable; menos fallos con tags gastados.\n- Arreglado un cuelgue al guardar más de 64 capturas en la SD.\n- Consumo en reposo reducido alrededor del 12 por ciento.\n\nPara flashear: poned el Nibbler en modo DFU (mantened BOOT mientras conectáis el USB-C) y cargad nibbler-2.3.bin con el flasher. No desconectéis a mitad.\n\nLas firmas SHA-256 están en el primer comentario fijado.',
 '', 'firmware', NOW() - INTERVAL 3 DAY),
(2, @admin,
 'GlitchWitch: perfiles de glitch para saltar el secure boot de un STM32F103',
 'He subido mis perfiles de glitch para el STM32F103. Con el clock a 8 MHz y un pulso corto entra casi siempre.\n\nAdjunto los offsets que mejor me funcionan. Si tenéis una placa con RDP nivel 1, probad primero en una de sacrificio: a veces el glitch deja el chip en un estado raro.\n\n¿Alguien lo ha probado contra la familia GD32 clónica?',
 '', 'firmware', NOW() - INTERVAL 6 DAY),
(3, @js,
 '[BETA] Firmware abierto para el ProxReaver (emulación Mifare Classic 1K)',
 'Abro beta del firmware abierto para el ProxReaver. Objetivo: emulación de Mifare Classic 1K estable y lectura de bloques sin depender de la app oficial.\n\nQué va: lectura y clonado de 1K. Qué falta: HID Prox de 125 kHz y escritura de UID bloqueado.\n\nReportad fallos en este hilo con el log por serial, por favor.',
 '', 'firmware', NOW() - INTERVAL 9 DAY),
(4, @ag,
 '[MÓDULO] QuackPad Mini: payload de exfil WiFi en Windows 11',
 'Payload para el QuackPad Mini que saca el perfil WiFi (SSID y clave en claro) en Windows 11 y lo manda por HTTP a tu host.\n\nEstá pensado para demos controladas. No lo uséis fuera de un laboratorio.\n\nSi Defender os corta el netsh, meted un pequeño retardo al inicio.',
 '', 'payloads', NOW() - INTERVAL 4 DAY),
(5, @ml,
 'Colección de payloads DuckyScript para el GhostTap X1',
 'Dejo una colección de payloads DuckyScript que uso con el GhostTap X1: abrir una reverse, volcar el portapapeles y un troll inofensivo para presentaciones.\n\nEl X1 tarda un poco en que el host lo reconozca como teclado, así que empezad siempre con un DELAY generoso.',
 '', 'payloads', NOW() - INTERVAL 8 DAY),
(6, @ag,
 '[BRICK] Actualicé el JTAGoblin y ahora no enumera por USB',
 'Actualicé el firmware del JTAGoblin y ahora no enumera por USB. No lo ve ni Windows ni Linux, y el LED se queda fijo en rojo.\n\n¿Se puede recuperar por el header SWD o lo he convertido en un pisapapeles? Acepto cualquier idea antes de tirarlo.',
 '', 'soporte', NOW() - INTERVAL 2 DAY),
(7, @ml,
 'El PineSprite no levanta el AP rogue tras el último flasheo',
 'Desde el último flasheo, el PineSprite no levanta el AP rogue. El panel carga, pero el SSID falso no aparece por ningún lado.\n\n¿A alguien más le pasa con el firmware nuevo? En 2.4 GHz tampoco va.',
 '', 'soporte', NOW() - INTERVAL 5 DAY),
(8, @mod,
 '[GUÍA] Flashear sin brickear: checklist antes de tocar el firmware',
 'Checklist rápida antes de tocar el firmware de cualquier cacharro, para no acabar en el subforo de bricks:\n\n- Batería por encima del 50 por ciento.\n- Cable de datos bueno (muchos USB-C solo llevan corriente).\n- Verificad el hash del binario antes de flashear.\n- Modo DFU o recovery, nunca en caliente.\n\nSi tras esto se os queda tieso, abrid hilo con el log completo.',
 '', 'soporte', NOW() - INTERVAL 11 DAY),
(9, @ml,
 'Unboxing del SubGhost — primeras impresiones vs el modelo viejo',
 'Me ha llegado el SubGhost nuevo y lo comparo con el modelo viejo que tenía.\n\nLo bueno: mejor front-end de RF, se nota el alcance a 433 MHz y el menú va más fluido. Lo malo: la carcasa sigue siendo endeble y la antena va justa.\n\n¿Merece el salto? Para replay sub-GHz, para mí sí.',
 '', 'general', NOW() - INTERVAL 7 DAY),
(10, @js,
 'Me presento: llevo 2 años con el Pwnapet colgado en la mochila',
 'Me presento: llevo dos años con el Pwnapet colgado en la mochila capturando handshakes por la ciudad. Al final te picas por subir de nivel al bicho.\n\nVengo a aprender de firmware y a cacharrear. Un saludo a todos.',
 '', 'general', NOW() - INTERVAL 12 DAY),
(11, @mod,
 'Normas del foro y cómo pedir ayuda con un brick',
 'Bienvenidos a Shellyx. Normas rápidas:\n\n1. Nada de datos ni credenciales reales de terceros en los payloads.\n2. Todo lo que se comparte aquí es para laboratorio y uso controlado.\n3. Antes de abrir un hilo de brick, mirad la guía fijada y pegad el log completo.\n\nGracias por mantener la comunidad sana.',
 '', 'general', NOW() - INTERVAL 15 DAY);

-- Respuestas sembradas (1-5 por usuario), para que los hilos se vean con conversación.
INSERT INTO replies (post_id, user_id, body, created_at) VALUES
(1, @ml,    'Flasheada sin problemas en mi unidad rev.B. El replay a 915 MHz va fino. Gracias por el currazo.', NOW() - INTERVAL 58 HOUR),
(1, @js,    'A mí me petó a mitad de flasheo y se quedó en bucle de arranque. ¿Hay que entrar sí o sí en modo DFU antes?', NOW() - INTERVAL 54 HOUR),
(1, @mod,   '@jsanchez sí, siempre DFU y con batería por encima del 50 por ciento. En caliente es justo como se brickean. Lo añado al post fijado.', NOW() - INTERVAL 30 HOUR),
(1, @ag,    'Confirmo que en reposo el consumo bajó un pelín con la 2.3. El lector NFC también va más fino con tags Mifare gastados.', NOW() - INTERVAL 14 HOUR),
(2, @js,    '¿Qué ancho de pulso usas para el glitch? A mí en el F103 con 80 ns no entra nunca.', NOW() - INTERVAL 120 HOUR),
(2, @ml,    'Prueba a bajar el clock a 8 MHz y mover el offset en pasos de 2 ns. Así me saltó el RDP a mí.', NOW() - INTERVAL 100 HOUR),
(3, @ml,    'Probada la beta. Clona mi llavero Mifare 1K sin despeinarse. ¿Caerá soporte de HID Prox más adelante?', NOW() - INTERVAL 168 HOUR),
(3, @ag,    'El sector 0 con UID bloqueado no me lo deja escribir. ¿A vosotros sí?', NOW() - INTERVAL 150 HOUR),
(4, @mod,   'Recordad no subir payloads con credenciales reales de nadie. Lo dejo enlazado en las normas.', NOW() - INTERVAL 70 HOUR),
(4, @admin, 'Buen payload. En Win11 con Defender a tope salta el AMSI; mete un pequeño retardo al principio.', NOW() - INTERVAL 5 HOUR),
(5, @ag,    'Me guardo la colección. El GhostTap X1 tarda en montar el HID, un DELAY 800 al inicio ayuda.', NOW() - INTERVAL 48 HOUR),
(6, @mod,   '¿Actualizaste por USB o por el header SWD? Si se cortó a mitad por USB, casi seguro es el bootloader.', NOW() - INTERVAL 30 HOUR),
(6, @admin, 'Pon el JTAGoblin en modo recovery con el puente JP1 y reflashea el loader. A mí me revivió uno igual.', NOW() - INTERVAL 26 HOUR),
(6, @ml,    'A mí fue el cable. Con uno de datos bueno volvió a enumerar. Prueba otro USB-C antes de nada.', NOW() - INTERVAL 1 HOUR),
(7, @mod,   'Revisa que el jumper de antena esté en EXT. Con la interna el rogue AP se cae cada dos por tres.', NOW() - INTERVAL 96 HOUR),
(7, @js,    '¿Lo levantaste en 2.4 o en 5 GHz? El PineSprite en 5 GHz solo tira con el firmware nuevo.', NOW() - INTERVAL 8 HOUR),
(8, @ag,    'Guía de cabecera. Añadiría: nunca flashear con la batería pelada, que es el error número uno.', NOW() - INTERVAL 240 HOUR),
(9, @js,    '¿Merece la pena el salto solo por el sub-GHz? Lo tengo en el carrito.', NOW() - INTERVAL 72 HOUR),
(9, @admin, 'Buen unboxing. El front-end nuevo se nota en el alcance a 433. Para mí vale el cambio.', NOW() - INTERVAL 26 HOUR),
(10, @ml,   '¡Bienvenido! El Pwnapet engancha, en nada tienes la mochila llena de handshakes. ¿Ya le pusiste pantalla e-ink?', NOW() - INTERVAL 72 HOUR);

-- Subforos de dispositivo: hilos sembrados por producto (ids de producto 1..9,
-- en el orden del INSERT de products). category queda en su DEFAULT.
INSERT INTO posts (id, user_id, title, body, embed_url, product_id, created_at) VALUES
(12, @js,  'Primer contacto con el Shellyx One: ¿por dónde empiezo?',
 'Acabo de recibir el Shellyx One y va genial. ¿Qué me recomendáis para las primeras pruebas? Busco algo sencillo para ir cogiendo soltura con el menú y los módulos.',
 '', 1, NOW() - INTERVAL 2 DAY),
(13, @ag,  'Shellyx One: mi set de scripts favoritos',
 'Dejo los scripts que más uso en el One: lectura rápida de tags, un par de utilidades de radio y uno para ordenar capturas en la SD. Si queréis los subo al hilo.',
 '', 1, NOW() - INTERVAL 5 DAY),
(14, @ml,  'El Mini no monta como teclado en algunos portátiles',
 'Con el Shellyx Mini me pasa que en ciertos portátiles tarda mucho en montar como teclado o no lo hace. ¿A vosotros os va bien con un DELAY alto al principio?',
 '', 2, NOW() - INTERVAL 3 DAY),
(15, @admin, 'GPIO del Pro: pinout y niveles lógicos',
 'Abro hilo de referencia para el GPIO del Shellyx Pro. Son 3V3, no metáis 5V directos. Adjunto el pinout en el primer comentario. Preguntad dudas por aquí.',
 '', 3, NOW() - INTERVAL 6 DAY),
(16, @ag,  'Rendimiento de captura del módulo WiFi Recon',
 'He estado midiendo el módulo WiFi Recon en 2.4 GHz y la captura es estable. ¿Qué cifras os salen a vosotros en un entorno con mucho ruido?',
 '', 4, NOW() - INTERVAL 4 DAY),
(17, @ml,  'Alcance real del módulo Sub-GHz con antena externa',
 'Con el módulo Sub-GHz y una antena decente el alcance a 433 MHz mejora bastante. ¿Hasta dónde habéis llegado en condiciones normales?',
 '', 5, NOW() - INTERVAL 7 DAY),
(18, @js,  '¿Merece la pena la antena de alta ganancia para 433?',
 'Dudando entre quedarme con la antena de serie o pasar a la de alta ganancia para trabajar a 433 MHz. ¿Se nota mucho el salto?',
 '', 6, NOW() - INTERVAL 8 DAY),
(19, @ag,  'Montaje de la carcasa translúcida sin romper las pestañas',
 'Truco rápido para montar la carcasa translúcida: haced palanca por las esquinas y con cuidado, que las pestañas son finas. La guía de luz del LED queda muy bien.',
 '', 7, NOW() - INTERVAL 9 DAY),
(20, @mod, 'Usando el Kit Educativo en clase: ideas de prácticas',
 'Vamos a usar el Kit Educativo B2B en un aula. Busco ideas de prácticas progresivas para empezar de cero. Si tenéis material que haya funcionado, se agradece.',
 '', 8, NOW() - INTERVAL 10 DAY),
(21, @js,  '¿Qué incluye exactamente el Pack de Firmwares?',
 'Antes de comprar el Pack de Firmwares Oficiales, ¿alguien confirma qué builds entran y si se actualiza? Gracias.',
 '', 9, NOW() - INTERVAL 6 DAY);

INSERT INTO replies (post_id, user_id, body, created_at) VALUES
(12, @ml,    'Empieza por el menú de radio y lee algún mando que tengas por casa; es la forma más rápida de ver que todo va. Luego ya pasas a NFC.', NOW() - INTERVAL 40 HOUR),
(12, @admin, 'Bienvenido. Mira el hilo de normas y el de firmware fijado; con eso no te pierdes. Cualquier cosa, pregunta por aquí.', NOW() - INTERVAL 20 HOUR),
(15, @js,    '¿Confirmas si los pines de la fila de arriba toleran 5V con divisor? No quiero cargarme la placa.', NOW() - INTERVAL 100 HOUR),
(16, @ml,    'A mí en un sitio con mucho AP me baja un poco, pero aguanta. Con el módulo bien orientado mejora bastante.', NOW() - INTERVAL 60 HOUR);
