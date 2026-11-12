-- Esquema compartido tienda + foro, y datos sembrados.
-- Se ejecuta automáticamente al inicializar MariaDB con el data dir vacío.

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

CREATE TABLE posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  title VARCHAR(255),
  body TEXT,
  embed_url VARCHAR(512),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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

-- Post de bienvenida (opcional) para que el foro no arranque vacio.
INSERT INTO posts (user_id, title, body, embed_url) VALUES
  (1, 'Bienvenido al foro de Shellyx',
   'Aqui compartimos modulos, firmwares y retos. Usa "Nueva publicacion" para abrir un hilo y puedes incrustar la vista previa de un recurso externo por URL.',
   NULL);
