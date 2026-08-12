-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Creato il: Ago 10, 2026 alle 18:07
-- Versione del server: 5.7.24
-- Versione PHP: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `esg_balance`
--

DELIMITER $$
--
-- Procedure
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AssociaRevisoreBilancio` (IN `p_id_revisore` INT, IN `p_id_bilancio` INT)   BEGIN
    INSERT INTO RevisioneBilancio (
        id_revisore,
        id_bilancio
    )
    VALUES (
        p_id_revisore,
        p_id_bilancio
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CreaBilancio` (IN `p_id_azienda` INT, OUT `p_id_bilancio` INT)   BEGIN
    INSERT INTO Bilancio (
        id_azienda,
        data_creazione,
        stato
    )
    VALUES (
        p_id_azienda,
        NOW(),
        'bozza'
    );

    SET p_id_bilancio = LAST_INSERT_ID();

    UPDATE Azienda
    SET nr_bilanci = nr_bilanci + 1
    WHERE id_azienda = p_id_azienda;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_InserisciCompetenzaRevisore` (IN `p_id_revisore` INT, IN `p_id_competenza` INT, IN `p_livello` INT)   BEGIN
    INSERT INTO CompetenzaRevisore (
        id_revisore,
        id_competenza,
        livello
    )
    VALUES (
        p_id_revisore,
        p_id_competenza,
        p_livello
    )
    ON DUPLICATE KEY UPDATE
        livello = p_livello;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_InserisciEmailUtente` (IN `p_id_utente` INT, IN `p_email` VARCHAR(255))   BEGIN
    INSERT INTO EmailUtente (
        id_utente,
        email
    )
    VALUES (
        p_id_utente,
        p_email
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_InserisciGiudizioBilancio` (IN `p_id_revisore` INT, IN `p_id_bilancio` INT, IN `p_esito` ENUM('approvazione','approvazione con rilievi','respingimento'), IN `p_rilievi` TEXT)   BEGIN
    UPDATE RevisioneBilancio
    SET
        esito = p_esito,
        data_giudizio = NOW(),
        rilievi = p_rilievi
    WHERE
        id_revisore = p_id_revisore
        AND id_bilancio = p_id_bilancio;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_InserisciIndicatoreESG` (IN `p_nome` VARCHAR(100), IN `p_immagine` VARCHAR(255), IN `p_rilevanza` INT, IN `p_tipo` ENUM('ambientale','sociale','generale'), IN `p_codice_normativa` VARCHAR(100), IN `p_ambito_sociale` VARCHAR(100), IN `p_frequenza_rilevazione` VARCHAR(50))   BEGIN
    DECLARE v_id_indicatore INT;

    INSERT INTO IndicatoreESG (
        nome,
        immagine,
        rilevanza,
        tipo
    )
    VALUES (
        p_nome,
        p_immagine,
        p_rilevanza,
        p_tipo
    );

    SET v_id_indicatore = LAST_INSERT_ID();

    IF p_tipo = 'ambientale' THEN

        INSERT INTO IndicatoreAmbientale (
            id_indicatore,
            codice_normativa
        )
        VALUES (
            v_id_indicatore,
            p_codice_normativa
        );

    ELSEIF p_tipo = 'sociale' THEN

        INSERT INTO IndicatoreSociale (
            id_indicatore,
            ambito_sociale,
            frequenza_rilevazione
        )
        VALUES (
            v_id_indicatore,
            p_ambito_sociale,
            p_frequenza_rilevazione
        );

    END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_InserisciNotaVoce` (IN `p_id_revisore` INT, IN `p_id_bilancio` INT, IN `p_id_voce` INT, IN `p_testo_nota` TEXT)   BEGIN
    INSERT INTO NotaVoce (
        id_revisore,
        id_bilancio,
        id_voce,
        data_nota,
        testo_nota
    )
    VALUES (
        p_id_revisore,
        p_id_bilancio,
        p_id_voce,
        NOW(),
        p_testo_nota
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_InserisciValoreESGVoce` (IN `p_id_bilancio` INT, IN `p_id_voce` INT, IN `p_id_indicatore` INT, IN `p_valore_numerico` DECIMAL(15,2), IN `p_fonte` VARCHAR(255), IN `p_data_rilevazione` DATE)   BEGIN
    INSERT INTO VoceBilancioIndicatoreESG (
        id_bilancio,
        id_voce,
        id_indicatore,
        valore_numerico,
        fonte,
        data_rilevazione
    )
    VALUES (
        p_id_bilancio,
        p_id_voce,
        p_id_indicatore,
        p_valore_numerico,
        p_fonte,
        p_data_rilevazione
    )
    ON DUPLICATE KEY UPDATE
        valore_numerico = p_valore_numerico,
        fonte = p_fonte,
        data_rilevazione = p_data_rilevazione;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_InserisciValoreVoceBilancio` (IN `p_id_bilancio` INT, IN `p_id_voce` INT, IN `p_valore_numerico` DECIMAL(15,2))   BEGIN
    INSERT INTO ValoreVoceBilancio (
        id_bilancio,
        id_voce,
        valore_numerico
    )
    VALUES (
        p_id_bilancio,
        p_id_voce,
        p_valore_numerico
    )
    ON DUPLICATE KEY UPDATE
        valore_numerico = p_valore_numerico;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_InserisciVoceTemplate` (IN `p_nome_voce` VARCHAR(100), IN `p_descrizione` TEXT)   BEGIN
    INSERT INTO VoceContabile (
        nome,
        descrizione
    )
    VALUES (
        p_nome_voce,
        p_descrizione
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_RegistraAzienda` (IN `p_nome` VARCHAR(100), IN `p_ragione_sociale` VARCHAR(150), IN `p_partita_iva` CHAR(11), IN `p_settore` VARCHAR(100), IN `p_num_dipendenti` INT, IN `p_logo` VARCHAR(255), IN `p_id_responsabile` INT)   BEGIN
    INSERT INTO Azienda (
        nome,
        ragione_sociale,
        partita_iva,
        settore,
        num_dipendenti,
        logo,
        nr_bilanci,
        id_responsabile
    )
    VALUES (
        p_nome,
        p_ragione_sociale,
        p_partita_iva,
        p_settore,
        p_num_dipendenti,
        p_logo,
        0,
        p_id_responsabile
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_RegistraUtente` (IN `p_username` VARCHAR(50), IN `p_password` VARCHAR(255), IN `p_cf` CHAR(16), IN `p_data_nascita` DATE, IN `p_luogo_nascita` VARCHAR(100), IN `p_tipo_utente` ENUM('amministratore','revisore','responsabile'))   BEGIN
    INSERT INTO Utente (
        username,
        password,
        cf,
        data_nascita,
        luogo_nascita,
        tipo_utente
    )
    VALUES (
        p_username,
        p_password,
        p_cf,
        p_data_nascita,
        p_luogo_nascita,
        p_tipo_utente
    );
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Struttura della tabella `amministratore`
--

CREATE TABLE `amministratore` (
  `id_amministratore` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dump dei dati per la tabella `amministratore`
--

INSERT INTO `amministratore` (`id_amministratore`) VALUES
(1);

-- --------------------------------------------------------

--
-- Struttura della tabella `azienda`
--

CREATE TABLE `azienda` (
  `id_azienda` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `ragione_sociale` varchar(150) NOT NULL,
  `partita_iva` char(11) NOT NULL,
  `settore` varchar(100) NOT NULL,
  `num_dipendenti` int(11) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `nr_bilanci` int(11) NOT NULL DEFAULT '0',
  `id_responsabile` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dump dei dati per la tabella `azienda`
--

INSERT INTO `azienda` (`id_azienda`, `nome`, `ragione_sociale`, `partita_iva`, `settore`, `num_dipendenti`, `logo`, `nr_bilanci`, `id_responsabile`) VALUES
(1, 'Green Solutions', 'Green Solutions S.r.l.', '12345678901', 'Energia', 25, 'logo_green.png', 2, 2);

-- --------------------------------------------------------

--
-- Struttura della tabella `bilancio`
--

CREATE TABLE `bilancio` (
  `id_bilancio` int(11) NOT NULL,
  `id_azienda` int(11) NOT NULL,
  `data_creazione` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `stato` enum('bozza','in revisione','approvato','respinto') NOT NULL DEFAULT 'bozza'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dump dei dati per la tabella `bilancio`
--

INSERT INTO `bilancio` (`id_bilancio`, `id_azienda`, `data_creazione`, `stato`) VALUES
(1, 1, '2026-08-10 18:39:43', 'approvato'),
(2, 1, '2026-08-10 18:50:44', 'approvato');

-- --------------------------------------------------------

--
-- Struttura della tabella `competenza`
--

CREATE TABLE `competenza` (
  `id_competenza` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dump dei dati per la tabella `competenza`
--

INSERT INTO `competenza` (`id_competenza`, `nome`) VALUES
(1, 'Analisi ESG');

-- --------------------------------------------------------

--
-- Struttura della tabella `competenzarevisore`
--

CREATE TABLE `competenzarevisore` (
  `id_revisore` int(11) NOT NULL,
  `id_competenza` int(11) NOT NULL,
  `livello` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dump dei dati per la tabella `competenzarevisore`
--

INSERT INTO `competenzarevisore` (`id_revisore`, `id_competenza`, `livello`) VALUES
(3, 1, 8);

-- --------------------------------------------------------

--
-- Struttura della tabella `emailutente`
--

CREATE TABLE `emailutente` (
  `id_utente` int(11) NOT NULL,
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dump dei dati per la tabella `emailutente`
--

INSERT INTO `emailutente` (`id_utente`, `email`) VALUES
(1, 'admin1@example.com'),
(2, 'responsabile1@example.com'),
(3, 'revisore1@example.com'),
(4, 'revisore2@example.com');

-- --------------------------------------------------------

--
-- Struttura della tabella `indicatoreambientale`
--

CREATE TABLE `indicatoreambientale` (
  `id_indicatore` int(11) NOT NULL,
  `codice_normativa` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dump dei dati per la tabella `indicatoreambientale`
--

INSERT INTO `indicatoreambientale` (`id_indicatore`, `codice_normativa`) VALUES
(1, 'ISO 14001');

-- --------------------------------------------------------

--
-- Struttura della tabella `indicatoreesg`
--

CREATE TABLE `indicatoreesg` (
  `id_indicatore` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `immagine` varchar(255) DEFAULT NULL,
  `rilevanza` int(11) NOT NULL,
  `tipo` enum('ambientale','sociale','generale') NOT NULL DEFAULT 'generale'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dump dei dati per la tabella `indicatoreesg`
--

INSERT INTO `indicatoreesg` (`id_indicatore`, `nome`, `immagine`, `rilevanza`, `tipo`) VALUES
(1, 'Consumo energia elettrica', 'energia.png', 8, 'ambientale'),
(2, 'Formazione dipendenti', 'formazione.png', 7, 'sociale');

-- --------------------------------------------------------

--
-- Struttura della tabella `indicatoresociale`
--

CREATE TABLE `indicatoresociale` (
  `id_indicatore` int(11) NOT NULL,
  `ambito_sociale` varchar(100) NOT NULL,
  `frequenza_rilevazione` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dump dei dati per la tabella `indicatoresociale`
--

INSERT INTO `indicatoresociale` (`id_indicatore`, `ambito_sociale`, `frequenza_rilevazione`) VALUES
(2, 'Formazione e sviluppo del personale', '12');

-- --------------------------------------------------------

--
-- Struttura della tabella `notavoce`
--

CREATE TABLE `notavoce` (
  `id_nota` int(11) NOT NULL,
  `id_revisore` int(11) NOT NULL,
  `id_bilancio` int(11) NOT NULL,
  `id_voce` int(11) NOT NULL,
  `data_nota` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `testo_nota` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dump dei dati per la tabella `notavoce`
--

INSERT INTO `notavoce` (`id_nota`, `id_revisore`, `id_bilancio`, `id_voce`, `data_nota`, `testo_nota`) VALUES
(1, 3, 2, 1, '2026-08-10 19:26:50', 'Verificare il valore associato alla voce Ricavi vendite');

-- --------------------------------------------------------

--
-- Struttura della tabella `responsabileaziendale`
--

CREATE TABLE `responsabileaziendale` (
  `id_responsabile` int(11) NOT NULL,
  `cv_pdf` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dump dei dati per la tabella `responsabileaziendale`
--

INSERT INTO `responsabileaziendale` (`id_responsabile`, `cv_pdf`) VALUES
(2, 'cv_responsabile1.pdf');

-- --------------------------------------------------------

--
-- Struttura della tabella `revisionebilancio`
--

CREATE TABLE `revisionebilancio` (
  `id_revisore` int(11) NOT NULL,
  `id_bilancio` int(11) NOT NULL,
  `esito` enum('approvazione','approvazione con rilievi','respingimento') DEFAULT NULL,
  `data_giudizio` datetime DEFAULT NULL,
  `rilievi` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dump dei dati per la tabella `revisionebilancio`
--

INSERT INTO `revisionebilancio` (`id_revisore`, `id_bilancio`, `esito`, `data_giudizio`, `rilievi`) VALUES
(3, 1, 'approvazione', '2026-08-10 18:47:04', NULL),
(3, 2, 'approvazione', '2026-08-10 18:52:03', NULL),
(4, 2, 'approvazione con rilievi', '2026-08-10 18:53:32', 'Verificare alcuni dati ESG');

--
-- Trigger `revisionebilancio`
--
DELIMITER $$
CREATE TRIGGER `trg_AggiornaEsitoBilancio` AFTER UPDATE ON `revisionebilancio` FOR EACH ROW BEGIN
    DECLARE v_totale_revisori INT;
    DECLARE v_totale_giudizi INT;
    DECLARE v_respingimenti INT;

    SELECT COUNT(*)
    INTO v_totale_revisori
    FROM RevisioneBilancio
    WHERE id_bilancio = NEW.id_bilancio;

    SELECT COUNT(*)
    INTO v_totale_giudizi
    FROM RevisioneBilancio
    WHERE id_bilancio = NEW.id_bilancio
      AND esito IS NOT NULL;

    IF v_totale_revisori = v_totale_giudizi THEN

        SELECT COUNT(*)
        INTO v_respingimenti
        FROM RevisioneBilancio
        WHERE id_bilancio = NEW.id_bilancio
          AND esito = 'respingimento';

        IF v_respingimenti > 0 THEN

            UPDATE Bilancio
            SET stato = 'respinto'
            WHERE id_bilancio = NEW.id_bilancio;

        ELSE

            UPDATE Bilancio
            SET stato = 'approvato'
            WHERE id_bilancio = NEW.id_bilancio;

        END IF;

    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_BilancioInRevisione` AFTER INSERT ON `revisionebilancio` FOR EACH ROW BEGIN
    UPDATE Bilancio
    SET stato = 'in revisione'
    WHERE id_bilancio = NEW.id_bilancio;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_IncrementaNumeroRevisioni` AFTER INSERT ON `revisionebilancio` FOR EACH ROW BEGIN
    UPDATE RevisoreESG
    SET nr_revisioni = nr_revisioni + 1
    WHERE id_revisore = NEW.id_revisore;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struttura della tabella `revisoreesg`
--

CREATE TABLE `revisoreesg` (
  `id_revisore` int(11) NOT NULL,
  `nr_revisioni` int(11) NOT NULL DEFAULT '0',
  `indice_affidabilita` decimal(5,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dump dei dati per la tabella `revisoreesg`
--

INSERT INTO `revisoreesg` (`id_revisore`, `nr_revisioni`, `indice_affidabilita`) VALUES
(3, 2, '0.00'),
(4, 1, '0.00');

-- --------------------------------------------------------

--
-- Struttura della tabella `utente`
--

CREATE TABLE `utente` (
  `id_utente` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `cf` char(16) NOT NULL,
  `data_nascita` date NOT NULL,
  `luogo_nascita` varchar(100) NOT NULL,
  `tipo_utente` enum('amministratore','revisore','responsabile') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dump dei dati per la tabella `utente`
--

INSERT INTO `utente` (`id_utente`, `username`, `password`, `cf`, `data_nascita`, `luogo_nascita`, `tipo_utente`) VALUES
(1, 'admin1', 'password123', 'RSSMRA80A01H501U', '1980-01-01', 'Roma', 'amministratore'),
(2, 'responsabile1', 'password123', 'BNCLGU90B02F205X', '1990-02-02', 'Milano', 'responsabile'),
(3, 'revisore1', 'password123', 'VRDLGI85C03H501Z', '1985-03-03', 'Roma', 'revisore'),
(4, 'revisore2', 'password123', 'NRINNA88D04F205Q', '1988-04-04', 'Milano', 'revisore');

-- --------------------------------------------------------

--
-- Struttura della tabella `valorevocebilancio`
--

CREATE TABLE `valorevocebilancio` (
  `id_bilancio` int(11) NOT NULL,
  `id_voce` int(11) NOT NULL,
  `valore_numerico` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dump dei dati per la tabella `valorevocebilancio`
--

INSERT INTO `valorevocebilancio` (`id_bilancio`, `id_voce`, `valore_numerico`) VALUES
(2, 1, '150000.00'),
(2, 2, '60000.00');

-- --------------------------------------------------------

--
-- Struttura della tabella `vocebilancioindicatoreesg`
--

CREATE TABLE `vocebilancioindicatoreesg` (
  `id_bilancio` int(11) NOT NULL,
  `id_voce` int(11) NOT NULL,
  `id_indicatore` int(11) NOT NULL,
  `valore_numerico` decimal(15,2) NOT NULL,
  `fonte` varchar(255) DEFAULT NULL,
  `data_rilevazione` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dump dei dati per la tabella `vocebilancioindicatoreesg`
--

INSERT INTO `vocebilancioindicatoreesg` (`id_bilancio`, `id_voce`, `id_indicatore`, `valore_numerico`, `fonte`, `data_rilevazione`) VALUES
(2, 1, 1, '12500.00', 'Bolletta energia 2026', '2026-08-10');

-- --------------------------------------------------------

--
-- Struttura della tabella `vocecontabile`
--

CREATE TABLE `vocecontabile` (
  `id_voce` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descrizione` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dump dei dati per la tabella `vocecontabile`
--

INSERT INTO `vocecontabile` (`id_voce`, `nome`, `descrizione`) VALUES
(1, 'Ricavi vendite', 'Totale dei ricavi derivanti dalle vendite'),
(2, 'Costo del personale', 'Totale dei costi sostenuti per il personale');

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `v_aziendapiuaffidabile`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `v_aziendapiuaffidabile` (
`id_azienda` int(11)
,`nome` varchar(100)
,`ragione_sociale` varchar(150)
,`percentuale_affidabilita` decimal(29,2)
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `v_classificabilanciesg`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `v_classificabilanciesg` (
`id_bilancio` int(11)
,`nome_azienda` varchar(100)
,`data_creazione` datetime
,`totale_indicatori_esg` bigint(21)
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `v_numeroaziende`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `v_numeroaziende` (
`totale_aziende` bigint(21)
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `v_numerorevisoriesg`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `v_numerorevisoriesg` (
`totale_revisori` bigint(21)
);

-- --------------------------------------------------------

--
-- Struttura per vista `v_aziendapiuaffidabile`
--
DROP TABLE IF EXISTS `v_aziendapiuaffidabile`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_aziendapiuaffidabile`  AS SELECT `a`.`id_azienda` AS `id_azienda`, `a`.`nome` AS `nome`, `a`.`ragione_sociale` AS `ragione_sociale`, round(((100.0 * sum((case when ((not(exists(select 1 from `revisionebilancio` `rb` where ((`rb`.`id_bilancio` = `b`.`id_bilancio`) and (`rb`.`esito` <> 'approvazione'))))) and exists(select 1 from `revisionebilancio` `rb2` where ((`rb2`.`id_bilancio` = `b`.`id_bilancio`) and (`rb2`.`esito` = 'approvazione')))) then 1 else 0 end))) / count(`b`.`id_bilancio`)),2) AS `percentuale_affidabilita` FROM (`azienda` `a` join `bilancio` `b` on((`a`.`id_azienda` = `b`.`id_azienda`))) GROUP BY `a`.`id_azienda`, `a`.`nome`, `a`.`ragione_sociale` ORDER BY `percentuale_affidabilita` DESC LIMIT 0, 11  ;

-- --------------------------------------------------------

--
-- Struttura per vista `v_classificabilanciesg`
--
DROP TABLE IF EXISTS `v_classificabilanciesg`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_classificabilanciesg`  AS SELECT `b`.`id_bilancio` AS `id_bilancio`, `a`.`nome` AS `nome_azienda`, `b`.`data_creazione` AS `data_creazione`, count(`vbe`.`id_indicatore`) AS `totale_indicatori_esg` FROM ((`bilancio` `b` join `azienda` `a` on((`b`.`id_azienda` = `a`.`id_azienda`))) left join `vocebilancioindicatoreesg` `vbe` on((`b`.`id_bilancio` = `vbe`.`id_bilancio`))) GROUP BY `b`.`id_bilancio`, `a`.`nome`, `b`.`data_creazione` ORDER BY `totale_indicatori_esg` DESC  ;

-- --------------------------------------------------------

--
-- Struttura per vista `v_numeroaziende`
--
DROP TABLE IF EXISTS `v_numeroaziende`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_numeroaziende`  AS SELECT count(0) AS `totale_aziende` FROM `azienda`  ;

-- --------------------------------------------------------

--
-- Struttura per vista `v_numerorevisoriesg`
--
DROP TABLE IF EXISTS `v_numerorevisoriesg`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_numerorevisoriesg`  AS SELECT count(0) AS `totale_revisori` FROM `utente` WHERE (`utente`.`tipo_utente` = 'revisore')  ;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `amministratore`
--
ALTER TABLE `amministratore`
  ADD PRIMARY KEY (`id_amministratore`);

--
-- Indici per le tabelle `azienda`
--
ALTER TABLE `azienda`
  ADD PRIMARY KEY (`id_azienda`),
  ADD UNIQUE KEY `ragione_sociale` (`ragione_sociale`),
  ADD UNIQUE KEY `partita_iva` (`partita_iva`),
  ADD KEY `id_responsabile` (`id_responsabile`);

--
-- Indici per le tabelle `bilancio`
--
ALTER TABLE `bilancio`
  ADD PRIMARY KEY (`id_bilancio`),
  ADD KEY `id_azienda` (`id_azienda`);

--
-- Indici per le tabelle `competenza`
--
ALTER TABLE `competenza`
  ADD PRIMARY KEY (`id_competenza`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Indici per le tabelle `competenzarevisore`
--
ALTER TABLE `competenzarevisore`
  ADD PRIMARY KEY (`id_revisore`,`id_competenza`),
  ADD KEY `id_competenza` (`id_competenza`);

--
-- Indici per le tabelle `emailutente`
--
ALTER TABLE `emailutente`
  ADD PRIMARY KEY (`id_utente`,`email`);

--
-- Indici per le tabelle `indicatoreambientale`
--
ALTER TABLE `indicatoreambientale`
  ADD PRIMARY KEY (`id_indicatore`);

--
-- Indici per le tabelle `indicatoreesg`
--
ALTER TABLE `indicatoreesg`
  ADD PRIMARY KEY (`id_indicatore`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Indici per le tabelle `indicatoresociale`
--
ALTER TABLE `indicatoresociale`
  ADD PRIMARY KEY (`id_indicatore`);

--
-- Indici per le tabelle `notavoce`
--
ALTER TABLE `notavoce`
  ADD PRIMARY KEY (`id_nota`),
  ADD KEY `id_revisore` (`id_revisore`,`id_bilancio`),
  ADD KEY `id_bilancio` (`id_bilancio`,`id_voce`);

--
-- Indici per le tabelle `responsabileaziendale`
--
ALTER TABLE `responsabileaziendale`
  ADD PRIMARY KEY (`id_responsabile`);

--
-- Indici per le tabelle `revisionebilancio`
--
ALTER TABLE `revisionebilancio`
  ADD PRIMARY KEY (`id_revisore`,`id_bilancio`),
  ADD KEY `id_bilancio` (`id_bilancio`);

--
-- Indici per le tabelle `revisoreesg`
--
ALTER TABLE `revisoreesg`
  ADD PRIMARY KEY (`id_revisore`);

--
-- Indici per le tabelle `utente`
--
ALTER TABLE `utente`
  ADD PRIMARY KEY (`id_utente`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `cf` (`cf`);

--
-- Indici per le tabelle `valorevocebilancio`
--
ALTER TABLE `valorevocebilancio`
  ADD PRIMARY KEY (`id_bilancio`,`id_voce`),
  ADD KEY `id_voce` (`id_voce`);

--
-- Indici per le tabelle `vocebilancioindicatoreesg`
--
ALTER TABLE `vocebilancioindicatoreesg`
  ADD PRIMARY KEY (`id_bilancio`,`id_voce`,`id_indicatore`),
  ADD KEY `id_indicatore` (`id_indicatore`);

--
-- Indici per le tabelle `vocecontabile`
--
ALTER TABLE `vocecontabile`
  ADD PRIMARY KEY (`id_voce`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `azienda`
--
ALTER TABLE `azienda`
  MODIFY `id_azienda` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `bilancio`
--
ALTER TABLE `bilancio`
  MODIFY `id_bilancio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `competenza`
--
ALTER TABLE `competenza`
  MODIFY `id_competenza` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `indicatoreesg`
--
ALTER TABLE `indicatoreesg`
  MODIFY `id_indicatore` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `notavoce`
--
ALTER TABLE `notavoce`
  MODIFY `id_nota` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `utente`
--
ALTER TABLE `utente`
  MODIFY `id_utente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `vocecontabile`
--
ALTER TABLE `vocecontabile`
  MODIFY `id_voce` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `amministratore`
--
ALTER TABLE `amministratore`
  ADD CONSTRAINT `amministratore_ibfk_1` FOREIGN KEY (`id_amministratore`) REFERENCES `utente` (`id_utente`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `azienda`
--
ALTER TABLE `azienda`
  ADD CONSTRAINT `azienda_ibfk_1` FOREIGN KEY (`id_responsabile`) REFERENCES `responsabileaziendale` (`id_responsabile`) ON UPDATE CASCADE;

--
-- Limiti per la tabella `bilancio`
--
ALTER TABLE `bilancio`
  ADD CONSTRAINT `bilancio_ibfk_1` FOREIGN KEY (`id_azienda`) REFERENCES `azienda` (`id_azienda`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `competenzarevisore`
--
ALTER TABLE `competenzarevisore`
  ADD CONSTRAINT `competenzarevisore_ibfk_1` FOREIGN KEY (`id_revisore`) REFERENCES `revisoreesg` (`id_revisore`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `competenzarevisore_ibfk_2` FOREIGN KEY (`id_competenza`) REFERENCES `competenza` (`id_competenza`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `emailutente`
--
ALTER TABLE `emailutente`
  ADD CONSTRAINT `emailutente_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `utente` (`id_utente`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `indicatoreambientale`
--
ALTER TABLE `indicatoreambientale`
  ADD CONSTRAINT `indicatoreambientale_ibfk_1` FOREIGN KEY (`id_indicatore`) REFERENCES `indicatoreesg` (`id_indicatore`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `indicatoresociale`
--
ALTER TABLE `indicatoresociale`
  ADD CONSTRAINT `indicatoresociale_ibfk_1` FOREIGN KEY (`id_indicatore`) REFERENCES `indicatoreesg` (`id_indicatore`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `notavoce`
--
ALTER TABLE `notavoce`
  ADD CONSTRAINT `notavoce_ibfk_1` FOREIGN KEY (`id_revisore`,`id_bilancio`) REFERENCES `revisionebilancio` (`id_revisore`, `id_bilancio`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notavoce_ibfk_2` FOREIGN KEY (`id_bilancio`,`id_voce`) REFERENCES `valorevocebilancio` (`id_bilancio`, `id_voce`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `responsabileaziendale`
--
ALTER TABLE `responsabileaziendale`
  ADD CONSTRAINT `responsabileaziendale_ibfk_1` FOREIGN KEY (`id_responsabile`) REFERENCES `utente` (`id_utente`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `revisionebilancio`
--
ALTER TABLE `revisionebilancio`
  ADD CONSTRAINT `revisionebilancio_ibfk_1` FOREIGN KEY (`id_revisore`) REFERENCES `revisoreesg` (`id_revisore`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `revisionebilancio_ibfk_2` FOREIGN KEY (`id_bilancio`) REFERENCES `bilancio` (`id_bilancio`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `revisoreesg`
--
ALTER TABLE `revisoreesg`
  ADD CONSTRAINT `revisoreesg_ibfk_1` FOREIGN KEY (`id_revisore`) REFERENCES `utente` (`id_utente`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `valorevocebilancio`
--
ALTER TABLE `valorevocebilancio`
  ADD CONSTRAINT `valorevocebilancio_ibfk_1` FOREIGN KEY (`id_bilancio`) REFERENCES `bilancio` (`id_bilancio`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `valorevocebilancio_ibfk_2` FOREIGN KEY (`id_voce`) REFERENCES `vocecontabile` (`id_voce`) ON UPDATE CASCADE;

--
-- Limiti per la tabella `vocebilancioindicatoreesg`
--
ALTER TABLE `vocebilancioindicatoreesg`
  ADD CONSTRAINT `vocebilancioindicatoreesg_ibfk_1` FOREIGN KEY (`id_bilancio`,`id_voce`) REFERENCES `valorevocebilancio` (`id_bilancio`, `id_voce`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `vocebilancioindicatoreesg_ibfk_2` FOREIGN KEY (`id_indicatore`) REFERENCES `indicatoreesg` (`id_indicatore`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
