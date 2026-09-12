<?php

session_start();

require_once __DIR__ . '/config/database.php';


/*
 * Numero totale di aziende
 */
$result = $connessione->query(
    "SELECT totale_aziende
     FROM v_NumeroAziende"
);

$numero_aziende = $result->fetch_assoc();


/*
 * Numero totale di revisori ESG
 */
$result = $connessione->query(
    "SELECT totale_revisori
     FROM v_NumeroRevisoriESG"
);

$numero_revisori = $result->fetch_assoc();


/*
 * Azienda più affidabile
 */
$result = $connessione->query(
    "SELECT
        id_azienda,
        nome,
        ragione_sociale,
        percentuale_affidabilita
     FROM v_AziendaPiuAffidabile"
);

$azienda_affidabile = $result->fetch_assoc();


/*
 * Classifica dei bilanci per numero di indicatori ESG
 */
$classifica = $connessione->query(
    "SELECT
        id_bilancio,
        nome_azienda,
        data_creazione,
        totale_indicatori_esg
     FROM v_ClassificaBilanciESG"
);

?>

<!DOCTYPE html>
<html lang="it">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Statistiche - ESG Balance</title>

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
            gap: 20px;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #f7f9f5;
            border: 1px solid #dfe6dc;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 5px 18px rgba(0,0,0,0.05);
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #dfe7df;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            margin-bottom: 14px;
        }

        .stat-title {
            color: #707970;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .stat-value {
            font-size: 30px;
            font-weight: 700;
            color: #303830;
        }

        .panel {
            background: #f7f9f5;
            border: 1px solid #dfe6dc;
            border-radius: 18px;
            padding: 26px;
            box-shadow: 0 5px 18px rgba(0,0,0,0.05);
            margin-bottom: 22px;
        }

        .panel h2 {
            margin-top: 0;
            margin-bottom: 18px;
            font-size: 21px;
        }

        .affidabile-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            border: 1px solid #e0e6dd;
        }

        .affidabile-card h3 {
            margin: 0 0 10px;
            font-size: 19px;
        }

        .affidabile-card p {
            margin: 7px 0;
            color: #616a61;
        }

        .affidabilita-badge {
            display: inline-block;
            margin-top: 8px;
            padding: 7px 11px;
            border-radius: 20px;
            background: #eee3bf;
            color: #796529;
            font-size: 12px;
            font-weight: 700;
        }

        .ranking {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .ranking-item {
            background: white;
            border: 1px solid #e0e6dd;
            border-radius: 14px;
            padding: 17px 18px;
            display: grid;
            grid-template-columns: 60px 1fr auto;
            align-items: center;
            gap: 14px;
        }

        .position {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #c9a64b;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .ranking-info h3 {
            margin: 0 0 6px;
            font-size: 16px;
        }

        .ranking-info p {
            margin: 3px 0;
            color: #737c73;
            font-size: 13px;
        }

        .indicator-count {
            padding: 7px 11px;
            border-radius: 20px;
            background: #dfe7df;
            color: #4d5f4e;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .empty {
            background: #f1f4ef;
            border-radius: 12px;
            padding: 22px;
            text-align: center;
            color: #6b746b;
        }

        @media (max-width: 1000px) {

            .stats-grid {
                grid-template-columns: 1fr;
            }

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
            }

            .ranking-item {
                grid-template-columns: 50px 1fr;
            }

            .indicator-count {
                grid-column: 2;
                justify-self: start;
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

                <?php if (isset($_SESSION["tipo_utente"])): ?>

                    <?php if ($_SESSION["tipo_utente"] === "amministratore"): ?>

                        <a href="admin/dashboard_admin.php">
                            🏠 Dashboard
                        </a>

                        <a href="admin/indicatori.php">
                            🌱 Indicatori ESG
                        </a>

                        <a href="admin/template.php">
                            📄 Template bilancio
                        </a>

                        <a href="admin/assegna_revisore.php">
                            👤 Assegna revisore
                        </a>

                        <a href="statistiche.php" class="active">
                            📊 Statistiche
                        </a>


                    <?php elseif ($_SESSION["tipo_utente"] === "responsabile"): ?>

                        <a href="responsabile/dashboard_responsabile.php">
                            🏠 Dashboard
                        </a>

                        <a href="responsabile/aziende.php">
                            🏢 Le mie aziende
                        </a>

                        <a href="responsabile/registra_azienda.php">
                            ＋ Registra azienda
                        </a>

                        <a href="responsabile/bilanci.php">
                            📄 Bilanci
                        </a>

                        <a href="statistiche.php" class="active">
                            📊 Statistiche
                        </a>


                    <?php elseif ($_SESSION["tipo_utente"] === "revisore"): ?>

                        <a href="revisore/dashboard_revisore.php">
                            🏠 Dashboard
                        </a>

                        <a href="revisore/competenze.php">
                            🎯 Le mie competenze
                        </a>

                        <a href="statistiche.php" class="active">
                            📊 Statistiche
                        </a>

                    <?php endif; ?>

                <?php else: ?>

                    <a href="index.php">
                        🏠 Home
                    </a>

                    <a href="statistiche.php" class="active">
                        📊 Statistiche
                    </a>

                <?php endif; ?>

            </nav>

        </div>

        <?php if (isset($_SESSION["tipo_utente"])): ?>

            <div class="logout-area">

                <a href="logout.php">
                    ↪ Logout
                </a>

            </div>

        <?php endif; ?>

    </aside>


    <main class="main">

        <div class="topbar">

            <h1>Statistiche ESG Balance</h1>

            <?php if (isset($_SESSION["tipo_utente"])): ?>

                <?php if ($_SESSION["tipo_utente"] === "amministratore"): ?>

                    <a
                        class="back-link"
                        href="admin/dashboard_admin.php"
                    >
                        ← Dashboard
                    </a>

                <?php elseif ($_SESSION["tipo_utente"] === "responsabile"): ?>

                    <a
                        class="back-link"
                        href="responsabile/dashboard_responsabile.php"
                    >
                        ← Dashboard
                    </a>

                <?php elseif ($_SESSION["tipo_utente"] === "revisore"): ?>

                    <a
                        class="back-link"
                        href="revisore/dashboard_revisore.php"
                    >
                        ← Dashboard
                    </a>

                <?php endif; ?>

            <?php else: ?>

                <a
                    class="back-link"
                    href="index.php"
                >
                    ← Home
                </a>

            <?php endif; ?>

        </div>


        <div class="intro">

            <p>
                Una panoramica generale dei dati ESG presenti nella piattaforma:
                aziende registrate, revisori e classifica dei bilanci.
            </p>

        </div>


        <div class="stats-grid">

            <div class="stat-card">

                <div class="stat-icon">
                    🏢
                </div>

                <div class="stat-title">
                    Aziende registrate
                </div>

                <div class="stat-value">
                    <?php
                    echo (int) $numero_aziende["totale_aziende"];
                    ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    👤
                </div>

                <div class="stat-title">
                    Revisori ESG
                </div>

                <div class="stat-value">
                    <?php
                    echo (int) $numero_revisori["totale_revisori"];
                    ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ⭐
                </div>

                <div class="stat-title">
                    Azienda più affidabile
                </div>

                <div class="stat-value" style="font-size: 20px;">

                    <?php if ($azienda_affidabile): ?>

                        <?php
                        echo htmlspecialchars(
                            $azienda_affidabile["nome"]
                        );
                        ?>

                    <?php else: ?>

                        N/D

                    <?php endif; ?>

                </div>

            </div>

        </div>


        <div class="panel">

            <h2>Azienda più affidabile</h2>

            <?php if ($azienda_affidabile): ?>

                <div class="affidabile-card">

                    <h3>
                        <?php
                        echo htmlspecialchars(
                            $azienda_affidabile["nome"]
                        );
                        ?>
                    </h3>

                    <p>
                        <strong>Ragione sociale:</strong>

                        <?php
                        echo htmlspecialchars(
                            $azienda_affidabile["ragione_sociale"]
                        );
                        ?>
                    </p>

                    <span class="affidabilita-badge">

                        Affidabilità:

                        <?php
                        echo number_format(
                            (float) $azienda_affidabile["percentuale_affidabilita"],
                            2,
                            ",",
                            "."
                        );
                        ?>%

                    </span>

                </div>

            <?php else: ?>

                <div class="empty">
                    Non sono disponibili dati sufficienti.
                </div>

            <?php endif; ?>

        </div>


        <div class="panel">

            <h2>Classifica bilanci ESG</h2>


            <?php if ($classifica->num_rows > 0): ?>

                <div class="ranking">

                    <?php
                    $posizione = 1;

                    while ($bilancio = $classifica->fetch_assoc()):
                    ?>

                        <div class="ranking-item">

                            <div class="position">
                                <?php echo $posizione; ?>
                            </div>


                            <div class="ranking-info">

                                <h3>
                                    <?php
                                    echo htmlspecialchars(
                                        $bilancio["nome_azienda"]
                                    );
                                    ?>
                                </h3>

                                <p>
                                    Bilancio
                                    #<?php echo (int) $bilancio["id_bilancio"]; ?>
                                </p>

                                <p>
                                    Creato il
                                    <?php
                                    echo htmlspecialchars(
                                        $bilancio["data_creazione"]
                                    );
                                    ?>
                                </p>

                            </div>


                            <div class="indicator-count">

                                <?php
                                echo (int) $bilancio["totale_indicatori_esg"];
                                ?>

                                indicatori ESG

                            </div>

                        </div>

                        <?php $posizione++; ?>

                    <?php endwhile; ?>

                </div>

            <?php else: ?>

                <div class="empty">
                    Non sono ancora presenti bilanci.
                </div>

            <?php endif; ?>

        </div>

    </main>

</div>

</body>

</html>