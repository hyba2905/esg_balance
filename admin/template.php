<?php

session_start();

require_once __DIR__ . '/../config/database.php';

if (
    !isset($_SESSION["id_utente"]) ||
    $_SESSION["tipo_utente"] !== "amministratore"
) {
    header("Location: ../login.php");
    exit;
}

$messaggio = "";

/* INSERIMENTO NUOVA VOCE */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $descrizione = trim($_POST["descrizione"] ?? "");

    if ($nome === "") {

        $messaggio = "Inserisci il nome della voce.";

    } else {

        try {

            $stmt = $connessione->prepare(
    "CALL sp_InserisciVoceTemplate(?, ?)"
);
            

            $stmt->bind_param(
                "ss",
                $nome,
                $descrizione
            );

            $stmt->execute();
            $stmt->close();

            while ($connessione->more_results()) {
                $connessione->next_result();

                if ($r = $connessione->store_result()) {
                    $r->free();
                }
            }

            $messaggio = "Voce aggiunta con successo!";

        } catch (Throwable $e) {

            $messaggio =
                "Errore durante l'inserimento: "
                . $e->getMessage();
        }
    }
}


/* RECUPERIAMO LE VOCI ESISTENTI */
$voci = $connessione->query(
    "SELECT id_voce, nome, descrizione
     FROM VoceContabile
     ORDER BY id_voce"
);

?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Template bilancio</title>

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
            grid-template-columns: 1.1fr 0.9fr;
            gap: 22px;
            align-items: start;
        }

        .panel {
            background: #f7f9f5;
            border-radius: 18px;
            padding: 25px;
            border: 1px solid #dfe6dc;
            box-shadow: 0 5px 18px rgba(0,0,0,0.05);
        }

        .panel h2 {
            margin-top: 0;
            font-size: 21px;
        }

        .voce {
            background: #e3e9e0;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 14px;
        }

        .voce:last-child {
            margin-bottom: 0;
        }

        .voce h3 {
            margin: 0 0 8px;
            font-size: 17px;
        }

        .descrizione {
            color: #5f685f;
            font-size: 14px;
            line-height: 1.5;
        }

        label {
            display: block;
            font-weight: 600;
            margin-top: 15px;
            margin-bottom: 6px;
            color: #424b42;
        }

        input,
        textarea {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #ccd5c8;
            border-radius: 10px;
            font-size: 14px;
            background: white;
            outline: none;
        }

        input:focus,
        textarea:focus {
            border-color: #9caf98;
            box-shadow: 0 0 0 3px rgba(156,175,152,0.16);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 12px;
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
            margin-bottom: 15px;
        }

        .empty {
            color: #6b746b;
        }

        @media (max-width: 950px) {
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

                <a href="dashboard_admin.php">
                    🏠 Dashboard
                </a>

                <a href="indicatori.php">
                    🌱 Indicatori ESG
                </a>

                <a href="template.php" class="active">
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

            <h1>Template bilancio</h1>

            <a class="back-link" href="dashboard_admin.php">
                ← Dashboard
            </a>

        </div>


        <div class="content-grid">

            <div class="panel">

                <h2>Voci contabili</h2>

                <?php if ($voci && $voci->num_rows > 0): ?>

                    <?php while ($voce = $voci->fetch_assoc()): ?>

                        <div class="voce">

                            <h3>
                                <?php echo htmlspecialchars($voce["nome"]); ?>
                            </h3>

                            <div class="descrizione">

                                <?php
                                echo !empty($voce["descrizione"])
                                    ? htmlspecialchars($voce["descrizione"])
                                    : "Nessuna descrizione";
                                ?>

                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <p class="empty">
                        Nessuna voce presente nel template.
                    </p>

                <?php endif; ?>

            </div>


            <div class="panel">

                <h2>Aggiungi nuova voce</h2>

                <?php if ($messaggio !== ""): ?>

                    <div class="messaggio">
                        <?php echo htmlspecialchars($messaggio); ?>
                    </div>

                <?php endif; ?>

                <form method="post">

                    <label for="nome">
                        Nome voce
                    </label>

                    <input
                        type="text"
                        name="nome"
                        id="nome"
                        required
                    >


                    <label for="descrizione">
                        Descrizione
                    </label>

                    <textarea
                        name="descrizione"
                        id="descrizione"
                        placeholder="Descrizione della voce contabile..."
                    ></textarea>


                    <button type="submit">
                        Aggiungi voce
                    </button>

                </form>

            </div>

        </div>

    </main>

</div>

</body>
</html>