<?php

// Add your own database Connection
$dsn = "mysql:host=db10551.public.databaseasp.net;dbname=db10551;";
$user = "db10551";
$Pwd = 'w#2L3tZ!k=4C';
$option = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8"
);
try {
    $con = new PDO($dsn, $user, $Pwd, $option);

    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $ex) {
    echo $ex->getMessage();
}