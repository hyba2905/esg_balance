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
<title>Valori ESG</title>

<style>

body {
    font-family: Arial, sans-serif;
    background-color: #f4f6f5;
    margin: 0;
    padding: 40px 20px;
}

.container {
    max-width: 850px;
    margin: 0 auto;
}

.box {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}

h1,
h2 {
    color: #333;
}

label {
    display: block;
    font-weight: bold;
    margin-top: 15px;
    margin-bottom: 6px;
}

input,
select {
    width: 100%;
    box-sizing: border-box;
    padding: 11px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 15px;
}

button {
    width: 100%;
    margin-top: 20px;
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

.valore {
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.valore:last-child {
    border-bottom: none;
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

    <div class="box">

        <h1>
            Valori ESG - Bilancio #<?php echo $bilancio["id_bilancio"]; ?>
        </h1>

        <p>
            <strong>Azienda:</strong>
            <?php echo htmlspecialchars($bilancio["nome_azienda"]); ?>
        </p>

        <p>
            <strong>Stato:</strong>
            <?php echo htmlspecialchars($bilancio["stato"]); ?>
        </p>

    </div>


    <div class="box">

        <h2>Aggiungi valore ESG</h2>

        <?php if ($messaggio !== ""): ?>

            <p class="messaggio">
                <?php echo htmlspecialchars($messaggio); ?>
            </p>

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


    <div class="box">

        <h2>Valori ESG inseriti</h2>

        <?php if ($valori_esg->num_rows > 0): ?>

            <?php while ($valore = $valori_esg->fetch_assoc()): ?>

                <div class="valore">

                    <p>
                        <strong>Voce:</strong>
                        <?php echo htmlspecialchars($valore["nome_voce"]); ?>
                    </p>

                    <p>
                        <strong>Indicatore:</strong>
                        <?php echo htmlspecialchars($valore["nome_indicatore"]); ?>
                        (<?php echo htmlspecialchars($valore["tipo"]); ?>)
                    </p>

                    <p>
                        <strong>Valore:</strong>
                        <?php echo htmlspecialchars($valore["valore_numerico"]); ?>
                    </p>

                    <p>
                        <strong>Fonte:</strong>
                        <?php echo htmlspecialchars($valore["fonte"]); ?>
                    </p>

                    <p>
                        <strong>Data rilevazione:</strong>
                        <?php echo htmlspecialchars($valore["data_rilevazione"]); ?>
                    </p>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p>
                Non sono ancora presenti valori ESG per questo bilancio.
            </p>

        <?php endif; ?>

    </div>


    <a
        class="indietro"
        href="compila_bilancio.php?id=<?php echo $bilancio["id_bilancio"]; ?>"
    >
        Torna al bilancio
    </a>

</div>

</body>
</html>