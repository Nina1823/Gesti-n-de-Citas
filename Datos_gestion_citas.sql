-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 02-02-2026 a las 23:03:51
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `gestion_citas`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `appointments`
--

CREATE TABLE `appointments` (
  `id_appointment` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('scheduled','cancelled','completed') DEFAULT 'scheduled',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `appointments`
--

INSERT INTO `appointments` (`id_appointment`, `user_id`, `service_id`, `provider_id`, `appointment_date`, `appointment_time`, `status`, `created_at`) VALUES
(1, 1, 1, 1, '2026-02-10', '09:00:00', 'scheduled', '2026-02-02 17:03:45'),
(2, 2, 2, 2, '2026-02-10', '10:00:00', 'scheduled', '2026-02-02 17:03:45'),
(3, 3, 3, 3, '2026-02-11', '14:00:00', 'scheduled', '2026-02-02 17:03:45'),
(4, 4, 1, 1, '2026-02-12', '11:30:00', 'scheduled', '2026-02-02 17:03:45'),
(5, 1, 4, 3, '2026-02-05', '15:00:00', 'completed', '2026-02-02 17:03:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `availability`
--

CREATE TABLE `availability` (
  `id_availability` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `availability`
--

INSERT INTO `availability` (`id_availability`, `provider_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(1, 1, 1, '08:00:00', '17:00:00'),
(2, 1, 2, '08:00:00', '17:00:00'),
(3, 1, 3, '08:00:00', '17:00:00'),
(4, 2, 1, '09:00:00', '16:00:00'),
(5, 2, 4, '09:00:00', '16:00:00'),
(6, 3, 2, '10:00:00', '18:00:00'),
(7, 3, 5, '10:00:00', '18:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `providers`
--

CREATE TABLE `providers` (
  `id_provider` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `description` varchar(400) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `providers`
--

INSERT INTO `providers` (`id_provider`, `name`, `email`, `description`, `created_at`) VALUES
(1, 'Dr. Carlos Mendoza', 'carlos@clinic.com', 'Médico general con 10 años de experiencia', '2026-02-02 17:03:45'),
(2, 'Dra. Ana López', 'ana@clinic.com', 'Especialista en pediatría', '2026-02-02 17:03:45'),
(3, 'Dr. Juan Pérez', 'juan@clinic.com', 'Odontólogo certificado', '2026-02-02 17:03:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `services`
--

CREATE TABLE `services` (
  `id_service` int(11) NOT NULL,
  `name_service` varchar(100) NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `services`
--

INSERT INTO `services` (`id_service`, `name_service`, `duration_minutes`, `created_at`) VALUES
(1, 'Consulta General', 30, '2026-02-02 17:03:45'),
(2, 'Consulta Pediátrica', 45, '2026-02-02 17:03:45'),
(3, 'Limpieza Dental', 60, '2026-02-02 17:03:45'),
(4, 'Extracción Dental', 90, '2026-02-02 17:03:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `document_type` enum('CC','CE','TI','PASAPORTE','NIT') NOT NULL,
  `document_number` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id_user`, `document_type`, `document_number`, `name`, `email`, `phone`, `created_at`) VALUES
(1, 'CC', '1234567890', 'María García', 'maria@email.com', '3001234567', '2026-02-02 17:03:45'),
(2, 'CE', '9876543210', 'Pedro Ramírez', 'pedro@email.com', '3109876543', '2026-02-02 17:03:45'),
(3, 'TI', '1122334455', 'Laura Martínez', 'laura@email.com', '3201122334', '2026-02-02 17:03:45'),
(4, 'PASAPORTE', 'AB123456', 'John Smith', 'john@email.com', '3159998877', '2026-02-02 17:03:45');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id_appointment`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indices de la tabla `availability`
--
ALTER TABLE `availability`
  ADD PRIMARY KEY (`id_availability`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indices de la tabla `providers`
--
ALTER TABLE `providers`
  ADD PRIMARY KEY (`id_provider`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id_service`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `document_type` (`document_type`,`document_number`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id_appointment` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `availability`
--
ALTER TABLE `availability`
  MODIFY `id_availability` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `providers`
--
ALTER TABLE `providers`
  MODIFY `id_provider` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `services`
--
ALTER TABLE `services`
  MODIFY `id_service` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id_service`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id_provider`);

--
-- Filtros para la tabla `availability`
--
ALTER TABLE `availability`
  ADD CONSTRAINT `availability_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id_provider`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
