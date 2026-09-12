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

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Bilancio non valido.");
}

$id_bilancio = (int) $_GET["id"];

/*
 * Controlliamo che questo bilancio sia davvero
 * assegnato al revisore loggato.
 */
$stmt = $connessione->prepare(
    "SELECT
        b.id_bilancio,
        b.stato,
        a.nome AS nome_azienda,
        rb.esito,
        rb.data_giudizio,
        rb.rilievi
     FROM RevisioneBilancio rb
     INNER JOIN Bilancio b
        ON rb.id_bilancio = b.id_bilancio
     INNER JOIN Azienda a
        ON b.id_azienda = a.id_azienda
     WHERE rb.id_revisore = ?
       AND rb.id_bilancio = ?"
);

$stmt->bind_param(
    "ii",
    $id_revisore,
    $id_bilancio
);

$stmt->execute();

$result = $stmt->get_result();
$bilancio = $result->fetch_assoc();

$stmt->close();

if (!$bilancio) {
    die("Bilancio non trovato oppure non assegnato a questo revisore.");
}

$messaggio = "";

/*
 * Salvataggio del giudizio
 */
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["salva_giudizio"])
) {

    $esito = $_POST["esito"] ?? "";
    $rilievi = trim($_POST["rilievi"] ?? "");

    $esiti_validi = [
        "approvazione",
        "approvazione con rilievi",
        "respingimento"
    ];

    if (!in_array($esito, $esiti_validi, true)) {

        $messaggio = "Esito non valido.";

    } else {

        try {

            /*
             * Usiamo la stored procedure già presente
             * nel database.
             */
            $stmt = $connessione->prepare(
                "CALL sp_InserisciGiudizioBilancio(?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "iiss",
                $id_revisore,
                $id_bilancio,
                $esito,
                $rilievi
            );

            $stmt->execute();
            $stmt->close();

            while ($connessione->more_results()) {
                $connessione->next_result();

                if ($r = $connessione->store_result()) {
                    $r->free();
                }
            }

            $messaggio = "Giudizio salvato con successo!";

            /*
             * Ricarichiamo i dati aggiornati della revisione.
             */
            $stmt = $connessione->prepare(
                "SELECT
                    b.id_bilancio,
                    b.stato,
                    a.nome AS nome_azienda,
                    rb.esito,
                    rb.data_giudizio,
                    rb.rilievi
                 FROM RevisioneBilancio rb
                 INNER JOIN Bilancio b
                    ON rb.id_bilancio = b.id_bilancio
                 INNER JOIN Azienda a
                    ON b.id_azienda = a.id_azienda
                 WHERE rb.id_revisore = ?
                   AND rb.id_bilancio = ?"
            );

            $stmt->bind_param(
                "ii",
                $id_revisore,
                $id_bilancio
            );

            $stmt->execute();

            $result = $stmt->get_result();
            $bilancio = $result->fetch_assoc();

            $stmt->close();

        } catch (Throwable $e) {

            $messaggio =
                "Errore durante il salvataggio del giudizio: "
                . $e->getMessage();
        }
    }
}

/*
 * Salvataggio di una nota su una voce del bilancio
 */
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["salva_nota"])
) {

    $id_voce = (int) ($_POST["id_voce"] ?? 0);
    $testo_nota = trim($_POST["testo_nota"] ?? "");

    if ($id_voce <= 0 || $testo_nota === "") {

        $messaggio = "Inserisci una nota valida.";

    } else {

        try {

            $stmt = $connessione->prepare(
                "CALL sp_InserisciNotaVoce(?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "iiis",
                $id_revisore,
                $id_bilancio,
                $id_voce,
                $testo_nota
            );

            $stmt->execute();
            $stmt->close();

            while ($connessione->more_results()) {
                $connessione->next_result();

                if ($r = $connessione->store_result()) {
                    $r->free();
                }
            }

            $messaggio = "Nota salvata con successo!";

        } catch (Throwable $e) {

            $messaggio =
                "Errore durante il salvataggio della nota: "
                . $e->getMessage();
        }
    }
}

/*
 * Recuperiamo le voci contabili e i relativi valori
 * del bilancio.
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

/*
 * Recuperiamo le note inserite dal revisore
 * per questo bilancio.
 */
$stmt_note = $connessione->prepare(
    "SELECT
        id_voce,
        data_nota,
        testo_nota
     FROM NotaVoce
     WHERE id_revisore = ?
       AND id_bilancio = ?
     ORDER BY data_nota DESC"
);

$stmt_note->bind_param(
    "ii",
    $id_revisore,
    $id_bilancio
);

$stmt_note->execute();

$result_note = $stmt_note->get_result();

$note_per_voce = [];

while ($nota = $result_note->fetch_assoc()) {

    $note_per_voce[$nota["id_voce"]][] = $nota;
}

$stmt_note->close();

/*
 * Recuperiamo i valori ESG del bilancio.
 */
$stmt_esg = $connessione->prepare(
    "SELECT
        vc.nome AS nome_voce,
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

$stmt_esg->bind_param("i", $id_bilancio);
$stmt_esg->execute();

$valori_esg = $stmt_esg->get_result();
?>

<!DOCTYPE html>
<html lang="it">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Revisione bilancio - ESG Balance</title>

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

        /* SIDEBAR */

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

        /* MAIN */

        .main {
            flex: 1;
            padding: 30px 35px;
            min-width: 0;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
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

        /* INFORMAZIONI BILANCIO */

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
            margin: 4px 0;
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

        /* MESSAGGIO */

        .messaggio {
            background: #e2eadf;
            border-left: 4px solid #a88b3f;
            border-radius: 9px;
            padding: 13px 15px;
            font-weight: 600;
            margin-bottom: 22px;
        }

        /* LAYOUT */

        .content-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 22px;
            align-items: start;
        }

        .column {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .panel {
            background: #f7f9f5;
            border-radius: 18px;
            padding: 26px;
            border: 1px solid #dfe6dc;
            box-shadow: 0 5px 18px rgba(0,0,0,0.05);
        }

        .panel h2 {
            margin: 0 0 8px;
            font-size: 21px;
        }

        .panel-description {
            margin: 0 0 22px;
            color: #6b746b;
            font-size: 14px;
            line-height: 1.6;
        }

        /* VOCI CONTABILI */

        .voce-card {
            background: white;
            border: 1px solid #e0e6dd;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 16px;
        }

        .voce-card:last-child {
            margin-bottom: 0;
        }

        .voce-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
        }

        .voce-nome {
            font-weight: 700;
            font-size: 16px;
        }

        .descrizione {
            color: #778077;
            font-size: 13px;
            line-height: 1.5;
            margin-top: 5px;
        }

        .valore {
            white-space: nowrap;
            padding: 6px 10px;
            border-radius: 9px;
            background: #dfe7df;
            color: #445444;
            font-size: 13px;
            font-weight: 700;
        }

        /* FORM NOTE */

        .nota-form {
            margin-top: 17px;
            padding-top: 16px;
            border-top: 1px solid #e6eae4;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        textarea,
        select {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ccd5c8;
            border-radius: 10px;
            background: #fbfcfa;
            font-family: inherit;
            font-size: 14px;
            outline: none;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        textarea:focus,
        select:focus {
            border-color: #9caf98;
            box-shadow: 0 0 0 3px rgba(156,175,152,0.16);
        }

        .nota-button {
            margin-top: 10px;
            padding: 9px 13px;
            border: none;
            border-radius: 9px;
            background: #9caf98;
            color: #263127;
            font-weight: 700;
            cursor: pointer;
        }

        .nota-button:hover {
            background: #899e86;
        }

        /* NOTE ESISTENTI */

        .note-list {
            margin-top: 15px;
        }

        .note-title {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #596259;
        }

        .nota-esistente {
            background: #f3f5f1;
            border-left: 3px solid #c9a64b;
            padding: 10px 12px;
            border-radius: 7px;
            margin-bottom: 8px;
            font-size: 13px;
            line-height: 1.5;
        }

        .nota-data {
            display: block;
            color: #858d85;
            font-size: 11px;
            margin-top: 5px;
        }

        /* ESG */

        .esg-card {
            background: white;
            border: 1px solid #e0e6dd;
            border-radius: 13px;
            padding: 16px;
            margin-bottom: 13px;
        }

        .esg-card:last-child {
            margin-bottom: 0;
        }

        .esg-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 13px;
        }

        .esg-header h3 {
            margin: 0;
            font-size: 16px;
        }

        .tipo {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            background: #dfe7df;
            color: #546454;
            font-size: 11px;
            font-weight: 700;
        }

        .esg-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 9px;
        }

        .info-item {
            background: #f6f8f4;
            padding: 9px;
            border-radius: 8px;
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
            word-break: break-word;
        }

        .empty {
            background: #f1f4ef;
            border-radius: 11px;
            padding: 20px;
            color: #6c756c;
            text-align: center;
            font-size: 14px;
        }

        /* GIUDIZIO */

        .giudizio-panel {
            border-top: 4px solid #c9a64b;
        }

        .giudizio-panel label {
            margin-top: 15px;
        }

        .giudizio-panel label:first-of-type {
            margin-top: 0;
        }

        .giudizio-panel textarea {
            min-height: 130px;
        }

        .save-judgment {
            width: 100%;
            margin-top: 18px;
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

        .save-judgment:hover {
            background: #b4923e;
            transform: translateY(-1px);
        }

        .judgment-info {
            background: #f3f0e6;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 18px;
            color: #6e6347;
            font-size: 13px;
            line-height: 1.5;
        }

        @media (max-width: 1050px) {

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
            }

            .esg-info {
                grid-template-columns: 1fr;
            }

            .voce-header {
                flex-direction: column;
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

                <a href="dashboard_revisore.php" class="active">
                    🏠 Dashboard
                </a>

                <a href="competenze.php">
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

            <h1>
                Revisione Bilancio #<?php echo $bilancio["id_bilancio"]; ?>
            </h1>

            <a
                class="back-link"
                href="dashboard_revisore.php"
            >
                ← Dashboard
            </a>

        </div>


        <div class="summary">

            <div>

                <h2>
                    <?php echo htmlspecialchars($bilancio["nome_azienda"]); ?>
                </h2>

                <p>
                    Analizza i dati contabili e ESG prima di esprimere il giudizio.
                </p>

                <?php if ($bilancio["data_giudizio"] !== null): ?>

                    <p>
                        <strong>Ultimo giudizio:</strong>
                        <?php echo htmlspecialchars($bilancio["data_giudizio"]); ?>
                    </p>

                <?php endif; ?>

            </div>

            <span class="stato <?php echo $classe_stato; ?>">
                <?php echo htmlspecialchars(ucfirst($bilancio["stato"])); ?>
            </span>

        </div>


        <?php if ($messaggio !== ""): ?>

            <div class="messaggio">
                <?php echo htmlspecialchars($messaggio); ?>
            </div>

        <?php endif; ?>


        <div class="content-grid">

            <!-- COLONNA SINISTRA -->

            <div class="column">

                <div class="panel">

                    <h2>Valori del bilancio</h2>

                    <p class="panel-description">
                        Consulta le voci contabili e inserisci eventuali
                        note utili alla revisione.
                    </p>


                    <?php while ($voce = $voci->fetch_assoc()): ?>

                        <div class="voce-card">

                            <div class="voce-header">

                                <div>

                                    <div class="voce-nome">
                                        <?php echo htmlspecialchars($voce["nome"]); ?>
                                    </div>

                                    <?php if (!empty($voce["descrizione"])): ?>

                                        <div class="descrizione">
                                            <?php echo htmlspecialchars(
                                                $voce["descrizione"]
                                            ); ?>
                                        </div>

                                    <?php endif; ?>

                                </div>


                                <div class="valore">

                                    <?php

                                    if ($voce["valore_numerico"] !== null) {

                                        echo number_format(
                                            (float) $voce["valore_numerico"],
                                            2,
                                            ",",
                                            "."
                                        ) . " €";

                                    } else {

                                        echo "Non inserito";
                                    }

                                    ?>

                                </div>

                            </div>


                            <form method="post" class="nota-form">

                                <input
                                    type="hidden"
                                    name="id_voce"
                                    value="<?php echo $voce["id_voce"]; ?>"
                                >

                                <label>
                                    Nota sulla voce
                                </label>

                                <textarea
                                    name="testo_nota"
                                    placeholder="Inserisci una nota su questa voce..."
                                    required
                                ></textarea>

                                <button
                                    class="nota-button"
                                    type="submit"
                                    name="salva_nota"
                                >
                                    Salva nota
                                </button>

                            </form>


                            <?php

                            $id_voce_corrente = $voce["id_voce"];

                            if (isset($note_per_voce[$id_voce_corrente])):

                            ?>

                                <div class="note-list">

                                    <div class="note-title">
                                        Note inserite
                                    </div>

                                    <?php foreach (
                                        $note_per_voce[$id_voce_corrente]
                                        as $nota
                                    ): ?>

                                        <div class="nota-esistente">

                                            <?php echo htmlspecialchars(
                                                $nota["testo_nota"]
                                            ); ?>

                                            <span class="nota-data">
                                                <?php echo htmlspecialchars(
                                                    $nota["data_nota"]
                                                ); ?>
                                            </span>

                                        </div>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endwhile; ?>

                </div>

            </div>


            <!-- COLONNA DESTRA -->

            <div class="column">

                <div class="panel">

                    <h2>Valori ESG</h2>

                    <p class="panel-description">
                        Indicatori ESG associati alle voci contabili
                        di questo bilancio.
                    </p>


                    <?php if ($valori_esg->num_rows > 0): ?>

                        <?php while ($esg = $valori_esg->fetch_assoc()): ?>

                            <div class="esg-card">

                                <div class="esg-header">

                                    <h3>
                                        <?php echo htmlspecialchars(
                                            $esg["nome_indicatore"]
                                        ); ?>
                                    </h3>

                                    <span class="tipo">
                                        <?php echo htmlspecialchars(
                                            ucfirst($esg["tipo"])
                                        ); ?>
                                    </span>

                                </div>


                                <div class="esg-info">

                                    <div class="info-item">

                                        <span class="info-label">
                                            Voce contabile
                                        </span>

                                        <span class="info-value">
                                            <?php echo htmlspecialchars(
                                                $esg["nome_voce"]
                                            ); ?>
                                        </span>

                                    </div>


                                    <div class="info-item">

                                        <span class="info-label">
                                            Valore
                                        </span>

                                        <span class="info-value">

                                            <?php echo number_format(
                                                (float) $esg["valore_numerico"],
                                                2,
                                                ",",
                                                "."
                                            ); ?>

                                        </span>

                                    </div>


                                    <div class="info-item">

                                        <span class="info-label">
                                            Fonte
                                        </span>

                                        <span class="info-value">
                                            <?php echo htmlspecialchars(
                                                $esg["fonte"]
                                            ); ?>
                                        </span>

                                    </div>


                                    <div class="info-item">

                                        <span class="info-label">
                                            Data rilevazione
                                        </span>

                                        <span class="info-value">
                                            <?php echo htmlspecialchars(
                                                $esg["data_rilevazione"]
                                            ); ?>
                                        </span>

                                    </div>

                                </div>

                            </div>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <div class="empty">
                            Nessun valore ESG inserito per questo bilancio.
                        </div>

                    <?php endif; ?>

                </div>


                <!-- GIUDIZIO -->

                <div class="panel giudizio-panel">

                    <h2>Giudizio finale</h2>

                    <p class="panel-description">
                        Dopo aver analizzato il bilancio,
                        seleziona l'esito della tua revisione.
                    </p>


                    <div class="judgment-info">
                        Puoi approvare il bilancio, approvarlo con rilievi
                        oppure respingerlo.
                    </div>


                    <form method="post">

                        <label for="esito">
                            Esito
                        </label>

                        <select
                            name="esito"
                            id="esito"
                            required
                        >

                            <option value="">
                                Seleziona un esito
                            </option>


                            <option
                                value="approvazione"
                                <?php
                                if ($bilancio["esito"] === "approvazione") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Approvazione
                            </option>


                            <option
                                value="approvazione con rilievi"
                                <?php
                                if (
                                    $bilancio["esito"] ===
                                    "approvazione con rilievi"
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >
                                Approvazione con rilievi
                            </option>


                            <option
                                value="respingimento"
                                <?php
                                if ($bilancio["esito"] === "respingimento") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Respingimento
                            </option>

                        </select>


                        <label for="rilievi">
                            Rilievi
                        </label>

                        <textarea
                            name="rilievi"
                            id="rilievi"
                            placeholder="Inserisci eventuali osservazioni..."
                        ><?php
                            echo htmlspecialchars(
                                $bilancio["rilievi"] ?? ""
                            );
                        ?></textarea>


                        <button
                            class="save-judgment"
                            type="submit"
                            name="salva_giudizio"
                        >
                            Salva giudizio
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </main>

</div>

</body>

</html>