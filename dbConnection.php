<?php

$dsn = "mysql";
$user = "";
$Pwd='';
$option = array (
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8"
);
try{
    $con = new PDO($dsn, $user, $Pwd, $option);

    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

}
catch (PDOException $ex)
{
    echo $ex->getMessage();
}

