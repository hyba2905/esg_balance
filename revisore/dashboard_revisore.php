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
$username = $_SESSION["username"];

$stmt = $connessione->prepare(
    "SELECT
        b.id_bilancio,
        a.nome AS nome_azienda,
        b.data_creazione,
        b.stato,
        rb.esito
     FROM RevisioneBilancio rb
     INNER JOIN Bilancio b
        ON rb.id_bilancio = b.id_bilancio
     INNER JOIN Azienda a
        ON b.id_azienda = a.id_azienda
     WHERE rb.id_revisore = ?
     ORDER BY b.data_creazione DESC"
);

$stmt->bind_param("i", $id_revisore);
$stmt->execute();

$bilanci = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Revisore ESG</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f5;
            margin: 0;
        }

        .header {
            background-color: #35b98a;
            color: white;
            padding: 20px 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            margin: 0;
            font-size: 26px;
        }

        .logout {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .benvenuto,
        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .card h3 {
            margin-top: 0;
        }

        .btn {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 15px;
            background-color: #35b98a;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        .btn:hover {
            background-color: #2da77b;
        }

        .stato {
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="header">

    <h1>Dashboard Revisore ESG</h1>

    <a class="logout" href="../logout.php">
        Logout
    </a>

</div>

<div class="container">

    <div class="benvenuto">

        <h2>
            Benvenuto, <?php echo htmlspecialchars($username); ?>!
        </h2>

        <p>
            Qui puoi visualizzare e revisionare i bilanci assegnati.
        </p>

    </div>

    <?php if ($bilanci->num_rows > 0): ?>

        <?php while ($bilancio = $bilanci->fetch_assoc()): ?>

            <div class="card">

                <h3>
                    Bilancio #<?php echo $bilancio["id_bilancio"]; ?>
                </h3>

                <p>
                    <strong>Azienda:</strong>
                    <?php echo htmlspecialchars($bilancio["nome_azienda"]); ?>
                </p>

                <p>
                    <strong>Data creazione:</strong>
                    <?php echo htmlspecialchars($bilancio["data_creazione"]); ?>
                </p>

                <p>
                    <strong>Stato:</strong>
                    <span class="stato">
                        <?php echo htmlspecialchars($bilancio["stato"]); ?>
                    </span>
                </p>

                <p>
                    <strong>Esito revisione:</strong>
                    <?php
                    echo $bilancio["esito"] !== null
                        ? htmlspecialchars($bilancio["esito"])
                        : "Non ancora espresso";
                    ?>
                </p>

                <a
                    class="btn"
                    href="revisione_bilancio.php?id=<?php echo $bilancio["id_bilancio"]; ?>"
                >
                    Apri revisione
                </a>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="card">
            <p>Non hai ancora bilanci assegnati.</p>
        </div>

    <?php endif; ?>

</div>

</body>
</html>