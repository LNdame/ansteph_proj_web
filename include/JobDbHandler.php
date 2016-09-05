<?php

class JobDbHandler {

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


    private function generateApiKey()
  {
    return md5(uniqid(rand(), true));
  }

  public function createTaxiID($prefix)
  {
    //the date middle
    $str = date("ymd");
    //the randoms
    $rand= rand(10000, 99999);

    //the full id
    return $prefix.$str.$rand;
  }

  /**
      * Fetching user api key
      * @param String $user_id user id primary key in user table
      */
     public function getApiKeyById($user_id) {
         $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
         $stmt->bind_param("i", $user_id);
         if ($stmt->execute()) {
             $api_key = $stmt->get_result()->fetch_assoc();
             $stmt->close();
             return $api_key;
         } else {
             return NULL;
         }
     }

     /**
      * Fetching user id by api key
      * @param String $api_key user api key
      */
     public function getUserId($api_key) {
         $stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
         $stmt->bind_param("s", $api_key);
         if ($stmt->execute()) {
             $user_id = $stmt->get_result()->fetch_assoc();
             $stmt->close();
             return $user_id;
         } else {
             return NULL;
         }
     }

     /**
        * Validating user api key
        * If the api key is there in db, it is a valid key
        * @param String $api_key user api key
        * @return boolean
        */



  /* ------------- `journey_request` table method ------------------ */

  public function createJourneyRequest($pickupAddr, $destAddr, $pickupTime, $proposedFare, $callAllowed=1, $pickupCoord, $destCoord,$tc_id, $shared=0,$city)
  {
    //crafting the statement
      $stmt = $this->conn->prepare("INSERT INTO `journey_request`( `jr_pickup_add`, `jr_destination_add`, `jr_pickup_time`,
    `jr_proposed_fare`, `jr_call_allowed`, `jr_pickup_coord`, `jr_destination_coord`, `jr_tc_id`, `jr_shared`,`jr_city`) VALUES (?,?,?,?,?,?,?,?,?,?) ");

      //Binding the params
      $stmt->bind_param("sssiisssis",$pickupAddr, $destAddr, $pickupTime, $proposedFare, $callAllowed, $pickupCoord, $destCoord,$tc_id, $shared,$city);

      $result =$stmt->execute();
      $new_td_id =$stmt->insert_id;

      //closing the statement
      $stmt->close();

      if($result){

        return CREATED_SUCCESSFULLY;
      }else{
        return CREATE_FAILED;
      }


  }

  public function retrievePendingJob($tc_id)
  {
    $stmt = $this->conn->prepare("SELECT * FROM `journey_request`  WHERE `jr_tc_id` = ? AND `jr_status` = 0");
    //binding params
    $stmt->bind_param("s",$tc_id);

    $stmt->execute();
    $jobs = $stmt->get_result();
    $stmt->close();
    return $jobs;
  }


  public function retrieveAllPendingJob()
  {
    $stmt = $this->conn->prepare("SELECT * FROM `journey_request`  WHERE `jr_status` = 0");
    //binding params
  //  $stmt->bind_param("s",$tc_id);

    $stmt->execute();
    $jobs = $stmt->get_result();
    $stmt->close();
    return $jobs;
  }


  public function retrievePendingJobPerCity($city)
  {
    $stmt = $this->conn->prepare("SELECT * FROM `journey_request`  WHERE `jr_tc_id` = ? AND `jr_status` = 0");
    //binding params
    $stmt->bind_param("s",$tc_id);

    $stmt->execute();
    $jobs = $stmt->get_result();
    $stmt->close();
    return $jobs;
  }


  public function retrieveAssignedJob($tc_id)
  {
    $stmt = $this->conn->prepare("SELECT * FROM `journey_request`  WHERE `jr_tc_id` = ? AND `jr_status` = 1");
    //binding params
    $stmt->bind_param("s",$tc_id);

    $stmt->execute();
    $jobs = $stmt->get_result();
    $stmt->close();
    return $jobs;
  }

  public function updateJourneyRequest($id, $code)
  {
  $stmt = $this->conn->prepare("UPDATE `journey_request` set `jr_status` = ? WHERE `id` = ?");
  $stmt->bind_param("is",$code, $id);
  $res=  $stmt->execute();
  $stmt->close();
      if($res){
        return UPDATED;
      }else{
        return CREATE_FAILED;
      }


  }

public function getEmail()
{
  $stmt = $this->conn->prepare("SELECT * FROM FBRegister");

  $stmt->execute();
  $emails = $stmt->get_result();
  $stmt->close();
  return $emails;
}

public function getoneEmail($email)
{
  $stmt = $this->conn->prepare("SELECT * FROM FBRegister WHERE email = ?");
$stmt->bind_param("s",$email);
  $stmt->execute();
  $emails = $stmt->get_result();
  $stmt->close();
  return $emails;
}

  public function fbRegister($fbid, $email)
  {
    $stmt = $this->conn->prepare("INSERT INTO `FBRegister`(`firebaseid`, `email`) VALUES (?,?)");
    $stmt->bind_param("ss",$fbid, $email);

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









}





/*$test = new DbHandler();
$fr =$test->fbRegister("122aga45678dfddsgh", "bely@gmail.com");
echo json_encode($fr ) ;
echo "done here";*/



//$fr = $test->retrievePendingJob("tcmaster");
//$fr=$test->createJourneyRequest('1 Sandler street','1 Sandler street','9:00','45','0','-33.9753733,25.604615,21','-33.9753733,25.604615,21','tcmaster', '0');
//$user = $test->retrieveUser("01123581321","wewillrocku");
//$test->createTaxiDriver('Tan','xanya@gt.io','Company','Opel','xxx 999 EC','CDfgr89','2005','+27787665613', '22222');$mobile, $pwd
//$name, $email,$company_name, $carmodel, $numplate='xxx 999 EC', $license, $year, $mobile, $otp='55555'
//$user =$test->activateUser(11111);
//$test->activateUserStatus(17);
////echo "done here";

?>
