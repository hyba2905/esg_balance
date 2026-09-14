const express = require('express');
const mysql = require('mysql2/promise');
const { MongoClient } = require('mongodb');
const cors = require('cors');
require('dotenv').config();

const app = express();
app.use(express.json());
app.use(cors());

const PORT = process.env.PORT || 3000;

// --- CONFIGURAZIONE DATABASE ---
const sqlPool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'esg_balance',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

const mongoUri = process.env.MONGO_URI || 'mongodb://localhost:27017';
const client = new MongoClient(mongoUri);
let mongoDb;

async function connectDatabases() {
  try {
    const connection = await sqlPool.getConnection();
    console.log(' Connesso a MySQL (esg_balance)');
    connection.release();

    await client.connect();
    mongoDb = client.db('esg_audit');
    console.log(' Connesso a MongoDB (esg_audit -> log_eventi)');
  } catch (err) {
    console.error(' Errore di connessione ai database:', err.message);
    process.exit(1);
  }
}

connectDatabases();

// --- HELPER AUDIT LOG MONGODB ---
async function registraEventoAudit(testoEvento) {
  try {
    await mongoDb.collection('log_eventi').insertOne({
      timestamp: new Date(),
      testo: testoEvento
    });
    console.log(`[AUDIT MONGO] Registrato: "${testoEvento}"`);
  } catch (err) {
    console.error('Errore durante il salvataggio su MongoDB:', err.message);
  }
}

// ==========================================
// 1. AUTENTICAZIONE UTENTI (Stored Procedure)
// ==========================================
app.post('/api/auth/login', async (req, res) => {
  const { username, password } = req.body;
  try {
    // Richiamo Stored Procedure sp_AutenticaUtente
    const [rows] = await sqlPool.execute(
      'CALL sp_AutenticaUtente(?, ?, @p_id_utente, @p_tipo_utente)',
      [username, password]
    );
    const [outParams] = await sqlPool.execute('SELECT @p_id_utente AS id_utente, @p_tipo_utente AS tipo_utente');
    
    if (!outParams[0].id_utente) {
      return res.status(401).json({ success: false, error: 'Credenziali non valide' });
    }

    res.json({ success: true, utente: outParams[0] });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// ==========================================
// 2. RESPONSABILI AZIENDALI & BILANCI
// ==========================================

// Creazione Bilancio (Stored Procedure sp_CreaBilancio + Audit Mongo)
app.post('/api/bilancio', async (req, res) => {
  const { idAzienda, username } = req.body;
  try {
    await sqlPool.execute('CALL sp_CreaBilancio(?, @p_id_bilancio)', [idAzienda]);
    const [outParams] = await sqlPool.execute('SELECT @p_id_bilancio AS id_bilancio');
    const bilancioId = outParams[0].id_bilancio;

    await registraEventoAudit(`Creazione nuovo bilancio (ID: ${bilancioId}) per azienda ID ${idAzienda} eseguita da ${username}`);

    res.json({ success: true, bilancioId, message: 'Bilancio creato con successo' });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// Inserimento Indicatore ESG su Voce (Stored Procedure sp_InserisciValoreESGVoce + Audit Mongo)
app.post('/api/bilancio/indicatore-esg', async (req, res) => {
  const { idBilancio, idVoce, idIndicatore, valore, fonte, dataRilevazione, username } = req.body;
  try {
    await sqlPool.execute(
      'CALL sp_InserisciValoreESGVoce(?, ?, ?, ?, ?, ?)',
      [idBilancio, idVoce, idIndicatore, valore, fonte, dataRilevazione]
    );

    await registraEventoAudit(`Inserimento valore indicatore ESG (ID Indicatore: ${idIndicatore}, Valore: ${valore}, Fonte: "${fonte}") per il bilancio ID ${idBilancio} da parte dell'utente ${username}`);

    res.json({ success: true, message: 'Indicatore ESG inserito con successo' });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// ==========================================
// 3. REVISIONE BILANCI (Stati gestiti da Trigger MySQL)
// ==========================================

// Assegnazione Revisore (Stored Procedure sp_AssociaRevisoreBilancio + Trigger trg_BilancioInRevisione + Audit Mongo)
app.post('/api/revisione/assegna-revisore', async (req, res) => {
  const { idRevisore, idBilancio, usernameAdmin } = req.body;
  try {
    await sqlPool.execute('CALL sp_AssociaRevisoreBilancio(?, ?)', [idRevisore, idBilancio]);

    await registraEventoAudit(`Inizio revisione per il bilancio ID ${idBilancio}: assegnato il revisore ID ${idRevisore} dall'amministratore ${usernameAdmin}`);

    res.json({ success: true, message: 'Revisore assegnato con successo (Stato bilancio aggiornato dal Trigger)' });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// Inserimento Giudizio Complessivo (Stored Procedure sp_InserisciGiudizioBilancio + Trigger trg_AggiornaEsitoBilancio + Audit Mongo)
app.post('/api/revisione/giudizio', async (req, res) => {
  const { idRevisore, idBilancio, esito, rilievi, usernameRevisore } = req.body;
  try {
    await sqlPool.execute(
      'CALL sp_InserisciGiudizioBilancio(?, ?, ?, ?)',
      [idRevisore, idBilancio, esito, rilievi || null]
    );

    await registraEventoAudit(`Inserito giudizio di revisione ("${esito}") per il bilancio ID ${idBilancio} dal revisore ${usernameRevisore}`);

    res.json({ success: true, message: 'Giudizio registrato correttamente' });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// ==========================================
// 4. STATISTICHE (Lettura Viste MySQL)
// ==========================================

// Numero Totale Aziende (Vista v_numeroaziende)
app.get('/api/statistiche/aziende-totali', async (req, res) => {
  try {
    const [rows] = await sqlPool.execute('SELECT * FROM v_numeroaziende');
    res.json({ success: true, data: rows[0] });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// Numero Totale Revisori ESG (Vista v_numerorevisoriesg)
app.get('/api/statistiche/revisori-totali', async (req, res) => {
  try {
    const [rows] = await sqlPool.execute('SELECT * FROM v_numerorevisoriesg');
    res.json({ success: true, data: rows[0] });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// Azienda più Affidabile (Vista v_aziendapiuaffidabile)
app.get('/api/statistiche/azienda-affidabile', async (req, res) => {
  try {
    const [rows] = await sqlPool.execute('SELECT * FROM v_aziendapiuaffidabile');
    res.json({ success: true, data: rows[0] || null });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// Classifica Bilanci Aziendali per Indicatori ESG (Vista v_classificabilanciesg)
app.get('/api/statistiche/classifica-esg', async (req, res) => {
  try {
    const [rows] = await sqlPool.execute('SELECT * FROM v_classificabilanciesg');
    res.json({ success: true, data: rows });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// --- AVVIO SERVER ---
app.listen(PORT, () => {
  console.log(` Server Node.js completo in esecuzione su http://localhost:${PORT}`);
});


/*const express = require('express');
const mysql = require('mysql2/promise');
const { MongoClient } = require('mongodb');
const cors = require('cors');
require('dotenv').config();

const app = express();
app.use(express.json());
app.use(cors());

const PORT = process.env.PORT || 3000;

// Configurazione Pool MySQL
const sqlPool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'esg_balance',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// Configurazione MongoDB
const mongoUri = process.env.MONGO_URI || 'mongodb://localhost:27017';
const client = new MongoClient(mongoUri);
let mongoDb;

async function connectDatabases() {
  try {
    const connection = await sqlPool.getConnection();
    console.log(' Connesso a MySQL');
    connection.release();

    await client.connect();
    mongoDb = client.db('esg_audit');
    console.log(' Connesso a MongoDB (Collezione: log_eventi)');
  } catch (err) {
    console.error(' Errore di connessione:', err.message);
    process.exit(1);
  }
}

connectDatabases();

// --- FUNZIONE HELPER PER REGISTRARE EVENTI SU MONGODB ---
// Salva l'evento come testo accompagnato da un timestamp
async function registraEventoAudit(testoEvento) {
  try {
    const documentoLog = {
      timestamp: new Date(),
      testo: testoEvento
    };
    await mongoDb.collection('log_eventi').insertOne(documentoLog);
    console.log(`[AUDIT MONGO] Registrato: "${testoEvento}"`);
  } catch (err) {
    console.error('Errore nel salvataggio del log su MongoDB:', err.message);
  }
}

// --- ROTTE API ---

// 1. EVENTO: CREAZIONE DI UN BILANCIO
app.post('/api/bilancio', async (req, res) => {
  const { partitaIva, username } = req.body;

  try {
    const [aziende] = await sqlPool.execute(
      'SELECT id_azienda, nome FROM azienda WHERE partita_iva = ?',
      [partitaIva]
    );

    if (aziende.length === 0) {
      return res.status(404).json({ success: false, error: 'Azienda non trovata' });
    }

    const idAzienda = aziende[0].id_azienda;
    const nomeAzienda = aziende[0].nome;

    const [result] = await sqlPool.execute(
      'INSERT INTO bilancio (id_azienda, data_creazione, stato) VALUES (?, NOW(), "bozza")',
      [idAzienda]
    );

    const bilancioId = result.insertId;

    // Registrazione Evento come testo + timestamp
    const testoLog = `Creazione nuovo bilancio (ID: ${bilancioId}) per l'azienda "${nomeAzienda}" (P.IVA: ${partitaIva}) eseguita dall'utente ${username}`;
    await registraEventoAudit(testoLog);

    res.json({ success: true, bilancioId, message: 'Bilancio creato con successo' });

  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// 2. EVENTO: INSERIMENTO VALORI INDICATORI AMBIENTALI O SOCIALI
app.post('/api/bilancio/indicatore-esg', async (req, res) => {
  const { idBilancio, idVoce, idIndicatore, valore, fonte, dataRilevazione, username } = req.body;

  try {
    await sqlPool.execute(
      `INSERT INTO vocebilancioindicatoreesg (id_bilancio, id_voce, id_indicatore, valore_numerico, fonte, data_rilevazione) 
       VALUES (?, ?, ?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE valore_numerico = ?, fonte = ?, data_rilevazione = ?`,
      [idBilancio, idVoce || 1, idIndicatore || 1, valore, fonte, dataRilevazione, valore, fonte, dataRilevazione]
    );

    // Registrazione Evento come testo + timestamp
    const testoLog = `Inserimento valore indicatore ESG (ID Indicatore: ${idIndicatore || 1}, Valore: ${valore}, Fonte: "${fonte}") per il bilancio ID ${idBilancio} da parte dell'utente ${username}`;
    await registraEventoAudit(testoLog);

    res.json({ success: true, message: 'Indicatore ESG salvato con successo' });

  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// 3. EVENTO: INIZIO DI UNA REVISIONE (ASSEGNAZIONE REVISORE)
app.post('/api/revisione/assegna-revisore', async (req, res) => {
  const { idBilancio, idRevisore, usernameAdmin } = req.body;

  try {
    await sqlPool.execute(
      'INSERT INTO revisionebilancio (id_revisore, id_bilancio) VALUES (?, ?)',
      [idRevisore || 3, idBilancio]
    );

    // Registrazione Evento come testo + timestamp
    const testoLog = `Inizio revisione per il bilancio ID ${idBilancio}: assegnato il revisore ID ${idRevisore || 3} dall'amministratore ${usernameAdmin}`;
    await registraEventoAudit(testoLog);

    res.json({ success: true, message: 'Inizio revisione registrato con successo' });

  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

app.listen(PORT, () => {
  console.log(` Server Node.js attivo su http://localhost:${PORT}`);
});*/