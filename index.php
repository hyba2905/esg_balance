<?php
session_start();
?>

<!DOCTYPE html>
<html lang="it">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ESG Balance</title>

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

        /* NAVBAR */

        .navbar {
            height: 78px;
            padding: 0 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f7f9f5;
            border-bottom: 1px solid #dde4da;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 23px;
            font-weight: 700;
            color: #344235;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #9caf98;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .login-link {
            color: #4d5b4e;
            text-decoration: none;
            font-weight: 600;
            padding: 10px 16px;
        }

        .register-link {
            background: #c9a64b;
            color: white;
            text-decoration: none;
            font-weight: 700;
            padding: 11px 18px;
            border-radius: 10px;
            transition: 0.2s;
        }

        .register-link:hover {
            background: #b4923e;
        }

        /* HERO */

        .hero {
            min-height: calc(100vh - 78px);
            display: flex;
            align-items: center;
            padding: 60px 8%;
            gap: 70px;
        }

        .hero-text {
            flex: 1;
            max-width: 650px;
        }

        .badge {
            display: inline-block;
            background: #dfe7df;
            color: #526253;
            padding: 8px 13px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .hero h1 {
            margin: 0 0 20px;
            font-size: clamp(45px, 6vw, 72px);
            line-height: 1.05;
            color: #303b31;
        }

        .hero h1 span {
            color: #9caf98;
        }

        .hero-description {
            max-width: 570px;
            margin: 0 0 30px;
            font-size: 18px;
            line-height: 1.7;
            color: #667067;
        }

        .hero-buttons {
            display: flex;
            gap: 13px;
            flex-wrap: wrap;
        }

        .primary-btn,
        .secondary-btn {
            display: inline-block;
            padding: 13px 21px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            transition: 0.2s;
        }

        .primary-btn {
            background: #c9a64b;
            color: white;
        }

        .primary-btn:hover {
            background: #b4923e;
            transform: translateY(-2px);
        }

        .secondary-btn {
            background: #f7f9f5;
            border: 1px solid #cbd5c8;
            color: #4b594c;
        }

        .secondary-btn:hover {
            background: #e4ebe2;
        }

        /* VISUAL */

        .hero-visual {
            flex: 0 1 500px;
        }

        .visual-card {
            background: #f7f9f5;
            border: 1px solid #dce4d9;
            border-radius: 26px;
            padding: 32px;
            box-shadow: 0 15px 45px rgba(55, 70, 56, 0.10);
        }

        .visual-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .visual-header h2 {
            margin: 0;
            font-size: 19px;
        }

        .status {
            background: #dce9d9;
            color: #4c684c;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .feature {
            display: flex;
            gap: 15px;
            align-items: center;
            padding: 17px 0;
            border-bottom: 1px solid #e0e6dd;
        }

        .feature:last-child {
            border-bottom: none;
        }

        .feature-icon {
            min-width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #dfe7df;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .feature h3 {
            margin: 0 0 4px;
            font-size: 15px;
        }

        .feature p {
            margin: 0;
            color: #778078;
            font-size: 13px;
            line-height: 1.4;
        }

        .bottom-accent {
            height: 5px;
            width: 70px;
            background: #c9a64b;
            border-radius: 10px;
            margin-top: 22px;
        }

        @media (max-width: 900px) {

            .hero {
                flex-direction: column;
                align-items: flex-start;
                gap: 45px;
            }

            .hero-visual {
                width: 100%;
                flex: none;
            }

        }

        @media (max-width: 600px) {

            .navbar {
                padding: 0 20px;
            }

            .register-link {
                display: none;
            }

            .hero {
                padding: 45px 25px;
            }

            .hero h1 {
                font-size: 44px;
            }

        }

    </style>

</head>

<body>

<header class="navbar">

    <div class="logo">

        <div class="logo-icon">
            🌿
        </div>

        ESG Balance

    </div>


    <div class="nav-actions">

        <a
            href="login.php"
            class="login-link"
        >
            Accedi
        </a>

        <a
            href="registrazione.php"
            class="register-link"
        >
            Registrati
        </a>

    </div>

</header>


<main class="hero">

    <section class="hero-text">

        <div class="badge">
            🌱 Gestione ESG aziendale
        </div>

        <h1>
            Il bilancio aziendale
            incontra la <span>sostenibilità.</span>
        </h1>

        <p class="hero-description">
            ESG Balance è la piattaforma dedicata alla gestione
            dei bilanci aziendali e degli indicatori ambientali,
            sociali e di governance.
        </p>


        <div class="hero-buttons">

            <a
                href="login.php"
                class="primary-btn"
            >
                Accedi alla piattaforma →
            </a>

            <a
                href="registrazione.php"
                class="secondary-btn"
            >
                Crea un account
            </a>

        </div>

    </section>


    <section class="hero-visual">

        <div class="visual-card">

            <div class="visual-header">

                <h2>
                    ESG Balance
                </h2>

                <span class="status">
                    ● ESG
                </span>

            </div>


            <div class="feature">

                <div class="feature-icon">
                    📄
                </div>

                <div>

                    <h3>
                        Bilanci aziendali
                    </h3>

                    <p>
                        Crea e gestisci i dati economici
                        delle aziende.
                    </p>

                </div>

            </div>


            <div class="feature">

                <div class="feature-icon">
                    🌱
                </div>

                <div>

                    <h3>
                        Indicatori ESG
                    </h3>

                    <p>
                        Integra dati ambientali e sociali
                        all'interno dei bilanci.
                    </p>

                </div>

            </div>


            <div class="feature">

                <div class="feature-icon">
                    ✓
                </div>

                <div>

                    <h3>
                        Revisione ESG
                    </h3>

                    <p>
                        I revisori analizzano i bilanci
                        ed esprimono il proprio giudizio.
                    </p>

                </div>

            </div>


            <div class="feature">

                <div class="feature-icon">
                    📊
                </div>

                <div>

                    <h3>
                        Statistiche
                    </h3>

                    <p>
                        Consulta indicatori e informazioni
                        aggregate della piattaforma.
                    </p>

                </div>

            </div>


            <div class="bottom-accent"></div>

        </div>

    </section>

</main>

</body>

</html>