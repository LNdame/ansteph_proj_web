<?php

class DriverDbHandler {

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

       public function isValidTDApiKey($api_key) {
           $stmt = $this->conn->prepare("SELECT id from taxi_driver WHERE api_key = ?");
           $stmt->bind_param("s", $api_key);
           $stmt->execute();
           $stmt->store_result();
           $num_rows = $stmt->num_rows;
           $stmt->close();
           return $num_rows > 0;
       }




	  /* ------------- `taxi_driver` table method ------------------ */
	public function createTaxiDriver($name, $email,$company_name, $carmodel, $numplate='xxx 999 EC', $licence, $year,$password, $mobile, $otp='55555') //++
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

			//crafting the statement id`, `td_name`, `td_company_name`, `td_email`, `td_mobile`, `td_license`, `td_year`, `td_password`, `td_apikey`, `td_status`, `td_created_at`, `company_id`
			$stmt = $this->conn->prepare("INSERT INTO `taxi_driver`(`id`, `td_name`, `td_company_name`, `td_email`, `td_mobile`, `td_license`, `td_year`, `td_password`, `td_apikey`)  VALUES (?,?,?,?,?,?,?,?,?) ");

      //Binding the params
			$stmt->bind_param("sssssssss", $id ,$name, $company_name, $email,  $mobile, $licence, $year,$password_hash,$apikey);

			$result =$stmt->execute();
			//$new_td_id =$stmt->insert_id;
			//closing the statement
			$stmt->close();

			if($result){
				$otp_result = $this->createOtpDriver($id, $otp);
        $this->saveDriverDummyProfile($id, $carmodel, $numplate);
				return USER_CREATED_SUCCESSFULLY;
			}else{
				return USER_CREATE_FAILED;
			}

		}else{

			return USER_ALREADY_EXISTED;
		}
	}


  //create the otp +
  public function createOtpDriver($td_id, $otp)
  {
    //delete the old otp u know y
    $stmt= $this->conn->prepare("DELETE FROM sms_code_driver WHERE sms_td_id = ? ");
    $stmt->bind_param("s", $td_id);
    $stmt->execute();

    $stmt= $this->conn->prepare("INSERT INTO sms_code_driver(sms_code, sms_td_id) values (?,?)");
    $stmt->bind_param("ss",$otp, $td_id);
    $result=$stmt->execute();
    return $result;
  }

  public function retrieveTDUser($mobile, $pwd)
  {
    require_once 'PassHash.php';
    //Encrypting the password
  //  $password_hash = PassHash::hash($pwd);
  //  echo "$password_hash";(`id`, `td_name`, `td_company_name`, `td_email`, `td_mobile`, `td_license`,
//  `td_year`, `td_password`, `td_apikey`, `td_status`, `td_created_at`, `td_propic`, `company_id`)

    $stmt=$this->conn->prepare("SELECT id, td_name, td_company_name, td_email, td_mobile, td_license,
      td_year, td_password,td_apikey, company_id, td_status FROM taxi_driver  WHERE td_mobile = ? ");
    $stmt->bind_param("s",$mobile);

    if(  $stmt->execute()){

        $stmt->bind_result($id, $name,$company_name, $email,  $mobile, $licence, $year, $pwd_hash, $apikey,$company_id,$status);
        $stmt->store_result();

        if($stmt->num_rows>0)
        {
          $stmt->fetch();

          $user =array();
          $user["id"] = $id;
          $user["name"] = $name;
          $user["company_name"] = $company_name;
          $user["email"] = $email;
          $user["mobile"] = $mobile;
          $user["licence"] = $licence;
          $user["year"] = $year;
          $user["password_hash"] = $pwd_hash;
          $user["apikey"] = $apikey;
          $user["company_id"] = $company_id;
          $user["status"] = $status;
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




  public function activateUser($otp)
  {
    $stmt= $this->conn->prepare("SELECT td.id, td.td_name, td.td_email, td.td_mobile, td.td_apikey, td.td_status, td.td_created_at FROM
    taxi_driver td, sms_code_driver sd WHERE sd.sms_code = ? AND sd.sms_td_id = td.id");

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
        //update the referral if any
        $this-> updatereferral($mobile);
        $this-> updatereferral($email);
        //  return user
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

    public function activateUserStatus($td_id)
    {
      $stmt = $this->conn->prepare("UPDATE taxi_driver set td_status = 1 WHERE id = ?");
    $stmt->bind_param("i", $td_id);
    $stmt->execute();

    $stmt = $this->conn->prepare("UPDATE sms_code_driver set sms_status = 1 WHERE sms_td_id = ?");
    $stmt->bind_param("i", $td_id);
    $stmt->execute();

    }


  /* -------------*********************** Profile table method ***********************------------------ */


  public function saveDriverProfileImage( $td_id)
  {
  // the upload folder
    $upload_path ="driverprofileimages/";
    $upload_target =dirname(__FILE__) . '/driverprofileimages/';
    $server_ip = gethostbyname(gethostname());

  //createing the upload url
    $upload_url = 'http://'.$server_ip.':8888/taxi/include/'.$upload_path;

    $response = array();

    //getting file info from the request
    $fileinfo = pathinfo($_FILES['image']['name']);
    //echo "$fileinfo" ;

    $extension = $fileinfo['extension'];
  //  echo "extension".$extension;
    //file url to store in database
    $file_url = $upload_url .$td_id."_". $this->getFileName().'.'.$extension;
 //file path to upload in the server
    $file_path= $upload_target.$td_id."_". $this->getFileName().'.'.$extension;

  //trying to save the file in the directory
  try {
    //saving the file
    move_uploaded_file($_FILES['image']['tmp_name'], $file_path);
    $stmt = $this->conn->prepare("INSERT INTO `driver_profile_image`( `car_picture_url`, `taxi_driver_id` ) VALUES (?,?)");

    $stmt->bind_param("ss",$file_url, $td_id);

    $result =$stmt->execute();
  //  $new_td_id =$stmt->insert_id;

    //closing the statement
    $stmt->close();
    if($result){

      return CREATED_SUCCESSFULLY;
    //  $response['error'] = false;
      //$response['url'] = $file_url;

    }else{
      return CREATE_FAILED;
    }

  } catch (Exception $e) {
    $response['error'] = true;
    $response['message']= $e->getMessage();
  }
 echo json_encode($response);

  }


  public function updatedriverprofileFromEn($td_id,$image,$image_tag)// testing this to used for updating the profile
  {
    // the upload folder
      $upload_path ="driverprofileimages/";
      $upload_target =dirname(__FILE__) . '/driverprofileimages/';
      //$server_ip = gethostbyname(gethostname());
      $server_ip = SERVERNAME;
      //creating the upload url
      $upload_url = 'http://'.$server_ip.'/api/include/'.$upload_path;

      //file url to store in database
      $file_url = $upload_url .$td_id."_".$image_tag."_01.jpg";
      $file_path= $upload_target.$td_id."_".$image_tag."_01.jpg";


      try {

        $stmt = $this->conn->prepare("UPDATE `driver_profile_image` SET `car_picture_url`=? WHERE `taxi_driver_id`= ? and `image_tag`=?");

    //    $stmt = $this->conn->prepare("INSERT INTO client_profile (cp_username, cp_profilepic) values ('JonJon',?)");
        $stmt->bind_param("sss", $file_url,$td_id,$image_tag);

        $result =$stmt->execute();

        $stmt->close();
        if($result){

            file_put_contents($file_path, base64_decode($image));
            echo "Successfully uploaded";
            return UPDATED;

        }else{
          return CREATE_FAILED;
        }

      } catch (Exception $e) {

        $response['error'] = true;
        $response['message']= $e->getMessage();
      }
      echo json_encode($response);


  }






  public function saveDriverDummyProfile( $td_id,$carmodel, $numplate) //being used to save the dummy profile
  {
  // the upload folder
    $upload_path ="DummiesProfilePic/";
    $upload_target =dirname(__FILE__) . '/DummiesProfilePic/';
  //  $server_ip = gethostbyname(gethostname());
    $server_ip = SERVERNAME;
  //createing the upload url
    $upload_url = 'http://'.$server_ip.'/api/include/'.$upload_path;

    $imageArray = array( 'driver'=>$upload_url."driver.jpg" , 'driver2'=> $upload_url."driver2.jpg",'back' => $upload_url."car_back.jpg" );
    $response = array();



  //trying to save the file in the directory
  try {
    //saving the file
        $result=null;

        foreach ($imageArray as $key => $file_url) {

          $stmt = $this->conn->prepare("INSERT INTO `driver_profile_image`( `car_picture_url`, `taxi_driver_id`, `image_tag` ) VALUES (?,?,?)");

          $stmt->bind_param("sss",$file_url, $td_id,$key);

          $result =$stmt->execute();
        //  $new_td_id =$stmt->insert_id;

          //closing the statement
          $stmt->close();
        }

        //saving the dummy profile !!!!!!!!!! this is missing some param
        $stmt = $this->conn->prepare("INSERT INTO `driver_profile`(  `taxi_driver_id`,`car_model`, `car_numberplate` ) VALUES (?,?,?)");

        $stmt->bind_param("sss", $td_id, $carmodel, $numplate);

        $result =$stmt->execute();

        //closing the statement
        $stmt->close();
        //end saving the dummy profile

      if($result){

        return CREATED_SUCCESSFULLY;
      //  $response['error'] = false;
        //$response['url'] = $file_url;

      }else{
        return CREATE_FAILED;
      }

  } catch (Exception $e) {
    $response['error'] = true;
    $response['message']= $e->getMessage();
  }
  echo json_encode($response);

  }





public function getFilename()
{
  $stmt = $this->conn->prepare("SELECT max(id) as id FROM driver_profile_image");

  $stmt->execute();
  $result = $stmt->get_result();
  $stmt->close();

  while ($row = $result->fetch_assoc()) {
    if($row["id"]==null){
      return 1;
    }
    else{
      return ++$row["id"];
    }

  }


}


public function retrieveDriverProfile($tc_id)
{
  $stmt = $this->conn->prepare("SELECT * FROM `driver_profile` WHERE taxi_driver_id = ?  ");
  //binding params
 $stmt->bind_param("s",$tc_id);

  $stmt->execute();
  $images = $stmt->get_result();
  $stmt->close();
  return $images;
}


public function retrieveDriverProfileForClient($tc_id)
{
  $stmt = $this->conn->prepare("SELECT dp.*, td_name,td_mobile,td_email,td_year,td_license FROM `driver_profile` dp JOIN taxi_driver td ON dp.`taxi_driver_id`=td.`id` WHERE dp.`taxi_driver_id` = ?  ");
  //binding params
 $stmt->bind_param("s",$tc_id);

  $stmt->execute();
  $images = $stmt->get_result();
  $stmt->close();
  return $images;
}


public function retrieveDriverImages($td_id)
{
//  echo "$tc_id";
  $stmt = $this->conn->prepare("SELECT * FROM `driver_profile_image` WHERE taxi_driver_id = ? ");
  //binding params
  $stmt->bind_param("s",$td_id);

  $stmt->execute();
  $images = $stmt->get_result();
  $stmt->close();
  return $images;
}

/* ------------- `referral program related method` table method ------------------ */


function createreferral($contact, $td_id)
{
  $stmt =$this->conn->prepare("INSERT INTO `driver_referral`( `ref_provided_contact`,  `taxi_driver_id`) VALUES (?,?)");

  $stmt->bind_param("ss" ,$contact, $td_id);

  $result=$stmt->execute();
  $new_tc_id =$stmt->insert_id;

  //closing the statement
    $stmt->close();
  if($result){

      return USER_CREATED_SUCCESSFULLY;
    }else{
      return USER_CREATE_FAILED;
    }
  }


  public function updatereferral($ref_contact)
  {
      $stmt = $this->conn->prepare("UPDATE driver_referral set ref_status = 1 WHERE ref_provided_contact = ?");
      $stmt->bind_param("s", $ref_contact);
      $stmt->execute();

      $stmt = $this->conn->prepare("UPDATE client_referral set ref_status = 1 WHERE ref_provided_contact = ?");
      $stmt->bind_param("s", $ref_contact);
      $stmt->execute();

  }


  public function retrieveClientreferral($td_id)
  {
  //  echo "$tc_id";
    $stmt = $this->conn->prepare("SELECT * FROM `driver_referral` WHERE `taxi_driver_id` = ? ");
    //binding params
    $stmt->bind_param("s",$td_id);

    $stmt->execute();
    $referral = $stmt->get_result();
    $stmt->close();
    return $referral;
  }







}





?>
