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

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Bilancio non valido.");
}

$id_bilancio = (int) $_GET["id"];

/*
 * Controlliamo che il bilancio appartenga
 * a un'azienda del responsabile loggato.
 */
$stmt = $connessione->prepare(
    "SELECT
        b.id_bilancio,
        b.stato,
        a.nome AS nome_azienda
     FROM Bilancio b
     INNER JOIN Azienda a
        ON b.id_azienda = a.id_azienda
     WHERE b.id_bilancio = ?
       AND a.id_responsabile = ?"
);

$stmt->bind_param(
    "ii",
    $id_bilancio,
    $id_responsabile
);

$stmt->execute();

$risultato_bilancio = $stmt->get_result();
$bilancio = $risultato_bilancio->fetch_assoc();

$stmt->close();

if (!$bilancio) {
    die("Bilancio non trovato oppure non autorizzato.");
}

$messaggio = "";

/*
 * Salvataggio valori.
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        foreach ($_POST["valori"] as $id_voce => $valore) {

            $id_voce = (int) $id_voce;
            $valore = (float) $valore;

            $stmt = $connessione->prepare(
                "INSERT INTO ValoreVoceBilancio
                    (id_bilancio, id_voce, valore_numerico)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    valore_numerico = VALUES(valore_numerico)"
            );

            $stmt->bind_param(
                "iid",
                $id_bilancio,
                $id_voce,
                $valore
            );

            $stmt->execute();
            $stmt->close();
        }

        $messaggio = "Valori salvati con successo!";

    } catch (Throwable $e) {

        $messaggio =
            "Errore durante il salvataggio: " .
            $e->getMessage();
    }
}

/*
 * Recuperiamo tutte le voci contabili
 * e, se già presenti, i valori di questo bilancio.
 */
$stmt = $connessione->prepare(
    "SELECT
        vc.id_voce,
        vc.nome,
        vc.descrizione,
        vvb.valore_numerico
     FROM VoceContabile vc
     LEFT JOIN ValoreVoceBilancio vvb
        ON vc.id_voce = vvb.id_voce
       AND vvb.id_bilancio = ?
     ORDER BY vc.id_voce"
);

$stmt->bind_param("i", $id_bilancio);
$stmt->execute();

$voci = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Compila bilancio</title>

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

        .info-panel {
            background: #dfe7df;
            border-radius: 18px;
            padding: 22px 25px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .info-left h2 {
            margin: 0 0 8px;
            font-size: 21px;
        }

        .info-left p {
            margin: 3px 0;
            color: #5d665d;
        }

        .stato {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .stato-bozza {
            background: #e5e5e5;
            color: #606060;
        }

        .stato-revisione {
            background: #eee3bf;
            color: #796529;
        }

        .stato-approvato {
            background: #d8e7d5;
            color: #496548;
        }

        .stato-respinto {
            background: #edd8d5;
            color: #804c46;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 0.38fr;
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
            margin-bottom: 25px;
            color: #687168;
            line-height: 1.6;
            font-size: 14px;
        }

        .voce {
            background: white;
            border: 1px solid #e0e6dd;
            border-radius: 13px;
            padding: 18px;
            margin-bottom: 15px;
        }

        .voce label {
            display: block;
            font-weight: 700;
            margin-bottom: 6px;
            color: #374037;
        }

        .descrizione {
            color: #778077;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 12px;
        }

        input {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ccd5c8;
            border-radius: 9px;
            font-size: 14px;
            background: #fbfcfa;
            outline: none;
        }

        input:focus {
            border-color: #9caf98;
            box-shadow: 0 0 0 3px rgba(156,175,152,0.16);
        }

        .save-button {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 10px;
            background: #9caf98;
            color: #263127;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 5px;
            transition: 0.2s;
        }

        .save-button:hover {
            background: #899e86;
            transform: translateY(-1px);
        }

        .messaggio {
            background: #e2eadf;
            border-left: 4px solid #a88b3f;
            border-radius: 8px;
            padding: 12px 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .side-card {
            background: #dfe7df;
            border-radius: 18px;
            padding: 22px;
        }

        .side-card h3 {
            margin-top: 0;
            margin-bottom: 12px;
        }

        .side-card p {
            color: #606a60;
            font-size: 14px;
            line-height: 1.6;
        }

        .esg-button {
            display: block;
            text-align: center;
            margin-top: 18px;
            padding: 11px 14px;
            border-radius: 10px;
            background: #c9a64b;
            color: white;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            transition: 0.2s;
        }

        .esg-button:hover {
            background: #b4923e;
            transform: translateY(-1px);
        }

        .tip {
            margin-top: 16px;
            padding: 13px;
            border-radius: 11px;
            background: rgba(255,255,255,0.38);
            color: #5f685f;
            font-size: 13px;
            line-height: 1.5;
        }

        @media (max-width: 1000px) {

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

<?php

$classe_stato = "stato-bozza";

if ($bilancio["stato"] === "in revisione") {

    $classe_stato = "stato-revisione";

} elseif ($bilancio["stato"] === "approvato") {

    $classe_stato = "stato-approvato";

} elseif ($bilancio["stato"] === "respinto") {

    $classe_stato = "stato-respinto";
}

?>

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

            <h1>
                Compila Bilancio #<?php echo $bilancio["id_bilancio"]; ?>
            </h1>

            <a
                class="back-link"
                href="bilanci.php"
            >
                ← Torna ai bilanci
            </a>

        </div>


        <div class="info-panel">

            <div class="info-left">

                <h2>
                    <?php echo htmlspecialchars($bilancio["nome_azienda"]); ?>
                </h2>

                <p>
                    Inserisci e aggiorna i valori delle voci contabili.
                </p>

            </div>

            <span class="stato <?php echo $classe_stato; ?>">
                <?php echo htmlspecialchars(
                    ucfirst($bilancio["stato"])
                ); ?>
            </span>

        </div>


        <div class="content-grid">

            <div class="panel">

                <h2>Voci contabili</h2>

                <p class="panel-description">
                    Compila tutti i valori richiesti.
                    Se un valore è già stato inserito, verrà mostrato
                    automaticamente nel campo corrispondente.
                </p>


                <?php if ($messaggio !== ""): ?>

                    <div class="messaggio">
                        <?php echo htmlspecialchars($messaggio); ?>
                    </div>

                <?php endif; ?>


                <form method="post">

                    <?php while ($voce = $voci->fetch_assoc()): ?>

                        <div class="voce">

                            <label>
                                <?php echo htmlspecialchars($voce["nome"]); ?>
                            </label>


                            <?php if (!empty($voce["descrizione"])): ?>

                                <div class="descrizione">
                                    <?php echo htmlspecialchars(
                                        $voce["descrizione"]
                                    ); ?>
                                </div>

                            <?php endif; ?>


                            <input
                                type="number"
                                step="0.01"
                                name="valori[<?php echo $voce["id_voce"]; ?>]"
                                value="<?php
                                    echo $voce["valore_numerico"] !== null
                                        ? htmlspecialchars(
                                            $voce["valore_numerico"]
                                        )
                                        : "";
                                ?>"
                                placeholder="Inserisci il valore"
                                required
                            >

                        </div>

                    <?php endwhile; ?>


                    <button
                        class="save-button"
                        type="submit"
                    >
                        Salva valori
                    </button>

                </form>

            </div>


            <div class="side-card">

                <h3>Dati ESG</h3>

                <p>
                    Dopo aver inserito i valori contabili puoi
                    associare al bilancio anche i valori relativi
                    agli indicatori ESG.
                </p>

                <a
                    class="esg-button"
                    href="esg_bilancio.php?id=<?php echo $bilancio["id_bilancio"]; ?>"
                >
                    Gestisci valori ESG
                </a>

                <div class="tip">

                    <strong>Consiglio</strong><br>
                    Salva prima i valori contabili e successivamente
                    passa alla gestione degli indicatori ESG.

                </div>

            </div>

        </div>

    </main>

</div>

</body>
</html>