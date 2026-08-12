<?php

require_once __DIR__ . '/config/database.php';

$messaggio = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $codice_fiscale = trim($_POST["codice_fiscale"]);
    $data_nascita = $_POST["data_nascita"];
    $luogo_nascita = trim($_POST["luogo_nascita"]);
    $email = trim($_POST["email"]);
    $ruolo = $_POST["ruolo"];

    try {

        // Inizio transazione: o viene salvato tutto oppure niente.
        $connessione->begin_transaction();

        /*
         * 1. Registrazione dell'utente
         */
        $stmt = $connessione->prepare(
            "CALL sp_RegistraUtente(?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssssss",
            $username,
            $password,
            $codice_fiscale,
            $data_nascita,
            $luogo_nascita,
            $ruolo
        );

        $stmt->execute();
        $stmt->close();

        // Libera eventuali risultati lasciati dalla stored procedure
        while ($connessione->more_results()) {
            $connessione->next_result();

            if ($result = $connessione->store_result()) {
                $result->free();
            }
        }

        /*
         * 2. Recuperiamo l'id dell'utente appena creato
         */
        $stmt = $connessione->prepare(
            "SELECT id_utente
             FROM Utente
             WHERE username = ?"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();
        $utente = $result->fetch_assoc();
        $stmt->close();

        if (!$utente) {
            throw new Exception("Impossibile recuperare il nuovo utente.");
        }

        $id_utente = $utente["id_utente"];

        /*
         * 3. Inserimento dell'email
         */
        $stmt = $connessione->prepare(
            "CALL sp_InserisciEmailUtente(?, ?)"
        );

        $stmt->bind_param(
            "is",
            $id_utente,
            $email
        );

        $stmt->execute();
        $stmt->close();

        while ($connessione->more_results()) {
            $connessione->next_result();

            if ($result = $connessione->store_result()) {
                $result->free();
            }
        }

        /*
         * 4. Inserimento nella tabella relativa al ruolo
         */
        if ($ruolo === "amministratore") {

            $stmt = $connessione->prepare(
                "INSERT INTO Amministratore (id_amministratore)
                 VALUES (?)"
            );

            $stmt->bind_param("i", $id_utente);
            $stmt->execute();
            $stmt->close();

        } elseif ($ruolo === "revisore") {

            $stmt = $connessione->prepare(
                "INSERT INTO RevisoreESG
                 (id_revisore, nr_revisioni, indice_affidabilita)
                 VALUES (?, 0, 0)"
            );

            $stmt->bind_param("i", $id_utente);
            $stmt->execute();
            $stmt->close();

        } elseif ($ruolo === "responsabile") {

            $stmt = $connessione->prepare(
                "INSERT INTO ResponsabileAziendale
                 (id_responsabile, cv_pdf)
                 VALUES (?, NULL)"
            );

            $stmt->bind_param("i", $id_utente);
            $stmt->execute();
            $stmt->close();

        } else {
            throw new Exception("Ruolo non valido.");
        }

        /*
         * Se tutto è andato bene confermiamo
         */
        $connessione->commit();

        $messaggio = "Registrazione completata con successo!";

    } catch (Throwable $e) {

        $connessione->rollback();

        $messaggio = "Errore durante la registrazione: " . $e->getMessage();
    }
}

?>

<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f6f5;
        margin: 0;
        padding: 40px 20px;
    }

    .registrazione-container {
        max-width: 500px;
        margin: 0 auto;
        background: white;
        padding: 35px 45px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }

    h1 {
        text-align: center;
        color: #333;
        margin-top: 0;
        margin-bottom: 30px;
    }

    form p {
        margin-bottom: 7px;
        color: #444;
        font-weight: 500;
    }

    input,
    select {
        width: 100%;
        box-sizing: border-box;
        padding: 11px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 15px;
        margin-bottom: 10px;
    }

    input:focus,
    select:focus {
        outline: none;
        border-color: #35b98a;
    }

    button {
        width: 100%;
        padding: 12px;
        background-color: #35b98a;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        margin-top: 10px;
    }

    button:hover {
        background-color: #2da77b;
    }

    .torna-home {
        display: block;
        text-align: center;
        margin-top: 20px;
        color: #35a77e;
        text-decoration: none;
    }

    .torna-home:hover {
        text-decoration: underline;
    }
</style>

<div class="registrazione-container"> 

<h1>Registrazione</h1>

<?php
if ($messaggio !== "") {
    echo "<p>" . htmlspecialchars($messaggio) . "</p>";
}
?>

<form method="post">

    <p>Username</p>
    <input type="text" name="username" required>

    <p>Password</p>
    <input type="password" name="password" required>

    <p>Codice fiscale</p>
    <input type="text" name="codice_fiscale" required>

    <p>Data di nascita</p>
    <input type="date" name="data_nascita" required>

    <p>Luogo di nascita</p>
    <input type="text" name="luogo_nascita" required>

    <p>Email</p>
    <input type="email" name="email" required>

    <p>Ruolo</p>

    <select name="ruolo" required>

        <option value="">Seleziona un ruolo</option>
        <option value="amministratore">Amministratore</option>
        <option value="revisore">Revisore ESG</option>
        <option value="responsabile">Responsabile aziendale</option>

    </select>

    <br><br>

    <button type="submit">Registrati</button>

</form>

<br>

<a class="torna-home" href="index.php">Torna alla Home</a>

</div>