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
    <title>Dashboard Responsabile</title>

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
            color: #333;
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

        <h1>Dashboard Responsabile Aziendale</h1>

        <a class="logout" href="../logout.php">
            Logout
        </a>

    </div>

    <div class="contenitore">

        <div class="benvenuto">

            <h2>Benvenuto, <?php echo htmlspecialchars($username); ?>!</h2>

            <p>
                Da questa area puoi gestire le aziende e i relativi bilanci.
            </p>

        </div>

        <div class="cards">

            <div class="card">

                <h3>Le mie aziende</h3>

                <p>
                    Visualizza le aziende associate al tuo account.
                </p>

                <a href="aziende.php">
                    Visualizza aziende
                </a>

            </div>

            <div class="card">

                <h3>Registra azienda</h3>

                <p>
                    Inserisci una nuova azienda nella piattaforma.
                </p>

                <a href="registra_azienda.php">
                    Registra azienda
                </a>

            </div>

            <div class="card">

                <h3>Bilanci</h3>

                <p>
                    Crea e gestisci i bilanci aziendali.
                </p>

                <a href="bilanci.php">
                    Gestisci bilanci
                </a>

            </div>

        </div>

    </div>

</body>
</html>