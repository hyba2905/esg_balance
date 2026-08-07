# - - - - - - - - - - - - - - - - - - - - - - -  - - - 
# 1. OPERAZIONI PER TUTTI GLI UTENTI
# - - - - - - - - - - - - - - - - - - - - - - -  - - - 

DELIMITER $
# Autenticazione / Registrazione sulla piattaforma
CREATE PROCEDURE sp_RegistraUtente(
    IN p_username VARCHAR(50),
    IN p_password VARCHAR(255),
    IN p_cf CHAR(16),
    IN p_data_nascita DATE,
    IN p_luogo_nascita VARCHAR(100),
    IN p_tipo_utente ENUM('amministratore', 'revisore', 'responsabile'),
    IN p_cv_pdf VARCHAR(255)
)
BEGIN
    INSERT INTO Utente (username, password, cf, data_nascita, luogo_nascita, tipo_utente, cv_pdf)
    VALUES (p_username, p_password, p_cf, p_data_nascita, p_luogo_nascita, p_tipo_utente, p_cv_pdf);
END $
DELIMITER ;


# - - - - - - - - - - - - - - - - - - - - - - -  - - - 
# 2. OPERAZIONI SOLO PER UTENTI AMMINISTRATORI
# - - - - - - - - - - - - - - - - - - - - - - -  - - - 

DELIMITER $
# Popolamento della lista degli indicatori ESG
CREATE PROCEDURE sp_InserisciIndicatoreESG(
    IN p_nome VARCHAR(100),
    IN p_immagine VARCHAR(255),
    IN p_rilevanza INT,
    IN p_tipo ENUM('ambientale', 'sociale', 'generale'),
    IN p_codice_normativa VARCHAR(100),
    IN p_ambito_sociale VARCHAR(100),
    IN p_frequenza_rilevazione VARCHAR(50)
)
BEGIN
    INSERT INTO IndicatoreESG (nome, immagine, rilevanza, tipo, codice_normativa, ambito_sociale, frequenza_rilevazione)
    VALUES (p_nome, p_immagine, p_rilevanza, p_tipo, p_codice_normativa, p_ambito_sociale, p_frequenza_rilevazione);
END $
DELIMITER ;

DELIMITER $
# Creazione del "template" di bilancio di esercizio
CREATE PROCEDURE sp_InserisciVoceTemplate(
    IN p_nome_voce VARCHAR(100),
    IN p_descrizione TEXT
)
BEGIN
    INSERT INTO TemplateVoce (nome, descrizione)
    VALUES (p_nome_voce, p_descrizione);
END $
DELIMITER ;

DELIMITER $
# Associazione di revisore ESG ad un bilancio aziendale
CREATE PROCEDURE sp_AssociaRevisoreBilancio(
    IN p_id_revisore INT,
    IN p_id_bilancio INT
)
BEGIN
    INSERT INTO RevisioneBilancio (id_revisore, id_bilancio)
    VALUES (p_id_revisore, p_id_bilancio);
END $
DELIMITER ;


# - - - - - - - - - - - - - - - - - - - - - - -  - - - 
# 3. OPERAZIONI SOLO PER REVISORI ESG
# - - - - - - - - - - - - - - - - - - - - - - -  - - - 

DELIMITER $
# Inserimento delle proprie competenze (nome competenza + livello)
CREATE PROCEDURE sp_InserisciCompetenzaRevisore(
    IN p_id_revisore INT,
    IN p_nome_competenza VARCHAR(100),
    IN p_livello INT
)
BEGIN
    INSERT INTO CompetenzaRevisore (id_revisore, nome_competenza, livello)
    VALUES (p_id_revisore, p_nome_competenza, p_livello)
    ON DUPLICATE KEY UPDATE livello = p_livello;
END $
DELIMITER ;

DELIMITER $
# Inserimento delle note su voci di bilancio
CREATE PROCEDURE sp_InserisciNotaVoce(
    IN p_id_revisore INT,
    IN p_id_bilancio INT,
    IN p_id_voce INT,
    IN p_testo_nota TEXT
)
BEGIN
    INSERT INTO NotaVoce (id_revisore, id_bilancio, id_voce, data_nota, testo_nota)
    VALUES (p_id_revisore, p_id_bilancio, p_id_voce, NOW(), p_testo_nota);
END $
DELIMITER ;

DELIMITER $
# Inserimento del giudizio complessivo
CREATE PROCEDURE sp_InserisciGiudizioBilancio(
    IN p_id_revisore INT,
    IN p_id_bilancio INT,
    IN p_esito ENUM('approvazione', 'approvazione con rilievi', 'respingimento'),
    IN p_rilievi TEXT
)
BEGIN
    UPDATE RevisioneBilancio
    SET esito = p_esito,
        data_giudizio = NOW(),
        rilievi = p_rilievi
    WHERE id_revisore = p_id_revisore AND id_bilancio = p_id_bilancio;
END $
DELIMITER ;


# - - - - - - - - - - - - - - - - - - - - - - -  - - - 
# 4. OPERAZIONI SOLO PER RESPONSABILI AZIENDALI
# - - - - - - - - - - - - - - - - - - - - - - -  - - - 

DELIMITER $
# Registrazione di un'azienda
CREATE PROCEDURE sp_RegistraAzienda(
    IN p_nome VARCHAR(100),
    IN p_ragione_sociale VARCHAR(100),
    IN p_partita_iva CHAR(11),
    IN p_settore VARCHAR(100),
    IN p_num_dipendenti INT,
    IN p_logo VARCHAR(255),
    IN p_id_responsabile INT
)
BEGIN
    INSERT INTO Azienda (nome, ragione_sociale, partita_iva, settore, num_dipendenti, logo, id_responsabile, nr_bilanci)
    VALUES (p_nome, p_ragione_sociale, p_partita_iva, p_settore, p_num_dipendenti, p_logo, p_id_responsabile, 0);
END $
DELIMITER ;

DELIMITER $
# Creazione/popolamento di un nuovo bilancio di esercizio
CREATE PROCEDURE sp_CreaBilancio(
    IN p_id_azienda INT,
    OUT p_id_bilancio INT
)
BEGIN
    INSERT INTO Bilancio (id_azienda, data_creazione, stato)
    VALUES (p_id_azienda, NOW(), 'bozza');
    
    SET p_id_bilancio = LAST_INSERT_ID();
    
    UPDATE Azienda 
    SET nr_bilanci = nr_bilanci + 1 
    WHERE id_azienda = p_id_azienda;
END $
DELIMITER ;

DELIMITER $
# Inserimento dei valori degli indicatori ESG per singole voci di bilancio
CREATE PROCEDURE sp_InserisciValoreESGVoce(
    IN p_id_bilancio INT,
    IN p_id_voce INT,
    IN p_id_indicatore INT,
    IN p_valore_numerico DECIMAL(12,2),
    IN p_fonte VARCHAR(255),
    IN p_data_rilevazione DATE
)
BEGIN
    INSERT INTO VoceBilancioIndicatoreESG (id_bilancio, id_voce, id_indicatore, valore_numerico, fonte, data_rilevazione)
    VALUES (p_id_bilancio, p_id_voce, p_id_indicatore, p_valore_numerico, p_fonte, p_data_rilevazione);
END $
DELIMITER ;


# - - - - - - - - - - - - - - - - - - - - - - -  - - - 
# 5. STATISTICHE (VISIBILI A TUTTI)
# - - - - - - - - - - - - - - - - - - - - - - -  - - - 

# Numero di aziende registrate in piattaforma
CREATE VIEW v_NumeroAziende AS
SELECT COUNT(*) AS totale_aziende
FROM Azienda;

# Numero di revisori ESG registrati in piattaforma
CREATE VIEW v_NumeroRevisoriESG AS
SELECT COUNT(*) AS totale_revisori
FROM Utente
WHERE tipo_utente = 'revisore';

# Azienda con il valore più alto di affidabilità (percentuale di bilanci approvati senza rilievi)
CREATE VIEW v_AziendaPiuAffidabile AS
WITH StatsAzienda AS (
    SELECT 
        a.id_azienda,
        a.nome,
        a.ragione_sociale,
        COUNT(b.id_bilancio) AS totale_bilanci,
        SUM(CASE WHEN b.stato = 'approvato' AND NOT EXISTS (
            SELECT 1 FROM RevisioneBilancio rb 
            WHERE rb.id_bilancio = b.id_bilancio AND rb.esito != 'approvazione'
        ) THEN 1 ELSE 0 END) AS bilanci_senza_rilievi
    FROM Azienda a
    JOIN Bilancio b ON a.id_azienda = b.id_azienda
    GROUP BY a.id_azienda, a.nome, a.ragione_sociale
)
SELECT 
    id_azienda,
    nome,
    ragione_sociale,
    (bilanci_senza_rilievi / totale_bilanci) * 100 AS percentuale_affidabilita
FROM StatsAzienda
ORDER BY percentuale_affidabilita DESC
LIMIT 1;

# Classifica dei bilanci aziendali ordinati in base al numero totale di indicatori ESG connessi
CREATE VIEW v_ClassificaBilanciESG AS
SELECT 
    b.id_bilancio,
    a.nome AS nome_azienda,
    b.data_creazione,
    COUNT(vbe.id_indicatore) AS totale_indicatori_esg
FROM Bilancio b
JOIN Azienda a ON b.id_azienda = a.id_azienda
LEFT JOIN VoceBilancioIndicatoreESG vbe ON b.id_bilancio = vbe.id_bilancio
GROUP BY b.id_bilancio, a.nome, b.data_creazione
ORDER BY totale_indicatori_esg DESC;