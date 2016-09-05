<?php

require_once './../include/DbHandler.php';

    if(isset($_POST['upload']) && $_FILES['userfile']['size'] > 0)
{
	$fileName = $_FILES['userfile']['name'];
	$tmpName  = $_FILES['userfile']['tmp_name'];
	$fileSize = $_FILES['userfile']['size'];
	$fileType = $_FILES['userfile']['type'];
	
	$fp      = fopen($tmpName, 'r');
	$content = fread($fp, filesize($tmpName));
	$content = addslashes($content);
	fclose($fp);
	
	if(!get_magic_quotes_gpc())
	{
	    $fileName = addslashes($fileName);
	}
	
	$image = addslashes($_FILES['userfile']['tmp_name']);
	$name = addslashes($_FILES['userfile']['name']);
	$image = file_get_contents($image);
	$image = base64_decode($image);
	
	
	
	//include 'library/config.php';
	//include 'library/opendb.php';
	$username = "jack";
	$clientID =2;
	
	$db = new DbHandler();
	$db->saveprofile($username, $image, $name, $fileType, $fileSize, $clientID);
	$res = $db->createClientProfile($username, $image, $name, $fileType, $fileSize, $clientID);
	
	//$query = "INSERT INTO upload (name, size, type, content ) ".
	//"VALUES ('$fileName', '$fileSize', '$fileType', '$content')";
	
	//mysql_query($query) or die('Error, query failed'); 
	//include 'library/closedb.php';
	
	echo "<br>File $fileName uploaded<br>";
} 
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">

		<!-- Always force latest IE rendering engine (even in intranet) & Chrome Frame
		Remove this if you use the .htaccess -->
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

		<title>new_file</title>
		<meta name="description" content="">
		<meta name="author" content="Loic Stephan">

		<meta name="viewport" content="width=device-width; initial-scale=1.0">

		<!-- Replace favicon.ico & apple-touch-icon.png in the root of your domain and delete these references -->
		<link rel="shortcut icon" href="/favicon.ico">
		<link rel="apple-touch-icon" href="/apple-touch-icon.png">
	</head>

	<body>
		<div>
			<header>
				<h1>new_file</h1>
			</header>
			<nav>
				<p>
					<a href="/">Home</a>
				</p>
				<p>
					<a href="/contact">Contact</a>
				</p>
			</nav>

			<div>
				<form method="post" enctype="multipart/form-data">
				<table width="350" border="0" cellpadding="1" cellspacing="1" class="box">
				<tr> 
				<td width="246">
				<input type="hidden" name="MAX_FILE_SIZE" value="2000000">
				<input name="userfile" type="file" id="userfile"> 
				</td>
				<td width="80"><input name="upload" type="submit" class="box" id="upload" value=" Upload "></td>
				</tr>
				</table>
				</form>


			</div>

			<footer>
				<p>
					&copy; Copyright  by Loic Stephan
				</p>
			</footer>
		</div>
	</body>
</html>
