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

$messaggio = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"]);
    $ragione_sociale = trim($_POST["ragione_sociale"]);
    $partita_iva = trim($_POST["partita_iva"]);
    $settore = trim($_POST["settore"]);
    $num_dipendenti = (int) $_POST["num_dipendenti"];

    $logo = trim($_POST["logo"]);
    if ($logo === "") {
        $logo = null;
    }

    $id_responsabile = $_SESSION["id_utente"];

    try {

        $stmt = $connessione->prepare(
            "CALL sp_RegistraAzienda(?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssssisi",
            $nome,
            $ragione_sociale,
            $partita_iva,
            $settore,
            $num_dipendenti,
            $logo,
            $id_responsabile
        );

        $stmt->execute();
        $stmt->close();

        while ($connessione->more_results()) {
            $connessione->next_result();

            if ($result = $connessione->store_result()) {
                $result->free();
            }
        }

        $messaggio = "Azienda registrata con successo!";

    } catch (Throwable $e) {

        $messaggio = "Errore durante la registrazione dell'azienda: "
                    . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="it">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registra azienda</title>

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

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 0.75fr;
            gap: 22px;
            align-items: start;
        }

        .panel {
            background: #f7f9f5;
            border-radius: 18px;
            padding: 28px;
            border: 1px solid #dfe6dc;
            box-shadow: 0 5px 18px rgba(0,0,0,0.05);
        }

        .panel h2 {
            margin-top: 0;
            margin-bottom: 8px;
            font-size: 22px;
        }

        .panel-description {
            margin-top: 0;
            margin-bottom: 24px;
            color: #687168;
            line-height: 1.6;
            font-size: 14px;
        }

        label {
            display: block;
            margin-top: 16px;
            margin-bottom: 6px;
            font-weight: 600;
            color: #424b42;
        }

        input {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #ccd5c8;
            border-radius: 10px;
            font-size: 14px;
            background: white;
            outline: none;
        }

        input:focus {
            border-color: #9caf98;
            box-shadow: 0 0 0 3px rgba(156,175,152,0.16);
        }

        button {
            width: 100%;
            margin-top: 24px;
            padding: 13px;
            border: none;
            border-radius: 10px;
            background: #9caf98;
            color: #263127;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        button:hover {
            background: #899e86;
            transform: translateY(-1px);
        }

        .messaggio {
            background: #e2eadf;
            border-left: 4px solid #a88b3f;
            border-radius: 8px;
            padding: 12px 14px;
            font-weight: 600;
            margin-bottom: 18px;
        }

        .info-card {
            background: #dfe7df;
            border-radius: 18px;
            padding: 24px;
        }

        .info-card h3 {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 19px;
        }

        .info-card p {
            color: #5f685f;
            line-height: 1.6;
            font-size: 14px;
        }

        .info-item {
            margin-top: 14px;
            padding: 14px;
            background: rgba(255,255,255,0.35);
            border-radius: 12px;
        }

        .info-item strong {
            display: block;
            margin-bottom: 4px;
        }

        .info-item span {
            color: #667066;
            font-size: 13px;
        }

        @media (max-width: 900px) {

            .content-grid {
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

                <a href="dashboard_responsabile.php">
                    🏠 Dashboard
                </a>

                <a href="aziende.php">
                    🏢 Le mie aziende
                </a>

                <a href="registra_azienda.php" class="active">
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

            <h1>Registra azienda</h1>

            <a
                class="back-link"
                href="dashboard_responsabile.php"
            >
                ← Dashboard
            </a>

        </div>


        <div class="content-grid">

            <div class="panel">

                <h2>Nuova azienda</h2>

                <p class="panel-description">
                    Inserisci le informazioni principali dell'azienda
                    da associare al tuo account.
                </p>

                <?php if ($messaggio !== ""): ?>

                    <div class="messaggio">
                        <?php echo htmlspecialchars($messaggio); ?>
                    </div>

                <?php endif; ?>


                <form method="post">

                    <label for="nome">
                        Nome
                    </label>

                    <input
                        type="text"
                        name="nome"
                        id="nome"
                        required
                    >


                    <label for="ragione_sociale">
                        Ragione sociale
                    </label>

                    <input
                        type="text"
                        name="ragione_sociale"
                        id="ragione_sociale"
                        required
                    >


                    <label for="partita_iva">
                        Partita IVA
                    </label>

                    <input
                        type="text"
                        name="partita_iva"
                        id="partita_iva"
                        maxlength="11"
                        minlength="11"
                        placeholder="11 cifre"
                        required
                    >


                    <label for="settore">
                        Settore
                    </label>

                    <input
                        type="text"
                        name="settore"
                        id="settore"
                        placeholder="es. Energia, Trasporti, Servizi..."
                        required
                    >


                    <label for="num_dipendenti">
                        Numero dipendenti
                    </label>

                    <input
                        type="number"
                        name="num_dipendenti"
                        id="num_dipendenti"
                        min="0"
                        required
                    >


                    <label for="logo">
                        Logo
                    </label>

                    <input
                        type="text"
                        name="logo"
                        id="logo"
                        placeholder="es. logo_azienda.png"
                    >


                    <button type="submit">
                        Registra azienda
                    </button>

                </form>

            </div>


            <div class="info-card">

                <h3>Informazioni aziendali</h3>

                <p>
                    I dati inseriti verranno utilizzati per identificare
                    l'azienda all'interno della piattaforma ESG Balance.
                </p>

                <div class="info-item">

                    <strong>Partita IVA</strong>

                    <span>
                        Deve essere composta da 11 caratteri.
                    </span>

                </div>


                <div class="info-item">

                    <strong>Numero dipendenti</strong>

                    <span>
                        Inserisci il numero attuale di dipendenti dell'azienda.
                    </span>

                </div>


                <div class="info-item">

                    <strong>Logo</strong>

                    <span>
                        È facoltativo. Puoi indicare il nome del file
                        associato al logo aziendale.
                    </span>

                </div>

            </div>

        </div>

    </main>

</div>

</body>
</html>