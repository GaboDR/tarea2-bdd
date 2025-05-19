-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-05-2025 a las 03:16:18
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

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `actualizar_revision` (IN `p_id` INT, IN `p_puntuacion_global` INT, IN `p_comentarios` TEXT, IN `p_originalidad` INT, IN `p_claridad` INT, IN `p_relevancia` INT)   BEGIN
    DECLARE v_articulo_id INT;
    DECLARE v_num_rev INT;

    -- Manejo de errores: si no se encuentra algún valor
    DECLARE CONTINUE HANDLER FOR NOT FOUND
    BEGIN
        -- Silencio o manejo de errores personalizados (opcional)
    END;

    -- Actualizar revisión
    UPDATE revision
    SET
        puntuacion_global = p_puntuacion_global,
        comentarios = p_comentarios,
        originalidad = p_originalidad,
        claridad = p_claridad,
        relevancia = p_relevancia
    WHERE ID = p_id;

    -- Obtener id del artículo relacionado
    SELECT ar.id_articulo INTO v_articulo_id
    FROM ARTICULO_REVISOR ar
    JOIN REVISION r ON r.ARTICULO_REVISOR_ID = ar.id
    WHERE r.id = p_id;

    -- Obtener cantidad de revisiones
    SELECT num_revisores INTO v_num_rev
    FROM ARTICULO
    WHERE id = v_articulo_id;

    -- Recalcular promedio si ya hay 3 revisiones
    IF v_num_rev = 3 THEN
        CALL calcular_promedio(v_articulo_id);
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AsignarRevisoresAutomaticamente` (IN `p_idArticulo` INT)   BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_rutRevisor VARCHAR(12) COLLATE utf8mb4_unicode_ci;
    DECLARE revisor_count INT DEFAULT 0;

    -- Cursor y handler
    DECLARE cur CURSOR FOR
        SELECT rutRevisor FROM RevisoresCandidatos;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    -- Crear tabla temporal
    DROP TEMPORARY TABLE IF EXISTS RevisoresCandidatos;
    CREATE TEMPORARY TABLE RevisoresCandidatos (
        rutRevisor VARCHAR(12) COLLATE utf8mb4_unicode_ci PRIMARY KEY
    );

    -- Insertar revisores válidos: al menos un tópico coincide y no son autores del artículo
    INSERT INTO RevisoresCandidatos(rutRevisor)
    SELECT DISTINCT r.RUT COLLATE utf8mb4_unicode_ci
    FROM Revisor r
    LEFT JOIN Especialidad_agregada ea ON r.ID = ea.ID_REVISOR
    WHERE (
        r.TOPICO_ESPECIALIDAD COLLATE utf8mb4_unicode_ci IN (
            SELECT TOPICO_PRINCIPAL COLLATE utf8mb4_unicode_ci FROM Articulo WHERE ID = p_idArticulo
            UNION
            SELECT TOPICO_EXTRA COLLATE utf8mb4_unicode_ci FROM Topicos_extra WHERE ID_ARTICULO = p_idArticulo
        )
        OR ea.ESPECIALIDAD_EXTRA COLLATE utf8mb4_unicode_ci IN (
            SELECT TOPICO_PRINCIPAL COLLATE utf8mb4_unicode_ci FROM Articulo WHERE ID = p_idArticulo
            UNION
            SELECT TOPICO_EXTRA COLLATE utf8mb4_unicode_ci FROM Topicos_extra WHERE ID_ARTICULO = p_idArticulo
        )
    )
    AND r.RUT COLLATE utf8mb4_unicode_ci NOT IN (
        SELECT a.RUT COLLATE utf8mb4_unicode_ci
        FROM Autor_participante ap
        JOIN Autor a ON ap.ID_AUTOR = a.ID
        WHERE ap.ID_ARTICULO = p_idArticulo
    );

    -- Cursor para seleccionar hasta 3 revisores
    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO v_rutRevisor;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Insertar relación en Articulo_revisor
        INSERT INTO Articulo_revisor (ID_ARTICULO, ID_REVISOR)
        SELECT p_idArticulo, r.ID
        FROM Revisor r
        WHERE r.RUT COLLATE utf8mb4_unicode_ci = v_rutRevisor;

        SET revisor_count = revisor_count + 1;
        IF revisor_count >= 3 THEN
            LEAVE read_loop;
        END IF;
    END LOOP;

    CLOSE cur;
    DROP TEMPORARY TABLE IF EXISTS RevisoresCandidatos;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_eliminar_revision` (IN `p_id` INT)   BEGIN
    DELETE FROM revision
    WHERE ID = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_guardar_revision` (IN `p_articulo_revisor_id` INT, IN `p_puntuacion_global` INT, IN `p_comentarios` TEXT, IN `p_originalidad` INT, IN `p_claridad` INT, IN `p_relevancia` INT)   BEGIN
    INSERT INTO revision (
        ARTICULO_REVISOR_ID,
        puntuacion_global,
        comentarios,
        originalidad,
        claridad,
        relevancia
    ) VALUES (
        p_articulo_revisor_id,
        p_puntuacion_global,
        p_comentarios,
        p_originalidad,
        p_claridad,
        p_relevancia
    );
END$$

--
-- Funciones
--
CREATE DEFINER=`root`@`localhost` FUNCTION `calcular_promedio_y_actualizar` (`articulo_id` INT) RETURNS DECIMAL(5,2) DETERMINISTIC BEGIN
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
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulo`
--

CREATE TABLE `articulo` (
  `ID` int(11) NOT NULL,
  `TITULO` varchar(50) NOT NULL,
  `AUTOR_CONTACTO` int(11) NOT NULL,
  `TOPICO_PRINCIPAL` varchar(50) NOT NULL,
  `FECHA_ENVIO` date NOT NULL DEFAULT curdate(),
  `RESUMEN` varchar(150) NOT NULL,
  `NUM_REVISORES` int(11) DEFAULT 0,
  `puntajeFinal` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `articulo`
--

INSERT INTO `articulo` (`ID`, `TITULO`, `AUTOR_CONTACTO`, `TOPICO_PRINCIPAL`, `FECHA_ENVIO`, `RESUMEN`, `NUM_REVISORES`, `puntajeFinal`) VALUES
(8, 'Porfa funciona', 5, 'environment', '0000-00-00', 'hay pura fe', 3, NULL),
(10, 'Hola', 8, 'culture', '0000-00-00', 'Prueba de correo enviado', 0, NULL),
(11, 'Prueba2', 9, 'education', '0000-00-00', 'test', 0, NULL),
(12, 'Chao', 9, 'education', '2025-05-18', 'Un resumen sin mas', 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulo_revisor`
--

CREATE TABLE `articulo_revisor` (
  `ID` int(11) NOT NULL,
  `ID_ARTICULO` int(11) NOT NULL,
  `ID_REVISOR` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `articulo_revisor`
--

INSERT INTO `articulo_revisor` (`ID`, `ID_ARTICULO`, `ID_REVISOR`) VALUES
(60, 10, 26);

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
(18, 'fashion'),
(18, 'health'),
(18, 'history'),
(20, 'education'),
(26, 'finance'),
(26, 'history'),
(27, 'education'),
(27, 'environment'),
(27, 'politics');

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

--
-- Disparadores `revision`
--
DELIMITER $$
CREATE TRIGGER `aumentar_num_revisores` AFTER INSERT ON `revision` FOR EACH ROW BEGIN
  DECLARE articulo_id INT;

  -- Obtener el ID del artículo a partir del ID de la relación articulo_revisor
  SELECT ID_ARTICULO INTO articulo_id
  FROM articulo_revisor
  WHERE ID = NEW.ARTICULO_REVISOR_ID;

  -- Aumentar el contador en la tabla articulo
  UPDATE articulo

  SET NUM_REVISORES = NUM_REVISORES + 1
  WHERE ID = articulo_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `disminuir_num_revisores` AFTER DELETE ON `revision` FOR EACH ROW BEGIN
  DECLARE articulo_id INT;

  -- Obtener el ID del artículo a partir de la relación articulo_revisor
  SELECT ID_ARTICULO INTO articulo_id
  FROM articulo_revisor
  WHERE ID = OLD.ARTICULO_REVISOR_ID;

  -- Disminuir el contador en la tabla articulo
  UPDATE articulo
  SET NUM_REVISORES = NUM_REVISORES - 1
  WHERE ID = articulo_id;

  -- Setear puntajeFinal a NULL al eliminar una revisión
  UPDATE articulo
  SET puntajeFinal = NULL
  WHERE ID = articulo_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trigger_actualizar_promedio_cuando_3` AFTER INSERT ON `revision` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

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
(18, '21854002-3', 'Martina', 'martiviva3@gmail.com', 'education', '$2y$10$AW79LoTD.6m89tJDh33kOO/8qMLlRlabbmGsjhcZcLqHFIEMYHb9G'),
(20, '44444444-4', 'Kitty', 'kitty@gmail.com', 'culture', '$2y$10$vwr.gXU.YLEhFes5F9.Z2utMmoyzqeKXM1faqDLIEDZzu7x.IbCwO'),
(26, '11111111-1', 'Lulu', 'lulu@gmail.com', 'culture', '$2y$10$yuoWEwMeYlQT.HdEOcuGsOP1QuC.iOf0UMr4oIg74/kOfc16rcEwu'),
(27, '77777777-7', 'UserPrueba', 'pruebauser@gmail.com', 'culture', '$2y$10$jVEtqzxmqkMvg4BXDVhzSuXOF0FsBhBBJgrQucAyvR8FZ..qaqf/q');

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
,`especialidadesRevisor` longtext
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
(10, 'science'),
(12, 'food');

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
-- Estructura Stand-in para la vista `vista_articulos_autores_revisores`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_articulos_autores_revisores` (
`articulo_id` int(11)
,`titulo_articulo` varchar(50)
,`autores` mediumtext
,`topicos` mediumtext
,`revisores` mediumtext
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_filtros_busqueda`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_filtros_busqueda` (
`id_articulo` int(11)
,`id_autores` mediumtext
,`fecha_envio` date
,`topicos` mediumtext
,`id_revisores` mediumtext
,`resumen` varchar(150)
,`titulo` varchar(50)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_revisores_completa`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_revisores_completa` (
`Revisor_ID` int(11)
,`Revisor_Nombre` varchar(50)
,`Revisor_Rut` varchar(10)
,`Todas_Especialidades` mediumtext
,`Articulos_Asignados` mediumtext
,`id_Articulos_Asignados` mediumtext
);

-- --------------------------------------------------------

--
-- Estructura para la vista `revisoryespecialidad`
--
DROP TABLE IF EXISTS `revisoryespecialidad`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `revisoryespecialidad`  AS SELECT `r`.`ID` AS `id`, `r`.`NOMBRE` AS `nombre`, `r`.`RUT` AS `rut`, `r`.`EMAIL` AS `email`, concat(`r`.`TOPICO_ESPECIALIDAD`,if(`extra`.`especialidades` is not null,concat(', ',`extra`.`especialidades`),'')) AS `especialidadesRevisor`, CASE WHEN `jc`.`RUT` is not null THEN 1 ELSE 0 END AS `esJefeComite` FROM ((`revisor` `r` left join (select `especialidad_agregada`.`ID_REVISOR` AS `ID_REVISOR`,group_concat(`especialidad_agregada`.`ESPECIALIDAD_EXTRA` separator ', ') AS `especialidades` from `especialidad_agregada` group by `especialidad_agregada`.`ID_REVISOR`) `extra` on(`r`.`ID` = `extra`.`ID_REVISOR`)) left join `jefe_comite` `jc` on(`jc`.`RUT` = `r`.`RUT`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_articulos_autores_revisores`
--
DROP TABLE IF EXISTS `vista_articulos_autores_revisores`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_articulos_autores_revisores`  AS SELECT `a`.`ID` AS `articulo_id`, `a`.`TITULO` AS `titulo_articulo`, group_concat(distinct concat(`aut`.`Nombre`) separator ', ') AS `autores`, group_concat(distinct `topico`.`Nombre` separator ', ') AS `topicos`, group_concat(distinct concat(`r`.`NOMBRE`) separator ', ') AS `revisores` FROM (((((((((`articulo` `a` left join `autor` `autor_principal` on(`autor_principal`.`ID` = `a`.`AUTOR_CONTACTO`)) left join `autor_participante` `ap` on(`ap`.`ID_ARTICULO` = `a`.`ID`)) left join `autor` `aut_part` on(`ap`.`ID_AUTOR` = `aut_part`.`ID`)) left join (select `autor`.`ID` AS `ID`,`autor`.`NOMBRE` AS `Nombre`,`autor`.`EMAIL` AS `Email` from `autor`) `aut` on(`aut`.`ID` = `a`.`AUTOR_CONTACTO` or `aut`.`ID` in (select `autor_participante`.`ID_AUTOR` from `autor_participante` where `autor_participante`.`ID_ARTICULO` = `a`.`ID`))) left join (select distinct `topico_especialidad`.`NOMBRE` AS `Nombre` from `topico_especialidad`) `topico_principal` on(`topico_principal`.`Nombre` = `a`.`TOPICO_PRINCIPAL`)) left join `topicos_extra` `te` on(`te`.`ID_ARTICULO` = `a`.`ID`)) left join (select `topico_especialidad`.`NOMBRE` AS `Nombre` from `topico_especialidad` union select `topicos_extra`.`TOPICO_EXTRA` AS `Nombre` from `topicos_extra`) `topico` on(`topico`.`Nombre` = `a`.`TOPICO_PRINCIPAL` or `topico`.`Nombre` = `te`.`TOPICO_EXTRA`)) left join `articulo_revisor` `ar` on(`ar`.`ID_ARTICULO` = `a`.`ID`)) left join `revisor` `r` on(`r`.`ID` = `ar`.`ID_REVISOR`)) GROUP BY `a`.`ID`, `a`.`TITULO` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_filtros_busqueda`
--
DROP TABLE IF EXISTS `vista_filtros_busqueda`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_filtros_busqueda`  AS SELECT `a`.`ID` AS `id_articulo`, group_concat(distinct `ap`.`ID_AUTOR` order by `ap`.`ID_AUTOR` ASC separator ', ') AS `id_autores`, `a`.`FECHA_ENVIO` AS `fecha_envio`, concat_ws(', ',`a`.`TOPICO_PRINCIPAL`,group_concat(distinct `te`.`TOPICO_EXTRA` order by `te`.`TOPICO_EXTRA` ASC separator ', ')) AS `topicos`, group_concat(distinct `ar`.`ID_REVISOR` order by `ar`.`ID_REVISOR` ASC separator ', ') AS `id_revisores`, `a`.`RESUMEN` AS `resumen`, `a`.`TITULO` AS `titulo` FROM (((`articulo` `a` left join `autor_participante` `ap` on(`ap`.`ID_ARTICULO` = `a`.`ID`)) left join `topicos_extra` `te` on(`te`.`ID_ARTICULO` = `a`.`ID`)) left join `articulo_revisor` `ar` on(`ar`.`ID_ARTICULO` = `a`.`ID`)) GROUP BY `a`.`ID`, `a`.`FECHA_ENVIO`, `a`.`RESUMEN`, `a`.`TITULO`, `a`.`TOPICO_PRINCIPAL` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_revisores_completa`
--
DROP TABLE IF EXISTS `vista_revisores_completa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_revisores_completa`  AS SELECT `r`.`ID` AS `Revisor_ID`, `r`.`NOMBRE` AS `Revisor_Nombre`, `r`.`RUT` AS `Revisor_Rut`, concat(`te`.`NOMBRE`,ifnull(concat(', ',(select group_concat(`ea`.`ESPECIALIDAD_EXTRA` separator ', ') from (`especialidad_agregada` `ea` join `topico_especialidad` `te_extra` on(`ea`.`ESPECIALIDAD_EXTRA` = `te_extra`.`NOMBRE`)) where `ea`.`ID_REVISOR` = `r`.`ID`)),'')) AS `Todas_Especialidades`, (select group_concat(`a`.`TITULO` separator '| ') from (`articulo_revisor` `ar` join `articulo` `a` on(`ar`.`ID_ARTICULO` = `a`.`ID`)) where `ar`.`ID_REVISOR` = `r`.`ID`) AS `Articulos_Asignados`, (select group_concat(`a`.`ID` separator '| ') from (`articulo_revisor` `ar` join `articulo` `a` on(`ar`.`ID_ARTICULO` = `a`.`ID`)) where `ar`.`ID_REVISOR` = `r`.`ID`) AS `id_Articulos_Asignados` FROM (`revisor` `r` left join `topico_especialidad` `te` on(`r`.`TOPICO_ESPECIALIDAD` = `te`.`NOMBRE`)) ;

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
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `articulo_revisor`
--
ALTER TABLE `articulo_revisor`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

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
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

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
