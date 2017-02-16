<?php


class AdvertHandler{


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

//this method when uploading image from 64 based encoded string
    public function saveadvertImage($image,$advert_desc)
    {
      // the upload folder
        $upload_path ="advertimages/";
        $upload_target =dirname(__FILE__) . '/advertimages/';
      //  $server_ip = gethostbyname(gethostname());
        $server_ip = SERVERNAME;
        //creating the upload url
        $upload_url = 'http://'.$server_ip.'/api/include/'.$upload_path;

        //file url to store in database
        $file_url = $upload_url ."_01.jpg";
        $file_path= $upload_target."_01.jpg";
        $image_tag="advert";

        try {

          $stmt = $this->conn->prepare("INSERT INTO `advert_image`(`advert_picture_url`, `image_tag`, `advert_desc`) VALUES (?,?,?)");

      //    $stmt = $this->conn->prepare("INSERT INTO client_profile (cp_username, cp_profilepic) values ('JonJon',?)");
          $stmt->bind_param("sss", $file_url,$image_tag,$advert_desc);

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



//this method when uploading image from webportal or postman
    public function saveadvertImagefromPostman($image,$image_tag,$advert_desc)
    {
      // the upload folder
        $upload_path ="advertimages/";
        $upload_target =dirname(__FILE__) . '/advertimages/';
      //  $server_ip = gethostbyname(gethostname());
        $server_ip = SERVERNAME;
        //creating the upload url
        $upload_url = 'http://'.$server_ip.'/api/include/'.$upload_path;
        $tag = rand(10000, 99999);
        //file url to store in database
        $file_url = $upload_url .$image_tag."_".$tag. ".jpg";
        $file_path= $upload_target.$image_tag."_".$tag. ".jpg";


        try {

          $stmt = $this->conn->prepare("INSERT INTO `advert_image`(`advert_picture_url`, `image_tag`, `advert_desc`) VALUES (?,?,?)");

          //    $stmt = $this->conn->prepare("INSERT INTO client_profile (cp_username, cp_profilepic) values ('JonJon',?)");
          $stmt->bind_param("sss", $file_url,$image_tag,$advert_desc);

          $result =$stmt->execute();

          $stmt->close();
          if($result){
            //saving the file
            move_uploaded_file($_FILES['image']['tmp_name'], $file_path);
            //  file_put_contents($file_path, base64_decode($image));
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





    public function retrieveAdvertImages()
    {
    //  echo "$tc_id";
      $stmt = $this->conn->prepare("SELECT * FROM `advert_image` ORDER BY RAND() LIMIT 1");
      //binding params
    //  $stmt->bind_param("s",$td_id);

      $stmt->execute();
      $images = $stmt->get_result();
      $stmt->close();
      return $images;
    }


}

 ?>
