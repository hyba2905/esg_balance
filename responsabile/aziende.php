<?php
session_start();

require_once "../config/database.php";

// Controlla che l'utente abbia effettuato il login
if (!isset($_SESSION["id_utente"])) {
    header("Location: ../login.php");
    exit;
}

$id_responsabile = $_SESSION["id_utente"];

// Recupera le aziende associate al responsabile
$sql = "SELECT * FROM azienda WHERE id_responsabile = ?";
$stmt = $connessione->prepare($sql);
$stmt->bind_param("i", $id_responsabile);
$stmt->execute();

$risultato = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Le mie aziende</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<div class="container">

    <h1>Le mie aziende</h1>

    <?php if ($risultato->num_rows > 0): ?>

        <?php while ($azienda = $risultato->fetch_assoc()): ?>

            <div class="card">
                <h2><?php echo htmlspecialchars($azienda["nome"]); ?></h2>

                <p>
                    <strong>Ragione sociale:</strong>
                    <?php echo htmlspecialchars($azienda["ragione_sociale"]); ?>
                </p>

                <p>
                    <strong>Partita IVA:</strong>
                    <?php echo htmlspecialchars($azienda["partita_iva"]); ?>
                </p>

                <p>
                    <strong>Settore:</strong>
                    <?php echo htmlspecialchars($azienda["settore"]); ?>
                </p>

                <p>
                    <strong>Numero dipendenti:</strong>
                    <?php echo htmlspecialchars($azienda["num_dipendenti"]); ?>
                </p>
            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <p>Non hai ancora registrato nessuna azienda.</p>

    <?php endif; ?>

    <br>

    <a href="dashboard_responsabile.php">Torna alla Dashboard</a>

</div>

</body>
</html>