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
<title>Indicatori ESG</title>

<style>

body {
    font-family: Arial, sans-serif;
    background-color: #f4f6f5;
    margin: 0;
    padding: 40px 20px;
}

.container {
    max-width: 850px;
    margin: 0 auto;
}

.box {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}

h1 {
    color: #333;
}

.indicatore {
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.indicatore:last-child {
    border-bottom: none;
}

.indicatore h3 {
    margin: 0 0 8px 0;
}

label {
    display: block;
    font-weight: bold;
    margin-top: 15px;
    margin-bottom: 6px;
}

input,
select {
    width: 100%;
    box-sizing: border-box;
    padding: 11px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 15px;
}

button {
    width: 100%;
    margin-top: 20px;
    padding: 12px;
    border: none;
    border-radius: 6px;
    background-color: #35b98a;
    color: white;
    font-size: 16px;
    cursor: pointer;
}

button:hover {
    background-color: #2da77b;
}

.messaggio {
    font-weight: bold;
    margin-bottom: 20px;
}

.campo-specifico {
    display: none;
}

.indietro {
    display: block;
    text-align: center;
    margin-top: 20px;
    color: #35a77e;
    text-decoration: none;
}

</style>

</head>

<body>

<div class="container">

    <h1>Indicatori ESG</h1>


    <div class="box">

        <h2>Indicatori presenti</h2>

        <?php if ($indicatori && $indicatori->num_rows > 0): ?>

            <?php while ($indicatore = $indicatori->fetch_assoc()): ?>

                <div class="indicatore">

                    <h3>
                        <?php echo htmlspecialchars($indicatore["nome"]); ?>
                    </h3>

                    <p>
                        <strong>Tipo:</strong>
                        <?php echo htmlspecialchars($indicatore["tipo"]); ?>
                    </p>

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


    <div class="box">

        <h2>Aggiungi indicatore ESG</h2>

        <?php if ($messaggio !== ""): ?>

            <p class="messaggio">
                <?php echo htmlspecialchars($messaggio); ?>
            </p>

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


    <a
        class="indietro"
        href="dashboard_admin.php"
    >
        Torna alla dashboard
    </a>

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