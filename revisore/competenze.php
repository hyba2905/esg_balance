<?php

session_start();

require_once __DIR__ . '/../config/database.php';

if (
    !isset($_SESSION["id_utente"]) ||
    $_SESSION["tipo_utente"] !== "revisore"
) {
    header("Location: ../login.php");
    exit;
}

$id_revisore = $_SESSION["id_utente"];
$messaggio = "";


/*
 * Salvataggio della competenza
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id_competenza = (int) ($_POST["id_competenza"] ?? 0);
    $livello = (int) ($_POST["livello"] ?? -1);

    if ($id_competenza <= 0 || $livello < 0 || $livello > 5) {

        $messaggio = "Dati non validi.";

    } else {

        try {

            $stmt = $connessione->prepare(
                "CALL sp_InserisciCompetenzaRevisore(?, ?, ?)"
            );

            $stmt->bind_param(
                "iii",
                $id_revisore,
                $id_competenza,
                $livello
            );

            $stmt->execute();
            $stmt->close();

            while ($connessione->more_results()) {
                $connessione->next_result();

                if ($r = $connessione->store_result()) {
                    $r->free();
                }
            }

            $messaggio = "Competenza salvata con successo!";

        } catch (Throwable $e) {

            $messaggio =
                "Errore durante il salvataggio: "
                . $e->getMessage();
        }
    }
}


/*
 * Recuperiamo tutte le competenze disponibili
 */
$competenze = $connessione->query(
    "SELECT id_competenza, nome
     FROM Competenza
     ORDER BY nome"
);


/*
 * Recuperiamo le competenze già possedute dal revisore
 */
$stmt = $connessione->prepare(
    "SELECT
        c.nome,
        cr.livello
     FROM CompetenzaRevisore cr
     INNER JOIN Competenza c
        ON cr.id_competenza = c.id_competenza
     WHERE cr.id_revisore = ?
     ORDER BY c.nome"
);

$stmt->bind_param("i", $id_revisore);
$stmt->execute();

$competenze_revisore = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Competenze - ESG Balance</title>

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

        .intro {
            background: #dfe7df;
            border-radius: 18px;
            padding: 22px 25px;
            margin-bottom: 25px;
        }

        .intro p {
            margin: 0;
            color: #5e675e;
            line-height: 1.6;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 0.9fr 1.1fr;
            gap: 22px;
            align-items: start;
        }

        .panel {
            background: #f7f9f5;
            border-radius: 18px;
            padding: 26px;
            border: 1px solid #dfe6dc;
            box-shadow: 0 5px 18px rgba(0,0,0,0.05);
        }

        .panel h2 {
            margin-top: 0;
            margin-bottom: 8px;
            font-size: 21px;
        }

        .panel-description {
            margin-top: 0;
            margin-bottom: 22px;
            color: #6b746b;
            font-size: 14px;
            line-height: 1.6;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 6px;
            font-weight: 600;
            color: #424b42;
        }

        select {
            width: 100%;
            padding: 11px 12px;
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

        .competenza-card {
            background: white;
            border: 1px solid #e0e6dd;
            border-radius: 13px;
            padding: 17px;
            margin-bottom: 13px;
        }

        .competenza-card:last-child {
            margin-bottom: 0;
        }

        .competenza-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 12px;
        }

        .competenza-nome {
            font-weight: 700;
            color: #384038;
        }

        .livello-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #eee3bf;
            color: #796529;
            font-size: 12px;
            font-weight: 700;
        }

        .barra-livello {
            width: 100%;
            height: 8px;
            background: #e7ebe5;
            border-radius: 20px;
            overflow: hidden;
        }

        .barra-valore {
            height: 100%;
            background: #9caf98;
            border-radius: 20px;
        }

        .empty {
            background: #f1f4ef;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            color: #6c756c;
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

                <a href="dashboard_revisore.php">
                    🏠 Dashboard
                </a>

                <a href="competenze.php" class="active">
                    🎯 Le mie competenze
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

            <h1>Le mie competenze</h1>

            <a
                class="back-link"
                href="dashboard_revisore.php"
            >
                ← Dashboard
            </a>

        </div>


        <div class="intro">

            <p>
                Gestisci le tue competenze ESG e indica per ciascuna
                il livello di preparazione da 0 a 5.
            </p>

        </div>


        <div class="content-grid">

            <div class="panel">

                <h2>Aggiungi o aggiorna competenza</h2>

                <p class="panel-description">
                    Seleziona una competenza e assegna il livello
                    che rappresenta la tua esperienza.
                </p>


                <?php if ($messaggio !== ""): ?>

                    <div class="messaggio">
                        <?php echo htmlspecialchars($messaggio); ?>
                    </div>

                <?php endif; ?>


                <form method="POST">

                    <label for="id_competenza">
                        Competenza
                    </label>

                    <select
                        name="id_competenza"
                        id="id_competenza"
                        required
                    >

                        <option value="">
                            Seleziona una competenza
                        </option>

                        <?php while ($competenza = $competenze->fetch_assoc()): ?>

                            <option
                                value="<?php echo $competenza["id_competenza"]; ?>"
                            >
                                <?php echo htmlspecialchars(
                                    $competenza["nome"]
                                ); ?>
                            </option>

                        <?php endwhile; ?>

                    </select>


                    <label for="livello">
                        Livello
                    </label>

                    <select
                        name="livello"
                        id="livello"
                        required
                    >

                        <option value="">
                            Seleziona il livello
                        </option>

                        <option value="0">0 - Nessuna esperienza</option>
                        <option value="1">1 - Base</option>
                        <option value="2">2 - Elementare</option>
                        <option value="3">3 - Intermedio</option>
                        <option value="4">4 - Avanzato</option>
                        <option value="5">5 - Esperto</option>

                    </select>


                    <button type="submit">
                        Salva competenza
                    </button>

                </form>

            </div>


            <div class="panel">

                <h2>Competenze inserite</h2>

                <p class="panel-description">
                    Qui puoi vedere il livello associato a ciascuna
                    delle tue competenze ESG.
                </p>


                <?php if ($competenze_revisore->num_rows > 0): ?>

                    <?php while ($riga = $competenze_revisore->fetch_assoc()): ?>

                        <?php
                        $livello = (int) $riga["livello"];
                        $percentuale = ($livello / 5) * 100;
                        ?>

                        <div class="competenza-card">

                            <div class="competenza-header">

                                <span class="competenza-nome">
                                    <?php echo htmlspecialchars(
                                        $riga["nome"]
                                    ); ?>
                                </span>

                                <span class="livello-badge">
                                    Livello <?php echo $livello; ?>/5
                                </span>

                            </div>


                            <div class="barra-livello">

                                <div
                                    class="barra-valore"
                                    style="width: <?php echo $percentuale; ?>%;"
                                ></div>

                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="empty">
                        Non hai ancora inserito competenze.
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>

</body>

</html>