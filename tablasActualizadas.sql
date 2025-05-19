-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 19, 2025 at 04:02 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tarea2`
--

DELIMITER $$
--
-- Procedures
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

    UNION

    SELECT a.RUT COLLATE utf8mb4_unicode_ci
    FROM Articulo art
    JOIN Autor a ON art.AUTOR_CONTACTO = a.ID
    WHERE art.ID = p_idArticulo
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
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `calcular_promedio_y_actualizar` (`articulo_id` INT) RETURNS DECIMAL(5,2) DETERMINISTIC BEGIN
    DECLARE promedio DECIMAL(5,2);

    -- Calcular el promedio
    SELECT AVG(r.puntuacion_global)
    INTO promedio
    FROM REVISION r
    INNER JOIN ARTICULO_REVISOR ar ON r.ARTICULO_REVISOR_ID = ar.id
    WHERE ar.id_articulo = articulo_id;

    -- Actualizar el puntaje en ARTICULO
    UPDATE ARTICULO
    SET puntajeFinal = promedio
    WHERE id = articulo_id;

    RETURN promedio;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `articulo`
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
-- Dumping data for table `articulo`
--

INSERT INTO `articulo` (`ID`, `TITULO`, `AUTOR_CONTACTO`, `TOPICO_PRINCIPAL`, `FECHA_ENVIO`, `RESUMEN`, `NUM_REVISORES`, `puntajeFinal`) VALUES
(8, 'Porfa funciona', 5, 'environment', '0000-00-00', 'hay pura fe', 1, NULL),
(10, 'Hola', 8, 'culture', '0000-00-00', 'Prueba de correo enviado', 0, NULL),
(11, 'Prueba2', 9, 'education', '0000-00-00', 'test', 3, 5.67),
(12, 'Redefining Education in a Digital Era', 5, 'culture', '2025-05-18', 'A look into how digital platforms and remote tools are reshaping modern education.', 1, NULL),
(13, 'Beneath the Ice: Discoveries in Antarctica', 5, 'literature', '2025-05-18', 'Recent explorations and what they reveal about Earth’s climate and ancient life.', 0, NULL),
(14, 'Plastic Oceans: A Crisis in Progress', 5, 'education', '2025-05-18', 'How plastic pollution is endangering marine life and what we can do about it.', 0, NULL),
(15, 'The Silent Cost of Fast Fashion', 5, 'fashion', '2025-05-18', 'Exploring the environmental and human toll of the fast fashion industry.', 0, NULL),
(16, 'Reclaiming Our Health After a Pandemic', 5, 'health', '2025-05-18', 'Steps communities are taking to rebuild public health post-pandemic.', 0, NULL),
(17, 'Breaking Boundaries in Sports Journalism', 5, 'sports', '2025-05-18', 'Exploring how the evolution of technology has changed sports journalism and fan engagement.', 0, NULL),
(18, 'The Changing Face of Fashion', 5, 'fashion', '2025-05-18', 'Fashion trends are shifting towards sustainability, and this article explores the impact on designers and consumers.', 0, NULL),
(19, 'Exploring Mars: The Final Frontier', 5, 'science', '2025-05-18', 'This article takes a deeper dive into the latest Mars missions and their potential to answer questions about life beyond Earth.', 0, NULL),
(20, 'The Rise of Space Exploration', 5, 'technology', '2025-05-18', 'Private companies like SpaceX and Blue Origin are pushing the limits of space exploration. What does the future hold?', 0, NULL),
(21, 'The Artificial Intelligence', 5, 'finance', '2025-05-18', 'AI is set to disrupt the finance sector in significant ways. This article outlines the potential benefits and risks.', 0, NULL),
(22, 'Mindful Eating: The Key to Health?', 5, 'health', '2025-05-18', 'How focusing on the moment of eating can improve physical health and well-being.', 0, NULL),
(23, 'The Impact of Music on Mental Health', 5, 'music', '2025-05-18', 'Research shows that music can be a powerful tool for mental health therapy. This article explores those findings.', 0, NULL),
(24, 'How Technology is Shaping Our Future', 5, 'technology', '2025-05-18', 'From AI to automation, technology is evolving rapidly. This article covers its implications for our future.', 0, NULL),
(25, 'Why Travel is Good for Your Health', 5, 'travel', '2025-05-18', 'Traveling allows for new experiences that boost mental health. Here’s how to make the most of your next trip.', 0, NULL),
(26, 'The New Frontiers in Technology', 5, 'technology', '2025-05-18', 'A deep dive into the technological innovations that are shaping our future.', 0, NULL),
(27, 'Health and Fitness: A Global Trend', 5, 'health', '2025-05-18', 'Examining the rise of health-consciousness worldwide and its impact on industries.', 0, NULL),
(28, 'The Future of Work', 5, 'technology', '2025-05-18', 'How digital transformation is revolutionizing the workforce and creating new opportunities.', 0, NULL),
(29, 'Social Media: The New Public Sphere', 5, 'culture', '2025-05-18', 'How social media has evolved into the modern-day public square, affecting social dynamics.', 0, NULL),
(30, 'Exploring the World of Financial Technology', 5, 'finance', '2025-05-18', 'The intersection of finance and technology is changing the way we manage money.', 0, NULL),
(31, 'The Evolution of Renewable Energy Sources', 5, 'environment', '2025-05-18', 'A look at the latest advancements in renewable energy and their potential to combat climate change.', 0, NULL),
(32, 'The Importance of Data Privacy', 5, 'technology', '2025-05-18', 'How data privacy is becoming a major concern in the digital age.', 0, NULL),
(33, 'The Impact of Artificial Healthcare', 5, 'health', '2025-05-18', 'Exploring how AI is being used in healthcare to improve patient outcomes and reduce costs.', 0, NULL),
(34, 'The Rise of Smart Cities', 5, 'technology', '2025-05-18', 'An analysis of the development of smart cities and how technology is revolutionizing urban living.', 0, NULL),
(35, 'The State of Global Education Post-Pandemic', 5, 'education', '2025-05-18', 'Examining how the pandemic has reshaped the education sector and what the future holds.', 0, NULL),
(36, 'The Future of Space Exploration', 5, 'science', '2025-05-18', 'Looking at the potential for human colonization of Mars and beyond, and the role of space agencies.', 0, NULL),
(37, 'The Growing Influence of Social Media Opinion', 5, 'culture', '2025-05-18', 'A look at how social media platforms are influencing political discourse and public opinion.', 0, NULL),
(38, 'Sustainable Food Practices for a Better Future', 5, 'food', '2025-05-18', 'Exploring sustainable food practices that can help combat food scarcity and environmental degradation.', 0, NULL),
(39, 'The Role of Technology in Modern Music Production', 5, 'music', '2025-05-18', 'How advancements in technology have revolutionized the music production industry.', 0, NULL),
(40, 'The Future of Digital Media', 5, 'technology', '2025-05-18', 'Exploring the future of digital media and its influence on global communication.', 0, NULL),
(41, 'Global Political Shifts', 5, 'politics', '2025-05-18', 'Analyzing the political shifts and their impact on global governance.', 0, NULL),
(42, 'Innovations in Healthcare', 5, 'health', '2025-05-18', 'A deep dive into the latest innovations in healthcare and their societal impact.', 0, NULL),
(43, 'Trends in Sustainable Fashion', 5, 'fashion', '2025-05-18', 'Exploring the rise of sustainable fashion and its impact on the environment.', 0, NULL),
(44, 'The Evolution of Social Marketing', 5, 'technology', '2025-05-18', 'An overview of the evolution of social media marketing and its influence on modern business.', 0, NULL),
(45, 'The Role of Women in Sports', 5, 'sports', '2025-05-18', 'An analysis of the increasing role of women in the sports industry and how it’s changing the landscape.', 0, NULL),
(46, 'Global Climate Change Solutions', 5, 'environment', '2025-05-18', 'Exploring the global efforts and solutions to combat climate change and their effectiveness.', 0, NULL),
(47, 'The Role of Digital Bussines', 5, 'technology', '2025-05-18', 'Exploring the impact of digital transformation on businesses and industries.', 0, NULL),
(48, 'The Influence of Sports on Culture', 5, 'sports', '2025-05-18', 'An analysis of how sports have influenced global culture and society.', 0, NULL),
(49, 'Advancements in Artificial Intelligence', 5, 'technology', '2025-05-18', 'A deep dive into AI advancements and their potential future impact on various industries.', 0, NULL),
(50, 'The Evolution of Modern Fashion', 5, 'fashion', '2025-05-18', 'Exploring the changes and trends in modern fashion, and its influence on global culture.', 0, NULL),
(51, 'The Future of Sustainability in Fashion', 5, 'fashion', '2025-05-18', 'Analyzing how sustainability practices are reshaping the fashion industry.', 0, NULL),
(52, 'The Impact of Globalization on Local Economies', 5, 'finance', '2025-05-18', 'Examining the effects of globalization on local economies and their growth prospects.', 0, NULL),
(53, 'The Changing Landscape of Media', 5, 'music', '2025-05-18', 'Exploring the rapid changes in media consumption and its effects on traditional outlets.', 0, NULL),
(54, 'The Importance of Renewable Energy Sources', 5, 'environment', '2025-05-18', 'A comprehensive review of renewable energy sources and their importance in the modern world.', 0, NULL),
(55, 'Global Health Challenges and Solutions', 5, 'health', '2025-05-18', 'An exploration of the major global health challenges and the efforts being made to address them.', 0, NULL),
(56, 'The Impact of Artificial Intelligence', 5, 'health', '2025-05-18', 'Exploring how AI is revolutionizing healthcare and improving patient care.', 0, NULL),
(57, 'The Role of Education in Global Development', 5, 'education', '2025-05-18', 'A look into how education plays a critical role in the development of nations.', 0, NULL),
(58, 'The Future of Communication Technology', 5, 'technology', '2025-05-18', 'Examining the the future developments in communication technologies and their impact on global connectivity.', 0, NULL),
(59, 'Economic Impacts of Climate Change', 5, 'environment', '2025-05-18', 'A deep dive into how climate change is affecting global economies and future trends.', 0, NULL),
(60, 'Advancing Education in the Digital Age', 5, 'education', '2025-05-18', 'Analyzing how digital tools are transforming the education landscape and enhancing learning.', 0, NULL),
(61, 'Politics and Environmental Policy Reform', 5, 'environment', '2025-05-18', 'Examining the role of political action in shaping environmental policies around the world.', 0, NULL),
(109, 'Innovations in Technology for the Future', 5, 'technology', '2025-05-18', 'Exploring the latest innovations in technology and how they will shape the future.', 0, NULL),
(164, 'Cultural Identity in a Globalized World', 5, 'culture', '2025-05-18', 'Discusses how globalization influences the preservation of local cultures.', 0, NULL),
(165, 'Literature as Social Commentary', 5, 'literature', '2025-05-18', 'Examines modern literature and its critique of contemporary society.', 0, NULL),
(166, 'Nutrition Trends and Public Health', 5, 'health', '2025-05-18', 'Looks into the impact of diet trends on global health indicators.', 0, NULL),
(167, 'Virtual Tourism and Cultural Experience', 5, 'travel', '2025-05-18', 'Evaluates the role of technology in virtual travel and education.', 0, NULL),
(168, 'Economic Inequality and Global Finance', 5, 'finance', '2025-05-18', 'Analyzes how international finance influences wealth disparity.', 0, NULL),
(169, 'Indigenous Cultures and Modern Influence', 5, 'culture', '2025-05-18', 'Explores how traditional practices persist amid modern changes.', 0, NULL),
(170, 'Narrative Innovation in Contemporary Novels', 5, 'literature', '2025-05-18', 'Studies new storytelling techniques in 21st-century fiction.', 0, NULL),
(171, 'Global Mental Health Awareness', 5, 'health', '2025-05-18', 'Analyzes efforts to improve mental health care worldwide.', 0, NULL),
(172, 'Sustainable Travel and Eco-Tourism', 5, 'travel', '2025-05-18', 'Examines sustainable tourism practices and traveler awareness.', 0, NULL),
(173, 'Fintech and Economic Inclusion', 5, 'finance', '2025-05-18', 'Discusses how fintech is promoting access to financial services.', 0, NULL),
(174, 'Art, Culture, and Resistance', 5, 'culture', '2025-05-18', 'Explores how cultural expressions serve as tools for resistance.', 0, NULL),
(175, 'Postmodernism in Latin American Literature', 5, 'literature', '2025-05-18', 'Examines postmodern trends in Latin American authorship.', 0, NULL),
(176, 'Telemedicine and Rural Health Access', 5, 'health', '2025-05-18', 'Analyzes how digital health solutions reach remote areas.', 0, NULL),
(177, 'Digital Nomads: A New Way of Living', 5, 'travel', '2025-05-18', 'Considers how mobile working is reshaping living and work norms.', 0, NULL),
(178, 'Financial Education for Youth', 5, 'finance', '2025-05-18', 'Focuses on the importance of teaching financial skills in schools.', 0, NULL),
(179, 'Cultural Fusion in Global Cities', 5, 'culture', '2025-05-18', 'Studies cultural hybridization in major urban centers.', 0, NULL),
(180, 'The Role of Literature in Empathy Building', 5, 'literature', '2025-05-18', 'Explores how stories foster empathy across diverse populations.', 0, NULL),
(181, 'Shaping Political Narratives Today', 5, 'politics', '2025-05-18', 'Explores the influence of social media on modern political communication.', 0, NULL),
(182, 'Environmental Recovery in Urban Spaces', 5, 'environment', '2025-05-18', 'Discusses initiatives to restore green areas in densely populated cities.', 0, NULL),
(183, 'Education Trends in the Digital Era', 5, 'education', '2025-05-18', 'Analyzes the role of digital tools in modern education systems.', 0, NULL),
(184, 'The Global Food Chain Disruption', 5, 'food', '2025-05-18', 'Reviews the impacts of pandemics on global food distribution.', 0, NULL),
(185, 'Fashion as Cultural Expression', 5, 'fashion', '2025-05-18', 'Looks into fashion’s influence on cultural identity.', 0, NULL),
(186, 'Women in Tech Leadership', 5, 'technology', '2025-05-18', 'Examines gender gaps in the tech industry and progress in leadership roles.', 0, NULL),
(187, 'The Evolution of Olympic Sports', 5, 'sports', '2025-05-18', 'Highlights changes in Olympic disciplines and athlete preparation.', 0, NULL),
(188, 'Forgotten Conflicts in World History', 5, 'history', '2025-05-18', 'Analyzes lesser-known historical wars and their global implications.', 0, NULL),
(189, 'Genetic Breakthroughs in 2025', 5, 'science', '2025-05-18', 'Covers recent advancements in gene editing and its ethical implications.', 0, NULL),
(190, 'Global Music Trends of the Decade', 5, 'music', '2025-05-18', 'Reviews evolving genres and the rise of international artists.', 0, NULL),
(191, 'Post-Pandemic Travel Behavior', 5, 'travel', '2025-05-18', 'Looks at how global travel patterns changed after COVID-19.', 0, NULL),
(192, 'Inflation and its Global Impact', 5, 'finance', '2025-05-18', 'Investigates the causes and effects of inflation in developing countries.', 0, NULL),
(193, 'Preserving Indigenous Cultures', 5, 'culture', '2025-05-18', 'Explores initiatives to protect disappearing cultural identities.', 0, NULL),
(194, 'Literary Themes in Modern Novels', 5, 'literature', '2025-05-18', 'Analyzes emerging motifs in contemporary fiction.', 0, NULL),
(195, 'Smart Cities and Environmental Sustainability', 5, 'environment', '2025-05-18', 'Analyzes smart city models focused on ecological impact.', 0, NULL),
(196, 'Mental Health in the Digital Era', 5, 'health', '2025-05-18', 'Explores how constant connectivity affects mental well-being.', 0, NULL),
(197, 'AI for Climate Monitoring', 5, 'science', '2025-05-18', 'Studies how artificial intelligence is used to track environmental changes.', 0, NULL),
(198, 'Community Health Outreach Models', 5, 'health', '2025-05-18', 'Reviews effective health education programs in local communities.', 0, NULL),
(199, 'The Role of Sports in Youth Development', 5, 'sports', '2025-05-18', 'Discusses how athletic programs contribute to education and social skills.', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `articulo_revisor`
--

CREATE TABLE `articulo_revisor` (
  `ID` int(11) NOT NULL,
  `ID_ARTICULO` int(11) NOT NULL,
  `ID_REVISOR` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `articulo_revisor`
--

INSERT INTO `articulo_revisor` (`ID`, `ID_ARTICULO`, `ID_REVISOR`) VALUES
(97, 8, 18),
(96, 8, 26),
(98, 8, 27),
(100, 10, 26),
(104, 11, 1),
(105, 11, 20),
(112, 11, 26),
(107, 12, 18),
(108, 12, 20),
(106, 12, 26);

-- --------------------------------------------------------

--
-- Table structure for table `autor`
--

CREATE TABLE `autor` (
  `ID` int(11) NOT NULL,
  `RUT` varchar(10) NOT NULL,
  `NOMBRE` varchar(50) NOT NULL,
  `EMAIL` varchar(50) NOT NULL,
  `CONTRASENA` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `autor`
--

INSERT INTO `autor` (`ID`, `RUT`, `NOMBRE`, `EMAIL`, `CONTRASENA`) VALUES
(5, '27148851-3', 'Gabriel Delgado', 'gaboo90685@gmail.com', '$2y$10$iCW.3vj18wHt27QDPFs34OdkKcNOcOL9celX0refCl08WTxZu/6Ki'),
(6, '27148786-K', 'Gabo', 'gabo@test.com', '$2y$10$W8lZ0sp3kGU0gQLd3HFY1.5HsLKtQq/KsBOdqj/Jjm9WDZPW3SLXq'),
(8, '21690897-K', 'Jaime', 'jaimeg8877@gmail.com', '$2y$10$NgBDWrXmhustCu1ZCKScfuMvWaAx/ctoRiW0QiLFLyw1GJyvN9vGa'),
(9, '22222222-2', 'TestUser', 'testuser@test.com', '$2y$10$ZEt1Xup/zTvzXg63K0l9ie/as1fMA3foCuKfL.FSd71kNJl4ZzwMa');

-- --------------------------------------------------------

--
-- Table structure for table `autor_participante`
--

CREATE TABLE `autor_participante` (
  `ID_ARTICULO` int(11) NOT NULL,
  `ID_AUTOR` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `autor_participante`
--

INSERT INTO `autor_participante` (`ID_ARTICULO`, `ID_AUTOR`) VALUES
(8, 6),
(11, 8),
(12, 9);

-- --------------------------------------------------------

--
-- Table structure for table `especialidad_agregada`
--

CREATE TABLE `especialidad_agregada` (
  `ID_REVISOR` int(11) NOT NULL,
  `ESPECIALIDAD_EXTRA` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `especialidad_agregada`
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
(26, 'education'),
(26, 'environment'),
(26, 'fashion'),
(26, 'finance'),
(26, 'food'),
(26, 'health'),
(26, 'history'),
(26, 'literature'),
(26, 'music'),
(26, 'politics'),
(26, 'science'),
(26, 'sports'),
(26, 'technology'),
(26, 'travel'),
(27, 'education'),
(27, 'environment'),
(27, 'politics');

-- --------------------------------------------------------

--
-- Table structure for table `jefe_comite`
--

CREATE TABLE `jefe_comite` (
  `RUT` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jefe_comite`
--

INSERT INTO `jefe_comite` (`RUT`) VALUES
('21854002-3'),
('27148851-3'),
('44444444-4');

-- --------------------------------------------------------

--
-- Table structure for table `revision`
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
-- Dumping data for table `revision`
--

INSERT INTO `revision` (`ID`, `ARTICULO_REVISOR_ID`, `puntuacion_global`, `comentarios`, `originalidad`, `claridad`, `relevancia`) VALUES
(1, 96, 6, 'bien hecho', 4, 3, 3),
(3, 106, 2, 'ponle corazon', 2, 2, 2),
(4, 105, 3, 'cuek', 3, 3, 3),
(5, 112, 7, 'mmmm vaya', 5, 5, 5),
(7, 104, 7, 'ojito peluche', 3, 4, 4);

--
-- Triggers `revision`
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
    DECLARE promedio DECIMAL(5,2);

    -- Obtener ID del artículo
    SELECT id_articulo INTO v_articulo_id
    FROM ARTICULO_REVISOR
    WHERE id = NEW.ARTICULO_REVISOR_ID;

    -- Contar revisiones del artículo
    SELECT COUNT(*)
    INTO total_revisiones
    FROM REVISION r
    INNER JOIN ARTICULO_REVISOR ar ON r.ARTICULO_REVISOR_ID = ar.id
    WHERE ar.id_articulo = v_articulo_id;

    -- Si hay 3 revisiones, calcular promedio
    IF total_revisiones = 3 THEN
        SET promedio = calcular_promedio_y_actualizar(v_articulo_id);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `revisor`
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
-- Dumping data for table `revisor`
--

INSERT INTO `revisor` (`ID`, `RUT`, `NOMBRE`, `EMAIL`, `TOPICO_ESPECIALIDAD`, `CONTRASENA`) VALUES
(1, '27148851-3', 'Gabriel Delgado', 'gaboo90685@gmail.com', 'education', '$2y$10$GDVYDpOpF22waexwCwU3yu5aiYaQfj8UJ0bffyTWBh3T0FEBfVYh2'),
(18, '21854002-3', 'Martina', 'martiviva3@gmail.com', 'education', '$2y$10$AW79LoTD.6m89tJDh33kOO/8qMLlRlabbmGsjhcZcLqHFIEMYHb9G'),
(20, '44444444-4', 'Kitty', 'kitty@gmail.com', 'culture', '$2y$10$vwr.gXU.YLEhFes5F9.Z2utMmoyzqeKXM1faqDLIEDZzu7x.IbCwO'),
(26, '11111111-1', 'Lulu', 'lulu@gmail.com', 'culture', '$2y$10$yuoWEwMeYlQT.HdEOcuGsOP1QuC.iOf0UMr4oIg74/kOfc16rcEwu'),
(27, '77777777-7', 'UserPrueba', 'pruebauser@gmail.com', 'culture', '$2y$10$jVEtqzxmqkMvg4BXDVhzSuXOF0FsBhBBJgrQucAyvR8FZ..qaqf/q');

-- --------------------------------------------------------

--
-- Stand-in structure for view `revisoryespecialidad`
-- (See below for the actual view)
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
-- Table structure for table `topicos_extra`
--

CREATE TABLE `topicos_extra` (
  `ID_ARTICULO` int(11) NOT NULL,
  `TOPICO_EXTRA` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `topicos_extra`
--

INSERT INTO `topicos_extra` (`ID_ARTICULO`, `TOPICO_EXTRA`) VALUES
(8, 'history'),
(8, 'science'),
(10, 'science'),
(12, 'fashion'),
(12, 'history'),
(13, 'science'),
(13, 'sports'),
(13, 'travel'),
(14, 'environment'),
(14, 'finance'),
(14, 'health'),
(14, 'science');

-- --------------------------------------------------------

--
-- Table structure for table `topico_especialidad`
--

CREATE TABLE `topico_especialidad` (
  `NOMBRE` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `topico_especialidad`
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
-- Stand-in structure for view `vista_articulos_autores_revisores`
-- (See below for the actual view)
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
-- Stand-in structure for view `vista_revisores_completa`
-- (See below for the actual view)
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
-- Structure for view `revisoryespecialidad`
--
DROP TABLE IF EXISTS `revisoryespecialidad`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `revisoryespecialidad`  AS SELECT `r`.`ID` AS `id`, `r`.`NOMBRE` AS `nombre`, `r`.`RUT` AS `rut`, `r`.`EMAIL` AS `email`, concat(`r`.`TOPICO_ESPECIALIDAD`,if(`extra`.`especialidades` is not null,concat(', ',`extra`.`especialidades`),'')) AS `especialidadesRevisor`, CASE WHEN `jc`.`RUT` is not null THEN 1 ELSE 0 END AS `esJefeComite` FROM ((`revisor` `r` left join (select `especialidad_agregada`.`ID_REVISOR` AS `ID_REVISOR`,group_concat(`especialidad_agregada`.`ESPECIALIDAD_EXTRA` separator ', ') AS `especialidades` from `especialidad_agregada` group by `especialidad_agregada`.`ID_REVISOR`) `extra` on(`r`.`ID` = `extra`.`ID_REVISOR`)) left join `jefe_comite` `jc` on(`jc`.`RUT` = `r`.`RUT`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vista_articulos_autores_revisores`
--
DROP TABLE IF EXISTS `vista_articulos_autores_revisores`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_articulos_autores_revisores`  AS SELECT `a`.`ID` AS `articulo_id`, `a`.`TITULO` AS `titulo_articulo`, group_concat(distinct concat(`aut`.`Nombre`) separator ', ') AS `autores`, group_concat(distinct `topico`.`Nombre` separator ', ') AS `topicos`, group_concat(distinct concat(`r`.`NOMBRE`) separator ', ') AS `revisores` FROM (((((((((`articulo` `a` left join `autor` `autor_principal` on(`autor_principal`.`ID` = `a`.`AUTOR_CONTACTO`)) left join `autor_participante` `ap` on(`ap`.`ID_ARTICULO` = `a`.`ID`)) left join `autor` `aut_part` on(`ap`.`ID_AUTOR` = `aut_part`.`ID`)) left join (select `autor`.`ID` AS `ID`,`autor`.`NOMBRE` AS `Nombre`,`autor`.`EMAIL` AS `Email` from `autor`) `aut` on(`aut`.`ID` = `a`.`AUTOR_CONTACTO` or `aut`.`ID` in (select `autor_participante`.`ID_AUTOR` from `autor_participante` where `autor_participante`.`ID_ARTICULO` = `a`.`ID`))) left join (select distinct `topico_especialidad`.`NOMBRE` AS `Nombre` from `topico_especialidad`) `topico_principal` on(`topico_principal`.`Nombre` = `a`.`TOPICO_PRINCIPAL`)) left join `topicos_extra` `te` on(`te`.`ID_ARTICULO` = `a`.`ID`)) left join (select `topico_especialidad`.`NOMBRE` AS `Nombre` from `topico_especialidad` union select `topicos_extra`.`TOPICO_EXTRA` AS `Nombre` from `topicos_extra`) `topico` on(`topico`.`Nombre` = `a`.`TOPICO_PRINCIPAL` or `topico`.`Nombre` = `te`.`TOPICO_EXTRA`)) left join `articulo_revisor` `ar` on(`ar`.`ID_ARTICULO` = `a`.`ID`)) left join `revisor` `r` on(`r`.`ID` = `ar`.`ID_REVISOR`)) GROUP BY `a`.`ID`, `a`.`TITULO` ;

-- --------------------------------------------------------

--
-- Structure for view `vista_revisores_completa`
--
DROP TABLE IF EXISTS `vista_revisores_completa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_revisores_completa`  AS SELECT `r`.`ID` AS `Revisor_ID`, `r`.`NOMBRE` AS `Revisor_Nombre`, `r`.`RUT` AS `Revisor_Rut`, concat(`te`.`NOMBRE`,ifnull(concat(', ',(select group_concat(`ea`.`ESPECIALIDAD_EXTRA` separator ', ') from (`especialidad_agregada` `ea` join `topico_especialidad` `te_extra` on(`ea`.`ESPECIALIDAD_EXTRA` = `te_extra`.`NOMBRE`)) where `ea`.`ID_REVISOR` = `r`.`ID`)),'')) AS `Todas_Especialidades`, (select group_concat(`a`.`TITULO` separator '| ') from (`articulo_revisor` `ar` join `articulo` `a` on(`ar`.`ID_ARTICULO` = `a`.`ID`)) where `ar`.`ID_REVISOR` = `r`.`ID`) AS `Articulos_Asignados`, (select group_concat(`a`.`ID` separator '| ') from (`articulo_revisor` `ar` join `articulo` `a` on(`ar`.`ID_ARTICULO` = `a`.`ID`)) where `ar`.`ID_REVISOR` = `r`.`ID`) AS `id_Articulos_Asignados` FROM (`revisor` `r` left join `topico_especialidad` `te` on(`r`.`TOPICO_ESPECIALIDAD` = `te`.`NOMBRE`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `articulo`
--
ALTER TABLE `articulo`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `UQ_AUTOR_TITULO` (`TITULO`,`AUTOR_CONTACTO`),
  ADD KEY `FK_TOPICO_PRINCIPAL` (`TOPICO_PRINCIPAL`),
  ADD KEY `FK_AUTOR_CONTACTO` (`AUTOR_CONTACTO`);

--
-- Indexes for table `articulo_revisor`
--
ALTER TABLE `articulo_revisor`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `UQ_ARTICULO_REVISOR` (`ID_ARTICULO`,`ID_REVISOR`),
  ADD KEY `FK_ID_REVISOR_TO_ARTICULO` (`ID_REVISOR`);

--
-- Indexes for table `autor`
--
ALTER TABLE `autor`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `RUT` (`RUT`),
  ADD UNIQUE KEY `EMAIL` (`EMAIL`);

--
-- Indexes for table `autor_participante`
--
ALTER TABLE `autor_participante`
  ADD PRIMARY KEY (`ID_ARTICULO`,`ID_AUTOR`),
  ADD KEY `FK_ID_AUTOR` (`ID_AUTOR`);

--
-- Indexes for table `especialidad_agregada`
--
ALTER TABLE `especialidad_agregada`
  ADD PRIMARY KEY (`ID_REVISOR`,`ESPECIALIDAD_EXTRA`),
  ADD KEY `FK_ESPECIALIDAD_EXTRA` (`ESPECIALIDAD_EXTRA`);

--
-- Indexes for table `jefe_comite`
--
ALTER TABLE `jefe_comite`
  ADD UNIQUE KEY `RUT` (`RUT`);

--
-- Indexes for table `revision`
--
ALTER TABLE `revision`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_ArticuloRevisor_Revision` (`ARTICULO_REVISOR_ID`);

--
-- Indexes for table `revisor`
--
ALTER TABLE `revisor`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `RUT` (`RUT`),
  ADD UNIQUE KEY `EMAIL` (`EMAIL`),
  ADD KEY `TOPICO_ESPECIALIDAD` (`TOPICO_ESPECIALIDAD`);

--
-- Indexes for table `topicos_extra`
--
ALTER TABLE `topicos_extra`
  ADD PRIMARY KEY (`ID_ARTICULO`,`TOPICO_EXTRA`),
  ADD KEY `FK_TOPICO_EXTRA` (`TOPICO_EXTRA`);

--
-- Indexes for table `topico_especialidad`
--
ALTER TABLE `topico_especialidad`
  ADD PRIMARY KEY (`NOMBRE`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `articulo`
--
ALTER TABLE `articulo`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;

--
-- AUTO_INCREMENT for table `articulo_revisor`
--
ALTER TABLE `articulo_revisor`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `autor`
--
ALTER TABLE `autor`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `revision`
--
ALTER TABLE `revision`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `revisor`
--
ALTER TABLE `revisor`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `articulo`
--
ALTER TABLE `articulo`
  ADD CONSTRAINT `FK_AUTOR_CONTACTO` FOREIGN KEY (`AUTOR_CONTACTO`) REFERENCES `autor` (`ID`),
  ADD CONSTRAINT `FK_TOPICO_PRINCIPAL` FOREIGN KEY (`TOPICO_PRINCIPAL`) REFERENCES `topico_especialidad` (`NOMBRE`);

--
-- Constraints for table `articulo_revisor`
--
ALTER TABLE `articulo_revisor`
  ADD CONSTRAINT `FK_ID_ARTICULO_TO_REVISOR` FOREIGN KEY (`ID_ARTICULO`) REFERENCES `articulo` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_ID_REVISOR_TO_ARTICULO` FOREIGN KEY (`ID_REVISOR`) REFERENCES `revisor` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `autor_participante`
--
ALTER TABLE `autor_participante`
  ADD CONSTRAINT `FK_ID_ARTICULO` FOREIGN KEY (`ID_ARTICULO`) REFERENCES `articulo` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_ID_AUTOR` FOREIGN KEY (`ID_AUTOR`) REFERENCES `autor` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `especialidad_agregada`
--
ALTER TABLE `especialidad_agregada`
  ADD CONSTRAINT `FK_ESPECIALIDAD_EXTRA` FOREIGN KEY (`ESPECIALIDAD_EXTRA`) REFERENCES `topico_especialidad` (`NOMBRE`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_ID_REVISOR` FOREIGN KEY (`ID_REVISOR`) REFERENCES `revisor` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `jefe_comite`
--
ALTER TABLE `jefe_comite`
  ADD CONSTRAINT `FK_rut_jefe_revisor` FOREIGN KEY (`RUT`) REFERENCES `revisor` (`RUT`) ON DELETE CASCADE;

--
-- Constraints for table `revision`
--
ALTER TABLE `revision`
  ADD CONSTRAINT `FK_ArticuloRevisor_Revision` FOREIGN KEY (`ARTICULO_REVISOR_ID`) REFERENCES `articulo_revisor` (`ID`);

--
-- Constraints for table `revisor`
--
ALTER TABLE `revisor`
  ADD CONSTRAINT `revisor_ibfk_1` FOREIGN KEY (`TOPICO_ESPECIALIDAD`) REFERENCES `topico_especialidad` (`NOMBRE`);

--
-- Constraints for table `topicos_extra`
--
ALTER TABLE `topicos_extra`
  ADD CONSTRAINT `FK_ID_ARTICULO_TO_TOPICO` FOREIGN KEY (`ID_ARTICULO`) REFERENCES `articulo` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_TOPICO_EXTRA` FOREIGN KEY (`TOPICO_EXTRA`) REFERENCES `topico_especialidad` (`NOMBRE`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
