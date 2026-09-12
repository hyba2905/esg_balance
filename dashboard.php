<?php

session_start();

if (!isset($_SESSION["id_utente"])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION["username"];
$tipo_utente = $_SESSION["tipo_utente"];

?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard - ESG Balance</title>

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

        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .btn {
            display: inline-block;
            margin-top: 10px;
            padding: 12px 18px;
            background-color: #35b98a;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        .btn:hover {
            background-color: #2da77b;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="card">

        <h1>ESG Balance</h1>

        <p>
            Benvenuto,
            <strong>
                <?php echo htmlspecialchars($username); ?>
            </strong>
        </p>

        <p>
            Ruolo:
            <?php echo htmlspecialchars($tipo_utente); ?>
        </p>

    </div>


    <div class="card">

        <h2>Statistiche</h2>

        <p>
            Visualizza le statistiche generali della piattaforma.
        </p>

        <a class="btn" href="statistiche.php">
            Visualizza statistiche
        </a>

    </div>


    <div class="card">

        <h2>Area personale</h2>

        <?php if ($tipo_utente === "amministratore"): ?>

            <a class="btn" href="admin/dashboard_admin.php">
                Vai alla dashboard amministratore
            </a>

        <?php elseif ($tipo_utente === "responsabile"): ?>

            <a class="btn" href="responsabile/dashboard_responsabile.php">
                Vai alla dashboard responsabile
            </a>

        <?php elseif ($tipo_utente === "revisore"): ?>

            <a class="btn" href="revisore/dashboard_revisore.php">
                Vai alla dashboard revisore
            </a>

        <?php endif; ?>

    </div>

</div>

</body>
</html>