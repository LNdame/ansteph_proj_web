<?php
   require_once './../include/DbHandler.php';
	define('DB_USERNAME', 'root');
	define('DB_PASSWORD', 'root');
	define('DB_HOST', 'localhost');
	define('DB_NAME', 'taxi');

	$con = mysqli_connect(DB_HOST,DB_USERNAME,DB_PASSWORD,DB_NAME) or die('Unable to Connect');	
	
	$sql="select cp_profilepic from client_profile";
	
	$res = mysqli_query($con,$sql);
 
	$result = array();
	
	while($row = mysqli_fetch_array($res)){
	 array_push($result,array('url'=>$row['image']));
 	}
 
 echo json_encode(array("result"=>$result));
 
 mysqli_close($con);
	
?>