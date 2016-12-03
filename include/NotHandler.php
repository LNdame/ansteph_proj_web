<?php
require_once __DIR__ . '/firebase.php';
require_once __DIR__ . '/push.php';

class NotHandler {

  private $conn;
  private  $firebase ;
  private  $push ;

  function __construct() {
    //Getting the DbConnect.php file
      require_once dirname(__FILE__) . '/DbConnect.php';
      //require_once dirname(__FILE__). '/firebase.php';
      //require_once dirname(__FILE__). '/push.php';
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


    public function sendJobResponseNotification($jr_id)
    {
      $firebase = new Firebase();
      $push = new Push();

       // notification title
      $title = "New Job Reponse";

      // notification message
       $message = "A driver responded to your request";

       // push type - single user / topic
       $push_type  ="individual";

       // optional payload
              $payload = array();
              $payload['tag'] = 'BeeCab';
              $payload['score'] = '15.6';

       // whether to include to image or not
        $include_image = FALSE;
        if ($include_image) {
            $push->setImage('http://api.androidhive.info/images/minion.jpg');
        } else {
            $push->setImage('');
        }

        $push->setTitle($title);
        $push->setMessage($message);
        $push->setIsBackground(FALSE);
        $push->setPayload($payload);

        $json = $push->getPush();
        $regId = $this->retrieveClientTokenFromJR($jr_id);

        $response = $firebase->send($regId, $json);
        //echo json_encode($response);

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
      //closing the statement
      $stmt->close();

      if($result){

        return CREATED_SUCCESSFULLY;
      }else{
        return CREATE_FAILED;
      }

    }





    public function retrieveDriverToken($id)
    {
      $stmt = $this->conn->prepare("SELECT `token` FROM `notification_driver` WHERE `taxi_driver_id` = ?");
      $stmt->bind_param("s", $id);
      if ($stmt->execute()) {
      $token = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      return $token["token"];
      } else {
      return NULL;
      }

    }

    public function retrieveClientToken($id)
    {
      $stmt = $this->conn->prepare("SELECT `token` FROM `notification_client` WHERE `taxi_client_id` = ?");
      $stmt->bind_param("s", $id);
      if ($stmt->execute()) {
      $token = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      return $token["token"];
      } else {
      return NULL;
      }
    }


    public function retrieveClientTokenFromJR($id)
    {
      $stmt = $this->conn->prepare("SELECT nc.`token` FROM `notification_client` nc join `journey_request` jr ON nc.`taxi_client_id` = jr.`jr_tc_id` WHERE jr.`id` IN (SELECT jr.`id` FROM `journey_request` jr join `journey_request_response` jre   on jre.`jre_jr_id` = jr.`id` WHERE jre.`jre_jr_id` =?) ");
      $stmt->bind_param("i", $id);

      if ($stmt->execute())
       {
          $token = $stmt->get_result()->fetch_assoc();
          $stmt->close();
          return $token["token"];
        } else {
      return NULL;
      }
    }


  public function send_notification($token, $message) ///being replaced as we type
  {
      $url ='https://fcm.googleapis.com/fcm/send'; //maybe not
      $fields = array('registration_ids' => $token,
                      'data'=>$message );

        $headers = array(
                  			'Authorization:key = AIzaSyDSN_LlBbp4ZXEsDXAu8QbTAnPlBA0N7pY',
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



  public function push_not_driver() //being replaced
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
