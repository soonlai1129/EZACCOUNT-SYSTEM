<?php 
//only php command
$servername = "localhost";
$username = "root";
$password = "";

//create connection to the MYSQL Server
$conn = mysqli_connect($servername,$username,$password);

$sqldb = "create database ezaccount";//sql command
$resultdb = $conn -> query($sqldb);//execute sql command

if ($resultdb){
    echo "The database is successfully created!";
}else{
    echo "Error! Cannot create database";
}

?>
