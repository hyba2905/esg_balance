<?php

session_start();

require_once __DIR__ . '/../config/database.php';

if (
    !isset($_SESSION["id_utente"]) ||
    $_SESSION["tipo_utente"] !== "amministratore"
) {
    header("Location: ../login.php");
    exit;
}

$messaggio = "";

/*
 * Recuperiamo i bilanci che possono ricevere revisori.
 */
$bilanci = $connessione->query(
    "SELECT
        b.id_bilancio,
        a.nome AS nome_azienda,
        b.data_creazione
     FROM Bilancio b
     INNER JOIN Azienda a
        ON b.id_azienda = a.id_azienda
     WHERE b.stato IN ('bozza', 'in revisione')
     ORDER BY b.data_creazione DESC"
);

/*
 * Recuperiamo tutti i revisori ESG.
 */
$revisori = $connessione->query(
    "SELECT
        r.id_revisore,
        u.username,
        r.nr_revisioni,
        r.indice_affidabilita
     FROM RevisoreESG r
     INNER JOIN Utente u
        ON r.id_revisore = u.id_utente
     ORDER BY u.username"
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id_bilancio = (int) $_POST["id_bilancio"];
    $id_revisore = (int) $_POST["id_revisore"];

    try {

        $stmt = $connessione->prepare(
            "CALL sp_AssociaRevisoreBilancio(?, ?)"
        );

        $stmt->bind_param(
            "ii",
            $id_revisore,
            $id_bilancio
        );

        $stmt->execute();
        $stmt->close();

        while ($connessione->more_results()) {
            $connessione->next_result();

            if ($result = $connessione->store_result()) {
                $result->free();
            }
        }

        $messaggio = "Revisore assegnato con successo!";

    } catch (Throwable $e) {

        $messaggio =
            "Errore durante l'assegnazione: "
            . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Assegna revisore</title>

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
            background: rgba(255, 255, 255, 0.22);
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
            background: rgba(255, 255, 255, 0.22);
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

        .page-grid {
            display: grid;
            grid-template-columns: 1fr 0.8fr;
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
            font-weight: 600;
            margin-top: 18px;
            margin-bottom: 7px;
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
            margin-top: 24px;
            padding: 13px;
            border: none;
            border-radius: 10px;
            background: #9caf98;
            color: #263127;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        button:hover {
            background: #899e86;
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
            border-radius: 16px;
            padding: 22px;
        }

        .info-card h3 {
            margin-top: 0;
            margin-bottom: 12px;
        }

        .info-card p {
            margin: 0;
            color: #5f685f;
            line-height: 1.6;
            font-size: 14px;
        }

        .steps {
            margin-top: 18px;
            display: grid;
            gap: 12px;
        }

        .step {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .step-number {
            min-width: 30px;
            width: 30px;
            height: 30px;
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
            color: #596259;
            line-height: 1.5;
            font-size: 14px;
        }

        .empty-message {
            background: #eee9d9;
            border-radius: 10px;
            padding: 15px;
            color: #655a39;
        }

        @media (max-width: 950px) {
            .page-grid {
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

                <a href="dashboard_admin.php">
                    🏠 Dashboard
                </a>

                <a href="indicatori.php">
                    🌱 Indicatori ESG
                </a>

                <a href="template.php">
                    📄 Template bilancio
                </a>

                <a href="assegna_revisore.php" class="active">
                    👥 Assegna revisore
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

            <h1>Assegna revisore</h1>

            <a class="back-link" href="dashboard_admin.php">
                ← Dashboard
            </a>

        </div>


        <div class="page-grid">

            <div class="panel">

                <h2>Nuova assegnazione</h2>

                <p class="panel-description">
                    Seleziona un bilancio disponibile e il revisore ESG
                    che vuoi associare alla revisione.
                </p>

                <?php if ($messaggio !== ""): ?>

                    <div class="messaggio">
                        <?php echo htmlspecialchars($messaggio); ?>
                    </div>

                <?php endif; ?>


                <?php if ($bilanci->num_rows > 0 && $revisori->num_rows > 0): ?>

                    <form method="post">

                        <label for="id_bilancio">
                            Bilancio
                        </label>

                        <select
                            name="id_bilancio"
                            id="id_bilancio"
                            required
                        >

                            <option value="">
                                Seleziona un bilancio
                            </option>

                            <?php while ($bilancio = $bilanci->fetch_assoc()): ?>

                                <option value="<?php echo $bilancio["id_bilancio"]; ?>">

                                    Bilancio #<?php echo $bilancio["id_bilancio"]; ?>
                                    -
                                    <?php echo htmlspecialchars($bilancio["nome_azienda"]); ?>

                                </option>

                            <?php endwhile; ?>

                        </select>


                        <label for="id_revisore">
                            Revisore ESG
                        </label>

                        <select
                            name="id_revisore"
                            id="id_revisore"
                            required
                        >

                            <option value="">
                                Seleziona un revisore
                            </option>

                            <?php while ($revisore = $revisori->fetch_assoc()): ?>

                                <option value="<?php echo $revisore["id_revisore"]; ?>">

                                    <?php echo htmlspecialchars($revisore["username"]); ?>

                                    -
                                    Revisioni:
                                    <?php echo $revisore["nr_revisioni"]; ?>

                                    -
                                    Affidabilità:
                                    <?php echo $revisore["indice_affidabilita"]; ?>

                                </option>

                            <?php endwhile; ?>

                        </select>


                        <button type="submit">
                            Assegna revisore
                        </button>

                    </form>

                <?php else: ?>

                    <div class="empty-message">
                        Non ci sono bilanci disponibili oppure revisori ESG registrati.
                    </div>

                <?php endif; ?>

            </div>


            <div class="info-card">

                <h3>Come funziona</h3>

                <p>
                    L'amministratore può associare uno o più revisori ESG
                    ai bilanci che devono essere controllati.
                </p>

                <div class="steps">

                    <div class="step">

                        <div class="step-number">
                            1
                        </div>

                        <div class="step-text">
                            Seleziona il bilancio da revisionare.
                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            2
                        </div>

                        <div class="step-text">
                            Scegli il revisore ESG da associare.
                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            3
                        </div>

                        <div class="step-text">
                            Dopo l'assegnazione il bilancio passa
                            automaticamente allo stato
                            <strong>in revisione</strong>.
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>

</body>
</html>