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
    <title>Compila bilancio</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f5;
            margin: 0;
            padding: 40px 20px;
        }

        .container {
            max-width: 700px;
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
            margin-bottom: 22px;
        }

        .voce label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .descrizione {
            color: #777;
            font-size: 14px;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            box-sizing: border-box;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: #35b98a;
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

        .messaggio {
            margin-bottom: 20px;
            font-weight: bold;
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
        Compila Bilancio #<?php echo $bilancio["id_bilancio"]; ?>
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
    </div>

    <?php if ($messaggio !== ""): ?>

        <p class="messaggio">
            <?php echo htmlspecialchars($messaggio); ?>
        </p>

    <?php endif; ?>

    <form method="post">

        <?php while ($voce = $voci->fetch_assoc()): ?>

            <div class="voce">

                <label>
                    <?php echo htmlspecialchars($voce["nome"]); ?>
                </label>

                <?php if (!empty($voce["descrizione"])): ?>

                    <div class="descrizione">
                        <?php echo htmlspecialchars($voce["descrizione"]); ?>
                    </div>

                <?php endif; ?>

                <input
                    type="number"
                    step="0.01"
                    name="valori[<?php echo $voce["id_voce"]; ?>]"
                    value="<?php
                        echo $voce["valore_numerico"] !== null
                            ? htmlspecialchars($voce["valore_numerico"])
                            : "";
                    ?>"
                    required
                >

            </div>

        <?php endwhile; ?>

        <button type="submit">
            Salva valori
        </button>

    </form>
<a
    class="indietro"
    href="esg_bilancio.php?id=<?php echo $bilancio["id_bilancio"]; ?>"
>
    Gestisci valori ESG
</a>
    <a class="indietro" href="bilanci.php">
        Torna ai bilanci
    </a>

</div>

</body>
</html>