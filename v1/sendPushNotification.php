<?php
require_once './../include/DbHandler.php';
require_once './../include/send_sms.php';
 ?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">

		<!-- Always force latest IE rendering engine (even in intranet) & Chrome Frame
		Remove this if you use the .htaccess -->
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

		<title>Firebase push</title>
		<meta name="description" content="">
		<meta name="author" content="Loic Stephan">

		<meta name="viewport" content="width=device-width; initial-scale=1.0">

		<!-- Replace favicon.ico & apple-touch-icon.png in the root of your domain and delete these references -->
		<link rel="shortcut icon" href="/favicon.ico">
		<link rel="apple-touch-icon" href="/apple-touch-icon.png">
	</head>

	<body>
		<div>
			<header>
				<h1>HTML</h1>
			</header>
			<nav>
				<p>
					<a href="/">Home</a>
				</p>
				<p>
					<a href="/contact">Contact</a>
				</p>
			</nav>

			<div>
        <!--here -->
        <?php
          $db = new DbHandler();
          $result = $db->getEmail();

         ?>
         <form action='send.php' method='post'>
           <select name='email'>
             <?php
                while ($email = $result->fetch_assoc() ){
                  //displaying the values in a dropdown list
             ?>

              <option value='<?php echo $email["email"]; ?>'> <?php echo $email["email"]; ?></option>
             <?php } ?>
           </select><br/>
           <!-- here user would enter the message to send to a particular device -->
           <textarea name='message'></textarea><br />

           <button>Send Notification</button><br />
          </form>
          <?php
 //Displaying a success message when the notification is sent
       if(isset($_REQUEST['success'])){
       ?>
       <strong>Great!</strong> Your message has been sent successfully...
       <?php
       }
       ?>
        <!-- and there-->
			</div>

			<footer>
				<p>
					&copy; Copyright  by Loic Stephan
				</p>
			</footer>
		</div>
	</body>
</html>
