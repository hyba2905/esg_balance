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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Le mie aziende</title>

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

        .intro {
            background: #dfe7df;
            border-radius: 18px;
            padding: 22px 25px;
            margin-bottom: 25px;
        }

        .intro p {
            margin: 0;
            color: #5e675e;
            line-height: 1.6;
        }

        .aziende-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .azienda-card {
            background: #f7f9f5;
            border-radius: 18px;
            padding: 24px;
            border: 1px solid #dfe6dc;
            box-shadow: 0 5px 18px rgba(0,0,0,0.05);
            transition: 0.2s;
        }

        .azienda-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 22px rgba(0,0,0,0.07);
        }

        .azienda-header {
            display: flex;
            align-items: center;
            gap: 13px;
            margin-bottom: 18px;
        }

        .azienda-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: #dfe7dc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
        }

        .azienda-card h2 {
            margin: 0;
            font-size: 19px;
        }

        .info-row {
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e2e7df;
        }

        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-label {
            display: block;
            font-size: 12px;
            color: #7a837a;
            margin-bottom: 3px;
        }

        .info-value {
            font-weight: 600;
            color: #3b433b;
        }

        .empty {
            background: #f7f9f5;
            border-radius: 18px;
            padding: 35px;
            border: 1px solid #dfe6dc;
            text-align: center;
        }

        .empty-icon {
            font-size: 40px;
            margin-bottom: 12px;
        }

        .empty h2 {
            margin: 0 0 8px;
        }

        .empty p {
            color: #6b746b;
            margin-bottom: 18px;
        }

        .primary-button {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 9px;
            background: #9caf98;
            color: #263127;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
        }

        .primary-button:hover {
            background: #899e86;
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

        }

    </style>
</head>

<body>

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

                <a href="aziende.php" class="active">
                    🏢 Le mie aziende
                </a>

                <a href="registra_azienda.php">
                    ＋ Registra azienda
                </a>

                <a href="bilanci.php">
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

            <h1>Le mie aziende</h1>

            <a
                class="back-link"
                href="dashboard_responsabile.php"
            >
                ← Dashboard
            </a>

        </div>


        <div class="intro">

            <p>
                In questa sezione puoi visualizzare tutte le aziende
                associate al tuo account e le principali informazioni aziendali.
            </p>

        </div>


        <?php if ($risultato->num_rows > 0): ?>

            <div class="aziende-grid">

                <?php while ($azienda = $risultato->fetch_assoc()): ?>

                    <div class="azienda-card">

                        <div class="azienda-header">

                            <div class="azienda-icon">
                                🏢
                            </div>

                            <h2>
                                <?php echo htmlspecialchars($azienda["nome"]); ?>
                            </h2>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Ragione sociale
                            </span>

                            <span class="info-value">
                                <?php echo htmlspecialchars($azienda["ragione_sociale"]); ?>
                            </span>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Partita IVA
                            </span>

                            <span class="info-value">
                                <?php echo htmlspecialchars($azienda["partita_iva"]); ?>
                            </span>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Settore
                            </span>

                            <span class="info-value">
                                <?php echo htmlspecialchars($azienda["settore"]); ?>
                            </span>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Numero dipendenti
                            </span>

                            <span class="info-value">
                                <?php echo htmlspecialchars($azienda["num_dipendenti"]); ?>
                            </span>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <div class="empty">

                <div class="empty-icon">
                    🏢
                </div>

                <h2>Nessuna azienda registrata</h2>

                <p>
                    Non hai ancora registrato nessuna azienda.
                </p>

                <a
                    class="primary-button"
                    href="registra_azienda.php"
                >
                    Registra la prima azienda
                </a>

            </div>

        <?php endif; ?>

    </main>

</div>

</body>
</html>