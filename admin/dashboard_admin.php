<?php

session_start();

if (
    !isset($_SESSION["id_utente"]) ||
    $_SESSION["tipo_utente"] !== "amministratore"
) {
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

    <title>Dashboard Amministratore</title>

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
            font-weight: 650;
        }

        .user-box {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }

        .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #9caf98;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }

        /* BENVENUTO */
        .welcome-card {
            background: #dfe7df;
            border-radius: 18px;
            padding: 28px;
            margin-bottom: 25px;
        }

        .welcome-card h2 {
            margin: 0 0 8px;
            font-size: 24px;
        }

        .welcome-card p {
            margin: 0;
            color: #5f685f;
            line-height: 1.6;
        }

        /* GRIGLIA */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .big-panel,
        .small-card {
            background: #dce4db;
            border-radius: 18px;
        }

        .big-panel {
            padding: 25px;
            min-height: 290px;
        }

        .big-panel h3 {
            margin-top: 0;
            font-size: 20px;
        }

        .big-number {
            font-size: 42px;
            font-weight: 700;
            color: #b18f32;
            margin: 15px 0;
        }

        .fake-chart {
            height: 150px;
            display: flex;
            align-items: end;
            gap: 18px;
            padding: 20px 10px 0;
            border-bottom: 1px solid #aab4aa;
        }

        .bar {
            flex: 1;
            background: #738172;
            border-radius: 5px 5px 0 0;
        }

        .bar.gold {
            background: #c5a24b;
        }

        .side-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .small-card {
            padding: 22px;
            min-height: 130px;
        }

        .small-card span {
            display: block;
            color: #697269;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .small-card strong {
            font-size: 28px;
            color: #b18f32;
        }

        /* CARDS FUNZIONI */
        .actions {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .action-card {
            background: #f6f8f4;
            border-radius: 18px;
            padding: 23px;
            transition: 0.2s;
            border: 1px solid #e1e7df;
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .action-card .icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #d8e2d5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .action-card h3 {
            margin: 0 0 8px;
            font-size: 17px;
        }

        .action-card p {
            color: #6c746c;
            font-size: 14px;
            line-height: 1.5;
            min-height: 42px;
        }

        .action-card a {
            display: inline-block;
            margin-top: 8px;
            padding: 10px 15px;
            background: #9caf98;
            color: #263127;
            text-decoration: none;
            border-radius: 9px;
            font-weight: 600;
            font-size: 14px;
        }

        .action-card a:hover {
            background: #899e86;
        }

        @media (max-width: 1000px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 700px) {
            .sidebar {
                width: 190px;
            }

            .actions {
                grid-template-columns: 1fr;
            }

            .side-cards {
                grid-template-columns: 1fr;
            }

            .main {
                padding: 20px;
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

                <a href="dashboard_admin.php" class="active">
                    🏠 Dashboard
                </a>

                <a href="indicatori.php">
                    🌱 Indicatori ESG
                </a>

                <a href="template.php">
                    📄 Template bilancio
                </a>

                <a href="assegna_revisore.php">
                    👥 Assegna revisore
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

            <h1>Dashboard</h1>

            <div class="user-box">

                <span>
                    <?php echo htmlspecialchars($username); ?>
                </span>

                <div class="avatar">
                    <?php echo strtoupper(substr($username, 0, 1)); ?>
                </div>

            </div>

        </div>


        <div class="welcome-card">

            <h2>
                Benvenuto, <?php echo htmlspecialchars($username); ?>!
            </h2>

            <p>
                Da questa area puoi gestire il template,
                gli indicatori ESG, le revisioni e le statistiche
                della piattaforma.
            </p>

        </div>


        <div class="dashboard-grid">

            <div class="big-panel">

                <h3>Panoramica ESG</h3>

                <div class="big-number">
                    ESG
                </div>

                <div class="fake-chart">

                    <div class="bar" style="height: 45%;"></div>
                    <div class="bar gold" style="height: 65%;"></div>
                    <div class="bar" style="height: 55%;"></div>
                    <div class="bar gold" style="height: 85%;"></div>
                    <div class="bar" style="height: 72%;"></div>
                    <div class="bar gold" style="height: 90%;"></div>
                    <div class="bar" style="height: 68%;"></div>

                </div>

            </div>


            <div class="side-cards">

                <div class="small-card">
                    <span>Gestione</span>
                    <strong>ESG</strong>
                </div>

                <div class="small-card">
                    <span>Template</span>
                    <strong>Bilanci</strong>
                </div>

                <div class="small-card">
                    <span>Revisioni</span>
                    <strong>Attive</strong>
                </div>

                <div class="small-card">
                    <span>Statistiche</span>
                    <strong>Live</strong>
                </div>

            </div>

        </div>


        <div class="actions">

            <div class="action-card">

                <div class="icon">
                    🌱
                </div>

                <h3>Indicatori ESG</h3>

                <p>
                    Inserisci nuovi indicatori ambientali o sociali.
                </p>

                <a href="indicatori.php">
                    Gestisci
                </a>

            </div>


            <div class="action-card">

                <div class="icon">
                    📄
                </div>

                <h3>Template bilancio</h3>

                <p>
                    Inserisci nuove voci contabili nel template.
                </p>

                <a href="template.php">
                    Gestisci
                </a>

            </div>


            <div class="action-card">

                <div class="icon">
                    👥
                </div>

                <h3>Assegna revisore</h3>

                <p>
                    Associa un revisore ESG a un bilancio.
                </p>

                <a href="assegna_revisore.php">
                    Assegna
                </a>

            </div>


            <div class="action-card">

                <div class="icon">
                    📊
                </div>

                <h3>Statistiche</h3>

                <p>
                    Visualizza le statistiche generali della piattaforma.
                </p>

                <a href="../statistiche.php">
                    Visualizza
                </a>

            </div>

        </div>

    </main>

</div>

</body>
</html>


































