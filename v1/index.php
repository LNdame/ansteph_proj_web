<?php
/**
 * Step 1: Require the Slim Framework
 *
 * If you are not using Composer, you need to require the
 * Slim Framework and register its PSR-0 autoloader.
 *
 * If you are using Composer, you can skip this step.
 */
 require_once './../include/DbHandler.php';
 require_once './../include/DriverDbHandler.php';
 require_once './../include/JobDbHandler.php';
 require_once './../include/ClientDbHandler.php';
 require_once './../include/NotHandler.php';
 require_once './../include/send_sms.php';
require './../libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

/**
 * Step 2: Instantiate a Slim application
 *
 * This example instantiates a Slim application using
 * its default settings. However, you will usually configure
 * your Slim application now by passing an associative array
 * of setting names and values into the application constructor.
 */
$app = new \Slim\Slim();

/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, `Slim::patch`, and `Slim::delete`
 * is an anonymous function.
 */
/**
 * ----------- USEFUL METHODS ---------------------------------
 */
function validateEmail($email)
{
	$app = \Slim\Slim::getInstance();
	if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
		 $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoResponse(400, $response);
        $app->stop();
	}
}


function authenticate (\Slim\Route $route)
{
  //Getting request headers
  $headers = apache_request_headers();
  $response = array();
  $app = \Slim\Slim::getInstance();

  //Verifying Authorization headers
  if(isset($headers['Authorization'])){
    $db = new DbHandler();

    //get the api key
    $api_key =$headers['Authorization'];
  //check client
    $isClient =$db->isValidTCApiKey();
    $isDriver=$db->isValidTDApiKey();
    if(!$isClient || !$isDriver)
    {
      // api key is not present in users table
      $response["error"] = true;
      $response["message"] = "Access Denied. Invalid Api Key";
      echoResponse(401, $response);
      $app->stop();
    }


  }else{
    //api key is missing in header
    $response["error"] = true;
    $response["message"]= "Api key is missing";
    echoResponse(400, $response);
    $app->stop();
  }




}



/**
 * Echoing json response to client
* array_values() removes the original keys and replaces
 * with plain consecutive numbers
 *$out = array_values($array);
 *json_encode($out);

 * @param String $status_code Http response code
 * @param Int $response Json response
 */

function echoResponse($status_code, $response)
{
	 $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');
    //$out = array_values($response);
    echo json_encode($response);
}

/**
 * Verifying required params posted or not
 */
 function verifyRequiredParams($required_fields){
 	$error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(400, $response);
        $app->stop();
    }
 }

// POST route
$app->post(
    '/post',
    function () {
        echo 'This is a POST route';
    }
);

$app->get('/get',
    function () {
    echo 'This is a get route';
  }
);
// PUT route
$app->put(
    '/put',
    function () {
        echo 'This is a PUT route';
    }
);

// PATCH route
$app->patch('/patch', function () {
    echo 'This is a PATCH route';
});

// DELETE route
$app->delete(
    '/delete',
    function () {
        echo 'This is a DELETE route';
    }
);

/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 */

  /* -------------************************** `Client realted routes**************************------------------ */

$app->post(
    '/register_client',
    function () use ($app){
       //check the params
       verifyRequiredParams(array('name','email','mobile','password'));

	   $response = array();
	   //read the param
	   $name = $app->request->post('name');
	   $email = $app->request->post('email');
	   $mobile = $app->request->post('mobile');
	   $password = $app->request->post('password');

	   $otp = rand(10000, 99999);
	   //$otp = 11111;
	   //validating email address
	   validateEmail($email);
	   $db = new ClientDbHandler();

	   $res =$db->createTaxiClient($name, $email, $mobile,$password,$otp);

	   if($res == USER_CREATED_SUCCESSFULLY){

		//send otp
		// $smsSender = new MyMobileAPI();
		//$smsSender->sendSms($mobile, $otp);
		//$smsSender->checkCredits();


	   	 		$response["error"] = false;
				//$response["sms"] = "SMS request is initiated!";
                $response["message"] = "You are successfully registered";
	   }
	   else if ($res== USER_CREATE_FAILED)
	   {
	   		$response["error"] = true;
            $response["message"] = "Oops! An error occurred while registering";
	   }else if ($res == USER_ALREADY_EXISTED)
	   {
	   	$response["error"] = true;
                $response["message"] = "Sorry, phone already exists";
	   }

	   //echo json response
	   echoResponse(201, $response);

    }
);



$app->post(
    '/activate_user',
    function () use($app) {
        //check param
        verifyRequiredParams(array('otp'));
		$response = array();
		$otp = $app->request->post('otp');

		$db = new ClientDbHandler();
		$user = $db->activateUser($otp);

		if($user !=null){

			$response["error"] = false;
			$response["message"] = "Great! You have been activited!";
			$response["profile"] = $user;
		}else{
			$response["error"] = true;
			$response["message"] = "Sorry! Failed at creating account";
		}

		 //echo json response
	   echoResponse(201, $response);
    }
);



$app->get('/retrievetcuser/:mobile/:password',
    function ($mobile, $pwd) {  //function ($mobile, $pwd) use($app){
      //check param
    //  verifyRequiredParams(array('mobile','password'));
    //  $mobile = $app->request->get('mobile');
    //  $pwd = $app->request->get('password');


      $response = array();
      $db = new ClientDbHandler();
      $user = $db->retrieveTCUser($mobile, $pwd);

      if($user!=null){
        $response["error"] = false;
  			$response["message"] = "Welcome back!";
  			$response["profile"] = $user;
      }else{
        $response["error"] = true;
  			$response["message"] = "Sorry! We could not recognised this account.";
      }

    //echo 'This is a get route';
    //echo json response
    echoResponse(200, $response);
  }
);



  /* -------------********************* `Driver realted routes**************************------------------ */
  $app->post(
      '/register_driver',
      function () use ($app){
         //check the params  name, $company_name, $email,  $mobile,$carmodel, $numplate, $license, $year,$apikey
         verifyRequiredParams(array('name','email','mobile','password', 'company_name',  'carmodel', 'numplate', 'license', 'year'));

  	   $response = array();
  	   //read the param
  	   $name = $app->request->post('name');
  	   $email = $app->request->post('email');
  	   $mobile = $app->request->post('mobile');
       $company_name = $app->request->post('company_name');
       $carmodel= $app->request->post('carmodel');
       $numplate = $app->request->post('numplate');
       $license = $app->request->post('license');
       $year = $app->request->post('year');
  	   $password = $app->request->post('password');

  	   $otp = rand(10000, 99999);
  	   //$otp = 11111;
  	   //validating email address
  	   validateEmail($email);
  	   $db = new DriverDbHandler();

  	   $res =$db->createTaxiDriver($name, $email,$company_name, $carmodel, $numplate, $license, $year,$password, $mobile,$otp);

  	   if($res == USER_CREATED_SUCCESSFULLY){

  		//send otp
  		// $smsSender = new MyMobileAPI();
  		//$smsSender->sendSms($mobile, $otp);
  		//$smsSender->checkCredits();


  	   	 		$response["error"] = false;
  				//$response["sms"] = "SMS request is initiated!";
                  $response["message"] = "You are successfully registered";
  	   }
  	   else if ($res== USER_CREATE_FAILED)
  	   {
  	   		$response["error"] = true;
              $response["message"] = "Oops! An error occurred while registering";
  	   }else if ($res == USER_ALREADY_EXISTED)
  	   {
  	   	$response["error"] = true;
                  $response["message"] = "Sorry, phone already exists";
  	   }

  	   //echo json response
  	   echoResponse(201, $response);

      }
  );


  $app->get('/retrievetduser/:mobile/:password',
      function ($mobile, $pwd) {  //function ($mobile, $pwd) use($app){
        //check param
      //  verifyRequiredParams(array('mobile','password'));
      //  $mobile = $app->request->get('mobile');
      //  $pwd = $app->request->get('password');


        $response = array();
        $db = new DriverDbHandler();
        $user = $db->retrieveTDUser($mobile, $pwd);

        if($user!=null){
          $response["error"] = false;
    			$response["message"] = "Welcome back!";
    			$response["profile"] = $user;
        }else{
          $response["error"] = true;
    			$response["message"] = "Sorry! We could not recognised this account.";
        }

      //echo 'This is a get route';
      //echo json response
      echoResponse(200, $response);
    }
  );

  $app->post(
      '/activate_user_driver',
      function () use($app) {
          //check param
          verifyRequiredParams(array('otp'));
  		$response = array();
  		$otp = $app->request->post('otp');

  		$db = new DriverDbHandler();
  		$user = $db->activateUser($otp);

  		if($user !=null){

  			$response["error"] = false;
  			$response["message"] = "Great! You have been activited...Mr Transporter";
  			$response["profile"] = $user;
  		}else{
  			$response["error"] = true;
  			$response["message"] = "Sorry! Failed at creating account";
  		}

  		 //echo json response
  	   echoResponse(201, $response);
      }
  );


  $app->post(
      '/save_driver_profile_image',
      function () use($app) {
          //check param
        //  verifyRequiredParams(array('id'));
      $response = array();
      $tc_id= $app->request->post('id');

    //  $filename = $app->request->file('image');
    //  basename($_FILES["image"]["name"];
    //  echo  " $filename " ;


      $db = new DriverDbHandler();
      $result = $db->saveDriverProfileImage($tc_id);

      if($result ==CREATED_SUCCESSFULLY){

        $response["error"] = false;
        $response["message"] = "Great! Your profile has been updated...Mr Transporter";
      //  $response["profile"] = $user;
      }else{
        $response["error"] = true;
        $response["message"] = "Sorry! Failed at creating account";
      }

       //echo json response
       echoResponse(201, $response);
      }
  );


  $app->post(
      '/update_driver_profile_image',
      function () use($app)  {
          //check param
      verifyRequiredParams(array('id','image_tag'));
      $response = array();
     $tc_id= $app->request->post('id');
      $image_tag= $app->request->post('image_tag');

    //  $filename = $app->request->file('image');
    //  basename($_FILES["image"]["name"];
    //  echo  " $filename " ;


      $db = new DriverDbHandler();
      $result = $db->UpdateDriverProfileImage($tc_id,$image_tag);

      if($result ==CREATED_SUCCESSFULLY){

        $response["error"] = false;
        $response["message"] = "Great! Your profile has been updated...Mr Transporter";
      //  $response["profile"] = $user;
      }else{
        $response["error"] = true;
        $response["message"] = "Sorry! Failed at creating account";
      }

       //echo json response
       echoResponse(201, $response);
      }
  );



  $app->post(
      '/save_driver_dummy_profile',
      function () use($app) {
          //check param
        //  verifyRequiredParams(array('otp'));
      $response = array();
      $tc_id= $app->request->post('id');

    //  $filename = $app->request->file('image');
    //  basename($_FILES["image"]["name"];
    //  echo  " $filename " ;


      $db = new DriverDbHandler();
      $result = $db->saveDriverDummyProfile($tc_id);

      if($result ==CREATED_SUCCESSFULLY){

        $response["error"] = false;
        $response["message"] = "Great! Your profile has been updated...Mr Transporter";
      //  $response["profile"] = $user;
      }else{
        $response["error"] = true;
        $response["message"] = "Sorry! Failed at creating account";
      }

       //echo json response
       echoResponse(201, $response);
      }
  );



  $app->get('/retrieve_dr_profile_image/:id', function($tc_id){
        $response = array();
        $db = new DriverDbHandler();

        $result =$db->retrieveDriverImages($tc_id);
        if($result){
        //  $response["error"] = false;
          // $response["message"] = "Image(s) found";

        //  $response["images"] = array();car_model
      //  $response[] = array();
          while ($images = $result->fetch_assoc() ) {
            $tmp = array();
            $tmp["id"] = $images["id"];

            $tmp["car_picture_url"] = $images["car_picture_url"];

            $tmp["taxi_driver_id"] = $images["taxi_driver_id"];


            array_push($response, $tmp);
          }

          echoResponse(200, $response);
        }else{
          $response["error"] = true;
          $response["message"] = "No Image found";
        }
  }

  );

  $app->get('/retrieve_dr_profile/:id',
      function($tc_id){  //function ($mobile, $pwd) use($app){
        $response = array();
        $db = new DriverDbHandler();

        $result =$db->retrieveDriverProfile($tc_id);

        if($result){
        //  $response["error"] = false;
          // $response["message"] = "Image(s) found";
          $response["error"] = false;
          $response["profile"]= array();
        //  $response["images"] = array();car_model
      //  $response[] = array();
          while ($profile = $result->fetch_assoc() ) {
            $tmp = array();
            $tmp["id"] = $profile["id"];
            $tmp["car_numberplate"] = $profile["car_numberplate"];

            $tmp["car_model"] = $profile["car_model"];
            $tmp["taxi_driver_id"] = $profile["taxi_driver_id"];
            $tmp["profile_rating"] = $profile["profile_rating"];
            $tmp["current_city"] = $profile["current_city"];
            array_push($response["profile"], $tmp);
          }

          $response["message"] = "Profile found";

        }else{
          $response["error"] = true;
          $response["message"] = "No Profile found";
        }

      //echo 'This is a get route';
      //echo json response
      echoResponse(200, $response);
    }
  );


    /* -------------********************* `Journey related routes *********************------------------ */
    $app->post('/createjob', function () use ($app){
      //check the params  name, $company_name, $email,  $mobile,$carmodel, $numplate, $license, $year,$apikey
      verifyRequiredParams(array('pickupAddr','destAddr','pickupTime','proposedFare', 'callAllowed',
      'pickupCoord', 'destCoord', 'tcID', 'shared','city'));
    //  verifyRequiredParams(array('pickupAddr','destAddr','pickupTime','proposedFare',
    //  'pickupCoord', 'destCoord', 'tcID'));

      //read the param
      $pickupAddr = $app->request->post('pickupAddr');
      $destAddr = $app->request->post('destAddr');
      $pickupTime = $app->request->post('pickupTime');
      $proposedFare = $app->request->post('proposedFare');
      $callAllowed = $app->request->post('callAllowed');
      $pickupCoord = $app->request->post('pickupCoord');
      $destCoord = $app->request->post('destCoord');
      $tc_id = $app->request->post('tcID');
      $shared = $app->request->post('shared');
      $city = $app->request->post('city');

        $response = array();
        $db = new JobDbHandler();
      //$result = $db->createJourneyRequest($pickupAddr, $destAddr, $pickupTime, $proposedFare, 1,$pickupCoord, $destCoord,$tc_id,1);
      $result = $db->createJourneyRequest($pickupAddr, $destAddr, $pickupTime, $proposedFare, $callAllowed,$pickupCoord, $destCoord,$tc_id,$shared,$city);
    //  $result = $db->createJourneyRequest($pickupAddr, $destAddr, $pickupTime, 40 , 1,$pickupCoord, $destCoord,$tc_id,1);
      if($result== CREATED_SUCCESSFULLY){

          $response["error"] = false;
          $response["message"] = "Request Sent...Now awaiting response";
      }
      else
      {
        $response["error"] = true;
        $response["message"] = "Oops! An error occurred while sending the request";
      }

      //echo json response
      echoResponse(201, $response);
    }

    );



    $app->get('/retrievependingjob/:tc_id', function($tc_id){
          $response = array();
          $db = new JobDbHandler();

          $result =$db->retrievePendingJob($tc_id);
          if($result){
            $response["error"] = false;
            $response["jobs"] = array();

            while ($job = $result->fetch_assoc() ) {
              $tmp = array();
              $tmp["id"] = $job["id"];
              $tmp["jr_pickup_add"] = $job["jr_pickup_add"];
              $tmp["jr_destination_add"] = $job["jr_destination_add"];
              $tmp["jr_pickup_coord"] = $job["jr_pickup_coord"];
              $tmp["jr_destination_coord"] = $job["jr_destination_coord"];
              $tmp["jr_pickup_time"] = $job["jr_pickup_time"];
              $tmp["jr_proposed_fare"] = $job["jr_proposed_fare"];
              $tmp["jr_tc_id"] = $job["jr_tc_id"];
              $tmp["jr_shared"] = $job["jr_shared"];
              $tmp["jr_status"] = $job["jr_status"];

              $tmp["jr_city"] = $job["jr_city"];
              $tmp["jr_time_created"] = $job["jr_time_created"];
              array_push($response["jobs"], $tmp);
            }

            echoResponse(200, $response);
          }
    }

    );


    $app->get('/retrieveassignjob/:tc_id', function($tc_id){
          $response = array();
          $db = new JobDbHandler();

          $result =$db->retrieveAssignedJob($tc_id);
          if($result){
            $response["error"] = false;
            $response["jobs"] = array();

            while ($job = $result->fetch_assoc() ) {
              $tmp = array();
              $tmp["id"] = $job["id"];
              $tmp["jr_pickup_add"] = $job["jr_pickup_add"];
              $tmp["jr_destination_add"] = $job["jr_destination_add"];
              $tmp["jr_pickup_coord"] = $job["jr_pickup_coord"];
              $tmp["jr_destination_coord"] = $job["jr_destination_coord"];
              $tmp["jr_pickup_time"] = $job["jr_pickup_time"];
              $tmp["jr_proposed_fare"] = $job["jr_proposed_fare"];
              $tmp["jr_tc_id"] = $job["jr_tc_id"];
              $tmp["jr_shared"] = $job["jr_shared"];
              $tmp["jr_status"] = $job["jr_status"];

              $tmp["jr_city"] = $job["jr_city"];
              $tmp["jr_time_created"] = $job["jr_time_created"];
              array_push($response["jobs"], $tmp);
            }

            echoResponse(200, $response);
          }
    }

    );



    $app->get('/retrieveallpendingjob', function(){
          $response = array();
          $db = new JobDbHandler();

          $result =$db->retrieveAllPendingJob();
          if($result){
            $response["error"] = false;
            $response["jobs"] = array();

            while ($job = $result->fetch_assoc() ) {
              $tmp = array();
              $tmp["id"] = $job["id"];
              $tmp["jr_pickup_add"] = $job["jr_pickup_add"];
              $tmp["jr_destination_add"] = $job["jr_destination_add"];
              $tmp["jr_pickup_coord"] = $job["jr_pickup_coord"];
              $tmp["jr_destination_coord"] = $job["jr_destination_coord"];
              $tmp["jr_pickup_time"] = $job["jr_pickup_time"];
              $tmp["jr_proposed_fare"] = $job["jr_proposed_fare"];
              $tmp["jr_tc_id"] = $job["jr_tc_id"];
              $tmp["jr_shared"] = $job["jr_shared"];
              $tmp["jr_status"] = $job["jr_status"];

              $tmp["jr_city"] = $job["jr_city"];
              $tmp["jr_time_created"] = $job["jr_time_created"];
              array_push($response["jobs"], $tmp);
            }

            echoResponse(200, $response);
          }
    }

    );



    $app->put('/updatejob/:id/:code', function($id,$code){


      $response = array();
      $db= new JobDbHandler();

      $result =$db->updateJourneyRequest($id,$code);
      if($result==UPDATED)
      {
        $response["error"] = false;
        $response["message"] = "Request updated";
      }else{
        $response["error"] = true;
        $response["message"] = "Oops! Request failed to update";
      }
      echoResponse(200, $response);
    });


    $app->post('/createjobresponse', function () use ($app){
      //check the params  name, $company_name, $email,  $mobile,$carmodel, $numplate, $license, $year,$apikey
      verifyRequiredParams(array('proposedFare',  'counterOffer', 'callAllowed',
      'jrID',  'tcID'));
    //  verifyRequiredParams(array('pickupAddr','destAddr','pickupTime','proposedFare',
    //  'pickupCoord', 'destCoord', 'tcID'));

      //read the param

      $proposedFare = $app->request->post('proposedFare');
      $counterOffer = $app->request->post('counterOffer');
      $callAllowed = $app->request->post('callAllowed');
      $jr_id = $app->request->post('jrID');
      $tc_id = $app->request->post('tcID');


        $response = array();
        $db = new JobDbHandler();
      //$result = $db->createJourneyRequest($pickupAddr, $destAddr, $pickupTime, $proposedFare, 1,$pickupCoord, $destCoord,$tc_id,1);
      $result = $db->createJourneyRequestReponse($proposedFare, $counterOffer, $callAllowed, $jr_id, $tc_id);
    //  $result = $db->createJourneyRequest($pickupAddr, $destAddr, $pickupTime, 40 , 1,$pickupCoord, $destCoord,$tc_id,1);
      if($result== CREATED_SUCCESSFULLY){

          $response["error"] = false;
          $response["message"] = "Request Sent...Now awaiting response";
      }
      else
      {
        $response["error"] = true;
        $response["message"] = "Oops! An error occurred while sending the request";
      }

      //echo json response
      echoResponse(201, $response);
    }

    );

    $app->get('/retrievejour_response/:jrID', function($jr_id){
          $response = array();
          $db = new JobDbHandler();

          $result =$db->getJourneyRequestReponse($jr_id);
          if($result){
            $response["error"] = false;
            $response["jobs"] = array();

            while ($job = $result->fetch_assoc() ) {
              $tmp = array();
              $tmp["id"] = $job["id"];
              $tmp["TaxiID"] = $job["TaxiID"];
                $tmp["JorID"] = $job["JorID"];
              $tmp["jre_proposed_fare"] = $job["jre_proposed_fare"];
              $tmp["jre_counter_offer"] = $job["jre_counter_offer"];
              $tmp["td_name"] = $job["td_name"];
              $tmp["td_company_name"] = $job["td_company_name"];
              $tmp["td_email"] = $job["td_email"];
              $tmp["td_mobile"] = $job["td_mobile"];
              $tmp["co_name"] = $job["co_name"];
              $tmp["car_picture_url"] = $job["car_picture_url"];
              $tmp["image_tag"] = $job["image_tag"];
              array_push($response["jobs"], $tmp);
            }

            echoResponse(200, $response);
          }
    }

    );


    $app->post('/createacceptedrequest', function () use ($app){
      //check the params  name, $company_name, $email,  $mobile,$carmodel, $numplate, $license, $year,$apikey
      verifyRequiredParams(array('pickupAddr','destAddr', 'pickupCoord', 'destCoord','acceptedFare', 'city', 'jrID','tcID', 'tdID'
      ));
    //  verifyRequiredParams(array('pickupAddr','destAddr','pickupTime','proposedFare',
    //  'pickupCoord', 'destCoord', 'tcID'));

      //read the param
      $pickupAddr = $app->request->post('pickupAddr');
      $destAddr = $app->request->post('destAddr');
      $pickupCoord = $app->request->post('pickupCoord');
      $destCoord = $app->request->post('destCoord');
      $acceptedFare = $app->request->post('acceptedFare');
$city = $app->request->post('city');
      $tc_id = $app->request->post('tcID');
      $jr_id = $app->request->post('jrID');
$td_id = $app->request->post('tdID');

        $response = array();
        $db = new JobDbHandler();
      //$result = $db->createJourneyRequest($pickupAddr, $destAddr, $pickupTime, $proposedFare, 1,$pickupCoord, $destCoord,$tc_id,1);
      $result = $db->createAcceptedRequest($pickupAddr, $destAddr, $pickupCoord, $destCoord, $acceptedFare ,$city,$jr_id,$tc_id,$td_id);
    //  $result = $db->createJourneyRequest($pickupAddr, $destAddr, $pickupTime, 40 , 1,$pickupCoord, $destCoord,$tc_id,1);
      if($result== CREATED_SUCCESSFULLY){

          $response["error"] = false;
          $res =$db->updateJourneyRequest($jr_id,1);
          $response["message"] = "Request Sent...Now awaiting response";
      }
      else
      {
        $response["error"] = true;
        $response["message"] = "Oops! An error occurred while sending the request";
      }

      //echo json response
      echoResponse(201, $response);
    }

    );

    $app->put('/updateacceptedrequest/:id/:code', function($id,$code) {
    //  verifyRequiredParams(array('code','id'));
      //get the params
    //  $code = $app->request->put('code');
      //$id = $app->request->put('id');

      $response = array();
      $db= new JobDbHandler();

      $result =$db->updateAcceptedRequest($id,$code);
      if($result==UPDATED)
      {
        $response["error"] = false;
        $response["message"] = "Request updated";
      }else{
        $response["error"] = true;
        $response["message"] = "Oops! Request failed to update";
      }
      echoResponse(200, $response);
    });



    /* -------------********************* Push notification realted routes *********************------------------ */
    $app->post('/register_fbNot', function()use($app){

      verifyRequiredParams(array('token','mobile','id','flag' )); //flag can be 0 for client or 1 for driver

      $token= $app->request->post('token');
      $mobile = $app->request->post('mobile');
      $id= $app->request->post('id');
      $flag = $app->request->post('flag');

      $response = array();
      $db= new NotHandler();

      $result=1 ;

      if($flag==0){
        $result =$db->registerClient($token, $mobile, $id);
      }else{
        $result =$db->registerDriver($token, $mobile, $id);
      }

      //$result =$db->fbRegister($fbid, $email);

      if($result==CREATED_SUCCESSFULLY)
      {
        $response["error"] = false;
        $response["message"] = "Registered successfully";
      }else{
        $response["error"] = true;
        $response["message"] = "Oops! Request failed create";
      }
      echoResponse(200, $response);

    });


    $app->post('/send_push', function()use($app){

      $token= $app->request->post('token');
      $msg = $app->request->post('msg');

      $message = array('message' => "$msg" );
      $response = array();
      $db= new NotHandler();
      $db->send_notification($token, $message);


      });


      /* -------------********************* `Test platform realted routes *********************------------------ */
      $app->post('/fbregister', function()use($app){

        $fbid = $app->request->post('firebaseid');
        $email = $app->request->post('email');

        $response = array();
        $db= new DbHandler();

        $result =$db->fbRegister($fbid, $email);

        if($result==CREATED_SUCCESSFULLY)
        {
          $response["error"] = false;
          $response["message"] = "Registered successfully";
        }else{
          $response["error"] = true;
          $response["message"] = "Oops! Request failed create";
        }
        echoResponse(200, $response);

      });

      $app->get('/getemail', function(){
        $response = array();
        $db = new DbHandler();

        $result = $db->getEmail();

        if($result){
          $response["error"] = false;
          $response["emails"] = array();
        }

      });



      $app->get('/retrieveallimage', function(){
            $response = array();
            $db = new DriverDbHandler();

            $result =$db->retrieveAllImages();
            if($result){
            //  $response["error"] = false;
              // $response["message"] = "Image(s) found";

            //  $response["images"] = array();car_model
          //  $response[] = array();
              while ($images = $result->fetch_assoc() ) {
                $tmp = array();
                $tmp["id"] = $images["id"];
                $tmp["car_numberplate"] = $images["car_numberplate"];
                $tmp["car_picture_url"] = $images["car_picture_url"];
                $tmp["car_model"] = $images["car_model"];
                $tmp["taxi_driver_id"] = $images["taxi_driver_id"];
                $tmp["profile_rating"] = $images["profile_rating"];

                array_push($response, $tmp);
              }

              echoResponse(200, $response);
            }else{
              $response["error"] = true;
              $response["message"] = "No Image found";
            }
      }

      );



/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();
