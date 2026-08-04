<?php

$host = "localhost";
$porta = 8889;
$utente = "root";
$password = "root";
$database = "esg_balance";

$connessione = new mysqli(
    $host,
    $utente,
    $password,
    $database,
    $porta
);

if ($connessione->connect_error) {
    die("Connessione al database fallita: " . $connessione->connect_error);
}

$connessione->set_charset("utf8mb4");