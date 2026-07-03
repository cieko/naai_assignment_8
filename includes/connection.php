<?php

function connection()
{
    $host = "localhost";
    $username = "root";
    $password = "";
    $db = "medanalytics";

    // Connect to phpadmin mysql server
    $serverconnection = mysqli_connect(
        $host,
        $username,
        $password
    );

    if (!$serverconnection) {
        die("Unable to connect to MySQL server");
    }

    // create database query
    $createDBquery = "CREATE DATABASE IF NOT EXISTS `$db`";
    mysqli_query($serverconnection, $createDBquery);

    mysqli_close($serverconnection);

    // the connection
    $conn = mysqli_connect(
        $host,
        $username,
        $password,
        $db
    );

    if (!$conn) {
        die("Unable to connect to Database.");
    }

    mysqli_set_charset($conn, "utf8mb4");

    initialiseDatabaseTable($conn);

    return $conn;
}