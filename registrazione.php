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

<!DOCTYPE html>
<html lang="it">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrazione - ESG Balance</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #eef2ed;
            color: #2f332f;
        }

        .page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 0.9fr 1.1fr;
        }

        /* PARTE SINISTRA */

        .brand-side {
            background: #9caf98;
            padding: 60px 70px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 25px;
            font-weight: 700;
            color: #263127;
        }

        .brand-icon {
            width: 44px;
            height: 44px;
            border-radius: 13px;
            background: rgba(255,255,255,0.28);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
        }

        .brand-content {
            max-width: 470px;
        }

        .brand-content h1 {
            margin: 0 0 20px;
            font-size: 48px;
            line-height: 1.08;
            color: #263127;
        }

        .brand-content p {
            margin: 0;
            color: #3f4c40;
            line-height: 1.7;
            font-size: 16px;
        }

        .brand-footer {
            color: #465447;
            font-size: 13px;
        }

        /* PARTE DESTRA */

        .register-side {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 45px 30px;
        }

        .register-box {
            width: 100%;
            max-width: 560px;
        }

        .register-box h2 {
            margin: 0 0 8px;
            font-size: 31px;
            color: #303830;
        }

        .subtitle {
            margin: 0 0 28px;
            color: #6d766d;
            line-height: 1.6;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0 16px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 14px;
            font-weight: 600;
            color: #414a41;
        }

        input,
        select {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #ccd5c8;
            border-radius: 10px;
            background: #f9fbf8;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
        }

        input:focus,
        select:focus {
            border-color: #9caf98;
            box-shadow: 0 0 0 3px rgba(156,175,152,0.16);
            background: white;
        }

        .register-button {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 10px;
            background: #c9a64b;
            color: white;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 4px;
        }

        .register-button:hover {
            background: #b4923e;
            transform: translateY(-1px);
        }

        .messaggio {
            margin-bottom: 20px;
            padding: 12px 14px;
            border-radius: 9px;
            background: #e2eadf;
            color: #4d604e;
            font-size: 14px;
            font-weight: 600;
        }

        .login-text {
            text-align: center;
            margin-top: 22px;
            color: #6b746b;
            font-size: 14px;
        }

        .login-text a {
            color: #7a6937;
            font-weight: 700;
            text-decoration: none;
        }

        .login-text a:hover {
            text-decoration: underline;
        }

        .back-home {
            display: inline-block;
            margin-top: 22px;
            text-decoration: none;
            color: #536354;
            font-size: 14px;
            font-weight: 600;
        }

        @media (max-width: 950px) {

            .page {
                grid-template-columns: 1fr;
            }

            .brand-side {
                min-height: 280px;
                padding: 35px 30px;
            }

            .brand-content h1 {
                font-size: 38px;
            }

            .brand-footer {
                display: none;
            }

        }

        @media (max-width: 650px) {

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

        }

    </style>

</head>

<body>

<div class="page">

    <section class="brand-side">

        <div class="brand-logo">

            <div class="brand-icon">
                🌿
            </div>

            ESG Balance

        </div>


        <div class="brand-content">

            <h1>
                Entra nella piattaforma ESG Balance.
            </h1>

            <p>
                Crea il tuo account e accedi alle funzionalità
                dedicate al tuo ruolo: amministratore,
                revisore ESG o responsabile aziendale.
            </p>

        </div>


        <div class="brand-footer">
            ESG Balance · Gestione e revisione dei bilanci ESG
        </div>

    </section>


    <section class="register-side">

        <div class="register-box">

            <h2>Crea il tuo account</h2>

            <p class="subtitle">
                Inserisci i tuoi dati e seleziona il ruolo
                con cui utilizzerai la piattaforma.
            </p>


            <?php if ($messaggio !== ""): ?>

                <div class="messaggio">
                    <?php echo htmlspecialchars($messaggio); ?>
                </div>

            <?php endif; ?>


            <form method="post">

                <div class="form-grid">

                    <div class="form-group">

                        <label for="username">
                            Username
                        </label>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Scegli uno username"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="email">
                            Email
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="nome@email.it"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="password">
                            Password
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Inserisci una password"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="codice_fiscale">
                            Codice fiscale
                        </label>

                        <input
                            type="text"
                            id="codice_fiscale"
                            name="codice_fiscale"
                            placeholder="Codice fiscale"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="data_nascita">
                            Data di nascita
                        </label>

                        <input
                            type="date"
                            id="data_nascita"
                            name="data_nascita"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="luogo_nascita">
                            Luogo di nascita
                        </label>

                        <input
                            type="text"
                            id="luogo_nascita"
                            name="luogo_nascita"
                            placeholder="Città di nascita"
                            required
                        >

                    </div>


                    <div class="form-group full">

                        <label for="ruolo">
                            Ruolo
                        </label>

                        <select
                            name="ruolo"
                            id="ruolo"
                            required
                        >

                            <option value="">
                                Seleziona un ruolo
                            </option>

                            <option value="amministratore">
                                Amministratore
                            </option>

                            <option value="revisore">
                                Revisore ESG
                            </option>

                            <option value="responsabile">
                                Responsabile aziendale
                            </option>

                        </select>

                    </div>

                </div>


                <button
                    type="submit"
                    class="register-button"
                >
                    Registrati
                </button>

            </form>


            <div class="login-text">

                Hai già un account?

                <a href="login.php">
                    Accedi
                </a>

            </div>


            <a
                class="back-home"
                href="index.php"
            >
                ← Torna alla Home
            </a>

        </div>

    </section>

</div>

</body>

</html>