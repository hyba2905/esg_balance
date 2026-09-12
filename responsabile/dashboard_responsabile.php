<?php

session_start();

if (!isset($_SESSION["id_utente"]) || $_SESSION["tipo_utente"] !== "responsabile") {
    header("Location: ../login.php");
    exit;
}

$username = $_SESSION["username"];

?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Responsabile</title>

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
            background: rgba(255, 255, 255, 0.22);
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
            background: rgba(255, 255, 255, 0.22);
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

        .user-area {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            color: #536354;
        }

        .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #c9a64b;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-weight: bold;
            font-size: 17px;
        }

        /* BENVENUTO */

        .welcome {
            background: #dfe7df;
            border-radius: 18px;
            padding: 28px;
            margin-bottom: 25px;
        }

        .welcome h2 {
            margin: 0 0 8px;
            font-size: 24px;
        }

        .welcome p {
            margin: 0;
            color: #5e675e;
            line-height: 1.6;
        }

        /* AREA PRINCIPALE */

        .overview {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 22px;
            margin-bottom: 25px;
        }

        .overview-card {
            background: #f7f9f5;
            border-radius: 18px;
            padding: 25px;
            border: 1px solid #dfe6dc;
            box-shadow: 0 5px 18px rgba(0,0,0,0.04);
        }

        .overview-card h3 {
            margin: 0 0 8px;
            font-size: 20px;
        }

        .overview-card p {
            color: #687168;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .progress-box {
            background: #e5ebe2;
            border-radius: 14px;
            padding: 18px;
        }

        .progress-title {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: 600;
        }

        .progress-bar {
            height: 9px;
            background: #d0d8cd;
            border-radius: 20px;
            overflow: hidden;
        }

        .progress-value {
            width: 70%;
            height: 100%;
            background: #c9a64b;
            border-radius: 20px;
        }

        .quick-info {
            display: grid;
            gap: 13px;
        }

        .info-row {
            background: #e3e9e0;
            border-radius: 12px;
            padding: 16px;
        }

        .info-row strong {
            display: block;
            margin-bottom: 4px;
        }

        .info-row span {
            color: #687168;
            font-size: 14px;
        }

        /* CARDS */

        .section-title {
            margin: 5px 0 15px;
            font-size: 20px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .card {
            background: #f7f9f5;
            border-radius: 17px;
            padding: 22px;
            border: 1px solid #dfe6dc;
            box-shadow: 0 4px 14px rgba(0,0,0,0.04);
            transition: 0.2s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.07);
        }

        .card-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: #dfe7dc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            margin-bottom: 17px;
        }

        .card h3 {
            margin: 0 0 8px;
            font-size: 17px;
        }

        .card p {
            color: #687168;
            line-height: 1.5;
            font-size: 14px;
            min-height: 42px;
        }

        .card a {
            display: inline-block;
            margin-top: 8px;
            padding: 9px 14px;
            border-radius: 9px;
            background: #9caf98;
            color: #263127;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            transition: 0.2s;
        }

        .card a:hover {
            background: #899e86;
        }

        /* RESPONSIVE */

        @media (max-width: 1100px) {
            .cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 850px) {
            .overview {
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

            .cards {
                grid-template-columns: 1fr;
            }

            .topbar {
                align-items: flex-start;
                gap: 15px;
                flex-direction: column;
            }
        }
    </style>

</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div>

            <div class="logo">
                ESG Balance
            </div>

            <nav class="menu">

                <a href="dashboard_responsabile.php" class="active">
                    🏠 Dashboard
                </a>

                <a href="aziende.php">
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


    <!-- CONTENUTO -->

    <main class="main">

        <div class="topbar">

            <h1>Dashboard Responsabile</h1>

            <div class="user-area">

                <span>
                    <?php echo htmlspecialchars($username); ?>
                </span>

                <div class="avatar">
                    <?php
                    echo strtoupper(
                        substr($username, 0, 1)
                    );
                    ?>
                </div>

            </div>

        </div>


        <!-- BENVENUTO -->

        <section class="welcome">

            <h2>
                Benvenuto, <?php echo htmlspecialchars($username); ?>!
            </h2>

            <p>
                Gestisci le tue aziende, crea i bilanci e monitora
                le informazioni ESG dalla tua area personale.
            </p>

        </section>


        <!-- PANORAMICA -->

        <div class="overview">

            <div class="overview-card">

                <h3>Gestione aziendale</h3>

                <p>
                    Da questa area puoi seguire il percorso dei tuoi
                    bilanci aziendali, dalla creazione fino alla revisione ESG.
                </p>

                <div class="progress-box">

                    <div class="progress-title">

                        <span>Percorso ESG</span>

                        <span>Gestione bilanci</span>

                    </div>

                    <div class="progress-bar">
                        <div class="progress-value"></div>
                    </div>

                </div>

            </div>


            <div class="overview-card">

                <h3>Accesso rapido</h3>

                <div class="quick-info">

                    <div class="info-row">

                        <strong>Aziende</strong>

                        <span>
                            Visualizza e gestisci le aziende associate.
                        </span>

                    </div>

                    <div class="info-row">

                        <strong>Bilanci ESG</strong>

                        <span>
                            Crea e compila i bilanci aziendali.
                        </span>

                    </div>

                </div>

            </div>

        </div>


        <!-- FUNZIONI -->

        <h2 class="section-title">
            Le tue attività
        </h2>

        <div class="cards">

            <div class="card">

                <div class="card-icon">
                    🏢
                </div>

                <h3>Le mie aziende</h3>

                <p>
                    Visualizza le aziende associate al tuo account.
                </p>

                <a href="aziende.php">
                    Visualizza aziende
                </a>

            </div>


            <div class="card">

                <div class="card-icon">
                    ＋
                </div>

                <h3>Registra azienda</h3>

                <p>
                    Inserisci una nuova azienda nella piattaforma.
                </p>

                <a href="registra_azienda.php">
                    Registra azienda
                </a>

            </div>


            <div class="card">

                <div class="card-icon">
                    📄
                </div>

                <h3>Bilanci</h3>

                <p>
                    Crea, compila e gestisci i bilanci aziendali.
                </p>

                <a href="bilanci.php">
                    Gestisci bilanci
                </a>

            </div>


            <div class="card">

                <div class="card-icon">
                    📊
                </div>

                <h3>Statistiche</h3>

                <p>
                    Visualizza le statistiche generali della piattaforma.
                </p>

                <a href="../statistiche.php">
                    Visualizza statistiche
                </a>

            </div>

        </div>

    </main>

</div>

</body>
</html>