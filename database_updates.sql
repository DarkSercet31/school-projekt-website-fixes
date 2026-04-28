-- ============================================================
-- database_updates.sql
-- Import this file via phpMyAdmin: Database > Import
-- Run AFTER the base schema (weather_station_db-db_2026-04-20.sql)
-- ============================================================

USE `weather_station_db`;

-- ----------------------------------------------------------
-- Email verification tokens (verify, reset, otp_login)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_tokens` (
  `token`       VARCHAR(64)                          NOT NULL,
  `fk_username` VARCHAR(50)                          NOT NULL,
  `type`        ENUM('verify','reset','otp_login')   NOT NULL,
  `expires_at`  DATETIME                             NOT NULL,
  PRIMARY KEY (`token`),
  FOREIGN KEY (`fk_username`) REFERENCES `user`(`pk_username`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- Shop products (managed by admin)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product` (
  `pk_product_id` INT           NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100)  NOT NULL,
  `description`   TEXT,
  `price`         DECIMAL(10,2) NOT NULL,
  `image_url`     VARCHAR(255)  DEFAULT NULL,
  `stock`         INT           DEFAULT 99,
  PRIMARY KEY (`pk_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- Shopping cart (one row per user+product)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cart` (
  `pk_cart_id`    INT         NOT NULL AUTO_INCREMENT,
  `fk_username`   VARCHAR(50) NOT NULL,
  `fk_product_id` INT         NOT NULL,
  `quantity`      INT         DEFAULT 1,
  PRIMARY KEY (`pk_cart_id`),
  UNIQUE KEY `uq_cart_user_product` (`fk_username`, `fk_product_id`),
  FOREIGN KEY (`fk_username`)   REFERENCES `user`(`pk_username`)   ON DELETE CASCADE,
  FOREIGN KEY (`fk_product_id`) REFERENCES `product`(`pk_product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- Orders header
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `pk_order_id` INT           NOT NULL AUTO_INCREMENT,
  `fk_username` VARCHAR(50)   NOT NULL,
  `ordered_at`  DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `total`       DECIMAL(10,2) NOT NULL,
  `status`      VARCHAR(20)   DEFAULT 'completed',
  PRIMARY KEY (`pk_order_id`),
  FOREIGN KEY (`fk_username`) REFERENCES `user`(`pk_username`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- Order line items (price/name snapshot at purchase time)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_item` (
  `pk_item_id`   INT           NOT NULL AUTO_INCREMENT,
  `fk_order_id`  INT           NOT NULL,
  `product_name` VARCHAR(100)  NOT NULL,
  `price`        DECIMAL(10,2) NOT NULL,
  `quantity`     INT           NOT NULL,
  PRIMARY KEY (`pk_item_id`),
  FOREIGN KEY (`fk_order_id`) REFERENCES `orders`(`pk_order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- User-to-user chat messages
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `message` (
  `pk_message_id` INT         NOT NULL AUTO_INCREMENT,
  `fk_sender`     VARCHAR(50) NOT NULL,
  `fk_receiver`   VARCHAR(50) NOT NULL,
  `body`          TEXT        NOT NULL,
  `sent_at`       DATETIME    DEFAULT CURRENT_TIMESTAMP,
  `is_read`       TINYINT(1)  DEFAULT 0,
  PRIMARY KEY (`pk_message_id`),
  INDEX `idx_receiver` (`fk_receiver`),
  FOREIGN KEY (`fk_sender`)   REFERENCES `user`(`pk_username`) ON DELETE CASCADE,
  FOREIGN KEY (`fk_receiver`) REFERENCES `user`(`pk_username`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- Support / complaint tickets
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `support_ticket` (
  `pk_ticket_id` INT          NOT NULL AUTO_INCREMENT,
  `fk_username`  VARCHAR(50)  NOT NULL,
  `subject`      VARCHAR(150) NOT NULL,
  `body`         TEXT         NOT NULL,
  `created_at`   DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk_ticket_id`),
  FOREIGN KEY (`fk_username`) REFERENCES `user`(`pk_username`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- Admin replies to support tickets
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `support_reply` (
  `pk_reply_id`  INT         NOT NULL AUTO_INCREMENT,
  `fk_ticket_id` INT         NOT NULL,
  `fk_admin`     VARCHAR(50) NOT NULL,
  `body`         TEXT        NOT NULL,
  `replied_at`   DATETIME    DEFAULT CURRENT_TIMESTAMP,
  `is_read`      TINYINT(1)  DEFAULT 0,
  PRIMARY KEY (`pk_reply_id`),
  FOREIGN KEY (`fk_ticket_id`) REFERENCES `support_ticket`(`pk_ticket_id`) ON DELETE CASCADE,
  FOREIGN KEY (`fk_admin`)     REFERENCES `user`(`pk_username`)             ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- Seed 5 demo products
-- ----------------------------------------------------------
INSERT IGNORE INTO `product` (`pk_product_id`, `name`, `description`, `price`, `image_url`) VALUES
(1, 'Temperature Sensor',  'Basic temperature sensor module for weather stations', 12.99, NULL),
(2, 'Humidity Sensor',     'DHT22 humidity and temperature sensor',                 9.99, NULL),
(3, 'Pressure Sensor',     'BMP280 barometric pressure sensor',                     7.49, NULL),
(4, 'Weather Station Kit', 'Complete starter kit with all sensors included',       39.99, NULL),
(5, 'Data Logger Module',  'SD card data logger for offline recording',            14.99, NULL);
