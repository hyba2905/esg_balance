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

$result = $stmt->get_result();
$bilancio = $result->fetch_assoc();

$stmt->close();

if (!$bilancio) {
    die("Bilancio non trovato oppure non autorizzato.");
}

$messaggio = "";

/*
 * Salvataggio valore ESG
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id_voce = (int) ($_POST["id_voce"] ?? 0);
    $id_indicatore = (int) ($_POST["id_indicatore"] ?? 0);
    $valore_numerico = (float) ($_POST["valore_numerico"] ?? 0);
    $fonte = trim($_POST["fonte"] ?? "");
    $data_rilevazione = $_POST["data_rilevazione"] ?? "";

    try {

        $stmt = $connessione->prepare(
            "CALL sp_InserisciValoreESGVoce(?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "iiidss",
            $id_bilancio,
            $id_voce,
            $id_indicatore,
            $valore_numerico,
            $fonte,
            $data_rilevazione
        );

        $stmt->execute();
        $stmt->close();

        while ($connessione->more_results()) {
            $connessione->next_result();

            if ($r = $connessione->store_result()) {
                $r->free();
            }
        }

        $messaggio = "Valore ESG salvato con successo!";

    } catch (Throwable $e) {

        $messaggio =
            "Errore durante il salvataggio: "
            . $e->getMessage();
    }
}


/*
 * Recuperiamo le voci contabili.
 */
$voci = $connessione->query(
    "SELECT id_voce, nome
     FROM VoceContabile
     ORDER BY nome"
);


/*
 * Recuperiamo gli indicatori ESG.
 */
$indicatori = $connessione->query(
    "SELECT id_indicatore, nome, tipo
     FROM IndicatoreESG
     ORDER BY nome"
);


/*
 * Recuperiamo i valori ESG già inseriti
 * per questo bilancio.
 */
$stmt = $connessione->prepare(
    "SELECT
        v.id_voce,
        vc.nome AS nome_voce,
        v.id_indicatore,
        i.nome AS nome_indicatore,
        i.tipo,
        v.valore_numerico,
        v.fonte,
        v.data_rilevazione
     FROM VoceBilancioIndicatoreESG v
     INNER JOIN VoceContabile vc
        ON v.id_voce = vc.id_voce
     INNER JOIN IndicatoreESG i
        ON v.id_indicatore = i.id_indicatore
     WHERE v.id_bilancio = ?
     ORDER BY vc.nome, i.nome"
);

$stmt->bind_param("i", $id_bilancio);
$stmt->execute();

$valori_esg = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="it">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Valori ESG</title>

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

        .summary {
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

        .summary h2 {
            margin: 0 0 7px;
            font-size: 21px;
        }

        .summary p {
            margin: 0;
            color: #5e675e;
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

        input,
        select {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ccd5c8;
            border-radius: 10px;
            font-size: 14px;
            background: white;
            outline: none;
        }

        input:focus,
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

        .valore {
            background: white;
            border: 1px solid #e0e6dd;
            border-radius: 13px;
            padding: 17px;
            margin-bottom: 13px;
        }

        .valore:last-child {
            margin-bottom: 0;
        }

        .valore-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }

        .valore h3 {
            margin: 0;
            font-size: 16px;
        }

        .tipo {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            background: #dfe7df;
            color: #546454;
        }

        .valore-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .info-item {
            background: #f6f8f4;
            border-radius: 9px;
            padding: 10px;
        }

        .info-label {
            display: block;
            color: #818981;
            font-size: 11px;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 13px;
            font-weight: 600;
            color: #414941;
            word-break: break-word;
        }

        .empty {
            background: #f1f4ef;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            color: #6c756c;
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

            .valore-info {
                grid-template-columns: 1fr;
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

            <h1>Valori ESG</h1>

            <a
                class="back-link"
                href="compila_bilancio.php?id=<?php echo $bilancio["id_bilancio"]; ?>"
            >
                ← Torna al bilancio
            </a>

        </div>


        <div class="summary">

            <div>

                <h2>
                    Bilancio #<?php echo $bilancio["id_bilancio"]; ?>
                </h2>

                <p>
                    <?php echo htmlspecialchars($bilancio["nome_azienda"]); ?>
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

                <h2>Aggiungi valore ESG</h2>

                <p class="panel-description">
                    Associa un indicatore ESG a una voce contabile
                    del bilancio e inserisci il relativo valore.
                </p>


                <?php if ($messaggio !== ""): ?>

                    <div class="messaggio">
                        <?php echo htmlspecialchars($messaggio); ?>
                    </div>

                <?php endif; ?>


                <form method="post">

                    <label for="id_voce">
                        Voce contabile
                    </label>

                    <select
                        name="id_voce"
                        id="id_voce"
                        required
                    >

                        <option value="">
                            Seleziona una voce
                        </option>

                        <?php while ($voce = $voci->fetch_assoc()): ?>

                            <option value="<?php echo $voce["id_voce"]; ?>">
                                <?php echo htmlspecialchars($voce["nome"]); ?>
                            </option>

                        <?php endwhile; ?>

                    </select>


                    <label for="id_indicatore">
                        Indicatore ESG
                    </label>

                    <select
                        name="id_indicatore"
                        id="id_indicatore"
                        required
                    >

                        <option value="">
                            Seleziona un indicatore
                        </option>

                        <?php while ($indicatore = $indicatori->fetch_assoc()): ?>

                            <option value="<?php echo $indicatore["id_indicatore"]; ?>">
                                <?php
                                echo htmlspecialchars($indicatore["nome"]);
                                echo " (" .
                                    htmlspecialchars($indicatore["tipo"]) .
                                    ")";
                                ?>
                            </option>

                        <?php endwhile; ?>

                    </select>


                    <label for="valore_numerico">
                        Valore numerico
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        name="valore_numerico"
                        id="valore_numerico"
                        placeholder="Inserisci il valore"
                        required
                    >


                    <label for="fonte">
                        Fonte
                    </label>

                    <input
                        type="text"
                        name="fonte"
                        id="fonte"
                        placeholder="es. bolletta energia"
                        required
                    >


                    <label for="data_rilevazione">
                        Data rilevazione
                    </label>

                    <input
                        type="date"
                        name="data_rilevazione"
                        id="data_rilevazione"
                        required
                    >


                    <button type="submit">
                        Salva valore ESG
                    </button>

                </form>

            </div>


            <div class="panel">

                <h2>Valori ESG inseriti</h2>

                <p class="panel-description">
                    Qui trovi tutti gli indicatori ESG già associati
                    alle voci contabili di questo bilancio.
                </p>


                <?php if ($valori_esg->num_rows > 0): ?>

                    <?php while ($valore = $valori_esg->fetch_assoc()): ?>

                        <div class="valore">

                            <div class="valore-header">

                                <div>

                                    <h3>
                                        <?php echo htmlspecialchars(
                                            $valore["nome_indicatore"]
                                        ); ?>
                                    </h3>

                                </div>


                                <span class="tipo">
                                    <?php echo htmlspecialchars(
                                        ucfirst($valore["tipo"])
                                    ); ?>
                                </span>

                            </div>


                            <div class="valore-info">

                                <div class="info-item">

                                    <span class="info-label">
                                        Voce contabile
                                    </span>

                                    <span class="info-value">
                                        <?php echo htmlspecialchars(
                                            $valore["nome_voce"]
                                        ); ?>
                                    </span>

                                </div>


                                <div class="info-item">

                                    <span class="info-label">
                                        Valore
                                    </span>

                                    <span class="info-value">
                                        <?php echo htmlspecialchars(
                                            $valore["valore_numerico"]
                                        ); ?>
                                    </span>

                                </div>


                                <div class="info-item">

                                    <span class="info-label">
                                        Fonte
                                    </span>

                                    <span class="info-value">
                                        <?php echo htmlspecialchars(
                                            $valore["fonte"]
                                        ); ?>
                                    </span>

                                </div>


                                <div class="info-item">

                                    <span class="info-label">
                                        Data rilevazione
                                    </span>

                                    <span class="info-value">
                                        <?php echo htmlspecialchars(
                                            $valore["data_rilevazione"]
                                        ); ?>
                                    </span>

                                </div>

                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="empty">
                        Non sono ancora presenti valori ESG per questo bilancio.
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>

</body>

</html>