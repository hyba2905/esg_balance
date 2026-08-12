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

<title>Template bilancio</title>

<style>

body {
    font-family: Arial, sans-serif;
    background-color: #f4f6f5;
    margin: 0;
    padding: 40px 20px;
}

.container {
    max-width: 800px;
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

.voce {
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.voce:last-child {
    border-bottom: none;
}

.voce h3 {
    margin: 0 0 5px 0;
}

.descrizione {
    color: #666;
}

label {
    display: block;
    font-weight: bold;
    margin-top: 15px;
    margin-bottom: 6px;
}

input,
textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 11px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 15px;
}

textarea {
    min-height: 100px;
    resize: vertical;
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

.indietro {
    display: block;
    text-align: center;
    color: #35a77e;
    text-decoration: none;
    margin-top: 20px;
}

</style>

</head>

<body>

<div class="container">

    <h1>Template bilancio</h1>

    <div class="box">

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

            <p>Nessuna voce presente nel template.</p>

        <?php endif; ?>

    </div>


    <div class="box">

        <h2>Aggiungi nuova voce</h2>

        <?php if ($messaggio !== ""): ?>

            <p class="messaggio">
                <?php echo htmlspecialchars($messaggio); ?>
            </p>

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


    <a
        class="indietro"
        href="dashboard_admin.php"
    >
        Torna alla dashboard
    </a>

</div>

</body>

</html>