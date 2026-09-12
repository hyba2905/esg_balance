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

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $immagine = trim($_POST["immagine"] ?? "");
    $rilevanza = (int) ($_POST["rilevanza"] ?? 0);
    $tipo = $_POST["tipo"] ?? "";

    $codice_normativa = trim($_POST["codice_normativa"] ?? "");
    $ambito_sociale = trim($_POST["ambito_sociale"] ?? "");
    $frequenza_rilevazione = trim($_POST["frequenza_rilevazione"] ?? "");

    if (!in_array($tipo, ["ambientale", "sociale"], true)) {

        $messaggio = "Tipo di indicatore non valido.";

    } else {

        /*
         * Impostiamo a NULL i campi che non riguardano
         * il tipo di indicatore scelto.
         */
        if ($tipo === "ambientale") {

            $ambito_sociale = null;
            $frequenza_rilevazione = null;

        } else {

            $codice_normativa = null;
        }

        try {

            $stmt = $connessione->prepare(
                "CALL sp_InserisciIndicatoreESG(?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssissss",
                $nome,
                $immagine,
                $rilevanza,
                $tipo,
                $codice_normativa,
                $ambito_sociale,
                $frequenza_rilevazione
            );

            $stmt->execute();
            $stmt->close();

            while ($connessione->more_results()) {
                $connessione->next_result();

                if ($r = $connessione->store_result()) {
                    $r->free();
                }
            }

            $messaggio = "Indicatore ESG aggiunto con successo!";

        } catch (Throwable $e) {

            $messaggio =
                "Errore durante l'inserimento: "
                . $e->getMessage();
        }
    }
}


/*
 * Recuperiamo gli indicatori già presenti.
 */
$indicatori = $connessione->query(
    "SELECT
        i.id_indicatore,
        i.nome,
        i.immagine,
        i.rilevanza,
        i.tipo,
        ia.codice_normativa,
        isoc.ambito_sociale,
        isoc.frequenza_rilevazione
     FROM IndicatoreESG i
     LEFT JOIN IndicatoreAmbientale ia
        ON i.id_indicatore = ia.id_indicatore
     LEFT JOIN IndicatoreSociale isoc
        ON i.id_indicatore = isoc.id_indicatore
     ORDER BY i.id_indicatore"
);

?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indicatori ESG</title>

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

        .indicatore {
            background: #e3e9e0;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 14px;
        }

        .indicatore:last-child {
            margin-bottom: 0;
        }

        .indicatore h3 {
            margin: 0 0 10px;
            font-size: 17px;
        }

        .indicatore p {
            margin: 6px 0;
            color: #5c655c;
            font-size: 14px;
        }

        .tag {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .tag.ambientale {
            background: #d7e5d4;
            color: #466047;
        }

        .tag.sociale {
            background: #eadfbd;
            color: #705f2f;
        }

        label {
            display: block;
            font-weight: 600;
            margin-top: 15px;
            margin-bottom: 6px;
            color: #424b42;
        }

        input,
        select {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #ccd5c8;
            border-radius: 10px;
            font-size: 14px;
            background: white;
            outline: none;
        }

        input:focus,
        select:focus {
            border-color: #9caf98;
            box-shadow: 0 0 0 3px rgba(156,175,152,0.16);
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

        .campo-specifico {
            display: none;
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

                <a href="indicatori.php" class="active">
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

            <h1>Indicatori ESG</h1>

            <a class="back-link" href="dashboard_admin.php">
                ← Dashboard
            </a>

        </div>


        <div class="content-grid">

            <div class="panel">

                <h2>Indicatori presenti</h2>

                <?php if ($indicatori && $indicatori->num_rows > 0): ?>

                    <?php while ($indicatore = $indicatori->fetch_assoc()): ?>

                        <div class="indicatore">

                            <span class="tag <?php echo htmlspecialchars($indicatore["tipo"]); ?>">
                                <?php echo ucfirst(htmlspecialchars($indicatore["tipo"])); ?>
                            </span>

                            <h3>
                                <?php echo htmlspecialchars($indicatore["nome"]); ?>
                            </h3>

                            <p>
                                <strong>Rilevanza:</strong>
                                <?php echo htmlspecialchars($indicatore["rilevanza"]); ?>
                            </p>

                            <?php if ($indicatore["tipo"] === "ambientale"): ?>

                                <p>
                                    <strong>Codice normativa:</strong>
                                    <?php echo htmlspecialchars(
                                        $indicatore["codice_normativa"] ?? ""
                                    ); ?>
                                </p>

                            <?php else: ?>

                                <p>
                                    <strong>Ambito sociale:</strong>
                                    <?php echo htmlspecialchars(
                                        $indicatore["ambito_sociale"] ?? ""
                                    ); ?>
                                </p>

                                <p>
                                    <strong>Frequenza rilevazione:</strong>
                                    <?php echo htmlspecialchars(
                                        $indicatore["frequenza_rilevazione"] ?? ""
                                    ); ?>
                                </p>

                            <?php endif; ?>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <p>Nessun indicatore presente.</p>

                <?php endif; ?>

            </div>


            <div class="panel">

                <h2>Aggiungi indicatore ESG</h2>

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


                    <label for="immagine">
                        Immagine
                    </label>

                    <input
                        type="text"
                        name="immagine"
                        id="immagine"
                        placeholder="es. energia.png"
                    >


                    <label for="rilevanza">
                        Rilevanza
                    </label>

                    <input
                        type="number"
                        name="rilevanza"
                        id="rilevanza"
                        required
                    >


                    <label for="tipo">
                        Tipo
                    </label>

                    <select
                        name="tipo"
                        id="tipo"
                        required
                        onchange="aggiornaCampi()"
                    >

                        <option value="">
                            Seleziona il tipo
                        </option>

                        <option value="ambientale">
                            Ambientale
                        </option>

                        <option value="sociale">
                            Sociale
                        </option>

                    </select>


                    <div
                        id="campoAmbientale"
                        class="campo-specifico"
                    >

                        <label for="codice_normativa">
                            Codice normativa
                        </label>

                        <input
                            type="text"
                            name="codice_normativa"
                            id="codice_normativa"
                            placeholder="es. ISO 14001"
                        >

                    </div>


                    <div
                        id="campoSociale"
                        class="campo-specifico"
                    >

                        <label for="ambito_sociale">
                            Ambito sociale
                        </label>

                        <input
                            type="text"
                            name="ambito_sociale"
                            id="ambito_sociale"
                        >


                        <label for="frequenza_rilevazione">
                            Frequenza rilevazione
                        </label>

                        <input
                            type="text"
                            name="frequenza_rilevazione"
                            id="frequenza_rilevazione"
                            placeholder="es. annuale"
                        >

                    </div>


                    <button type="submit">
                        Aggiungi indicatore
                    </button>

                </form>

            </div>

        </div>

    </main>

</div>


<script>

function aggiornaCampi() {

    const tipo = document.getElementById("tipo").value;

    const ambientale =
        document.getElementById("campoAmbientale");

    const sociale =
        document.getElementById("campoSociale");

    if (tipo === "ambientale") {

        ambientale.style.display = "block";
        sociale.style.display = "none";

        document.getElementById(
            "codice_normativa"
        ).required = true;

        document.getElementById(
            "ambito_sociale"
        ).required = false;

        document.getElementById(
            "frequenza_rilevazione"
        ).required = false;

    } else if (tipo === "sociale") {

        ambientale.style.display = "none";
        sociale.style.display = "block";

        document.getElementById(
            "codice_normativa"
        ).required = false;

        document.getElementById(
            "ambito_sociale"
        ).required = true;

        document.getElementById(
            "frequenza_rilevazione"
        ).required = true;

    } else {

        ambientale.style.display = "none";
        sociale.style.display = "none";

    }
}

</script>

</body>
</html>