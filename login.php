<?php

session_start();

require_once __DIR__ . '/config/database.php';

$messaggio = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    /*
     * Cerchiamo l'utente tramite la sua email.
     * EmailUtente contiene l'email ed è collegata a Utente
     * tramite id_utente.
     */
    $stmt = $connessione->prepare(
        "SELECT 
            u.id_utente,
            u.username,
            u.password,
            u.tipo_utente
         FROM Utente u
         INNER JOIN EmailUtente e
            ON u.id_utente = e.id_utente
         WHERE e.email = ?"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $utente = $result->fetch_assoc();

    $stmt->close();

    if (!$utente) {

        $messaggio = "Email o password non corretti.";

    } elseif ($password !== $utente["password"]) {

        $messaggio = "Email o password non corretti.";

    } else {

        /*
         * Login corretto:
         * salviamo i dati dell'utente nella sessione.
         */
        $_SESSION["id_utente"] = $utente["id_utente"];
        $_SESSION["username"] = $utente["username"];
        $_SESSION["tipo_utente"] = $utente["tipo_utente"];

        /*
         * Reindirizzamento in base al ruolo.
         */
        if ($utente["tipo_utente"] === "amministratore") {

            header("Location: admin/dashboard_admin.php");
            exit;

        } elseif ($utente["tipo_utente"] === "responsabile") {

            header("Location: responsabile/dashboard_responsabile.php");
            exit;

        } elseif ($utente["tipo_utente"] === "revisore") {

            header("Location: revisore/dashboard_revisore.php");
            exit;

        } else {

            $messaggio = "Tipo di utente non riconosciuto.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="it">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - ESG Balance</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #eef2ed;
            color: #2f332f;
        }

        .page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
        }

        /* PARTE SINISTRA */

        .brand-side {
            background: #9caf98;
            padding: 60px 70px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 25px;
            font-weight: 700;
            color: #263127;
        }

        .brand-icon {
            width: 44px;
            height: 44px;
            border-radius: 13px;
            background: rgba(255,255,255,0.28);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
        }

        .brand-content {
            max-width: 520px;
        }

        .brand-content h1 {
            margin: 0 0 20px;
            font-size: 52px;
            line-height: 1.08;
            color: #263127;
        }

        .brand-content p {
            margin: 0;
            color: #3f4c40;
            line-height: 1.7;
            font-size: 17px;
        }

        .brand-footer {
            color: #465447;
            font-size: 13px;
        }

        /* PARTE DESTRA */

        .login-side {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 30px;
        }

        .login-box {
            width: 100%;
            max-width: 450px;
        }

        .login-box h2 {
            margin: 0 0 8px;
            font-size: 31px;
            color: #303830;
        }

        .subtitle {
            margin: 0 0 30px;
            color: #6d766d;
            line-height: 1.6;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 14px;
            font-weight: 600;
            color: #414a41;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            margin-bottom: 19px;
            border: 1px solid #ccd5c8;
            border-radius: 10px;
            background: #f9fbf8;
            font-size: 15px;
            outline: none;
            transition: 0.2s;
        }

        input:focus {
            border-color: #9caf98;
            box-shadow: 0 0 0 3px rgba(156,175,152,0.16);
            background: white;
        }

        .login-button {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 10px;
            background: #c9a64b;
            color: white;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        .login-button:hover {
            background: #b4923e;
            transform: translateY(-1px);
        }

        .messaggio {
            margin-bottom: 20px;
            padding: 12px 14px;
            border-radius: 9px;
            background: #edd8d5;
            color: #804c46;
            font-size: 14px;
            font-weight: 600;
        }

        .register-text {
            text-align: center;
            margin-top: 24px;
            color: #6b746b;
            font-size: 14px;
        }

        .register-text a {
            color: #7a6937;
            font-weight: 700;
            text-decoration: none;
        }

        .register-text a:hover {
            text-decoration: underline;
        }

        .back-home {
            display: inline-block;
            margin-top: 25px;
            text-decoration: none;
            color: #536354;
            font-size: 14px;
            font-weight: 600;
        }

        @media (max-width: 900px) {

            .page {
                grid-template-columns: 1fr;
            }

            .brand-side {
                min-height: 300px;
                padding: 35px 30px;
            }

            .brand-content h1 {
                font-size: 38px;
            }

            .brand-footer {
                display: none;
            }

        }

    </style>

</head>

<body>

<div class="page">

    <section class="brand-side">

        <div class="brand-logo">

            <div class="brand-icon">
                🌿
            </div>

            ESG Balance

        </div>


        <div class="brand-content">

            <h1>
                Gestisci i tuoi bilanci in modo sostenibile.
            </h1>

            <p>
                Accedi alla piattaforma per gestire aziende,
                bilanci, indicatori ESG e revisioni in base
                al tuo ruolo.
            </p>

        </div>


        <div class="brand-footer">
            ESG Balance · Gestione e revisione dei bilanci ESG
        </div>

    </section>


    <section class="login-side">

        <div class="login-box">

            <h2>Bentornato</h2>

            <p class="subtitle">
                Inserisci le tue credenziali per accedere
                alla piattaforma.
            </p>


            <?php if ($messaggio !== ""): ?>

                <div class="messaggio">
                    <?php echo htmlspecialchars($messaggio); ?>
                </div>

            <?php endif; ?>


            <form method="post">

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="nome@email.it"
                    required
                >


                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Inserisci la password"
                    required
                >


                <button
                    type="submit"
                    class="login-button"
                >
                    Accedi
                </button>

            </form>


            <div class="register-text">

                Non hai ancora un account?

                <a href="registrazione.php">
                    Registrati
                </a>

            </div>


            <a
                class="back-home"
                href="index.php"
            >
                ← Torna alla Home
            </a>

        </div>

    </section>

</div>

</body>

</html>