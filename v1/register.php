<?php
if(isset($_POST["Token"]))
{

  $token =$_POST["Token"];
   $conn = mysqli_connect( "localhost", "root", "root", "taxi");

   $query = "INSERT INTO users (Token) values('$token') ON DUPLICATE KEY UPDATE Token ='$token';";

   mysqli_query($conn, $query);

mysqli_close($conn);

}

 ?>
