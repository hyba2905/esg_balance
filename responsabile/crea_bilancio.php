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
    <title>Crea bilancio</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f5;
            margin: 0;
            padding: 40px 20px;
        }

        .container {
            max-width: 550px;
            margin: 0 auto;
            background: white;
            padding: 35px 45px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-top: 0;
        }

        p {
            color: #444;
        }

        select {
            width: 100%;
            box-sizing: border-box;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            margin-bottom: 20px;
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
            text-align: center;
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

    <h1>Crea nuovo bilancio</h1>

    <?php if ($messaggio !== ""): ?>

        <p class="messaggio">
            <?php echo htmlspecialchars($messaggio); ?>
        </p>

    <?php endif; ?>

    <?php if ($aziende->num_rows > 0): ?>

        <form method="post">

            <p>Seleziona l'azienda</p>

            <select name="id_azienda" required>

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
                Crea bilancio
            </button>

        </form>

    <?php else: ?>

        <p>
            Devi prima registrare almeno un'azienda.
        </p>

    <?php endif; ?>

    <a class="indietro" href="bilanci.php">
        Torna ai bilanci
    </a>

</div>

</body>
</html>