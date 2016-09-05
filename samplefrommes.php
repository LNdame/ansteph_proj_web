<?php
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'root');
define('DB_HOST', 'localhost');
define('DB_NAME', 'HydrowatcherDBModel');

$con = mysqli_connect(DB_HOST,DB_USERNAME,DB_PASSWORD,DB_NAME) or die('Unable to Connect');
$sql = "SELECT * FROM `Measurement `";
$res = mysqli_query($con, $sql);
$response = array();
$response["Measurement"]= array();

$TimeStr= gmdate() ;// "2012-01-01 12:00:00";
$TimeZoneNameFrom="UTC";
$TimeZoneNameTo="Africa/Johannesburg";
echo date_create($TimeStr, new DateTimeZone($TimeZoneNameFrom)) -> setTimezone(new DateTimeZone($TimeZoneNameTo))->format("Y-m-d H:i:s");

//INsert into Measurement_temp (id, value, timestamp) values ('1','26', )

while ($row = mysqli_fetch_array($res))
{

  $tmp = array();
  $tmp["Me_ID"] = $row["Me_ID"];
  $tmp["Me_Value"] = $row["Me_Value"];

  $tmp["Me_ST_ID"] = $row["Me_ST_ID"];
  $tmp["Me_Timestamp"] = $row["Me_Timestamp"];
  $tmp["Me_Se_ID"] = $row["Me_Se_ID"];

  array_push($response["Measurement"], $tmp);

}
mysqli_close($con);
$response["error"] = false;
echo json_encode($response );

?>
