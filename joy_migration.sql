-- Joy DB Migration Dump
-- Generated: 2026-03-28 10:59:42.585865

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table data: order_items
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES (1, 1, 11, 1, '4500.00');

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `address` text NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Processing','Shipped','Delivered','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table data: orders
INSERT INTO `orders` (`id`, `user_id`, `address`, `total_amount`, `status`, `created_at`) VALUES (1, 1, 'aqefa ADFASDF A', '4500.00', 'Pending', '2026-03-27 00:00:23');

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` enum('Pending','Paid','Failed') DEFAULT 'Pending',
  `payment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table data: payments
INSERT INTO `payments` (`id`, `order_id`, `amount`, `payment_method`, `payment_status`, `payment_date`) VALUES (1, 1, '4500.00', 'Credit Card', 'Pending', '2026-03-27 00:00:23');

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `stock` int DEFAULT '0',
  `category` varchar(100) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table data: products
INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock`, `category`, `image_url`, `created_at`) VALUES (11, 'Celestial Diadem Ring', 'A stunning 18k white gold ring featuring a 2-carat central diamond surrounded by a halo of micro-pave stones. Ideal for engagements or grand celebrations.', '4500.00', 5, 'Rings', 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?q=80&w=1000&auto=format&fit=crop', '2026-03-26 23:51:39');
INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock`, `category`, `image_url`, `created_at`) VALUES (12, 'Etheria Pearl Necklace', 'Lustrous South Sea pearls hand-strung on a delicate 22k yellow gold chain. A timeless piece that radiates sophistication.', '1200.50', 10, 'Necklaces', 'https://images.unsplash.com/photo-1599643477877-530eb83abc8e?q=80&w=1000&auto=format&fit=crop', '2026-03-26 23:51:39');
INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock`, `category`, `image_url`, `created_at`) VALUES (13, 'Midnight Velvet Sapphire Earrings', 'Deep blue sapphires encased in a vintage-inspired platinum setting. These earrings capture the essence of high-society elegance.', '2850.00', 3, 'Earrings', 'https://images.unsplash.com/photo-1635767798638-3e25d30925a9?q=80&w=1000&auto=format&fit=crop', '2026-03-26 23:51:39');
INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock`, `category`, `image_url`, `created_at`) VALUES (14, 'Gilded Serpent Bracelet', 'An intricately designed 24k gold bracelet with a modern snake-like wrap design. Features tiny emerald accents for eyes.', '3200.00', 7, 'Bracelets', 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?q=80&w=1000&auto=format&fit=crop', '2026-03-26 23:51:39');
INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock`, `category`, `image_url`, `created_at`) VALUES (15, 'Rose Gold Twilight Pendant', 'A heart-shaped rose gold pendant with a subtle pink tourmaline centerpiece. Perfect for a romantic gift.', '850.00', 15, 'Pendants', 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?q=80&w=1000&auto=format&fit=crop', '2026-03-26 23:51:39');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table data: users
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `role`, `created_at`) VALUES (1, 'md afsar', 'mdafsaransal@gmail.com', '$2y$12$eK6pqHGu02d5RuoRhxYR/egORXtIAKF0D8NJgzyVXr1KoThtW4iTC', '08235449893', 'Bhuli D block sec-6 q no.7, DHANBAD JHARKHAND', 'user', '2026-03-26 23:41:48');

SET FOREIGN_KEY_CHECKS = 1;
