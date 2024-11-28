<!-- index.php -->

<?php
include "dbConnection.php";
?>

<form action="register.php" method="POST">
    <input type="text" name="name">
    <br>
    <input type="text" name="email">
    <br>
    
    <input type="password" name="password">
    <br>
    
    <input type="text" name="phone_number">

    <input type="submit">
</form>