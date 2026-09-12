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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Revisore ESG</title>

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

        .user-box {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #566056;
            font-weight: 600;
        }

        .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #c9a64b;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .welcome {
            background: #dfe7df;
            border-radius: 18px;
            padding: 24px 26px;
            margin-bottom: 25px;
        }

        .welcome h2 {
            margin: 0 0 8px;
            font-size: 23px;
        }

        .welcome p {
            margin: 0;
            color: #606960;
            line-height: 1.6;
        }

        .quick-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .quick-card {
            background: #f7f9f5;
            border-radius: 18px;
            padding: 23px;
            border: 1px solid #dfe6dc;
            box-shadow: 0 5px 18px rgba(0,0,0,0.05);
        }

        .quick-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: #dfe7df;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            margin-bottom: 15px;
        }

        .quick-card h3 {
            margin: 0 0 8px;
            font-size: 18px;
        }

        .quick-card p {
            margin: 0 0 16px;
            color: #687168;
            font-size: 14px;
            line-height: 1.5;
        }

        .btn {
            display: inline-block;
            padding: 9px 14px;
            border-radius: 9px;
            background: #9caf98;
            color: #263127;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            transition: 0.2s;
        }

        .btn:hover {
            background: #899e86;
        }

        .section-title {
            margin: 0 0 16px;
            font-size: 22px;
        }

        .bilanci-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
            gap: 20px;
        }

        .bilancio-card {
            background: #f7f9f5;
            border-radius: 18px;
            padding: 22px;
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
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 18px;
        }

        .title-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .bilancio-icon {
            width: 43px;
            height: 43px;
            border-radius: 12px;
            background: #dfe7df;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .bilancio-card h3 {
            margin: 0;
            font-size: 18px;
        }

        .stato,
        .esito {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
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

        .esito-pending {
            background: #ececec;
            color: #666;
        }

        .esito-positivo {
            background: #d8e7d5;
            color: #496548;
        }

        .esito-rilievi {
            background: #eee3bf;
            color: #796529;
        }

        .esito-negativo {
            background: #edd8d5;
            color: #804c46;
        }

        .info-row {
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e2e7df;
        }

        .info-row:last-of-type {
            border-bottom: none;
        }

        .info-label {
            display: block;
            color: #7b847b;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .info-value {
            color: #3d453d;
            font-weight: 600;
        }

        .review-button {
            display: inline-block;
            margin-top: 8px;
            padding: 10px 14px;
            border-radius: 9px;
            background: #c9a64b;
            color: white;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            transition: 0.2s;
        }

        .review-button:hover {
            background: #b4923e;
        }

        .empty {
            background: #f7f9f5;
            border-radius: 18px;
            padding: 35px;
            text-align: center;
            border: 1px solid #dfe6dc;
            color: #687168;
        }

        @media (max-width: 850px) {

            .quick-grid {
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

                <a href="dashboard_revisore.php" class="active">
                    🏠 Dashboard
                </a>

                <a href="competenze.php">
                    🎯 Le mie competenze
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

            <h1>Dashboard Revisore ESG</h1>

            <div class="user-box">

                <span>
                    <?php echo htmlspecialchars($username); ?>
                </span>

                <div class="avatar">
                    <?php echo strtoupper(
                        substr($username, 0, 1)
                    ); ?>
                </div>

            </div>

        </div>


        <div class="welcome">

            <h2>
                Benvenuto, <?php echo htmlspecialchars($username); ?>!
            </h2>

            <p>
                Da questa area puoi consultare i bilanci assegnati,
                esprimere il tuo giudizio ESG e gestire le tue competenze.
            </p>

        </div>


        <div class="quick-grid">

            <div class="quick-card">

                <div class="quick-icon">
                    🎯
                </div>

                <h3>Le mie competenze</h3>

                <p>
                    Inserisci o aggiorna le tue competenze ESG
                    indicando il relativo livello.
                </p>

                <a class="btn" href="competenze.php">
                    Gestisci competenze
                </a>

            </div>


            <div class="quick-card">

                <div class="quick-icon">
                    📊
                </div>

                <h3>Statistiche</h3>

                <p>
                    Consulta le statistiche generali della piattaforma ESG Balance.
                </p>

                <a class="btn" href="../statistiche.php">
                    Visualizza statistiche
                </a>

            </div>

        </div>


        <h2 class="section-title">
            Bilanci assegnati
        </h2>


        <?php if ($bilanci->num_rows > 0): ?>

            <div class="bilanci-grid">

                <?php while ($bilancio = $bilanci->fetch_assoc()): ?>

                    <?php

                    $classe_stato = "stato-bozza";

                    if ($bilancio["stato"] === "in revisione") {

                        $classe_stato = "stato-revisione";

                    } elseif ($bilancio["stato"] === "approvato") {

                        $classe_stato = "stato-approvato";

                    } elseif ($bilancio["stato"] === "respinto") {

                        $classe_stato = "stato-respinto";
                    }


                    $classe_esito = "esito-pending";
                    $testo_esito = "Non ancora espresso";

                    if ($bilancio["esito"] !== null) {

                        $testo_esito = $bilancio["esito"];

                        if ($bilancio["esito"] === "approvazione") {

                            $classe_esito = "esito-positivo";

                        } elseif ($bilancio["esito"] === "approvazione con rilievi") {

                            $classe_esito = "esito-rilievi";

                        } elseif ($bilancio["esito"] === "respingimento") {

                            $classe_esito = "esito-negativo";
                        }
                    }

                    ?>

                    <div class="bilancio-card">

                        <div class="card-header">

                            <div class="title-wrap">

                                <div class="bilancio-icon">
                                    📄
                                </div>

                                <h3>
                                    Bilancio #<?php echo $bilancio["id_bilancio"]; ?>
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


                        <div class="info-row">

                            <span class="info-label">
                                Esito della tua revisione
                            </span>

                            <span class="esito <?php echo $classe_esito; ?>">
                                <?php echo htmlspecialchars(
                                    ucfirst($testo_esito)
                                ); ?>
                            </span>

                        </div>


                        <a
                            class="review-button"
                            href="revisione_bilancio.php?id=<?php echo $bilancio["id_bilancio"]; ?>"
                        >
                            Apri revisione →
                        </a>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <div class="empty">
                Non hai ancora bilanci assegnati.
            </div>

        <?php endif; ?>

    </main>

</div>

</body>

</html>