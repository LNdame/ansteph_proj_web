<?php
require_once 'PassHash.php';

echo "The time is " . date("h:i:sa");
echo "The time is " . date("Y");
echo "<br/>";
echo "The time is " . date("Y-m-d h:i:sa");
echo "<br/>";
echo "The time is " . date("ymd");
echo "<br/>";
$str = date("ymd");
echo "$str";
echo "<br/>";
$rand= rand(10000, 99999);
$str="td".$str.$rand;
echo "$str";
echo "<br/>";

echo "".createTaxiID("tc");


$server_ip = gethostbyname(gethostname());
echo "server";
echo "<br/>";
echo "$server_ip";


$password_hash = PassHash::hash("wewillrocku");


echo "<br/>";
echo "$password_hash";

$apikey=generateApiKey();

echo "<br/>";
echo "$apikey";

function createTaxiID($prefix)
{
  //the date middle
  $str = date("ymd");
  //the randoms
  $rand= rand(10000, 99999);

  //the full id
  return $prefix.$str.$rand;
}

function generateApiKey()
{
return md5(uniqid(rand(), true));
}


 ?>
