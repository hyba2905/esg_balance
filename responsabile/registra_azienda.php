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
    <title>Registra azienda</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f5;
            margin: 0;
            padding: 40px 20px;
        }

        .container {
            max-width: 550px;
            margin: 0 auto;
            background: white;
            padding: 35px 45px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        h1 {
            text-align: center;
            margin-top: 0;
            color: #333;
        }

        p {
            margin-bottom: 7px;
            color: #444;
        }

        input {
            width: 100%;
            box-sizing: border-box;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-bottom: 12px;
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: #35b98a;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background-color: #35b98a;
            color: white;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background-color: #2da77b;
        }

        .messaggio {
            text-align: center;
            margin-bottom: 20px;
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

    <h1>Registra azienda</h1>

    <?php if ($messaggio !== "") { ?>
        <p class="messaggio">
            <?php echo htmlspecialchars($messaggio); ?>
        </p>
    <?php } ?>

    <form method="post">

        <p>Nome</p>
        <input type="text" name="nome" required>

        <p>Ragione sociale</p>
        <input type="text" name="ragione_sociale" required>

        <p>Partita IVA</p>
        <input
            type="text"
            name="partita_iva"
            maxlength="11"
            minlength="11"
            required
        >

        <p>Settore</p>
        <input type="text" name="settore" required>

        <p>Numero dipendenti</p>
        <input
            type="number"
            name="num_dipendenti"
            min="0"
            required
        >

        <p>Logo (nome file, facoltativo)</p>
        <input
            type="text"
            name="logo"
            placeholder="es. logo_azienda.png"
        >

        <button type="submit">
            Registra azienda
        </button>

    </form>

    <a class="indietro" href="dashboard_responsabile.php">
        Torna alla dashboard
    </a>

</div>

</body>
</html>