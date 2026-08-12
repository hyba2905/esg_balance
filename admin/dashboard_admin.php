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
    <title>Dashboard Amministratore</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f5;
            margin: 0;
        }

        .header {
            background-color: #35b98a;
            color: white;
            padding: 20px 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            margin: 0;
            font-size: 26px;
        }

        .logout {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .contenitore {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .benvenuto {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        .card h3 {
            margin-top: 0;
        }

        .card a {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 15px;
            background-color: #35b98a;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        .card a:hover {
            background-color: #2da77b;
        }
    </style>
</head>

<body>

<div class="header">

    <h1>Dashboard Amministratore</h1>

    <a class="logout" href="../logout.php">
        Logout
    </a>

</div>

<div class="contenitore">

    <div class="benvenuto">

        <h2>
            Benvenuto, <?php echo htmlspecialchars($username); ?>!
        </h2>

        <p>
            Da questa area puoi gestire il template,
            gli indicatori ESG e le revisioni dei bilanci.
        </p>

    </div>

    <div class="cards">

        <div class="card">

            <h3>Indicatori ESG</h3>

            <p>
                Inserisci nuovi indicatori ambientali o sociali.
            </p>

            <a href="indicatori.php">
                Gestisci indicatori
            </a>

        </div>

        <div class="card">

            <h3>Template bilancio</h3>

            <p>
                Inserisci nuove voci contabili nel template.
            </p>

            <a href="template.php">
                Gestisci template
            </a>

        </div>

        <div class="card">

            <h3>Assegna revisore</h3>

            <p>
                Associa un revisore ESG a un bilancio.
            </p>

            <a href="assegna_revisore.php">
                Assegna revisore
            </a>

        </div>

    </div>

</div>

</body>
</html>