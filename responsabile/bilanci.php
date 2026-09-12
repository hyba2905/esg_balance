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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>I miei bilanci</title>

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

        /* CONTENUTO */

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

        .new-button {
            display: inline-block;
            padding: 11px 17px;
            border-radius: 10px;
            background: #c9a64b;
            color: white;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            transition: 0.2s;
        }

        .new-button:hover {
            background: #b4923e;
            transform: translateY(-1px);
        }

        /* INTRO */

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

        /* BILANCI */

        .bilanci-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
            gap: 20px;
        }

        .bilancio-card {
            background: #f7f9f5;
            border-radius: 18px;
            padding: 23px;
            border: 1px solid #dfe6dc;
            box-shadow: 0 5px 18px rgba(0,0,0,0.05);
            transition: 0.2s;
        }

        .bilancio-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 22px rgba(0,0,0,0.07);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 20px;
        }

        .bilancio-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .bilancio-icon {
            width: 43px;
            height: 43px;
            border-radius: 12px;
            background: #dfe7dc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .bilancio-card h3 {
            margin: 0;
            font-size: 18px;
        }

        .bilancio-card h3 a {
            color: #303830;
            text-decoration: none;
        }

        .bilancio-card h3 a:hover {
            color: #71846e;
        }

        /* BADGE STATO */

        .stato {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
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

        .info-row {
            margin-bottom: 13px;
            padding-bottom: 13px;
            border-bottom: 1px solid #e2e7df;
        }

        .info-row:last-of-type {
            border-bottom: none;
        }

        .info-label {
            display: block;
            color: #7a837a;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .info-value {
            font-weight: 600;
            color: #3b433b;
        }

        .open-button {
            display: inline-block;
            margin-top: 8px;
            padding: 9px 14px;
            border-radius: 9px;
            background: #9caf98;
            color: #263127;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
        }

        .open-button:hover {
            background: #899e86;
        }

        /* NESSUN BILANCIO */

        .empty {
            background: #f7f9f5;
            border-radius: 18px;
            padding: 40px;
            border: 1px solid #dfe6dc;
            text-align: center;
        }

        .empty-icon {
            font-size: 42px;
            margin-bottom: 12px;
        }

        .empty h2 {
            margin: 0 0 8px;
        }

        .empty p {
            color: #687168;
            margin-bottom: 18px;
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
                gap: 15px;
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

                <a href="aziende.php">
                    🏢 Le mie aziende
                </a>

                <a href="registra_azienda.php">
                    ＋ Registra azienda
                </a>

                <a href="bilanci.php" class="active">
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

            <h1>I miei bilanci</h1>

            <a
                class="new-button"
                href="crea_bilancio.php"
            >
                ＋ Crea nuovo bilancio
            </a>

        </div>


        <div class="intro">

            <p>
                Visualizza e gestisci i bilanci delle tue aziende.
                Seleziona un bilancio per aprirlo e consultarne
                o compilarne i dati.
            </p>

        </div>


        <?php if ($risultato->num_rows > 0): ?>

            <div class="bilanci-grid">

                <?php while ($bilancio = $risultato->fetch_assoc()): ?>

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

                    <div class="bilancio-card">

                        <div class="card-header">

                            <div class="bilancio-title">

                                <div class="bilancio-icon">
                                    📄
                                </div>

                                <h3>
                                    <a href="compila_bilancio.php?id=<?php echo $bilancio["id_bilancio"]; ?>">
                                        Bilancio #<?php echo $bilancio["id_bilancio"]; ?>
                                    </a>
                                </h3>

                            </div>


                            <span class="stato <?php echo $classe_stato; ?>">
                                <?php echo htmlspecialchars(
                                    ucfirst($bilancio["stato"])
                                ); ?>
                            </span>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Azienda
                            </span>

                            <span class="info-value">
                                <?php echo htmlspecialchars(
                                    $bilancio["nome_azienda"]
                                ); ?>
                            </span>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Data creazione
                            </span>

                            <span class="info-value">
                                <?php echo htmlspecialchars(
                                    $bilancio["data_creazione"]
                                ); ?>
                            </span>

                        </div>


                        <a
                            class="open-button"
                            href="compila_bilancio.php?id=<?php echo $bilancio["id_bilancio"]; ?>"
                        >
                            Apri bilancio →
                        </a>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <div class="empty">

                <div class="empty-icon">
                    📄
                </div>

                <h2>Nessun bilancio</h2>

                <p>
                    Non hai ancora creato nessun bilancio.
                </p>

                <a
                    class="new-button"
                    href="crea_bilancio.php"
                >
                    ＋ Crea il primo bilancio
                </a>

            </div>

        <?php endif; ?>

    </main>

</div>

</body>
</html>