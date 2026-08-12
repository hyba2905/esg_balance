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
 * Recuperiamo i bilanci ancora in bozza.
 */
$bilanci = $connessione->query(
    "SELECT
        b.id_bilancio,
        a.nome AS nome_azienda,
        b.data_creazione
     FROM Bilancio b
     INNER JOIN Azienda a
        ON b.id_azienda = a.id_azienda
     WHERE b.stato = 'bozza'
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
    <title>Assegna revisore</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f5;
            margin: 0;
            padding: 40px 20px;
        }

        .container {
            max-width: 600px;
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
            margin-bottom: 20px;
            font-size: 15px;
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

    <h1>Assegna revisore</h1>

    <?php if ($messaggio !== ""): ?>

        <p class="messaggio">
            <?php echo htmlspecialchars($messaggio); ?>
        </p>

    <?php endif; ?>

    <?php if ($bilanci->num_rows > 0 && $revisori->num_rows > 0): ?>

        <form method="post">

            <p>Bilancio</p>

            <select name="id_bilancio" required>

                <option value="">
                    Seleziona un bilancio
                </option>

                <?php while ($bilancio = $bilanci->fetch_assoc()): ?>

                    <option value="<?php echo $bilancio["id_bilancio"]; ?>">
                        Bilancio #<?php echo $bilancio["id_bilancio"]; ?>
                        - <?php echo htmlspecialchars($bilancio["nome_azienda"]); ?>
                    </option>

                <?php endwhile; ?>

            </select>

            <p>Revisore ESG</p>

            <select name="id_revisore" required>

                <option value="">
                    Seleziona un revisore
                </option>

                <?php while ($revisore = $revisori->fetch_assoc()): ?>

                    <option value="<?php echo $revisore["id_revisore"]; ?>">

                        <?php echo htmlspecialchars($revisore["username"]); ?>

                        - Revisioni:
                        <?php echo $revisore["nr_revisioni"]; ?>

                        - Affidabilità:
                        <?php echo $revisore["indice_affidabilita"]; ?>

                    </option>

                <?php endwhile; ?>

            </select>

            <button type="submit">
                Assegna revisore
            </button>

        </form>

    <?php else: ?>

        <p>
            Non ci sono bilanci in bozza oppure revisori disponibili.
        </p>

    <?php endif; ?>

    <a class="indietro" href="dashboard_admin.php">
        Torna alla dashboard
    </a>

</div>

</body>
</html>