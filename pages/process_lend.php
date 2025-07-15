<?php
require_once '../config/config.php';

// Database connection
$conn = new mysqli("localhost", "root", "", "gate_management_system");

// Check connection
if ($conn->connect_error) {
    die("Error of database Connection: " . $conn->connect_error);
}
if(isset($_GET['accept']))
{
    $id=$_GET['id'];
    $sql="UPDATE computer_lending set status='Accepted' where id=$id";
    $conn->query($sql);
     header("Location: Approve.php");
}
if(isset($_GET['reject']))
{
    $id=$_GET['id'];
    $sql="UPDATE computer_lending set status='Rejected' where id=$id";
    $conn->query($sql);
     header("Location: Approve.php");
}
?>