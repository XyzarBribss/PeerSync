create database PEERS_DB;
use PEERS_DB;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

 CREATE TABLE `bubbles` (
    `id` int(11) NOT NULL,
    `bubble_name` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `creator_id` int(11) NOT NULL,
    `profile_image` longblob DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
  
  CREATE TABLE `bubble_comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `bubble_message` (
  `id` int(11) NOT NULL,
  `bubble_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `bubble_posts` (
  `id` int(11) NOT NULL,
  `bubble_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `image` blob DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `direct_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user_bubble` (
  `user_id` int(11) NOT NULL,
  `bubble_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `bubbles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `creator_id` (`creator_id`);
  
  ALTER TABLE `bubble_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `bubble_message`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bubble_id` (`bubble_id`),
  ADD KEY `user_id` (`user_id`);
  
  ALTER TABLE `bubble_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bubble_id` (`bubble_id`),
  ADD KEY `user_id` (`user_id`);
  
  ALTER TABLE `direct_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);
  
  ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);
  
  ALTER TABLE `user_bubble`
  ADD PRIMARY KEY (`user_id`,`bubble_id`),
  ADD KEY `bubble_id` (`bubble_id`);
  
  ALTER TABLE `bubbles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;
  
  ALTER TABLE `bubble_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
  
  ALTER TABLE `bubble_message`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;
  
  ALTER TABLE `bubble_posts`
  ADD CONSTRAINT `bubble_posts_ibfk_1` FOREIGN KEY (`bubble_id`) REFERENCES `bubbles` (`id`),
  ADD CONSTRAINT `bubble_posts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
  
  ALTER TABLE `direct_messages`
  ADD CONSTRAINT `direct_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `direct_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`);
  
  ALTER TABLE `user_bubble`
  ADD CONSTRAINT `user_bubble_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_bubble_ibfk_2` FOREIGN KEY (`bubble_id`) REFERENCES `bubbles` (`id`) ON DELETE CASCADE;
COMMIT;

ALTER TABLE users
ADD COLUMN status ENUM('active', 'inactive', 'suspended') DEFAULT 'active';
