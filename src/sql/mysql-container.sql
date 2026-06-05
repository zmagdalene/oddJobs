-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: mysql-container
-- Generation Time: Apr 15, 2026 at 11:54 AM
-- Server version: 8.0.44
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `book_store`
--
CREATE DATABASE IF NOT EXISTS `book_store` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `book_store`;

-- --------------------------------------------------------

--
-- Table structure for table `Books`
--

CREATE TABLE `Books` (
  `book_id` int NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Books`
--

INSERT INTO `Books` (`book_id`, `title`, `price`) VALUES
(100, '1984', 15.99),
(101, 'To Kill a Mockingbird', 10.50),
(102, 'The Great Gatsby', 11.99),
(103, 'Learn to Program in One Day!', 14.60);

-- --------------------------------------------------------

--
-- Table structure for table `Customers`
--

CREATE TABLE `Customers` (
  `customer_id` int NOT NULL,
  `name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Customers`
--

INSERT INTO `Customers` (`customer_id`, `name`) VALUES
(1, 'Maire'),
(2, 'Jack'),
(3, 'James'),
(4, 'Emily'),
(5, 'Aoife');

-- --------------------------------------------------------

--
-- Table structure for table `Orders`
--

CREATE TABLE `Orders` (
  `order_id` int NOT NULL,
  `customer_id` int DEFAULT NULL,
  `order_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Orders`
--

INSERT INTO `Orders` (`order_id`, `customer_id`, `order_date`) VALUES
(200, 1, '2024-04-20'),
(201, 2, '2025-01-05'),
(202, 4, '2026-01-01');

-- --------------------------------------------------------

--
-- Table structure for table `Order_Items`
--

CREATE TABLE `Order_Items` (
  `order_id` int NOT NULL,
  `book_id` int NOT NULL,
  `quantity` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Order_Items`
--

INSERT INTO `Order_Items` (`order_id`, `book_id`, `quantity`) VALUES
(200, 100, 1),
(200, 101, 2),
(201, 103, 1),
(202, 101, 1),
(202, 103, 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Books`
--
ALTER TABLE `Books`
  ADD PRIMARY KEY (`book_id`);

--
-- Indexes for table `Customers`
--
ALTER TABLE `Customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `Orders`
--
ALTER TABLE `Orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `Order_Items`
--
ALTER TABLE `Order_Items`
  ADD PRIMARY KEY (`order_id`,`book_id`),
  ADD KEY `book_id` (`book_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Books`
--
ALTER TABLE `Books`
  MODIFY `book_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `Customers`
--
ALTER TABLE `Customers`
  MODIFY `customer_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `Orders`
--
ALTER TABLE `Orders`
  MODIFY `order_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=203;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Orders`
--
ALTER TABLE `Orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `Customers` (`customer_id`);

--
-- Constraints for table `Order_Items`
--
ALTER TABLE `Order_Items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `Orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `Books` (`book_id`);
--
-- Database: `local_jobs`
--
CREATE DATABASE IF NOT EXISTS `local_jobs` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `local_jobs`;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `application_id` int NOT NULL,
  `job_id` int DEFAULT NULL,
  `worker_id` int DEFAULT NULL,
  `application_status_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`application_id`, `job_id`, `worker_id`, `application_status_id`) VALUES
(1, 1, 3, 2),
(2, 2, 4, 1),
(3, 3, 3, 2),
(4, 3, 5, 1),
(5, 4, 4, 3),
(6, 4, 3, 2);

-- --------------------------------------------------------

--
-- Table structure for table `application_status`
--

CREATE TABLE `application_status` (
  `status_id` int NOT NULL,
  `status_name` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `application_status`
--

INSERT INTO `application_status` (`status_id`, `status_name`) VALUES
(2, 'accepted'),
(4, 'completed'),
(1, 'pending'),
(3, 'rejected');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `job_id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `title` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `pay` decimal(6,2) DEFAULT NULL,
  `family_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`job_id`, `category_id`, `title`, `description`, `pay`, `family_id`) VALUES
(1, 1, 'Babysitting Evening', 'Look after 2 kids for 3 hours', 25.00, 1),
(2, 5, 'Dog Walking', 'Walk medium dog once daily', 15.00, 1),
(3, 2, 'Maths Tutoring', 'Junior cert maths help', 30.00, 2),
(4, 3, 'House Cleaning', 'General cleaning of house', 40.00, 2);

-- --------------------------------------------------------

--
-- Table structure for table `job_category`
--

CREATE TABLE `job_category` (
  `category_id` int NOT NULL,
  `category_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `job_category`
--

INSERT INTO `job_category` (`category_id`, `category_name`) VALUES
(1, 'babysitting'),
(2, 'tutoring'),
(3, 'cleaning'),
(4, 'gardening'),
(5, 'animal_care'),
(6, 'errands'),
(7, 'general_help'),
(8, 'outdoor_work'),
(9, 'tech_help');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int NOT NULL,
  `job_id` int DEFAULT NULL,
  `reviewer_id` int DEFAULT NULL,
  `reviewee_id` int DEFAULT NULL,
  `rating` int DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `job_id`, `reviewer_id`, `reviewee_id`, `rating`, `comment`) VALUES
(1, 1, 1, 3, 5, 'Very responsible and great with kids'),
(2, 2, 1, 4, 4, 'Good job walking the dog'),
(3, 3, 2, 3, 5, 'Excellent tutoring, very helpful'),
(4, 4, 2, 4, 3, 'Average cleaning job'),
(5, 2, 2, 5, 5, 'Excellent and very reliable worker'),
(6, 1, 1, 5, 4, 'Good job, punctual and polite');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `user_type_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `user_type_id`) VALUES
(1, 'Sarah Murphy', 'sarahMurphy6@gmail.com', 1),
(2, 'John O’Brian', 'johnOBrian454@gmail.com', 1),
(3, 'Emma Kelly', 'emmaKelly09@gmail.com', 2),
(4, 'Liam Byrne', 'liamByrne123@gmail.com', 2),
(5, 'Aoife Nolan', 'aoifeNolan57@gmail.com', 2);

-- --------------------------------------------------------

--
-- Table structure for table `user_type`
--

CREATE TABLE `user_type` (
  `user_type_id` int NOT NULL,
  `user_type` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_type`
--

INSERT INTO `user_type` (`user_type_id`, `user_type`) VALUES
(1, 'family'),
(2, 'worker'),
(3, 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `application_status_id` (`application_status_id`);

--
-- Indexes for table `application_status`
--
ALTER TABLE `application_status`
  ADD PRIMARY KEY (`status_id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`job_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `family_id` (`family_id`);

--
-- Indexes for table `job_category`
--
ALTER TABLE `job_category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `reviewer_id` (`reviewer_id`),
  ADD KEY `reviewee_id` (`reviewee_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_type_id` (`user_type_id`);

--
-- Indexes for table `user_type`
--
ALTER TABLE `user_type`
  ADD PRIMARY KEY (`user_type_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `application_status`
--
ALTER TABLE `application_status`
  MODIFY `status_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `job_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `job_category`
--
ALTER TABLE `job_category`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_type`
--
ALTER TABLE `user_type`
  MODIFY `user_type_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`),
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`application_status_id`) REFERENCES `application_status` (`status_id`);

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `job_category` (`category_id`),
  ADD CONSTRAINT `jobs_ibfk_2` FOREIGN KEY (`family_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`reviewee_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`user_type_id`) REFERENCES `user_type` (`user_type_id`);
--
-- Database: `moviesdb`
--
CREATE DATABASE IF NOT EXISTS `moviesdb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `moviesdb`;

-- --------------------------------------------------------

--
-- Table structure for table `Directors`
--

CREATE TABLE `Directors` (
  `director_id` int NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `nationality` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Directors`
--

INSERT INTO `Directors` (`director_id`, `name`, `nationality`, `dob`) VALUES
(1, 'Christopher Nolan', 'English', '1970-07-30'),
(2, 'Jaume Collet-Serra', 'Spanish', '1974-03-23'),
(3, 'Paul Greengrass', 'English', '1955-08-13'),
(4, 'Sam Taylor-Johnson', 'English', '1967-03-04');

-- --------------------------------------------------------

--
-- Table structure for table `Movies`
--

CREATE TABLE `Movies` (
  `id` int NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `genre` varchar(255) DEFAULT NULL,
  `year` year DEFAULT NULL,
  `box_office` int DEFAULT NULL,
  `director_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Movies`
--

INSERT INTO `Movies` (`id`, `title`, `genre`, `year`, `box_office`, `director_id`) VALUES
(1, 'Oppenheimer', 'film noir', '2023', 954, 1),
(2, 'Dunkirk', 'action', '2017', 527, 1),
(3, 'Jungle Cruise', 'adventure', '2021', 221, 2),
(4, 'Jason Bourne', 'action-thriller', '2016', 415, 3),
(5, 'The Bourne Ultimatum', 'action-thriller', '2007', 444, 3),
(6, 'Back to Black', 'biopic', '2024', 51, 4);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Directors`
--
ALTER TABLE `Directors`
  ADD PRIMARY KEY (`director_id`);

--
-- Indexes for table `Movies`
--
ALTER TABLE `Movies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `director_id` (`director_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Directors`
--
ALTER TABLE `Directors`
  MODIFY `director_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `Movies`
--
ALTER TABLE `Movies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Movies`
--
ALTER TABLE `Movies`
  ADD CONSTRAINT `movies_ibfk_1` FOREIGN KEY (`director_id`) REFERENCES `Directors` (`director_id`);
--
-- Database: `movies_mtm`
--
CREATE DATABASE IF NOT EXISTS `movies_mtm` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `movies_mtm`;

-- --------------------------------------------------------

--
-- Table structure for table `actors`
--

CREATE TABLE `actors` (
  `actor_id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `dob` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `actors`
--

INSERT INTO `actors` (`actor_id`, `name`, `nationality`, `dob`) VALUES
(1, 'Cillian Murphy', 'Irish', '1976-05-25'),
(2, 'Emily Blunt', 'English', '1983-02-23'),
(3, 'Matt Damon', 'American', '1970-10-08'),
(4, 'Marisa Abela', 'English', '1996-12-07'),
(5, 'Harry Styles', 'English', '1994-02-01'),
(6, 'Jack Whitehall', 'English', '1988-07-07'),
(7, 'Julia Styles Styles', 'American', '1981-03-28'),
(8, 'Leslie Manville', 'English', '1956-03-12'),
(9, 'Jack O\'Connell', 'English', '1990-08-01');

-- --------------------------------------------------------

--
-- Table structure for table `actor_movie`
--

CREATE TABLE `actor_movie` (
  `actor_id` int NOT NULL,
  `movie_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `actor_movie`
--

INSERT INTO `actor_movie` (`actor_id`, `movie_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(1, 2),
(5, 2),
(2, 3),
(6, 3),
(3, 4),
(7, 4),
(3, 5),
(7, 5),
(4, 6),
(8, 6),
(9, 6);

-- --------------------------------------------------------

--
-- Table structure for table `directors`
--

CREATE TABLE `directors` (
  `director_id` int NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `nationality` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `directors`
--

INSERT INTO `directors` (`director_id`, `name`, `nationality`, `dob`) VALUES
(1, 'Christopher Nolan', 'English', '1970-07-30'),
(2, 'Jaume Collet-Serra', 'Spanish', '1974-03-23'),
(3, 'Paul Greengrass', 'English', '1955-08-13'),
(4, 'Sam Taylor-Johnson', 'English', '1967-03-04');

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

CREATE TABLE `movies` (
  `movie_id` int NOT NULL,
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `genre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year` year DEFAULT NULL,
  `box_office` decimal(10,0) NOT NULL,
  `director_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `movies`
--

INSERT INTO `movies` (`movie_id`, `title`, `genre`, `year`, `box_office`, `director_id`) VALUES
(1, 'Oppenheimer', 'Film Noir', '2023', 954, 1),
(2, 'Dunkirk', 'Action', '2017', 527, 1),
(3, 'Jungle Cruise', 'Adventure', '2021', 221, 2),
(4, 'Jason Bourne', 'action-thriller', '2016', 415, 3),
(5, 'The Bourne Ultimatum', 'action-thriller', '2007', 444, 3),
(6, 'Back to Black', 'biopic', '2024', 0, 4);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `actors`
--
ALTER TABLE `actors`
  ADD PRIMARY KEY (`actor_id`);

--
-- Indexes for table `actor_movie`
--
ALTER TABLE `actor_movie`
  ADD PRIMARY KEY (`actor_id`,`movie_id`),
  ADD KEY `movie_id` (`movie_id`);

--
-- Indexes for table `directors`
--
ALTER TABLE `directors`
  ADD PRIMARY KEY (`director_id`);

--
-- Indexes for table `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`movie_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `actors`
--
ALTER TABLE `actors`
  MODIFY `actor_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `directors`
--
ALTER TABLE `directors`
  MODIFY `director_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `movies`
--
ALTER TABLE `movies`
  MODIFY `movie_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `actor_movie`
--
ALTER TABLE `actor_movie`
  ADD CONSTRAINT `actor_movie_ibfk_1` FOREIGN KEY (`actor_id`) REFERENCES `actors` (`actor_id`),
  ADD CONSTRAINT `actor_movie_ibfk_2` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`movie_id`);
--
-- Database: `movies_zoembikakeu`
--
CREATE DATABASE IF NOT EXISTS `movies_zoembikakeu` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `movies_zoembikakeu`;

-- --------------------------------------------------------

--
-- Table structure for table `actors_table`
--

CREATE TABLE `actors_table` (
  `actor_id` int NOT NULL,
  `actor_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `movie_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `actors_table`
--

INSERT INTO `actors_table` (`actor_id`, `actor_name`, `movie_id`) VALUES
(1, 'Leonardo DiCaprio', 1),
(2, 'Matthew McConaughey', 2),
(3, 'Killian Murphy', 3),
(5, 'Timothy Chalamet', 7);

-- --------------------------------------------------------

--
-- Table structure for table `directors_table`
--

CREATE TABLE `directors_table` (
  `director_id` int NOT NULL,
  `director_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `directors_table`
--

INSERT INTO `directors_table` (`director_id`, `director_name`) VALUES
(1, 'Christopher Nolan'),
(2, 'Jaume Collet-Serra');

-- --------------------------------------------------------

--
-- Table structure for table `movies_table`
--

CREATE TABLE `movies_table` (
  `movie_id` int NOT NULL,
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `release_year` year DEFAULT NULL,
  `box_office` decimal(10,1) NOT NULL,
  `genre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `director_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `movies_table`
--

INSERT INTO `movies_table` (`movie_id`, `title`, `release_year`, `box_office`, `genre`, `director_id`) VALUES
(1, 'Inception', '2010', 829.9, 'Science Fiction', 1),
(3, 'Dunkerque', '2017', 527.0, 'War', 1),
(4, 'Jungle Cruise', '2021', 100.0, 'Adventure', 2),
(5, 'The Dark Knight', '2008', 1004.9, 'Action', 1),
(6, 'Oppenheimer', '2023', 952.0, 'Drama', 1),
(7, 'Dune: Part Two', '2024', 714.8, 'Science Fiction', 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `actors_table`
--
ALTER TABLE `actors_table`
  ADD PRIMARY KEY (`actor_id`);

--
-- Indexes for table `directors_table`
--
ALTER TABLE `directors_table`
  ADD PRIMARY KEY (`director_id`);

--
-- Indexes for table `movies_table`
--
ALTER TABLE `movies_table`
  ADD PRIMARY KEY (`movie_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `actors_table`
--
ALTER TABLE `actors_table`
  MODIFY `actor_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `directors_table`
--
ALTER TABLE `directors_table`
  MODIFY `director_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `movies_table`
--
ALTER TABLE `movies_table`
  MODIFY `movie_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
--
-- Database: `retail_store`
--
CREATE DATABASE IF NOT EXISTS `retail_store` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `retail_store`;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int NOT NULL,
  `first_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `first_name`, `last_name`, `email`, `phone`) VALUES
(1, 'Emma', 'Williams', 'emma@example.com', '555-234-7878'),
(2, 'Liam', 'Johnson', 'liam@example.com', '555-434-9876');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int NOT NULL,
  `customer_id` int DEFAULT NULL,
  `product_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);
--
-- Database: `schooldb`
--
CREATE DATABASE IF NOT EXISTS `schooldb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `schooldb`;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int NOT NULL,
  `course_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `credits` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_name`, `credits`) VALUES
(1, 'Database Systems', 3),
(2, 'Computer Networks', 4),
(3, 'Software Engineering', 3);

-- --------------------------------------------------------

--
-- Table structure for table `enrolments`
--

CREATE TABLE `enrolments` (
  `enrolment_id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `course_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrolments`
--

INSERT INTO `enrolments` (`enrolment_id`, `student_id`, `course_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 2, 1),
(5, 3, 2),
(6, 3, 3);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int NOT NULL,
  `student_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `age` int DEFAULT NULL,
  `enrolment_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `student_name`, `age`, `enrolment_date`) VALUES
(1, 'Alice Johnson', 21, '2021-09-01'),
(2, 'Bob Smith', 22, '2020-09-01'),
(3, 'Charlie Davis', 24, '2019-09-01'),
(4, 'Sarah Mason', 20, '2021-08-02'),
(5, 'John Murphy', 22, '2020-10-03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `enrolments`
--
ALTER TABLE `enrolments`
  ADD PRIMARY KEY (`enrolment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `enrolments`
--
ALTER TABLE `enrolments`
  MODIFY `enrolment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `enrolments`
--
ALTER TABLE `enrolments`
  ADD CONSTRAINT `enrolments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `enrolments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`);
--
-- Database: `tasksdb`
--
CREATE DATABASE IF NOT EXISTS `tasksdb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `tasksdb`;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int NOT NULL,
  `body` char(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `body`) VALUES
(1, 'Finish the project documentation'),
(2, 'Review the codebase for bugs'),
(3, 'Prepare for the client meeting'),
(4, 'Deploy the latest build to production');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- Database: `testdb`
--
CREATE DATABASE IF NOT EXISTS `testdb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `testdb`;

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `id` int NOT NULL,
  `body` char(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`id`, `body`) VALUES
(1, 'Hello how are you today?'),
(2, 'Today is a great day'),
(3, 'The weather is nice'),
(4, 'I am learning Docker');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
