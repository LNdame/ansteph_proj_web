<?php
    require_once './../include/DbHandler.php';
	define('DB_USERNAME', 'root');
	define('DB_PASSWORD', 'root');
	define('DB_HOST', 'localhost');
	define('DB_NAME', 'taxi');

	$con = mysqli_connect(DB_HOST,DB_USERNAME,DB_PASSWORD,DB_NAME) or die('Unable to Connect');	
	
	if($_SERVER['REQUEST_METHOD']=='POST')
	{
		$image = $_POST['image'];
		
		$sql = "SELECT id from client_profile ORDER BY id ASC";
		$res = mysqli_query($con, $sql);
		
		$id = 0;
		while ($row = mysqli_fetch_array($res))
		{
			$id = $row['id'];
		}
		
		$path = "image/$id.png";
		$actualpath = "localhost:8888/taxi/$path"; //could be an issue

		$sql= "INSERT INTO client_profile (cp_username, cp_profilepic) values ('JonJon','$actualpath')"		;
		
		if(mysqli_query($con, $sql)){
			file_put_contents($path, base64_decode($image));
			echo "Successfully uploaded";
			mysqli_close($con);
		}else{
			echo "ERROR";
		}
		
	}
	
	
?>