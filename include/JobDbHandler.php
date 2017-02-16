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


  public function retrieveJobPerID($id)
  {
    $stmt = $this->conn->prepare("SELECT * FROM `journey_request`  WHERE `id` = ? ");
    //binding params
    $stmt->bind_param("s",$id);

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


  public function retrievePendingJobPerCity($td_id)
  {
    $city = $this->getDriverCity($td_id);

    $stmt = $this->conn->prepare("SELECT * FROM `journey_request`  WHERE `jr_city` = ? AND `jr_status` = 0");
    //binding params
    $stmt->bind_param("s",$city);

    $stmt->execute();
    $jobs = $stmt->get_result();
    $stmt->close();
    return $jobs;
  }

  public function getDriverCity($td_id)
  {
    $stmt = $this->conn->prepare("SELECT `current_city` FROM `driver_profile` WHERE `taxi_driver_id` = ?");
    $stmt->bind_param("s", $td_id);
    if ($stmt->execute()) {
      $user_city = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      return $user_city["current_city"];
      } else {
      return NULL;
      }

  }

  public function retrieveAssignedJob($tc_id)
  {
    $stmt = $this->conn->prepare("SELECT * FROM `journey_request`  WHERE `jr_tc_id` = ? AND (`jr_status` = 1 OR `jr_status` = 2)");
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
  $stmt->bind_param("ii",$code, $id);
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



  public function createJourneyRequestReponse($proposedFare, $counterOffer, $callAllowed=1, $jr_id, $td_id)
  {
    $hasFareAccepted =0;
   if ($proposedFare == $counterOffer) {
        $hasFareAccepted =1;
    }else{
        $hasFareAccepted =0;
    }

    //crafting the statement
      $stmt = $this->conn->prepare("INSERT INTO `journey_request_response` (`jre_initial_fare_accepted`, `jre_proposed_fare`, `jre_counter_offer`,
        `jre_call_allowed`, `jre_jr_id`, `jre_td_id`) VALUES (?,?,?,?,?,?) ");

      //Binding the params
      $stmt->bind_param("iiiiis",$hasFareAccepted,$proposedFare,$counterOffer, $callAllowed, $jr_id,$td_id);

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


  public function updateJourneyRequestResponse($id, $code)
    {
    $stmt = $this->conn->prepare("UPDATE `journey_request_response` set `jre_status` = ? WHERE `jre_jr_id` = ?");
    $stmt->bind_param("ii",$code, $id);
    $res=  $stmt->execute();
    $stmt->close();
        if($res){
          return UPDATED;
        }else{
          return CREATE_FAILED;
        }


    }



  public function getJourneyRequestReponse($jr_id)
  {


   $sqlquery = "SELECT * FROM `journey_response2` WHERE JorID = ?" ;
//$sql = "select `jr`.`id` AS `id`,`td`.`id` AS `TaxiID`,`jr`.`jre_jr_id` AS `JorID`,`jr`.`jre_proposed_fare` AS `jre_proposed_fare`,`jr`.`jre_counter_offer` AS `jre_counter_offer`,`td`.`td_name` AS `td_name`,`td`.`td_company_name` AS `td_company_name`,`td`.`td_email` AS `td_email`,`td`.`td_mobile` AS `td_mobile`,`c`.`co_name` AS `co_name`,`dp`.`car_picture_url` AS `car_picture_url`,`dp`.`image_tag` AS `image_tag` from (((`journey_request_response` `jr` join `taxi_driver` `td` on((`jr`.`jre_td_id` = `td`.`id`))) left join `driver_profile_image` `dp` on((`td`.`id` = `dp`.`taxi_driver_id`))) join `company` `c` on((`td`.`company_id` = `c`.`id`))) where ((`dp`.`image_tag` = \'driver2\') or isnull(`dp`.`image_tag`))";


    $stmt = $this->conn->prepare($sqlquery);

      //Binding the params
    $stmt->bind_param("i", $jr_id);

    $stmt->execute();
  //  $stmt->store_result();

    $jobresponses = $stmt->get_result();
    $stmt->close();

    return $jobresponses;




  }


  public function getJourneyRequestReponse2($jr_id)
  {


   $sqlquery = "SELECT * FROM `journey_response2` WHERE JorID = ?" ;
  //$sql = "select `jr`.`id` AS `id`,`td`.`id` AS `TaxiID`,`jr`.`jre_jr_id` AS `JorID`,`jr`.`jre_proposed_fare` AS `jre_proposed_fare`,`jr`.`jre_counter_offer` AS `jre_counter_offer`,`td`.`td_name` AS `td_name`,`td`.`td_company_name` AS `td_company_name`,`td`.`td_email` AS `td_email`,`td`.`td_mobile` AS `td_mobile`,`c`.`co_name` AS `co_name`,`dp`.`car_picture_url` AS `car_picture_url`,`dp`.`image_tag` AS `image_tag` from (((`journey_request_response` `jr` join `taxi_driver` `td` on((`jr`.`jre_td_id` = `td`.`id`))) left join `driver_profile_image` `dp` on((`td`.`id` = `dp`.`taxi_driver_id`))) join `company` `c` on((`td`.`company_id` = `c`.`id`))) where ((`dp`.`image_tag` = \'driver2\') or isnull(`dp`.`image_tag`))";


    $stmt = $this->conn->prepare($sqlquery);

      //Binding the params
    $stmt->bind_param("i", $jr_id);

    $stmt->execute();
  //  $stmt->store_result();

    $jobresponses = $stmt->get_result();
    $stmt->close();

    return $jobresponses;


  }



public function createAcceptedRequest($pickupAddr, $destAddr, $pickupCoord, $destCoord, $acceptedFare ,$city,$jr_id,$tc_id,$td_id)

{

  //crafting the statement
    $stmt = $this->conn->prepare("INSERT INTO `accepted_request`( `ar_pickup_add`, `ar_destination_add`,
   `ar_pickup_coord`, `ar_destination_coord`, `ar_final_fare` ,`ar_status` , `ar_city`,`ar_jr_id`, `ar_tc_id`, `ar_td_id`) VALUES (?,?,?,?,?,1,?,?,?,?) ");

    //Binding the params
    $stmt->bind_param("ssssisiss",$pickupAddr, $destAddr, $pickupCoord, $destCoord, $acceptedFare ,$city,$jr_id,$tc_id,$td_id);

    $result =$stmt->execute();
    $new_td_id =$stmt->insert_id;

    //closing the statement
    $stmt->close();

    if($result){
$this->updateJourneyRequestResponse($jr_id, 1);

      return CREATED_SUCCESSFULLY;

    }else{
      return CREATE_FAILED;
    }


}

  public function updateAcceptedRequest($id,$code)
  {
    $stmt = $this->conn->prepare("UPDATE accepted_request set ar_status = ? WHERE ar_jr_id = ?");
  $stmt->bind_param("ii",$code, $id);
  $res= $stmt->execute();
    if($res){
      //update also the journey_request
      return UPDATED;
    }else{
      return CREATE_FAILED;
    }
  }



  public function retrievePendingResponseJob($td_id)
  {
    $stmt = $this->conn->prepare("SELECT jre.`id`, `jr_pickup_add`, `jr_destination_add`, `jr_pickup_time`, `jr_proposed_fare`, `jr_call_allowed`, `jr_pickup_coord`, `jr_destination_coord`, `jr_tc_id`, `jr_shared`,`jr_status`, `jre_status`,`jre_td_id`, `jr_city`, `jr_time_created`,`jre_counter_offer`
      FROM `journey_request` jr JOIN `journey_request_response` jre ON jre.`jre_jr_id` = jr.`id` WHERE jre.`jre_td_id`  = ? AND jre.`jre_status` = 0 AND jr.`jr_status` =0");
    //binding params
    $stmt->bind_param("s",$td_id);

    $stmt->execute();
    $jobs = $stmt->get_result();
    $stmt->close();
    return $jobs;
  }





  public function retrieveAcceptedResponseJob($td_id)
  {
    $stmt = $this->conn->prepare("SELECT ar.*, jr_pickup_time , jr_proposed_fare FROM `accepted_request` ar join `journey_request`jr ON ar.`ar_jr_id` = jr.`id` WHERE `ar_td_id` = ? AND (`ar_status` = 1 OR `ar_status` = 2)");
    //binding params
    $stmt->bind_param("s",$td_id);

    $stmt->execute();
    $jobs = $stmt->get_result();
    $stmt->close();
    return $jobs;
  }


}

?>
