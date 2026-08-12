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

<style>

    body {
        font-family: Arial, sans-serif;
        background-color: #f4f6f5;
        margin: 0;
        padding: 40px 20px;
    }

    .login-container {
        max-width: 450px;
        margin: 80px auto;
        background: white;
        padding: 35px 45px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }

    h1 {
        text-align: center;
        color: #333;
        margin-top: 0;
        margin-bottom: 30px;
    }

    form p {
        margin-bottom: 7px;
        color: #444;
    }

    input {
        width: 100%;
        box-sizing: border-box;
        padding: 11px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 15px;
        margin-bottom: 15px;
    }

    input:focus {
        outline: none;
        border-color: #35b98a;
    }

    button {
        width: 100%;
        padding: 12px;
        background-color: #35b98a;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
    }

    button:hover {
        background-color: #2da77b;
    }

    .messaggio {
        text-align: center;
        margin-bottom: 20px;
        color: #c0392b;
    }

    .torna-home {
        display: block;
        text-align: center;
        margin-top: 20px;
        color: #35a77e;
        text-decoration: none;
    }

</style>

<div class="login-container">

    <h1>Login</h1>

    <?php if ($messaggio !== "") { ?>
        <p class="messaggio">
            <?php echo htmlspecialchars($messaggio); ?>
        </p>
    <?php } ?>

    <form method="post">

        <p>Email</p>
        <input type="email" name="email" required>

        <p>Password</p>
        <input type="password" name="password" required>

        <br>

        <button type="submit">Accedi</button>

    </form>

    <a class="torna-home" href="index.php">
        Torna alla Home
    </a>

</div>