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

$sql = "
    SELECT
        b.id_bilancio,
        b.data_creazione,
        b.stato,
        a.nome AS nome_azienda
    FROM Bilancio b
    INNER JOIN Azienda a
        ON b.id_azienda = a.id_azienda
    WHERE a.id_responsabile = ?
    ORDER BY b.data_creazione DESC
";

$stmt = $connessione->prepare($sql);
$stmt->bind_param("i", $id_responsabile);
$stmt->execute();

$risultato = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="it">

<head>

    <meta charset="UTF-8">
    <title>I miei bilanci</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f5;
            margin: 0;
            padding: 40px 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        h1 {
            color: #333;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #35b98a;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        .btn:hover {
            background-color: #2da77b;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .card h3 a {
            color: #2c3e50;
            text-decoration: none;
        }

        .card h3 a:hover {
            color: #35b98a;
        }

        .stato {
            font-weight: bold;
        }

        .indietro {
            display: inline-block;
            margin-top: 20px;
            color: #35a77e;
            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="container">

    <div class="top">

        <h1>I miei bilanci</h1>

        <a class="btn" href="crea_bilancio.php">
            Crea nuovo bilancio
        </a>

    </div>

    <?php if ($risultato->num_rows > 0): ?>

        <?php while ($bilancio = $risultato->fetch_assoc()): ?>

            <div class="card">

                <h3>
                    <a href="compila_bilancio.php?id=<?php echo $bilancio["id_bilancio"]; ?>">
                        Bilancio #<?php echo $bilancio["id_bilancio"]; ?>
                    </a>
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

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="card">
            <p>Non hai ancora creato nessun bilancio.</p>
        </div>

    <?php endif; ?>

    <a class="indietro" href="dashboard_responsabile.php">
        Torna alla dashboard
    </a>

</div>

</body>
</html>