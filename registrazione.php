<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - ESG Balance</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="contenitore">

    <h1>Registrazione</h1>

    <form method="post">

        <p>Username</p>
        <input type="text" name="username" required>

        <p>Password</p>
        <input type="password" name="password" required>

        <p>Codice fiscale</p>
        <input type="text" name="codice_fiscale" required>

        <p>Data di nascita</p>
        <input type="date" name="data_nascita" required>

        <p>Luogo di nascita</p>
        <input type="text" name="luogo_nascita" required>

        <p>Email</p>
        <input type="email" name="email" required>

        <p>Ruolo</p>
        <select name="ruolo" required>
            <option value="">Seleziona un ruolo</option>
            <option value="amministratore">Amministratore</option>
            <option value="revisore">Revisore ESG</option>
            <option value="responsabile">Responsabile aziendale</option>
        </select>

        <br><br>

        <button type="submit">Registrati</button>

    </form>

    <br>

    <a href="index.php">Torna alla Home</a>

</div>

</body>
</html>