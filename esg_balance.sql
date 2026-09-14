DROP DATABASE IF EXISTS ESG_BALANCE;
CREATE DATABASE ESG_BALANCE;
USE ESG_BALANCE;

#----- creazione tabelle ------#

#-------- tabella UTENTE ---------#
CREATE TABLE Utente(
	IdUtente int AUTO_INCREMENT primary key,
	Username varchar(50) NOT NULL UNIQUE,
	Password varchar(255) NOT NULL,
	CF char(16) NOT NULL UNIQUE,
	DataNascita date NOT NULL,
	LuogoNascita varchar(100) NOT NULL,
	TipoUtente ENUM('amministratore', 'revisore', 'responsabile') NOT NULL,
	CurriculumVitae varchar(255),
	NumeroRevisioni int DEFAULT 0,
	IndiceAffidabilita decimal(5,2) DEFAULT 0.00
)Engine=InnoDB;
#-------------------------#

#-------- tabella EMAIL_UTENTE ---------#
CREATE TABLE EMAIL_UTENTE(
	IdUtente int,
	Email varchar(100),
	Primary key(IdUtente, Email),
	foreign key (IdUtente) references Utente(IdUtente) ON DELETE CASCADE
)Engine=InnoDB;
#-------------------------#

#-------- tabella COMPETENZA_REVISORE ---------#
CREATE TABLE COMPETENZA_REVISORE(
	IdRevisore int,
	NomeCompetenza varchar(100),
	Livello int CHECK (Livello BETWEEN 0 AND 5),
	Primary key(IdRevisore, NomeCompetenza),
	foreign key (IdRevisore) references Utente(IdUtente) ON DELETE CASCADE
)Engine=InnoDB;
#-------------------------#

#--------- tabella AZIENDA ---------#
CREATE TABLE AZIENDA(
	IdAzienda int AUTO_INCREMENT primary key,
	Nome varchar(100) NOT NULL,
	RagioneSociale varchar(100) NOT NULL UNIQUE,
	PartitaIVA char(11) NOT NULL UNIQUE,
	Settore varchar(100) NOT NULL,
	NumeroDipendenti int NOT NULL,
	Logo varchar(255),
	NrBilanci int DEFAULT 0,
	IdResponsabile int NOT NULL,
	foreign key (IdResponsabile) references Utente(IdUtente)
)Engine=InnoDB;
#-------------------------#

#--------- tabella TEMPLATE_VOCE ---------#
CREATE TABLE TEMPLATE_VOCE(
	IdVoce int AUTO_INCREMENT primary key,
	Nome varchar(100) NOT NULL UNIQUE,
	Descrizione text
)Engine=InnoDB;
#-------------------------#

#--------- tabella BILANCIO ---------#
CREATE TABLE BILANCIO(
	IdBilancio int AUTO_INCREMENT primary key,
	IdAzienda int NOT NULL,
	DataCreazione datetime NOT NULL,
	Stato ENUM('bozza', 'in revisione', 'approvato', 'respinto') DEFAULT 'bozza',
	foreign key (IdAzienda) references AZIENDA(IdAzienda) ON DELETE CASCADE
)Engine=InnoDB;
#-------------------------#

#--------- tabella VOCE_BILANCIO ---------#
CREATE TABLE VOCE_BILANCIO(
	IdBilancio int,
	IdVoce int,
	Valore decimal(15,2) NOT NULL,
	Primary key(IdBilancio, IdVoce),
	foreign key (IdBilancio) references BILANCIO(IdBilancio) ON DELETE CASCADE,
	foreign key (IdVoce) references TEMPLATE_VOCE(IdVoce)
)Engine=InnoDB;
#-------------------------#

#--------- tabella INDICATORE_ESG ---------#
CREATE TABLE INDICATORE_ESG(
	IdIndicatore int AUTO_INCREMENT primary key,
	Nome varchar(100) NOT NULL UNIQUE,
	Immagine varchar(255),
	Rilevanza int CHECK (Rilevanza BETWEEN 0 AND 10),
	Tipo ENUM('ambientale', 'sociale', 'generale') NOT NULL,
	CodiceNormativa varchar(100),
	AmbitoSociale varchar(100),
	FrequenzaRilevazione varchar(50)
)Engine=InnoDB;
#-------------------------#

#--------- tabella VOCE_INDICATORE_ESG ---------#
CREATE TABLE VOCE_INDICATORE_ESG(
	IdBilancio int,
	IdVoce int,
	IdIndicatore int,
	ValoreNumerico decimal(12,2) NOT NULL,
	Fonte varchar(255) NOT NULL,
	DataRilevazione date NOT NULL,
	Primary key(IdBilancio, IdVoce, IdIndicatore),
	foreign key (IdBilancio, IdVoce) references VOCE_BILANCIO(IdBilancio, IdVoce) ON DELETE CASCADE,
	foreign key (IdIndicatore) references INDICATORE_ESG(IdIndicatore)
)Engine=InnoDB;
#-------------------------#

#--------- tabella REVISIONE_BILANCIO ---------#
CREATE TABLE REVISIONE_BILANCIO(
	IdRevisore int,
	IdBilancio int,
	Esito ENUM('approvazione', 'approvazione con rilievi', 'respingimento'),
	DataGiudizio datetime,
	Rilievi text,
	Primary key(IdRevisore, IdBilancio),
	foreign key (IdRevisore) references Utente(IdUtente),
	foreign key (IdBilancio) references BILANCIO(IdBilancio) ON DELETE CASCADE
)Engine=InnoDB;
#-------------------------#

#--------- tabella NOTA_VOCE ---------#
CREATE TABLE NOTA_VOCE(
	IdNota int AUTO_INCREMENT primary key,
	IdRevisore int,
	IdBilancio int,
	IdVoce int,
	DataNota datetime NOT NULL,
	TestoNota text NOT NULL,
	foreign key (IdRevisore) references Utente(IdUtente),
	foreign key (IdBilancio, IdVoce) references VOCE_BILANCIO(IdBilancio, IdVoce) ON DELETE CASCADE
)Engine=InnoDB;
#-------------------------#

# ----------------------------------#

#--------- 3. Implementazione Trigger -----------#

#--------- Trigger a ----------#
DELIMITER %
	CREATE TRIGGER CambiaStatoInRevisione
	AFTER INSERT ON REVISIONE_BILANCIO
	FOR EACH ROW
		BEGIN
			-- Lo stato diventa 'in revisione' quando viene associato un revisore ad un bilancio in bozza
			UPDATE BILANCIO
			SET Stato = 'in revisione'
			WHERE (IdBilancio = NEW.IdBilancio) AND (Stato = 'bozza');
		END
% DELIMITER ;
#-------------------------#

#--------- Trigger b ----------#
DELIMITER %
	CREATE TRIGGER ValutaStatoBilancio
	AFTER UPDATE ON REVISIONE_BILANCIO
	FOR EACH ROW
		BEGIN
			DECLARE TotaleRevisori int;
			DECLARE GiudiziEspressi int;
			DECLARE NumeroRespingimenti int;

			-- Verifica se e stato inserito o modificato l'esito del giudizio
			IF (NEW.Esito IS NOT NULL) AND (OLD.Esito IS NULL OR OLD.Esito != NEW.Esito) THEN
				
				-- Incrementa il contatore delle revisioni effettuate dal revisore
				UPDATE Utente
				SET NumeroRevisioni = NumeroRevisioni + 1
				WHERE (IdUtente = NEW.IdRevisore);

				-- Conta i revisori totali assegnati e quanti giudizi sono stati espressi
				SET TotaleRevisori = (SELECT COUNT(*) FROM REVISIONE_BILANCIO WHERE IdBilancio = NEW.IdBilancio);
				SET GiudiziEspressi = (SELECT COUNT(Esito) FROM REVISIONE_BILANCIO WHERE IdBilancio = NEW.IdBilancio);

				-- Se tutti i revisori hanno espresso un giudizio
				IF (TotaleRevisori = GiudiziEspressi) THEN
					SET NumeroRespingimenti = (SELECT COUNT(*) FROM REVISIONE_BILANCIO WHERE (IdBilancio = NEW.IdBilancio) AND (Esito = 'respingimento'));

					IF (NumeroRespingimenti > 0) THEN
						UPDATE BILANCIO
						SET Stato = 'respinto'
						WHERE (IdBilancio = NEW.IdBilancio);
					ELSE
						UPDATE BILANCIO
						SET Stato = 'approvato'
						WHERE (IdBilancio = NEW.IdBilancio);
					END IF;
				END IF;
			END IF;
		END
% DELIMITER ;
#-------------------------#

#-----------------------------------------------#

#--------- 1. popolamento tabelle ---------#

#------ TABELLA UTENTE ------#
INSERT INTO Utente VALUES(1, 'admin_esg', 'pass_admin', 'CFADM01A01H501A', '1980-01-15', 'Roma', 'amministratore', NULL, 0, 0.00);
INSERT INTO Utente VALUES(2, 'rev_mario', 'pass_rev1', 'CFRVR01B02F205B', '1985-04-10', 'Milano', 'revisore', NULL, 0, 0.00);
INSERT INTO Utente VALUES(3, 'rev_anna', 'pass_rev2', 'CFRVA02C03L219C', '1990-09-22', 'Torino', 'revisore', NULL, 0, 0.00);
INSERT INTO Utente VALUES(4, 'resp_green', 'pass_resp1', 'CFRSP01D04G273D', '1988-11-05', 'Bologna', 'cv_mario_bianchi.pdf', 0, 0.00);
#------------------------#

#------ TABELLA EMAIL_UTENTE ------#
INSERT INTO EMAIL_UTENTE VALUES(1, 'admin@esgbalance.it');
INSERT INTO EMAIL_UTENTE VALUES(2, 'mario.revisore@auditing.com');
INSERT INTO EMAIL_UTENTE VALUES(2, 'mario.pec@pec.it');
INSERT INTO EMAIL_UTENTE VALUES(3, 'anna.revisore@auditing.com');
INSERT INTO EMAIL_UTENTE VALUES(4, 'responsabile@ecocorp.it');
#------------------------#

#------ TABELLA TEMPLATE_VOCE ------#
INSERT INTO TEMPLATE_VOCE VALUES(1, 'Ricavi delle vendite', 'Valore complessivo del fatturato aziendale');
INSERT INTO TEMPLATE_VOCE VALUES(2, 'Costi per servizi energetici', 'Utenze di energia elettrica, gas e combustibili');
INSERT INTO TEMPLATE_VOCE VALUES(3, 'Spese formazione risorse', 'Corsi e aggiornamento professionale del personale');
#------------------------#

#------ TABELLA INDICATORE_ESG ------#
INSERT INTO INDICATORE_ESG VALUES(1, 'Consumo Idrico Annuo', 'water.png', 8, 'ambientale', 'D.Lgs 152/06', NULL, NULL);
INSERT INTO INDICATORE_ESG VALUES(2, 'Ore Formazione Dipendenti', 'training.png', 7, 'sociale', NULL, 'Sviluppo Capitale Umano', 'Annuale');
INSERT INTO INDICATORE_ESG VALUES(3, 'Indice Trasparenza Fornitori', 'governance.png', 6, 'generale', NULL, NULL, NULL);
#------------------------#

#----------------------------------#

#--------- 2. Implementazione Procedure ---------#

#---------- Procedura a ---------#
DELIMITER %
	CREATE PROCEDURE RegistraUtente(
		IN User varchar(50), 
		IN Pass varchar(255), 
		IN CodFisc char(16), 
		IN DataNasc date, 
		IN LuogoNasc varchar(100), 
		IN Ruolo ENUM('amministratore', 'revisore', 'responsabile'), 
		IN PercorsoCV varchar(255), 
		IN RecapitoEmail varchar(100)
	)
		BEGIN
			DECLARE NuovoId int;
			-- Inserimento del record utente
			INSERT INTO Utente (Username, Password, CF, DataNascita, LuogoNascita, TipoUtente, CurriculumVitae) 
			VALUES (User, Pass, CodFisc, DataNasc, LuogoNasc, Ruolo, PercorsoCV);
			
			SET NuovoId = LAST_INSERT_ID();
			
			-- Se fornita, inserimento della prima email associata
			IF (RecapitoEmail IS NOT NULL) THEN
				INSERT INTO EMAIL_UTENTE (IdUtente, Email) VALUES (NuovoId, RecapitoEmail);
			END IF;
		END
% DELIMITER ;
#-------------------------#

#---------- Procedura b ---------#
DELIMITER %
	CREATE PROCEDURE InserisciIndicatore(
		IN NomeInd varchar(100), 
		IN Img varchar(255), 
		IN ValRilevanza int, 
		IN Categoria ENUM('ambientale', 'sociale', 'generale'), 
		IN Normativa varchar(100), 
		IN Ambito varchar(100), 
		IN Freq varchar(50)
	)
		BEGIN
			-- Inserimento di un nuovo indicatore ESG
			INSERT INTO INDICATORE_ESG (Nome, Immagine, Rilevanza, Tipo, CodiceNormativa, AmbitoSociale, FrequenzaRilevazione)
			VALUES (NomeInd, Img, ValRilevanza, Categoria, Normativa, Ambito, Freq);
		END
% DELIMITER ;
#-------------------------#

#---------- Procedura c ---------#
DELIMITER %
	CREATE PROCEDURE InserisciVoceTemplate(IN NomeV varchar(100), IN Descriz text)
		BEGIN
			-- Inserimento voce contabile condivisa
			INSERT INTO TEMPLATE_VOCE (Nome, Descrizione) VALUES (NomeV, Descriz);
		END
% DELIMITER ;
#-------------------------#

#---------- Procedura d ---------#
DELIMITER %
	CREATE PROCEDURE AssociaRevisore(IN IdRev int, IN IdBil int)
		BEGIN
			-- Verifica che l'utente sia effettivamente di tipo revisore
			IF ((SELECT TipoUtente FROM Utente WHERE IdUtente = IdRev) = 'revisore') THEN
				INSERT INTO REVISIONE_BILANCIO (IdRevisore, IdBilancio) VALUES (IdRev, IdBil);
			END IF;
		END
% DELIMITER ;
#-------------------------#

#---------- Procedura e ---------#
DELIMITER %
	CREATE PROCEDURE DichiaraCompetenza(IN IdRev int, IN Competenza varchar(100), IN Grado int)
		BEGIN
			-- Inserimento o aggiornamento del livello di competenza del revisore
			IF ((SELECT TipoUtente FROM Utente WHERE IdUtente = IdRev) = 'revisore') THEN
				INSERT INTO COMPETENZA_REVISORE (IdRevisore, NomeCompetenza, Livello)
				VALUES (IdRev, Competenza, Grado)
				ON DUPLICATE KEY UPDATE Livello = Grado;
			END IF;
		END
% DELIMITER ;
#-------------------------#

#---------- Procedura f ---------#
DELIMITER %
	CREATE PROCEDURE InserisciNota(IN IdRev int, IN IdBil int, IN IdV int, IN Testo text)
		BEGIN
			-- Inserimento annotazione su specifica voce di bilancio
			INSERT INTO NOTA_VOCE (IdRevisore, IdBilancio, IdVoce, DataNota, TestoNota)
			VALUES (IdRev, IdBil, IdV, NOW(), Testo);
		END
% DELIMITER ;
#-------------------------#

#---------- Procedura g ---------#
DELIMITER %
	CREATE PROCEDURE RegistraGiudizio(IN IdRev int, IN IdBil int, IN Valutazione ENUM('approvazione', 'approvazione con rilievi', 'respingimento'), IN CommentoRilievi text)
		BEGIN
			-- Inserimento esito della revisione e rilievi
			UPDATE REVISIONE_BILANCIO
			SET Esito = Valutazione,
				DataGiudizio = NOW(),
				Rilievi = CommentoRilievi
			WHERE (IdRevisore = IdRev) AND (IdBilancio = IdBil);
		END
% DELIMITER ;
#-------------------------#

#---------- Procedura h ---------#
DELIMITER %
	CREATE PROCEDURE RegistraAzienda(
		IN NomeAz varchar(100), 
		IN RagSoc varchar(100), 
		IN PIVA char(11), 
		IN Sett varchar(100), 
		IN Dipendenti int, 
		IN LogoAz varchar(255), 
		IN IdResp int
	)
		BEGIN
			-- Registrazione nuova azienda associata al responsabile
			IF ((SELECT TipoUtente FROM Utente WHERE IdUtente = IdResp) = 'responsabile') THEN
				INSERT INTO AZIENDA (Nome, RagioneSociale, PartitaIVA, Settore, NumeroDipendenti, Logo, NrBilanci, IdResponsabile)
				VALUES (NomeAz, RagSoc, PIVA, Sett, Dipendenti, LogoAz, 0, IdResp);
			END IF;
		END
% DELIMITER ;
#-------------------------#

#---------- Procedura i ---------#
DELIMITER %
	CREATE PROCEDURE CreaBilancio(IN IdAz int, OUT IdNuovoBilancio int)
		BEGIN
			-- Creazione bilancio in bozza
			INSERT INTO BILANCIO (IdAzienda, DataCreazione, Stato)
			VALUES (IdAz, NOW(), 'bozza');
			
			SET IdNuovoBilancio = LAST_INSERT_ID();
			
			-- Incremento della ridondanza NrBilanci sull'azienda
			UPDATE AZIENDA
			SET NrBilanci = NrBilanci + 1
			WHERE (IdAzienda = IdAz);
		END
% DELIMITER ;
#-------------------------#

#---------- Procedura l ---------#
DELIMITER %
	CREATE PROCEDURE PopolaVoceBilancio(IN IdBil int, IN IdV int, IN Importo decimal(15,2))
		BEGIN
			-- Associa il valore contabile alla voce del template per il dato bilancio
			INSERT INTO VOCE_BILANCIO (IdBilancio, IdVoce, Valore)
			VALUES (IdBil, IdV, Importo)
			ON DUPLICATE KEY UPDATE Valore = Importo;
		END
% DELIMITER ;
#-------------------------#

#---------- Procedura m ---------#
DELIMITER %
	CREATE PROCEDURE AssociaValoreESG(
		IN IdBil int, 
		IN IdV int, 
		IN IdInd int, 
		IN Misura decimal(12,2), 
		IN Origine varchar(255), 
		IN DataRil date
	)
		BEGIN
			-- Inserimento valore dell'indicatore ESG collegato alla voce contabile
			INSERT INTO VOCE_INDICATORE_ESG (IdBilancio, IdVoce, IdIndicatore, ValoreNumerico, Fonte, DataRilevazione)
			VALUES (IdBil, IdV, IdInd, Misura, Origine, DataRil);
		END
% DELIMITER ;
#-------------------------#

# -----------------------------------------#

#--------- 4. Implementazione Viste -----------#

#---------- Vista a ---------#
CREATE VIEW NUMERO_AZIENDE_REGISTRATE(TotaleAziende) AS
	SELECT COUNT(*) 
	FROM AZIENDA;
#-----------------------#

#---------- Vista b ---------#
CREATE VIEW NUMERO_REVISORI_REGISTRATI(TotaleRevisori) AS
	SELECT COUNT(*) 
	FROM Utente
	WHERE (TipoUtente = 'revisore');
#-----------------------#

#---------- Vista c ---------#
CREATE VIEW AZIENDA_PIU_AFFIDABILE(IdAzienda, Nome, RagioneSociale, PercentualeAffidabilita) AS
	WITH STATS_BILANCI(IdBilancio, IdAzienda, Stato, HaSoloApprovazioni) AS (
		SELECT b.IdBilancio, b.IdAzienda, b.Stato,
			   CASE WHEN (COUNT(rb.IdRevisore) > 0 AND SUM(CASE WHEN rb.Esito = 'approvazione' THEN 1 ELSE 0 END) = COUNT(rb.IdRevisore)) THEN 1 ELSE 0 END
		FROM BILANCIO AS b JOIN REVISIONE_BILANCIO AS rb ON b.IdBilancio = rb.IdBilancio
		GROUP BY b.IdBilancio, b.IdAzienda, b.Stato
	),
	RIEPILOGO_AZIENDE(IdAzienda, TotaleBilanciValutati, BilanciSenzaRilievi) AS (
		SELECT a.IdAzienda, 
			   COUNT(sb.IdBilancio), 
			   SUM(CASE WHEN sb.Stato = 'approvato' AND sb.HaSoloApprovazioni = 1 THEN 1 ELSE 0 END)
		FROM AZIENDA AS a JOIN STATS_BILANCI AS sb ON a.IdAzienda = sb.IdAzienda
		GROUP BY a.IdAzienda
	)
	SELECT a.IdAzienda, a.Nome, a.RagioneSociale, ROUND((r.BilanciSenzaRilievi / r.TotaleBilanciValutati) * 100, 2) AS PercentualeAffidabilita
	FROM AZIENDA AS a JOIN RIEPILOGO_AZIENDE AS r ON a.IdAzienda = r.IdAzienda
	ORDER BY PercentualeAffidabilita DESC
	LIMIT 1;
#-----------------------#

#---------- Vista d ---------#
CREATE VIEW CLASSIFICA_BILANCI_ESG(IdBilancio, NomeAzienda, DataCreazione, TotaleIndicatoriConnessi) AS
	SELECT b.IdBilancio, a.Nome, b.DataCreazione, COUNT(vie.IdIndicatore) AS TotaleIndicatoriConnessi
	FROM BILANCIO AS b JOIN AZIENDA AS a ON b.IdAzienda = a.IdAzienda LEFT JOIN VOCE_INDICATORE_ESG AS vie ON b.IdBilancio = vie.IdBilancio
	GROUP BY b.IdBilancio, a.Nome, b.DataCreazione
	ORDER BY TotaleIndicatoriConnessi DESC;
#-----------------------#

# -----------------------------------------#
