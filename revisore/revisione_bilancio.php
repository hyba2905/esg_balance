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
if ($_SERVER["REQUEST_METHOD"] === "POST") {

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
 * Recuperiamo le voci contabili e i relativi valori
 * del bilancio.
 */
$stmt = $connessione->prepare(
    "SELECT
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
    <title>Revisione bilancio</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f5;
            margin: 0;
            padding: 40px 20px;
        }

        .container {
            max-width: 750px;
            margin: 0 auto;
            background: white;
            padding: 35px 45px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
            color: #333;
        }

        .info {
            margin-bottom: 25px;
            color: #555;
        }

        .voce {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .voce strong {
            display: block;
            margin-bottom: 5px;
        }

        .descrizione {
            color: #777;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .valore {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        select,
        textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            margin-bottom: 20px;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background-color: #35b98a;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #2da77b;
        }

        .messaggio {
            font-weight: bold;
            margin-bottom: 20px;
        }

        .indietro {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #35a77e;
            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>
        Revisione Bilancio #<?php echo $bilancio["id_bilancio"]; ?>
    </h1>

    <div class="info">

        <p>
            <strong>Azienda:</strong>
            <?php echo htmlspecialchars($bilancio["nome_azienda"]); ?>
        </p>

        <p>
            <strong>Stato:</strong>
            <?php echo htmlspecialchars($bilancio["stato"]); ?>
        </p>

        <?php if ($bilancio["data_giudizio"] !== null): ?>

            <p>
                <strong>Data giudizio:</strong>
                <?php echo htmlspecialchars($bilancio["data_giudizio"]); ?>
            </p>

        <?php endif; ?>

    </div>

    <?php if ($messaggio !== ""): ?>

        <p class="messaggio">
            <?php echo htmlspecialchars($messaggio); ?>
        </p>

    <?php endif; ?>


    <h2>Valori del bilancio</h2>

    <?php while ($voce = $voci->fetch_assoc()): ?>

        <div class="voce">

            <strong>
                <?php echo htmlspecialchars($voce["nome"]); ?>
            </strong>

            <?php if (!empty($voce["descrizione"])): ?>

                <div class="descrizione">
                    <?php echo htmlspecialchars($voce["descrizione"]); ?>
                </div>

            <?php endif; ?>

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
                    echo "Valore non inserito";
                }
                ?>

            </div>

        </div>

    <?php endwhile; ?>

<h2>Valori ESG</h2>

<?php if ($valori_esg->num_rows > 0): ?>

    <?php while ($esg = $valori_esg->fetch_assoc()): ?>

        <div class="voce">

            <h3>
                <?php echo htmlspecialchars($esg["nome_indicatore"]); ?>
            </h3>

            <p>
                <strong>Voce contabile:</strong>
                <?php echo htmlspecialchars($esg["nome_voce"]); ?>
            </p>

            <p>
                <strong>Tipo:</strong>
                <?php echo htmlspecialchars($esg["tipo"]); ?>
            </p>

            <p>
                <strong>Valore:</strong>
                <?php echo number_format(
                    $esg["valore_numerico"],
                    2,
                    ",",
                    "."
                ); ?>
            </p>

            <p>
                <strong>Fonte:</strong>
                <?php echo htmlspecialchars($esg["fonte"]); ?>
            </p>

            <p>
                <strong>Data rilevazione:</strong>
                <?php echo htmlspecialchars($esg["data_rilevazione"]); ?>
            </p>

        </div>

    <?php endwhile; ?>

<?php else: ?>

    <p>
        Nessun valore ESG inserito per questo bilancio.
    </p>

<?php endif; ?>

    <h2>Giudizio</h2>

    <form method="post">

        <label for="esito">Esito</label>

        <select name="esito" id="esito" required>

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
                if ($bilancio["esito"] === "approvazione con rilievi") {
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


        <label for="rilievi">Rilievi</label>

        <textarea
            name="rilievi"
            id="rilievi"
            placeholder="Inserisci eventuali osservazioni..."
        ><?php
            echo htmlspecialchars($bilancio["rilievi"] ?? "");
        ?></textarea>


        <button type="submit">
            Salva giudizio
        </button>

    </form>


    <a class="indietro" href="dashboard_revisore.php">
        Torna alla dashboard
    </a>

</div>

</body>
</html>