<?php

$response = array();

 include_once dirname(__FILE__) . '/Config.php';

//include 'connection.inc.php';
$conn = mysql_connect(DB_HOST, DB_USERNAME, DB_PASSWORD);
//a3022907_health
if(! $conn )
{
  die('Could not connect: ' . mysql_error());
}
mysql_select_db(DB_NAME); //database
// prepare the SQL query
$sql = 'SELECT * FROM live_table';
// submit the query and capture the result
$result =mysql_query($sql, $conn) ;
//$result =$conn->query($sql) or die(mysqli_error());
// find out how many records were retrieved
$numRows= mysql_num_rows($result);

if ($numRows > 0) {
    // looping through all results
    // products node
    echo "$numRows";
    $response["live"] = array();
	$i=0;
	$response["succes"] =1;
	while($i<$numRows)
		{
				//echo "$i";
			//temp array			
				$live = array();
				
				$live["id"]=mysql_result($result, $i,"id");
			
				$live["name"] =mysql_result($result, $i,"name");
				// $name =	$live["name"] ;			
				//	echo "$name";
				$live["company"] =mysql_result($result, $i,"company");
                $live["zip"] =mysql_result($result, $i,"zip");
				 $live["city"] =mysql_result($result, $i,"city");			
				
			//	print_r($live);
				//push single array into final response aray
				array_push($response["live"],$live);
				$i++;
		}
		//print_r($response);
		echo json_encode($response);
		echo "$a";
	}else{
		//no live found 
		$response["success"]= 0;
		$response["message"]= "No live found.";
					
		//echoing the JSON
            echo json_encode($response);
	}
?>