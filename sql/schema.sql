CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(255) NOT NULL,
  email VARCHAR(255),
  phone VARCHAR(50),
  company VARCHAR(255),
  address TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  unit VARCHAR(20),
  vat_rate DECIMAL(5,2),
  price DECIMAL(10,2),
  currency VARCHAR(10),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE providers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  website VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  product_id INT,
  provider_id INT,
  site_name VARCHAR(255),
  service_type VARCHAR(50),
  start_date DATE,
  due_date DATE,
  duration INT,
  unit VARCHAR(10),
  price DECIMAL(10,2),
  currency VARCHAR(10),
  vat_rate DECIMAL(5,2),
  price_try DECIMAL(10,2),
  status VARCHAR(20),
  notes TEXT,
  reminder_enabled TINYINT(1) DEFAULT 0,
  reminder_days VARCHAR(50),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (provider_id) REFERENCES providers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE service_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  service_id INT NOT NULL,
  item_name VARCHAR(255),
  quantity INT DEFAULT 1,
  unit VARCHAR(20),
  unit_price DECIMAL(10,2),
  vat_rate DECIMAL(5,2),
  currency VARCHAR(10),
  provider_id INT,
  description TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
  FOREIGN KEY (provider_id) REFERENCES providers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exchange_rates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rate_date DATE,
  usd_try DECIMAL(10,4),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  service_id INT,
  amount_try DECIMAL(10,2),
  amount_orig DECIMAL(10,2),
  currency VARCHAR(10),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255),
  role ENUM('admin','user') DEFAULT 'admin',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE settings (
  `key` VARCHAR(50) PRIMARY KEY,
  value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE provider_purchases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider_id INT NOT NULL,
  item_name VARCHAR(255),
  quantity INT DEFAULT 1,
  unit VARCHAR(20),
  unit_price DECIMAL(10,2),
  vat_rate DECIMAL(5,2),
  currency VARCHAR(10),
  purchase_date DATE,
  payment_date DATE,
  price_try DECIMAL(10,2),
  notes TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE email_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  service_id INT,
  client_id INT,
  email_to VARCHAR(255),
  subject VARCHAR(255),
  content TEXT,
  sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
  FOREIGN KEY (client_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE provider_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider_id INT NOT NULL,
  amount_try DECIMAL(10,2),
  amount_orig DECIMAL(10,2),
  currency VARCHAR(10),
  pay_date DATE,
  notes TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (email, password, role) VALUES
('info@precadmedya.com.tr', '$2y$12$g0QsFECHVjIwr2WhxPLLV.i/wskHA2S0VuZY0bowUph3KdXmaZ3MS', 'admin');

INSERT INTO settings (`key`, value) VALUES
('logo', ''),
('logo_login_width','140'),
('logo_login_height','40'),
('logo_header_width','120'),
('logo_header_height','40'),
('mail_logo',''),
('smtp_host','smtp.yandex.com.tr'),
('smtp_port','465'),
('smtp_encryption','ssl'),
('smtp_user','info@precadmedya.com.tr'),
('smtp_pass','Precadmedya34523'),
('smtp_from_name','Precad Medya'),
('smtp_from_email','info@precadmedya.com.tr'),
('footer_text','Precad Medya 2025 Tüm Hakları Saklıdır.'),
('footer_logo',''),
('footer_logo_width','120'),
('footer_logo_height','40');
