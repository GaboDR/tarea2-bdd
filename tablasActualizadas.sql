-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-05-2025 a las 20:21:55
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `tarea2`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulo`
--

CREATE TABLE `articulo` (
  `ID` int(11) NOT NULL,
  `TITULO` varchar(50) NOT NULL,
  `AUTOR_CONTACTO` int(11) NOT NULL,
  `TOPICO_PRINCIPAL` varchar(50) NOT NULL,
  `FECHA_ENVIO` date NOT NULL,
  `RESUMEN` varchar(150) NOT NULL,
  `NUM_REVISORES` int(11) DEFAULT 0,
  `puntajeFinal` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `articulo`
--

INSERT INTO `articulo` (`ID`, `TITULO`, `AUTOR_CONTACTO`, `TOPICO_PRINCIPAL`, `FECHA_ENVIO`, `RESUMEN`, `NUM_REVISORES`, `puntajeFinal`) VALUES
(8, 'Porfa funciona', 5, 'environment', '0000-00-00', 'hay pura fe', 0, NULL),
(10, 'Hola', 8, 'culture', '0000-00-00', 'Prueba de correo enviado', 0, NULL),
(11, 'Prueba2', 9, 'education', '0000-00-00', 'test', 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulo_revisor`
--

CREATE TABLE `articulo_revisor` (
  `ID` int(11) NOT NULL,
  `ID_ARTICULO` int(11) NOT NULL,
  `ID_REVISOR` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `autor`
--

CREATE TABLE `autor` (
  `ID` int(11) NOT NULL,
  `RUT` varchar(10) NOT NULL,
  `NOMBRE` varchar(50) NOT NULL,
  `EMAIL` varchar(50) NOT NULL,
  `CONTRASENA` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `autor`
--

INSERT INTO `autor` (`ID`, `RUT`, `NOMBRE`, `EMAIL`, `CONTRASENA`) VALUES
(5, '27148851-3', 'Gabriel Delgado', 'gaboo90685@gmail.com', '$2y$10$iCW.3vj18wHt27QDPFs34OdkKcNOcOL9celX0refCl08WTxZu/6Ki'),
(6, '27148786-K', 'Gabo', 'gabo@test.com', '$2y$10$W8lZ0sp3kGU0gQLd3HFY1.5HsLKtQq/KsBOdqj/Jjm9WDZPW3SLXq'),
(8, '21690897-K', 'Jaime', 'jaimeg8877@gmail.com', '$2y$10$NgBDWrXmhustCu1ZCKScfuMvWaAx/ctoRiW0QiLFLyw1GJyvN9vGa'),
(9, '22222222-2', 'TestUser', 'testuser@test.com', '$2y$10$ZEt1Xup/zTvzXg63K0l9ie/as1fMA3foCuKfL.FSd71kNJl4ZzwMa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `autor_participante`
--

CREATE TABLE `autor_participante` (
  `ID_ARTICULO` int(11) NOT NULL,
  `ID_AUTOR` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `autor_participante`
--

INSERT INTO `autor_participante` (`ID_ARTICULO`, `ID_AUTOR`) VALUES
(8, 6),
(11, 8);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidad_agregada`
--

CREATE TABLE `especialidad_agregada` (
  `ID_REVISOR` int(11) NOT NULL,
  `ESPECIALIDAD_EXTRA` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `especialidad_agregada`
--

INSERT INTO `especialidad_agregada` (`ID_REVISOR`, `ESPECIALIDAD_EXTRA`) VALUES
(1, 'environment'),
(1, 'fashion'),
(1, 'finance'),
(1, 'food'),
(1, 'health'),
(1, 'history'),
(18, 'health'),
(18, 'history'),
(19, 'education'),
(19, 'environment'),
(20, 'education'),
(20, 'environment'),
(22, 'education'),
(22, 'environment');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jefe_comite`
--

CREATE TABLE `jefe_comite` (
  `RUT` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `jefe_comite`
--

INSERT INTO `jefe_comite` (`RUT`) VALUES
('21854002-3'),
('27148851-3'),
('44444444-4');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `revision`
--

CREATE TABLE `revision` (
  `ID` int(11) NOT NULL,
  `ARTICULO_REVISOR_ID` int(11) NOT NULL,
  `puntuacion_global` int(11) DEFAULT NULL CHECK (`puntuacion_global` between 1 and 10),
  `comentarios` text DEFAULT NULL,
  `originalidad` int(11) DEFAULT NULL CHECK (`originalidad` between 1 and 5),
  `claridad` int(11) DEFAULT NULL CHECK (`claridad` between 1 and 5),
  `relevancia` int(11) DEFAULT NULL CHECK (`relevancia` between 1 and 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `revisor`
--

CREATE TABLE `revisor` (
  `ID` int(11) NOT NULL,
  `RUT` varchar(10) NOT NULL,
  `NOMBRE` varchar(50) NOT NULL,
  `EMAIL` varchar(50) NOT NULL,
  `TOPICO_ESPECIALIDAD` varchar(50) NOT NULL,
  `CONTRASENA` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `revisor`
--

INSERT INTO `revisor` (`ID`, `RUT`, `NOMBRE`, `EMAIL`, `TOPICO_ESPECIALIDAD`, `CONTRASENA`) VALUES
(1, '27148851-3', 'Gabriel Delgado', 'gaboo90685@gmail.com', 'education', '$2y$10$GDVYDpOpF22waexwCwU3yu5aiYaQfj8UJ0bffyTWBh3T0FEBfVYh2'),
(18, '21854002-3', 'Martina', 'martiviva3@gmail.com', 'fashion', '$2y$10$AW79LoTD.6m89tJDh33kOO/8qMLlRlabbmGsjhcZcLqHFIEMYHb9G'),
(19, '99999999-9', 'Test', 'test@test.com', 'culture', '$2y$10$bnGPJJfFBd2nVcIJ5.tXFOUTcWBtIWxIqz.kZz.xCUfmEm4lTOtoe'),
(20, '44444444-4', 'Kitty', 'kitty@gmail.com', 'culture', '$2y$10$vwr.gXU.YLEhFes5F9.Z2utMmoyzqeKXM1faqDLIEDZzu7x.IbCwO'),
(22, '11111111-1', 'Lulu', 'lulu@gmail.com', 'culture', '$2y$10$BchJdCtIHWgp0UEPRRx5nuBunCeC.jnCPjoVchYw8NjWfW6.brSLG');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `revisoryespecialidad`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `revisoryespecialidad` (
`id` int(11)
,`nombre` varchar(50)
,`rut` varchar(10)
,`email` varchar(50)
,`especialidadesRevisor` mediumtext
,`esJefeComite` int(1)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `topicos_extra`
--

CREATE TABLE `topicos_extra` (
  `ID_ARTICULO` int(11) NOT NULL,
  `TOPICO_EXTRA` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `topicos_extra`
--

INSERT INTO `topicos_extra` (`ID_ARTICULO`, `TOPICO_EXTRA`) VALUES
(8, 'history'),
(8, 'science'),
(10, 'science');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `topico_especialidad`
--

CREATE TABLE `topico_especialidad` (
  `NOMBRE` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `topico_especialidad`
--

INSERT INTO `topico_especialidad` (`NOMBRE`) VALUES
('culture'),
('education'),
('environment'),
('fashion'),
('finance'),
('food'),
('health'),
('history'),
('literature'),
('music'),
('politics'),
('science'),
('sports'),
('technology'),
('travel');

-- --------------------------------------------------------

--
-- Estructura para la vista `revisoryespecialidad`
--
DROP TABLE IF EXISTS `revisoryespecialidad`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `revisoryespecialidad`  AS SELECT `revisor`.`ID` AS `id`, `revisor`.`NOMBRE` AS `nombre`, `revisor`.`RUT` AS `rut`, `revisor`.`EMAIL` AS `email`, group_concat(distinct coalesce(`e`.`ESPECIALIDAD_EXTRA`,`revisor`.`TOPICO_ESPECIALIDAD`) separator ', ') AS `especialidadesRevisor`, CASE WHEN `jc`.`RUT` is not null THEN 1 ELSE 0 END AS `esJefeComite` FROM ((`revisor` left join `especialidad_agregada` `e` on(`e`.`ID_REVISOR` = `revisor`.`ID`)) left join `jefe_comite` `jc` on(`revisor`.`RUT` = `jc`.`RUT`)) GROUP BY `revisor`.`ID`, `revisor`.`NOMBRE`, `revisor`.`RUT`, `revisor`.`EMAIL` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `articulo`
--
ALTER TABLE `articulo`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `UQ_AUTOR_TITULO` (`TITULO`,`AUTOR_CONTACTO`),
  ADD KEY `FK_TOPICO_PRINCIPAL` (`TOPICO_PRINCIPAL`),
  ADD KEY `FK_AUTOR_CONTACTO` (`AUTOR_CONTACTO`);

--
-- Indices de la tabla `articulo_revisor`
--
ALTER TABLE `articulo_revisor`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `UQ_ARTICULO_REVISOR` (`ID_ARTICULO`,`ID_REVISOR`),
  ADD KEY `FK_ID_REVISOR_TO_ARTICULO` (`ID_REVISOR`);

--
-- Indices de la tabla `autor`
--
ALTER TABLE `autor`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `RUT` (`RUT`),
  ADD UNIQUE KEY `EMAIL` (`EMAIL`);

--
-- Indices de la tabla `autor_participante`
--
ALTER TABLE `autor_participante`
  ADD PRIMARY KEY (`ID_ARTICULO`,`ID_AUTOR`),
  ADD KEY `FK_ID_AUTOR` (`ID_AUTOR`);

--
-- Indices de la tabla `especialidad_agregada`
--
ALTER TABLE `especialidad_agregada`
  ADD PRIMARY KEY (`ID_REVISOR`,`ESPECIALIDAD_EXTRA`),
  ADD KEY `FK_ESPECIALIDAD_EXTRA` (`ESPECIALIDAD_EXTRA`);

--
-- Indices de la tabla `jefe_comite`
--
ALTER TABLE `jefe_comite`
  ADD UNIQUE KEY `RUT` (`RUT`);

--
-- Indices de la tabla `revision`
--
ALTER TABLE `revision`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_ArticuloRevisor_Revision` (`ARTICULO_REVISOR_ID`);

--
-- Indices de la tabla `revisor`
--
ALTER TABLE `revisor`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `RUT` (`RUT`),
  ADD UNIQUE KEY `EMAIL` (`EMAIL`),
  ADD KEY `TOPICO_ESPECIALIDAD` (`TOPICO_ESPECIALIDAD`);

--
-- Indices de la tabla `topicos_extra`
--
ALTER TABLE `topicos_extra`
  ADD PRIMARY KEY (`ID_ARTICULO`,`TOPICO_EXTRA`),
  ADD KEY `FK_TOPICO_EXTRA` (`TOPICO_EXTRA`);

--
-- Indices de la tabla `topico_especialidad`
--
ALTER TABLE `topico_especialidad`
  ADD PRIMARY KEY (`NOMBRE`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `articulo`
--
ALTER TABLE `articulo`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `articulo_revisor`
--
ALTER TABLE `articulo_revisor`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `autor`
--
ALTER TABLE `autor`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `revision`
--
ALTER TABLE `revision`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `revisor`
--
ALTER TABLE `revisor`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Restricciones para tablas volcadas
--

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `articulo`
--
ALTER TABLE `articulo`
  ADD CONSTRAINT `FK_AUTOR_CONTACTO` FOREIGN KEY (`AUTOR_CONTACTO`) REFERENCES `autor` (`ID`),
  ADD CONSTRAINT `FK_TOPICO_PRINCIPAL` FOREIGN KEY (`TOPICO_PRINCIPAL`) REFERENCES `topico_especialidad` (`NOMBRE`);

--
-- Filtros para la tabla `articulo_revisor`
--
ALTER TABLE `articulo_revisor`
  ADD CONSTRAINT `FK_ID_ARTICULO_TO_REVISOR` FOREIGN KEY (`ID_ARTICULO`) REFERENCES `articulo` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_ID_REVISOR_TO_ARTICULO` FOREIGN KEY (`ID_REVISOR`) REFERENCES `revisor` (`ID`) ON DELETE CASCADE;

--
-- Filtros para la tabla `autor_participante`
--
ALTER TABLE `autor_participante`
  ADD CONSTRAINT `FK_ID_ARTICULO` FOREIGN KEY (`ID_ARTICULO`) REFERENCES `articulo` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_ID_AUTOR` FOREIGN KEY (`ID_AUTOR`) REFERENCES `autor` (`ID`) ON DELETE CASCADE;

--
-- Filtros para la tabla `especialidad_agregada`
--
ALTER TABLE `especialidad_agregada`
  ADD CONSTRAINT `FK_ESPECIALIDAD_EXTRA` FOREIGN KEY (`ESPECIALIDAD_EXTRA`) REFERENCES `topico_especialidad` (`NOMBRE`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_ID_REVISOR` FOREIGN KEY (`ID_REVISOR`) REFERENCES `revisor` (`ID`) ON DELETE CASCADE;

--
-- Filtros para la tabla `jefe_comite`
--
ALTER TABLE `jefe_comite`
  ADD CONSTRAINT `FK_rut_jefe_revisor` FOREIGN KEY (`RUT`) REFERENCES `revisor` (`RUT`) ON DELETE CASCADE;

--
-- Filtros para la tabla `revision`
--
ALTER TABLE `revision`
  ADD CONSTRAINT `FK_ArticuloRevisor_Revision` FOREIGN KEY (`ARTICULO_REVISOR_ID`) REFERENCES `articulo_revisor` (`ID`);

--
-- Filtros para la tabla `revisor`
--
ALTER TABLE `revisor`
  ADD CONSTRAINT `revisor_ibfk_1` FOREIGN KEY (`TOPICO_ESPECIALIDAD`) REFERENCES `topico_especialidad` (`NOMBRE`);

--
-- Filtros para la tabla `topicos_extra`
--
ALTER TABLE `topicos_extra`
  ADD CONSTRAINT `FK_ID_ARTICULO_TO_TOPICO` FOREIGN KEY (`ID_ARTICULO`) REFERENCES `articulo` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_TOPICO_EXTRA` FOREIGN KEY (`TOPICO_EXTRA`) REFERENCES `topico_especialidad` (`NOMBRE`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

--triggers y funtion

DELIMITER //
CREATE TRIGGER aumentar_num_revisores
AFTER INSERT ON revision
FOR EACH ROW
BEGIN
  DECLARE articulo_id INT;

  -- Obtener el ID del artículo a partir del ID de la relación articulo_revisor
  SELECT ID_ARTICULO INTO articulo_id
  FROM articulo_revisor
  WHERE ID = NEW.ARTICULO_REVISOR_ID;

  -- Aumentar el contador en la tabla articulo
  UPDATE articulo
  SET NUM_REVISORES = NUM_REVISORES + 1
  WHERE ID = articulo_id;
END;
//
DELIMITER ;


DELIMITER //

CREATE FUNCTION calcular_promedio_y_actualizar(articulo_id INT) 
RETURNS DECIMAL(5,2)
DETERMINISTIC
BEGIN
    DECLARE promedio DECIMAL(5,2);

    -- Calcular el promedio de puntuaciones para el artículo
    SELECT AVG(r.puntuacion_global)
    INTO promedio
    FROM REVISION r
    INNER JOIN ARTICULO_REVISOR ar ON r.ARTICULO_REVISOR_ID = ar.id
    WHERE ar.id_articulo = articulo_id;

    -- Actualizar el puntajeFinal del artículo
    UPDATE ARTICULO
    SET puntajeFinal = promedio
    WHERE id = articulo_id;

    RETURN promedio;
END;
//

DELIMITER ;


DELIMITER //

CREATE TRIGGER trigger_actualizar_promedio_cuando_3
AFTER INSERT ON REVISION
FOR EACH ROW
BEGIN
    DECLARE v_articulo_id INT;
    DECLARE total_revisiones INT;

    -- Obtener el ID del artículo desde ARTICULO_REVISOR
    SELECT id_articulo INTO v_articulo_id
    FROM ARTICULO_REVISOR
    WHERE id = NEW.ARTICULO_REVISOR_ID;

    -- Contar cuántas revisiones hay para ese artículo
    SELECT COUNT(*)
    INTO total_revisiones
    FROM REVISION r
    INNER JOIN ARTICULO_REVISOR ar ON r.ARTICULO_REVISOR_ID = ar.id
    WHERE ar.id_articulo = v_articulo_id;

    -- Si hay exactamente 3 revisiones, actualizar el puntaje final
    IF total_revisiones = 3 THEN
        CALL calcular_promedio_y_actualizar(v_articulo_id);
    END IF;
END;
//

DELIMITER ;
