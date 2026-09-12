<?php

session_start();

require_once __DIR__ . '/../config/database.php';

if (
    !isset($_SESSION["id_utente"]) ||
    $_SESSION["tipo_utente"] !== "responsabile"
) {
    header("Location: ../login.php");
    exit;
}

$id_responsabile = $_SESSION["id_utente"];
$messaggio = "";

/*
 * Recuperiamo solo le aziende del responsabile loggato.
 */
$stmt = $connessione->prepare(
    "SELECT id_azienda, nome
     FROM Azienda
     WHERE id_responsabile = ?
     ORDER BY nome"
);

$stmt->bind_param("i", $id_responsabile);
$stmt->execute();

$aziende = $stmt->get_result();
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id_azienda = (int) $_POST["id_azienda"];

    try {

        /*
         * Controlliamo che l'azienda scelta appartenga davvero
         * al responsabile loggato.
         */
        $stmt = $connessione->prepare(
            "SELECT id_azienda
             FROM Azienda
             WHERE id_azienda = ?
               AND id_responsabile = ?"
        );

        $stmt->bind_param(
            "ii",
            $id_azienda,
            $id_responsabile
        );

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Azienda non valida.");
        }

        $stmt->close();

        /*
         * Chiamiamo la procedura sp_CreaBilancio.
         * Il secondo parametro è OUT.
         */
        $stmt = $connessione->prepare(
            "CALL sp_CreaBilancio(?, @nuovo_id_bilancio)"
        );

        $stmt->bind_param("i", $id_azienda);
        $stmt->execute();
        $stmt->close();

        /*
         * Puliamo eventuali risultati lasciati dalla CALL.
         */
        while ($connessione->more_results()) {
            $connessione->next_result();

            if ($result = $connessione->store_result()) {
                $result->free();
            }
        }

        /*
         * Recuperiamo l'id generato dalla procedura.
         */
        $result = $connessione->query(
            "SELECT @nuovo_id_bilancio AS id_bilancio"
        );

        $riga = $result->fetch_assoc();
        $id_bilancio = $riga["id_bilancio"];

        $messaggio =
            "Bilancio creato con successo! ID bilancio: "
            . $id_bilancio;

    } catch (Throwable $e) {

        $messaggio =
            "Errore durante la creazione del bilancio: "
            . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="it">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Crea bilancio</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #eef2ed;
            color: #2f332f;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 240px;
            background: #9caf98;
            padding: 35px 0 25px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .logo {
            padding: 0 28px 35px;
            font-size: 24px;
            font-weight: 700;
            color: #263127;
        }

        .menu {
            display: flex;
            flex-direction: column;
        }

        .menu a {
            text-decoration: none;
            color: #303830;
            padding: 16px 28px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            transition: 0.2s;
        }

        .menu a:hover,
        .menu a.active {
            background: rgba(255,255,255,0.22);
            border-left: 4px solid #c9a64b;
            padding-left: 24px;
        }

        .logout-area a {
            text-decoration: none;
            color: #303830;
            padding: 16px 28px;
            display: block;
        }

        .logout-area a:hover {
            background: rgba(255,255,255,0.22);
        }

        .main {
            flex: 1;
            padding: 30px 35px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .topbar h1 {
            margin: 0;
            font-size: 30px;
        }

        .back-link {
            text-decoration: none;
            color: #536354;
            font-weight: 600;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 0.75fr;
            gap: 22px;
            align-items: start;
        }

        .panel {
            background: #f7f9f5;
            border-radius: 18px;
            padding: 28px;
            border: 1px solid #dfe6dc;
            box-shadow: 0 5px 18px rgba(0,0,0,0.05);
        }

        .panel h2 {
            margin-top: 0;
            margin-bottom: 8px;
            font-size: 22px;
        }

        .panel-description {
            margin-top: 0;
            margin-bottom: 24px;
            color: #687168;
            line-height: 1.6;
            font-size: 14px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            color: #424b42;
        }

        select {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #ccd5c8;
            border-radius: 10px;
            font-size: 14px;
            background: white;
            outline: none;
        }

        select:focus {
            border-color: #9caf98;
            box-shadow: 0 0 0 3px rgba(156,175,152,0.16);
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 13px;
            border: none;
            border-radius: 10px;
            background: #c9a64b;
            color: white;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        button:hover {
            background: #b4923e;
            transform: translateY(-1px);
        }

        .messaggio {
            background: #e2eadf;
            border-left: 4px solid #a88b3f;
            border-radius: 8px;
            padding: 12px 14px;
            font-weight: 600;
            margin-bottom: 18px;
        }

        .info-card {
            background: #dfe7df;
            border-radius: 18px;
            padding: 24px;
        }

        .info-card h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 19px;
        }

        .info-card p {
            color: #5f685f;
            line-height: 1.6;
            font-size: 14px;
        }

        .step {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }

        .step-number {
            width: 30px;
            height: 30px;
            min-width: 30px;
            border-radius: 50%;
            background: #c9a64b;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
        }

        .step-text {
            font-size: 14px;
            line-height: 1.5;
            color: #596259;
        }

        .empty {
            background: #eee9d9;
            border-radius: 12px;
            padding: 18px;
            color: #655a39;
        }

        .empty a {
            display: inline-block;
            margin-top: 12px;
            padding: 9px 14px;
            border-radius: 9px;
            background: #9caf98;
            color: #263127;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
        }

        @media (max-width: 900px) {

            .content-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 700px) {

            .sidebar {
                width: 190px;
            }

            .main {
                padding: 20px;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

        }

    </style>

</head>

<body>

<div class="layout">

    <aside class="sidebar">

        <div>

            <div class="logo">
                ESG Balance
            </div>

            <nav class="menu">

                <a href="dashboard_responsabile.php">
                    🏠 Dashboard
                </a>

                <a href="aziende.php">
                    🏢 Le mie aziende
                </a>

                <a href="registra_azienda.php">
                    ＋ Registra azienda
                </a>

                <a href="bilanci.php" class="active">
                    📄 Bilanci
                </a>

                <a href="../statistiche.php">
                    📊 Statistiche
                </a>

            </nav>

        </div>

        <div class="logout-area">

            <a href="../logout.php">
                ↪ Logout
            </a>

        </div>

    </aside>


    <main class="main">

        <div class="topbar">

            <h1>Crea nuovo bilancio</h1>

            <a
                class="back-link"
                href="bilanci.php"
            >
                ← Torna ai bilanci
            </a>

        </div>


        <div class="content-grid">

            <div class="panel">

                <h2>Nuovo bilancio aziendale</h2>

                <p class="panel-description">
                    Seleziona una delle aziende associate al tuo account.
                    Il nuovo bilancio verrà creato automaticamente come bozza.
                </p>


                <?php if ($messaggio !== ""): ?>

                    <div class="messaggio">
                        <?php echo htmlspecialchars($messaggio); ?>
                    </div>

                <?php endif; ?>


                <?php if ($aziende->num_rows > 0): ?>

                    <form method="post">

                        <label for="id_azienda">
                            Azienda
                        </label>

                        <select
                            name="id_azienda"
                            id="id_azienda"
                            required
                        >

                            <option value="">
                                Seleziona un'azienda
                            </option>

                            <?php while ($azienda = $aziende->fetch_assoc()): ?>

                                <option value="<?php echo $azienda["id_azienda"]; ?>">
                                    <?php echo htmlspecialchars($azienda["nome"]); ?>
                                </option>

                            <?php endwhile; ?>

                        </select>


                        <button type="submit">
                            ＋ Crea bilancio
                        </button>

                    </form>

                <?php else: ?>

                    <div class="empty">

                        <strong>
                            Nessuna azienda disponibile
                        </strong>

                        <p>
                            Devi prima registrare almeno un'azienda
                            prima di poter creare un bilancio.
                        </p>

                        <a href="registra_azienda.php">
                            Registra azienda
                        </a>

                    </div>

                <?php endif; ?>

            </div>


            <div class="info-card">

                <h3>Come funziona</h3>

                <p>
                    Dopo la creazione potrai aprire il bilancio
                    e inserire tutte le informazioni richieste.
                </p>

                <div class="step">

                    <div class="step-number">
                        1
                    </div>

                    <div class="step-text">
                        Seleziona l'azienda a cui appartiene il bilancio.
                    </div>

                </div>


                <div class="step">

                    <div class="step-number">
                        2
                    </div>

                    <div class="step-text">
                        Crea il nuovo bilancio.
                    </div>

                </div>


                <div class="step">

                    <div class="step-number">
                        3
                    </div>

                    <div class="step-text">
                        Aprilo dalla sezione Bilanci e compila
                        le voci contabili e i dati ESG.
                    </div>

                </div>

            </div>

        </div>

    </main>

</div>

</body>

</html>