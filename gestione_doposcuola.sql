-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 03, 2025 alle 14:01
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gestione_doposcuola`
--
CREATE DATABASE IF NOT EXISTS `gestione_doposcuola` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `gestione_doposcuola`;

-- --------------------------------------------------------

--
-- Struttura della tabella `alunni`
--

DROP TABLE IF EXISTS `alunni`;
CREATE TABLE `alunni` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cognome` varchar(100) NOT NULL,
  `scuola` varchar(100) NOT NULL,
  `id_pacchetto` int(11) DEFAULT NULL,
  `prezzo_finale` decimal(10,2) NOT NULL,
  `stato` enum('attivo','disattivato') DEFAULT 'attivo',
  `data_iscrizione` date NOT NULL,
  `id_genitore` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `alunni`
--

INSERT INTO `alunni` (`id`, `nome`, `cognome`, `scuola`, `id_pacchetto`, `prezzo_finale`, `stato`, `data_iscrizione`, `id_genitore`) VALUES
(8, 'Giuseppe', 'Bevilacqua', 'Nautico', 7, 180.00, 'attivo', '2024-10-03', 4),
(9, 'Antonino', 'Barbaro', 'Industriale', 9, 180.00, 'attivo', '2024-10-03', 5),
(10, 'Vincenzo', 'Bosco', 'Calvino', 7, 180.00, 'disattivato', '2024-11-04', 6),
(11, 'Sergio', 'Agnello', 'Ragioneria', 11, 270.00, 'disattivato', '2024-10-08', 7),
(13, 'Manuel', 'Bruno', 'Ragioneria', 6, 230.00, 'attivo', '2024-11-04', 9),
(15, 'Andrea', 'Candela', 'Industriale', 7, 180.00, 'attivo', '2024-10-03', 11),
(16, 'Antonio', 'Amoroso', 'Industriale', 6, 180.00, 'attivo', '2024-10-31', 12),
(17, 'Leonardo', 'Carpitella', 'Nautico', 7, 150.00, 'attivo', '2024-10-01', 13),
(18, 'Roberto', 'Cavasino', 'Calvino', 11, 270.00, 'attivo', '2024-10-17', 14),
(19, 'Gabriele / Lucrezia', 'Contorno', 'Industriale', 7, 200.00, 'attivo', '2024-10-15', 15),
(20, 'Francesco', 'D\'Alessandro', 'Industriale', 11, 270.00, 'attivo', '2024-09-23', 16),
(21, 'Daniele', 'Romano', 'Nautico', 5, 200.00, 'attivo', '2024-11-20', 17),
(22, 'Elisa', 'Del Giudice', 'Medie', 4, 100.00, 'attivo', '2024-10-01', 18),
(23, 'Andrea', 'Ferrara', 'Industriale', 11, 230.00, 'attivo', '2024-10-01', 19),
(24, 'Marcello', 'Gandolfo', 'Industriale', 9, 200.00, 'attivo', '2024-10-07', 20),
(25, 'Michele / Davide', 'Garaffa', 'turistico', 9, 150.00, 'attivo', '2024-10-02', 21),
(26, 'Aldo', 'Giacalone', 'Industriale', 7, 180.00, 'attivo', '2024-09-02', 22),
(28, 'Gabriele', 'Giacalone', 'Classico', 6, 200.00, 'attivo', '2025-01-02', 24),
(29, 'Sara', 'Gianno', 'Scientifico', 7, 180.00, 'attivo', '2024-11-26', 25),
(30, 'Francesca', 'Graziano', 'Econ. Sociale', 7, 180.00, 'attivo', '2024-10-01', 26),
(31, 'Marco', 'Incandela', 'Medie', 11, 260.00, 'disattivato', '2024-09-10', 27),
(32, 'Lars', 'Soltao', 'Scientifico', 6, 190.00, 'attivo', '2024-11-26', 28),
(33, 'Melissa', 'Lombardo', 'Medie', 4, 100.00, 'attivo', '2024-09-17', 29),
(34, 'Massimo', 'Marini', 'Classico', 6, 170.00, 'attivo', '2024-10-14', 30),
(35, 'Alessandro', 'Marino', 'Classico', 9, 200.00, 'attivo', '2024-09-06', 31),
(36, 'Gemelli', 'Miceli', 'Medie', 5, 180.00, 'attivo', '2025-04-24', 32),
(37, 'Giulio', 'Montuori', 'Scientifico', 6, 200.00, 'attivo', '2024-10-30', 33),
(38, 'Diego', 'Paladino', 'Scientifico', 6, 180.00, 'attivo', '2024-11-29', 34),
(39, 'Simone', 'Pellegrino', 'Industriale', 7, 180.00, 'attivo', '2024-09-19', 35),
(40, 'Dario', 'Pisano', 'Industriale', 6, 220.00, 'attivo', '2024-10-03', 36),
(41, 'Mauro', 'Ruggirello', 'medie', 11, 270.00, 'attivo', '2024-10-17', 37),
(42, 'Chiara', 'Sansica', 'Ragioneria', 13, 0.00, 'attivo', '2024-10-11', 38),
(43, 'Fabio', 'Santini', 'Turistico', 6, 150.00, 'attivo', '2024-11-07', 39),
(44, 'Davide', 'Schifano', 'Industriale', 6, 200.00, 'attivo', '2024-11-19', 40),
(45, 'Davide', 'Schifano', 'Industriale', 6, 200.00, 'attivo', '2024-11-19', 41),
(46, 'Gabriele', 'Schifano', 'Industriale', 9, 220.00, 'attivo', '2024-10-01', 42),
(47, 'Giorgio', 'Simonte', 'Medie', 13, 0.00, 'attivo', '2024-09-24', 43),
(48, 'Annakhara', 'Stabile', 'Linguistico', 6, 220.00, 'attivo', '2024-11-07', 44),
(49, 'Alberto', 'Todaro', 'Scientifico', 13, 0.00, 'attivo', '2024-09-16', 45),
(50, 'Alessandro', 'Tosto', 'Turistico', 7, 140.00, 'attivo', '2024-09-24', 46),
(51, 'Gabriele', 'Tutone', 'Industriale', 6, 200.00, 'attivo', '2024-10-31', 47),
(52, 'Giada', 'Vario', 'Medie', 11, 250.00, 'attivo', '2024-09-17', 48),
(53, 'Andrea', 'Vella', 'nautico', 3, 120.00, 'attivo', '2024-11-12', 49),
(54, 'Rita', 'Virgilio', 'Medie', 4, 100.00, 'attivo', '2024-09-19', 50),
(55, 'Eleonora', 'Degoli', 'Rosina Salvo', 11, 270.00, 'attivo', '2025-01-08', 51),
(57, 'jennifer', 'Genna', 'Medie', 6, 176.00, 'disattivato', '2025-01-01', 53),
(58, 'Marco', 'Monteleone', 'Industriale', 11, 160.00, 'attivo', '2025-01-13', 54),
(59, 'Andrea', 'Casabella', 'Alberghiero', 11, 280.00, 'attivo', '2025-01-10', 55),
(60, 'Артур', 'Ким', 'Industriale', 5, 200.00, 'attivo', '2024-11-20', 56),
(61, 'PierGiorgio', 'Ficara', 'Industriale', 6, 176.00, 'attivo', '2025-01-15', 57);

-- --------------------------------------------------------

--
-- Struttura della tabella `genitori`
--

DROP TABLE IF EXISTS `genitori`;
CREATE TABLE `genitori` (
  `id` int(11) NOT NULL,
  `nome_completo` varchar(100) NOT NULL,
  `residenza` varchar(255) DEFAULT NULL,
  `codice_fiscale` varchar(16) NOT NULL,
  `telefono` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `genitori`
--

INSERT INTO `genitori` (`id`, `nome_completo`, `residenza`, `codice_fiscale`, `telefono`) VALUES
(1, 'Ciccino', 'Via', 'BRNLSN01R25D423P', '3476855380'),
(2, 'Ciccino2', 'Via2', 'BRNLSN01R25D423P', '3476855380'),
(3, 'Ciccino2', 'Via2', 'BRNLSN01R25D423P', '3476855380'),
(4, 'Laura Vitale', '---', 'BRNLSN25Q11R423P', '3294134500'),
(5, 'Maria Modica', '---', 'MTZXBS36L10Z345N', '1234567891'),
(6, 'Dont Know', '---', 'CCLFWG30S42A637D', '1234567891'),
(7, 'Boccia Daniela', 'via Sinone Pizzolungo 2', 'BCCDNL75R52D423Y', '1234567891'),
(8, 'Tommaso Amoroso', '---', 'CCLFWG30S42A637D', '1234567891'),
(9, 'Vitalina Accardi', 'Via delle acacie 14.', 'CCRVLN73C46L331O', '3393527356'),
(10, 'Nicolo Candela', 'Via Rosario Ferrante 5', 'CNDNCL80P28D423O', '1234567891'),
(11, 'Nicolò Candela', 'Via Rosario Ferrante 5', 'CNDNCL80P28D423O', '1234567891'),
(12, 'Tommaso Amoroso', '---', 'CCLFWG30S42A637D', '1234567891'),
(13, 'Micaela Galuppo', '---', 'CCLFWG30S42A637D', '1234567891'),
(14, 'Enza Serena Sciuto', 'Via Egadi 3', 'SCTNSR73D45D423F', '1234567891'),
(15, 'Maria Letizia Strazzera', 'Via Salvatore Impellizzeri 3', 'STRMLT70P41D423P', '1234567891'),
(16, 'Maria Caterina Di Marca', 'Via A. Manzoni 100', 'DMRMCT67D54B429J', '3357770660'),
(17, 'Romano Marco', '---', 'IDKXBS36L10Z345N', '3496795524'),
(18, 'Lorenzo Del Giudice', '---', 'IDKXBS36L10Z345N', '1234567891'),
(19, 'Caterina Galia', 'Viale Falcone Borsellino 28', 'GLACRN74M68D423F', '3470786495'),
(20, 'Pierangela Loreti', 'Via del Corallo 4B', 'LRTPNG76D47D423S', '3391075670'),
(21, 'Tommaso Garaffa', 'Strada Enrico Rinaldo-Marausa 2', 'GRFTMS72L13L331W', '1234567891'),
(22, 'Matteo Giacalone', 'Via Nino Bixio 85', 'GCLMTT75C15L331Y', '1234567891'),
(23, '---', '---', 'IDKXBS36L10Z345N', '1234567891'),
(24, '---', '---', 'IDKXBS36L10Z345N', '1234567891'),
(25, 'Tiziana Signorello', 'via Delle Naiadi 17', 'SGNTZN81B66D423Z', '3292767405'),
(26, 'Stella Tedesco', '---', 'IDKXBS36L10Z345N', '3899494888'),
(27, 'Giacoma Loria', '---', 'IDKXBS36L10Z345N', '3339918645'),
(28, 'Elena Buscaino', '---', 'IDKXBS36L10Z345N', '1234567891'),
(29, 'Jennifer Lombardo', '---', 'IDKXBS36L10Z345N', '1234567891'),
(30, 'Alessandra Colli', 'Corso Italia 25', 'CLLLSN70E62D423K', '3281115273'),
(31, 'Franesco Marino', 'Via 4 Novembre 2', 'MRNFNC71C19D423S', '3274574168'),
(32, '----', '----', 'IDKFNC71C19D423S', '1234567891'),
(33, 'Andrea Montuori', 'Via Gian Salvatore Cassisa 2', 'MNTNDR76H05F205C', '3347371568'),
(34, 'Paola Gianno', '----', 'IDKDNL75R52D423Y', '1234567891'),
(35, 'Caterina Cernigliaro', '---', 'IDKDNL75R52D423Y', '1234567891'),
(36, 'Elena Zummo', 'Corso Piersanti Mattarella 102', 'ZMMLNE70L44G273R', '3476966923'),
(37, 'Paola Prosperini', '---', 'IDKDNL75R52D423Y', '1234567891'),
(38, 'Lucia Ettari', '---', 'IDKDNL75R52D423Y', '1234567891'),
(39, 'Tiziana Alastra', '---', 'IDKDNL75R52D423Y', '1234567891'),
(40, 'Angela Schifano', '---', 'IDKDNL75R52D423Y', '3294324878'),
(41, 'Angela Schifano', '---', 'IDKDNL75R52D423Y', '3294324878'),
(42, '---', '---', 'IDKDNL75R52D423Y', '1234567891'),
(43, 'Lucia Mazzonello', '---', 'IDKDNL75R52D423Y', '1234567891'),
(44, 'Michele Stabile', 'VIA EURIPIDE 10', 'STBMHL69R25D423J', '1234567891'),
(45, 'AnnaRita De Caro', 'Viale della Provincia 1', 'IDKDNL75R52D423Y', '3280222785'),
(46, 'Stella Barbara', 'Via Anita Garibaldi 311', 'IDKDNL75R52D423Y', '3335951506'),
(47, '---', '---', 'IDKDNL75R52D423Y', '1234567891'),
(48, 'Anna Vario', '---', 'IDKDNL75R52D423Y', '3427625402'),
(49, '---', '---', 'IDKDNL75R52D423Y', '1234567891'),
(50, 'Caterina Maiorana', '---', 'IDKDNL75R52D423Y', '1234567891'),
(51, 'Degoli Gianriccardo', 'Via Niso 17', 'DGLGRC70T23L049G', '3313665183'),
(52, 'Valentina Bianco', '---', 'IDKDNL75R52D423Y', '1234567891'),
(53, 'Valentina Bianco', '---', 'IDKDNL75R52D423Y', '1234567891'),
(54, '---', '---', 'IDKDNL75R52D423Y', '1234567891'),
(55, '---', '---', 'IDKDNL75R52D423Y', '1234567891'),
(56, 'Fatima', '---', 'IDKDNL75R52D423Y', '3334370608'),
(57, '---', '---', 'IDKDNL75R52D423Y', '1234567891');

-- --------------------------------------------------------

--
-- Struttura della tabella `pacchetti`
--

DROP TABLE IF EXISTS `pacchetti`;
CREATE TABLE `pacchetti` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('orario','mensile') NOT NULL,
  `ore` int(11) NOT NULL,
  `prezzo` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `pacchetti`
--

INSERT INTO `pacchetti` (`id`, `nome`, `tipo`, `ore`, `prezzo`) VALUES
(1, '1H', 'orario', 1, 25.00),
(2, '3H', 'orario', 3, 65.00),
(3, '9H', 'orario', 9, 140.00),
(4, '12H', 'orario', 12, 160.00),
(5, '20H', 'orario', 20, 200.00),
(6, '24H', 'orario', 24, 220.00),
(7, 'Mensile 24H Medie e Superiori', 'mensile', 24, 210.00),
(8, 'Mensile 24H Elementari', 'mensile', 24, 120.00),
(9, 'Mensile 36H Medie e Superiori', 'mensile', 36, 270.00),
(10, 'Mensile 36H Elementari', 'mensile', 36, 180.00),
(11, 'Mensile 60H Medie e Superiori', 'mensile', 60, 300.00),
(12, 'Mensile 60H Elementari', 'mensile', 60, 220.00),
(13, 'Pro Bono', 'mensile', 0, 0.00),
(14, 'Esame di stato', 'mensile', 36, 200.00);

-- --------------------------------------------------------

--
-- Struttura della tabella `pagamenti`
--

DROP TABLE IF EXISTS `pagamenti`;
CREATE TABLE `pagamenti` (
  `id` int(11) NOT NULL,
  `id_alunno` int(11) DEFAULT NULL,
  `id_pacchetto` int(11) DEFAULT NULL,
  `data_pagamento` date NOT NULL,
  `metodo_pagamento` varchar(50) NOT NULL,
  `totale_pagato` decimal(10,2) NOT NULL,
  `tipologia` enum('saldo','acconto') NOT NULL,
  `mese_pagato` varchar(50) NOT NULL,
  `ore_effettuate` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `pagamenti`
--

INSERT INTO `pagamenti` (`id`, `id_alunno`, `id_pacchetto`, `data_pagamento`, `metodo_pagamento`, `totale_pagato`, `tipologia`, `mese_pagato`, `ore_effettuate`) VALUES
(6, 8, NULL, '2025-03-07', 'Contanti', 180.00, 'saldo', 'MENSILE FEBBRAIO', 29),
(12, 8, NULL, '2025-04-28', 'Contanti', 180.00, 'saldo', 'Mensile Marzo', 8),
(13, 30, NULL, '2025-05-02', 'Contanti', 180.00, 'saldo', 'MARZO', 9),
(14, 35, NULL, '2025-05-02', 'Bonifico', 200.00, 'saldo', 'APRILE', 35);

-- --------------------------------------------------------

--
-- Struttura della tabella `storico_modifiche`
--

DROP TABLE IF EXISTS `storico_modifiche`;
CREATE TABLE `storico_modifiche` (
  `id` int(11) NOT NULL,
  `id_utente` int(11) DEFAULT NULL,
  `id_alunno` int(11) DEFAULT NULL,
  `tipo_modifica` varchar(50) NOT NULL,
  `dettagli` text DEFAULT NULL,
  `data_modifica` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `utenti`
--

DROP TABLE IF EXISTS `utenti`;
CREATE TABLE `utenti` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utenti`
--

INSERT INTO `utenti` (`id`, `username`, `password`) VALUES
(2, 'alessandro', 'd46a6166a7921e4e8df10d64c4b1ecdf');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `alunni`
--
ALTER TABLE `alunni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pacchetto` (`id_pacchetto`),
  ADD KEY `id_genitore` (`id_genitore`);

--
-- Indici per le tabelle `genitori`
--
ALTER TABLE `genitori`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `pacchetti`
--
ALTER TABLE `pacchetti`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `pagamenti`
--
ALTER TABLE `pagamenti`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_alunno` (`id_alunno`),
  ADD KEY `id_pacchetto` (`id_pacchetto`);

--
-- Indici per le tabelle `storico_modifiche`
--
ALTER TABLE `storico_modifiche`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utente` (`id_utente`),
  ADD KEY `id_alunno` (`id_alunno`);

--
-- Indici per le tabelle `utenti`
--
ALTER TABLE `utenti`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `alunni`
--
ALTER TABLE `alunni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT per la tabella `genitori`
--
ALTER TABLE `genitori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT per la tabella `pacchetti`
--
ALTER TABLE `pacchetti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT per la tabella `pagamenti`
--
ALTER TABLE `pagamenti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT per la tabella `storico_modifiche`
--
ALTER TABLE `storico_modifiche`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `utenti`
--
ALTER TABLE `utenti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `alunni`
--
ALTER TABLE `alunni`
  ADD CONSTRAINT `alunni_ibfk_1` FOREIGN KEY (`id_pacchetto`) REFERENCES `pacchetti` (`id`),
  ADD CONSTRAINT `alunni_ibfk_2` FOREIGN KEY (`id_genitore`) REFERENCES `genitori` (`id`);

--
-- Limiti per la tabella `pagamenti`
--
ALTER TABLE `pagamenti`
  ADD CONSTRAINT `pagamenti_ibfk_1` FOREIGN KEY (`id_alunno`) REFERENCES `alunni` (`id`),
  ADD CONSTRAINT `pagamenti_ibfk_2` FOREIGN KEY (`id_pacchetto`) REFERENCES `pacchetti` (`id`);

--
-- Limiti per la tabella `storico_modifiche`
--
ALTER TABLE `storico_modifiche`
  ADD CONSTRAINT `storico_modifiche_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `utenti` (`id`),
  ADD CONSTRAINT `storico_modifiche_ibfk_2` FOREIGN KEY (`id_alunno`) REFERENCES `alunni` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
