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

-- Producto demo (eliminar al personalizar el catálogo).
INSERT INTO products (name, price, description) VALUES
  ('Camiseta Demo', 9.99, 'Producto de ejemplo; eliminar al personalizar');

-- Usuarios del foro: contraseñas del top 1000 de rockyou, guardadas como MD5.
INSERT INTO users (username, password_md5) VALUES
  ('admin',     MD5('letmein')),
  ('moderador', MD5('password')),
  ('jsanchez',  MD5('iloveyou')),
  ('mlopez',    MD5('qwerty')),
  ('agarcia',   MD5('sunshine'));
