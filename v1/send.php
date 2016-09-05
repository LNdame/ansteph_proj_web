<?php
require_once './../include/DbHandler.php';
  const DEFAULT_URL = '';

  if($_SERVER['REQUEST_METHOD']=='POST'){
    $db = new DbHandler();


    //Importing firebase libraries
     require_once 'firebaseInterface.php';
     require_once 'firebaseLib.php';
     require_once 'firebaseStub.php';

     //Geting email and message from the request
 $email = $_POST['email'];
 $msg = $_POST['message'];

  $res =$db->getoneEmail($email);
  $mail = $res->fetch_assoc();

  $uniqueid= $mail["firebaseid"];
  echo "$uniqueid";

  //creating a firebase variable
 $firebase = new \Firebase\FirebaseLib(FIREBASEURL,'');

 //changing the msg of the selected person on firebase with the message we want to send
 $firebase->set($uniqueid.'/msg', $msg);

 //redirecting back to the sendnotification page
 header('Location: sendPushNotification.php?success');

  }else{
    header('Location: sendPushNotification.php');
  }

 ?>
