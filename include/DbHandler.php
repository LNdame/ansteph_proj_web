<?php

class DbHandler {

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
       public function isValidTCApiKey($api_key) {
           $stmt = $this->conn->prepare("SELECT id from taxi_client WHERE api_key = ?");
           $stmt->bind_param("s", $api_key);
           $stmt->execute();
           $stmt->store_result();
           $num_rows = $stmt->num_rows;
           $stmt->close();
           return $num_rows > 0;
       }


       public function isValidTDApiKey($api_key) {
           $stmt = $this->conn->prepare("SELECT id from taxi_driver WHERE api_key = ?");
           $stmt->bind_param("s", $api_key);
           $stmt->execute();
           $stmt->store_result();
           $num_rows = $stmt->num_rows;
           $stmt->close();
           return $num_rows > 0;
       }


    /* ------------- `taxi_client` table method ------------------ */
	public function createTaxiClient($name, $email, $mobile, $password, $otp='55555') //++
	{
		 require_once 'PassHash.php';

		if(!$this->isTaxiClientExists($mobile))
		{
			//Encrypting the password
            $password_hash = PassHash::hash($password);
    //Generating the id
      $id = $this->createTaxiID("tc");


			//Generating an API Key
			$apikey=$this->generateApiKey();

			//crafting the statement
			$stmt = $this->conn->prepare("INSERT INTO taxi_client(id,tc_name, tc_email, tc_mobile, tc_password,tc_apikey) values (?,?,?,?,?,?) ");
			//Binding the params
			$stmt->bind_param("ssssss",$id,$name, $email, $mobile,$password_hash, $apikey);

			$result =$stmt->execute();
			//$new_tc_id =$stmt->insert_id;
			//echo $new_tc_id;
			//closing the statement
			$stmt->close();

			if($result){
				$otp_result = $this->createOtpClient($id, $otp);
				return USER_CREATED_SUCCESSFULLY;
			}else{
				return USER_CREATE_FAILED;
			}

		}else{

			return USER_ALREADY_EXISTED;
		}
	}

	//create the otp +
	public function createOtpClient($tc_id, $otp)
	{
		//delete the old otp u know y
		$stmt= $this->conn->prepare("DELETE FROM sms_code_client WHERE sms_tc_id = ? ");
		$stmt->bind_param("i", $tc_id);
		$stmt->execute();

		$stmt= $this->conn->prepare("INSERT INTO sms_code_client(sms_code, sms_tc_id) values (?,?)");
		$stmt->bind_param("ss",$otp, $tc_id);
		$result=$stmt->execute();
		return $result;
	}


	//Checking whether a taxi client exist
	public function isTaxiClientExists($mobile)
	{
		$stmt=$this->conn->prepare("SELECT id from taxi_client WHERE tc_mobile = ?");
		$stmt->bind_param("s",$mobile);
		$stmt->execute();
		$stmt->store_result();
		$num_rows =$stmt->num_rows;
		$stmt->close();
		return $num_rows>0;
	}

  //Retrieve client
  public function retrieveTCUser($mobile, $pwd)
  {
    require_once 'PassHash.php';
    //Encrypting the password
  //  $password_hash = PassHash::hash($pwd);
  //  echo "$password_hash";

    $stmt=$this->conn->prepare("SELECT id, tc_name, tc_email, tc_mobile,tc_password,tc_apikey
      FROM taxi_client  WHERE tc_mobile = ? ");
    $stmt->bind_param("s",$mobile);

    if(  $stmt->execute()){

        $stmt->bind_result($id, $name, $email, $mobile,$pwd_hash, $apikey);
        $stmt->store_result();

        if($stmt->num_rows>0)
        {
          $stmt->fetch();

          $user =array();
          $user["id"] = $id;
  				$user["name"] = $name;
          $user["email"] = $email;
          $user["mobile"] = $mobile;
          $user["password_hash"] = $pwd_hash;
          $user["apikey"] = $apikey;

  				$stmt->close();

          $isPwdCheck = PassHash::check_password($pwd_hash,$pwd);
          if($isPwdCheck)
          {
            return $user;
          }else{
            return NULL;
          }


        }else{
          return NULL;
        }

  }else{return NULL;}



  }



	public function activateUser($otp)
	{
		$stmt= $this->conn->prepare("SELECT tc.id, tc.tc_name, tc.tc_email, tc.tc_mobile,tc.tc_apikey, tc.tc_status, tc.tc_created_at FROM
		taxi_client tc, sms_code_client sc WHERE sc.sms_code = ? AND sc.sms_tc_id = tc.id");

		$stmt->bind_param("s",$otp);

		if($stmt->execute()){
			$stmt->bind_result($id, $name, $email, $mobile, $apikey, $status, $created_at);

			$stmt->store_result();

			if($stmt->num_rows > 0)
			{
				$stmt->fetch();
				//activate the user
				//echo $id;
				$this->activateUserStatus($id);

				$user =array();
				$user["name"] = $name;
                $user["email"] = $email;
                $user["mobile"] = $mobile;
                $user["apikey"] = $apikey;
                $user["status"] = $status;
                $user["created_at"] = $created_at;

				$stmt->close();

				return $user;

			}else{
				return NULL;
			}
		}else {
			return NULL;
		}

		return $result;
	}

    public function activateUserStatus($tc_id)
    {
    	$stmt = $this->conn->prepare("UPDATE taxi_client set tc_status = 1 WHERE id = ?");
		$stmt->bind_param("i", $tc_id);
		$stmt->execute();

		$stmt = $this->conn->prepare("UPDATE sms_code_client set sms_status = 1 WHERE sms_tc_id = ?");
		$stmt->bind_param("i", $tc_id);
		$stmt->execute();

    }



	  /* ------------- `taxi_driver` table method ------------------ */
	public function createTaxiDriver($name, $email,$company_name, $carmodel, $numplate='xxx 999 EC', $license, $year,$password, $mobile, $otp='55555') //++
	{
     require_once 'PassHash.php';
		if(!$this->isTaxiDriverExists($mobile))
		{
			//Encrypting the password
      $password_hash = PassHash::hash($password);
      //Generating the id
        $id = $this->createTaxiID("td");
			//Generating an API Key
			$apikey=$this->generateApiKey();

			//crafting the statement
			$stmt = $this->conn->prepare("INSERT INTO taxi_driver(id,td_name, td_company_name, td_email, td_mobile, td_carmodel, td_numplate, td_license, td_year,td_password,td_apikey) values (?,?,?,?,?,?,?,?,?,?,?) ");

      //Binding the params
			$stmt->bind_param("sssssssssss",$id ,$name, $company_name, $email,  $mobile,$carmodel, $numplate, $license, $year,$password_hash,$apikey);

			$result =$stmt->execute();
			//$new_td_id =$stmt->insert_id;
			//closing the statement
			$stmt->close();

			if($result){
				$otp_result = $this->createOtpDriver($id, $otp);
				return USER_CREATED_SUCCESSFULLY;
			}else{
				return USER_CREATE_FAILED;
			}

		}else{

			return USER_ALREADY_EXISTED;
		}
	}


  public function retrieveTDUser($mobile, $pwd)
  {
    require_once 'PassHash.php';
    //Encrypting the password
  //  $password_hash = PassHash::hash($pwd);
  //  echo "$password_hash";

    $stmt=$this->conn->prepare("SELECT id, td_name, td_email, td_mobile,td_password,td_apikey
      FROM taxi_driver  WHERE td_mobile = ? ");
    $stmt->bind_param("s",$mobile);

    if(  $stmt->execute()){

        $stmt->bind_result($id, $name, $email, $mobile,$pwd_hash, $apikey);
        $stmt->store_result();

        if($stmt->num_rows>0)
        {
          $stmt->fetch();

          $user =array();
          $user["id"] = $id;
          $user["name"] = $name;
          $user["email"] = $email;
          $user["mobile"] = $mobile;
          $user["password_hash"] = $pwd_hash;
          $user["apikey"] = $apikey;

          $stmt->close();

          $isPwdCheck = PassHash::check_password($pwd_hash,$pwd);
          if($isPwdCheck)
          {
            return $user;
          }else{
            return NULL;
          }


        }else{
          return NULL;
        }

  }else{return NULL;}



  }














	function saveprofile($username, $image,$filename,$type, $size,$clientID)
	{
		$con =mysql_connect("localhost","root","root");
		mysql_select_db("taxi",$con);
		$query = "INSERT INTO client_profile(cp_username, type, size,cp_profilepic, tc_cp_id) values('$username','$type','$size','$image','$clientID')";

		$res =mysql_query($query,$con);

		if($res)
		{
			echo "inserted";
		}else{
			echo "not inserted";
		}

		mysql_close($con);
	}


	function createClientProfile($username, $image,$filename,$type, $size,$clientID)
	{
		$stmt =$this->conn->prepare("INSERT INTO client_profile(cp_username, type, size,cp_profilepic, tc_cp_id) values(?,?,?,?,?)");
		//$stmt =$this->conn->prepare("INSERT INTO client_profile(cp_username, type, size, tc_cp_id) values(?,?,?,?)");
		//Binding the params
		$null = NULL;
		//$stmt->bind_param("ssibi" ,$username,$type,$size,$null,$clientID);
		$stmt->bind_param("ssibi" ,$username,$type,$size,$image,$clientID);
	//	$stmt->bind_param("ssii" ,$username,$type,$size,$clientID);
		//$stmt->send_long_data(0, file_get_contents($filename));
		$result=$stmt->execute();
		$new_tc_id =$stmt->insert_id;

		//closing the statement
			$stmt->close();
		if($result){
				echo "inserted";
				return USER_CREATED_SUCCESSFULLY;
			}else{
				return USER_CREATE_FAILED;
			}
	}

	//Checking whether a taxi client exist
	public function isTaxiDriverExists($mobile)
	{
		$stmt=$this->conn->prepare("SELECT id from taxi_driver WHERE td_mobile = ?");
		$stmt->bind_param("s",$mobile);
		$stmt->execute();
		$stmt->store_result();
		$num_rows =$stmt->num_rows;
		$stmt->close();
		return $num_rows>0;
	}

   public function getAllNames() {
        $stmt = $this->conn->prepare("SELECT name, company, zip FROM live_table ORDER BY name ASC");

        $stmt->execute();
        $names = $stmt->get_result();
        $stmt->close();
        return $names;
    }

    //create the otp +
    public function createOtpDriver($td_id, $otp)
    {
      //delete the old otp u know y
      $stmt= $this->conn->prepare("DELETE FROM sms_code_driver WHERE sms_td_id = ? ");
      $stmt->bind_param("i", $td_id);
      $stmt->execute();

      $stmt= $this->conn->prepare("INSERT INTO sms_code_driver(sms_code, sms_td_id) values (?,?)");
      $stmt->bind_param("ss",$otp, $td_id);
      $result=$stmt->execute();
      return $result;
    }

  /* ------------- `journey_request` table method ------------------ */

  public function createJourneyRequest($pickupAddr, $destAddr, $pickupTime, $proposedFare, $callAllowed=1, $pickupCoord, $destCoord,$tc_id, $shared=1)
  {
    //crafting the statement
      $stmt = $this->conn->prepare("INSERT INTO `journey_request`( `jr_pickup_add`, `jr_destination_add`, `jr_pickup_time`,
    `jr_proposed_fare`, `jr_call_allowed`, `jr_pickup_coord`, `jr_destination_coord`, `jr_tc_id`, `jr_shared`) VALUES (?,?,?,?,?,?,?,?,?) ");

      //Binding the params
      $stmt->bind_param("sssiisssi",$pickupAddr, $destAddr, $pickupTime, $proposedFare, $callAllowed, $pickupCoord, $destCoord,$tc_id, $shared);

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
