<?php


class NotHandler {

  private $conn;

  function __construct() {
    //Getting the DbConnect.php file
      require_once dirname(__FILE__) . '/DbConnect.php';

      //Creating a DbConnect object to connect to the database
      $db = new DbConnect();

      //Initializing our connection link of this class
      //by calling the method connect of DbConnect class
      $this->conn = $db->connect();
  }





public function dothis()
{
  echo "i did";
}




public function registerClient($token, $mobile, $tc_id)
{

  $stmt = $this->conn->prepare("UPDATE notification_client SET token =? WHERE taxi_client_id =?");
  $stmt->bind_param("ss",$token,  $tc_id);

  $result =$stmt->execute();


  $stmt = $this->conn->prepare("INSERT INTO notification_client(token, mobile, taxi_client_id) VALUES (?,?,?)");
  $stmt->bind_param("sss",$token, $mobile, $tc_id);

  $result =$stmt->execute();
//  $new_td_id =$stmt->insert_id;

  //closing the statement
  $stmt->close();

  if($result){

    return CREATED_SUCCESSFULLY;
  }else{
    return CREATE_FAILED;
  }

}

public function registerDriver($token, $mobile, $td_id)
{
  $stmt = $this->conn->prepare("UPDATE notification_driver SET token=? WHERE taxi_driver_id =?");
  $stmt->bind_param("ss",$token,  $td_id);
  $result =$stmt->execute();


  $stmt = $this->conn->prepare("INSERT INTO notification_driver(token, mobile, taxi_driver_id) VALUES (?,?,?)");
  $stmt->bind_param("sss",$token, $mobile, $td_id);

  $result =$stmt->execute();
//  $new_td_id =$stmt->insert_id;

  //closing the statement
  $stmt->close();

  if($result){

    return CREATED_SUCCESSFULLY;
  }else{
    return CREATE_FAILED;
  }

}



public function send_notification($token, $message)
{
  $url ='https://fcm.googleapis.com/fcm/send'; //maybe not
  $fields = array('registration_ids' => $token,
                  'data'=>$message );

    $headers = array(
              			'Authorization:key = AIzaSyCxHysXyihePSYOgkFMgO8ijBj4s8aevP8',
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



public function push_not_driver()
{

  $stmt = $this->conn->prepare("SELECT token FROM notification_driver");

  $stmt->execute();
  $result  = $stmt->get_result();
  $stmt->close();

  $token = array();
  if(mysqli_num_rows($result)>0)
  {
    while ($row = mysqli_fetch_assoc($result)) {
      $token [] =$row["token"];
    }
  }



  $message = array('message' => "if u send this we good" );

  $message_status = $this->send_notification($token,$message);

  echo "$message_status";
}






}



//$test = new NotHandler();
//$test->push_not_driver();
/*$fr =$test->registerClient("1bailai22aga45678dfddsgh", "tonyni@gmail.com");
echo json_encode($fr ) ;
echo "Done";//json_encode($fr );*/
 ?>
