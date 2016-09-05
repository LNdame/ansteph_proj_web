<?php

function send_notification ($token, $message)
{
  $url ='https://fcm.googleapis.com/fcm/send'; //maybe not
  $fields = array('registration_ids' => $token,
                  'data'=>$message );

    $headers = array(
              			'Authorization:key = AIzaSyAeZ17VvsuoL4y3l-pbVPQfzvqk4tuDaDg',
              			'Content-Type: application/json'
              			);


                    $ch = curl_init();
                      curl_setopt($ch, CURLOPT_URL, $url);
                      curl_setopt($ch, CURLOPT_POST, true);
                      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                      curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
                      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                      $result = curl_exec($ch);
                      if ($result === FALSE) {
                          die('Curl failed: ' . curl_error($ch));
                      }
                      curl_close($ch);
                      return $result;


}

 $conn = mysqli_connect( "localhost", "root", "root", "taxi");

$query ="SELECT Token FROM users";
$result = mysqli_query($conn, $query);
$token = array();
 if(mysqli_num_rows($result)>0)
 {
   while ($row = mysqli_fetch_assoc($result)) {
     $token [] =$row["Token"];
   }
 }

 mysqli_close($conn);

 $message = array('message' => "Sleeping test notification" );

 $message_status = send_notification($token,$message);

 echo "$message_status";

 ?>
