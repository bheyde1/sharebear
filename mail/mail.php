<?php

require_once 'class.phpmailer.php';


// not used
function send_invite($user_id, $group_id){

  $perferred_contact_method = User::get_perferred_contact_method($user_id);
   
   if(!is_null($perferred_contact_method)){
    
      if(strrpos ( $perferred_contact_method , "@") == FALSE){
        //contact method id a phone number

        send_text_message_invite( $perferred_contact_method, $group_id, $user_id );

      }else{ //Contact method is an email address
        
        send_email_invite( $perferred_contact_method, $group_id, $user_id );
        
      }
  }
}

function send_email_invite($email,$group_id,$refer_id){

  $group_name = Group::get_group_name($group_id);
  $refer_name = User::get_user_fullname($refer_id);

  //only returns a max of 4 preview images
  $group_photos = Group::get_thumbnails_for_email($group_id);

  

  $mail = new PHPMailer(true); //defaults to using php "mail()"; the true param means it will throw exceptions on errors, which we need to catch
  

  //read in email template
  //Dev
  //$email_template = file_get_contents('/home/bheyde1/public_html/dev/mail/email_templates/invite.html');

  //Live
  $email_template = file_get_contents('/home/bheyde1/public_html/mail/email_templates/invite.html');

  

  for ($i = 0; $i < count($group_photos); $i++) { // loop though all images

      $mail->AddEmbeddedImage($group_photos[$i], 'group-photo-' . ($i+1), $group_name . '-' . ($i+1) . '.jpg ');

      $email_template = str_replace("%image_" .  ($i+1) ."%", "<a href='http://www.sharebearapp.com/download' ><img src='cid:group-photo-" . ($i+1) . "' alt='group-photo-" . ($i+1) . "'  /></a>", $email_template);

    }

   //If 4 images were not returned, replace image tokens with blank. 
  $email_template = str_replace("%image_1%", "&nbsp;", $email_template); 
  $email_template = str_replace("%image_2%", "&nbsp;", $email_template);
  $email_template = str_replace("%image_3%", "&nbsp;", $email_template);
  $email_template = str_replace("%image_4%", "&nbsp;", $email_template);
  $email_template = str_replace("%image_5%", "&nbsp;", $email_template);
  $email_template = str_replace("%image_6%", "&nbsp;", $email_template);

  //set group name and refer name
  $email_template = str_replace("%refer_name%", $refer_name, $email_template);
  $email_template = str_replace("%group_name%", $group_name, $email_template);  
 
  /* debug
  echo'<textarea rows="2" cols="20">';
  echo $email_template;
  echo'</textarea>';
  */

  try {

      $mail->AddReplyTo('invite@sharebearapp.com', 'Share Bear');
      $mail->AddAddress($email);
      $mail->addBCC("info@sharebearapp.com","Share Bear App");
      $mail->SetFrom('invite@sharebearapp.com', 'Share Bear');
      $mail->Subject = $refer_name . ' wants you to download Share Bear';
      $mail->MsgHTML($email_template);
      
      $mail->Send();

  } catch (phpmailerException $e) {

    if($debug == "true"){
      echo $e->errorMessage(); //Pretty error messages from PHPMailer
    }

  } catch (Exception $e) {

    if($debug == "true"){
      echo $e->getMessage(); //Boring error messages from anything else!
    }
  }
}

function send_text_message_invite($phone,$group_id,$refer_id){

  $carriers = array("Alltel"=>"message.alltel.com",
                  "AT&T"=>"txt.att.net",
                  "Boost Mobile"=>"myboostmobile.com",
                  "Sprint"=>"messaging.sprintpcs.com",
                  "T-Mobile"=>"tmomail.net",
                  "US Cellular"=>"email.uscc.net",
                  "Verizon"=>"vtext.com",
                  "Virgin Mobile"=>"vmobl.com" );
  
  foreach($carriers as $carrier => $value) {
  
    send_text_message_low_level($phone,$value);

 }

}

function send_text_message_low_level($phone, $carrier_address){

  $mail = new PHPMailer(true); //defaults to using php "mail()"; the true param means it will throw exceptions on errors, which we need to catch

  $html_message = "One of your friends wants to share pictures with you on Share Bear. Click here http://www.sharebearapp.com/download to download the app and view the pictures.  <br/><br>";

  try {

      $mail->AddReplyTo('invite@sharebearapp.com', 'Share Bear');
      $mail->AddAddress($phone . "@" . $carrier_address);
      $mail->SetFrom('invite@sharebearapp.com', 'Share Bear');
      $mail->Subject = 'Share Bear - Invite Text Message';
      $mail->MsgHTML($html_message);
      
      $mail->Send();

  } catch (phpmailerException $e) {

    if($debug == "true"){
      echo $e->errorMessage(); //Pretty error messages from PHPMailer
    }

  } catch (Exception $e) {

    if($debug == "true"){
      echo $e->getMessage(); //Boring error messages from anything else!
    }
  }

  

}


function send_email_validation($user){

  $user_id = $user->get("user_id");

  global $DBH;
  
  $STH = $DBH->prepare("SELECT * FROM contact_methods WHERE user_id = ?");
          
  $STH->bindParam(1, $user_id);
       
  $STH->execute();

  $STH->setFetchMode(PDO::FETCH_ASSOC);  
          
  while ($returned_row = $STH->fetch()) {

    // is email address
    if(strrpos ( $returned_row["contact_method"] , "@") != FALSE){

      send_email_low_level ( $user_id, $user->get("firstname"), $user->get("lastname"), $returned_row["contact_method"],$returned_row["validate_code"] );

    }
        
  }
     
}


function send_email_low_level($user_id, $fname, $lname, $email, $validate_code){

  $mail = new PHPMailer(true); //defaults to using php "mail()"; the true param means it will throw exceptions on errors, which we need to catch

  //not used anymore. Template is being served from Manrdill
  $html_message = "Please click the link below to verify your email address and activate your account <br/><br>";
  $html_message = $html_message . '<a href="http://www.sharebearapp.com/mail/validate_email.php?vid=' . $validate_code . '&uid=' . $user_id . '">Validate Email Now!</a><br/><br>';

  $mail->IsSMTP(); // telling the class to use SMTP
  
  try {

      //Set SMTP Servers to Mandrill
     
      $mail->Host       = "smtp.mandrillapp.com"; // SMTP server
      $mail->SMTPSecure = 'ssl';
      //$mail->SMTPDebug  = 2;

      $mail->SMTPAuth   = true;                  // enable SMTP authentication
      $mail->Host       = "smtp.mandrillapp.com"; // sets the SMTP server
      $mail->Port       = 465;                    // set the SMTP port for the GMAIL server
      $mail->Username   = "bheyde1"; // SMTP account username
      $mail->Password   = "61bf321c-bcb2-4678-ba55-e5d0e60ebb70"; 

      //Set Custom Headers      
      $mail->addCustomHeader("X-MC-Track: opens, clicks_all");
      $mail->addCustomHeader("X-MC-AutoText: true");
      $mail->addCustomHeader("X-MC-GoogleAnalytics: sharebearapp.com, www.sharebearapp.com");
      $mail->addCustomHeader("X-MC-GoogleAnalyticsCampaign: validate_email");
      $mail->addCustomHeader('X-MC-MergeVars: {"uid": "'. $user_id .'","vid": "'. $validate_code .'"}');
      $mail->addCustomHeader("X-MC-Template: Validate Email");
      

      

      $mail->AddReplyTo('validation@sharebearapp.com', 'Share Bear');
      $mail->AddAddress($email, $fname . " " . $lname);
      $mail->SetFrom('validation@sharebearapp.com', 'Share Bear');
      $mail->Subject = 'Share Bear Email Validation - Action Required';
      $mail->MsgHTML($html_message);
      
      $mail->Send();

  } catch (phpmailerException $e) {

    if($debug == "true"){
      echo $e->errorMessage(); //Pretty error messages from PHPMailer
    }

  } catch (Exception $e) {

    if($debug == "true"){
      echo $e->getMessage(); //Boring error messages from anything else!
    }

  }

}

function get_alternate_emails($user){
  
   global $DBH;
  
    
    # Get count
    $STH = $DBH->prepare("SELECT COUNT(*) FROM users_emails WHERE user_id = ?");
    $STH->bindParam(1, $user->get_user_id());

    if ($STH->execute()) {

      if ($STH->fetchColumn() > 0) {
    
          $STH = $DBH->prepare("SELECT email, validate_code FROM users_emails WHERE user_id = ?");
          
          $STH->bindParam(1, $user->get_user_id());
       
          $STH->execute();
          
          $STH->setFetchMode(PDO::FETCH_ASSOC);  
          
          $counter = 0;
          //define multi-dimensional array
          $alternate_emails = array( 'emails'=>array(),'validate_codes'=>array() );

          while ($returned_row = $STH->fetch()) {
            $alternate_emails["emails"][$counter] = $returned_row["email"];
            $alternate_emails["validate_codes"][$counter] = $returned_row["validate_code"];
            
             $counter++; 
          }

          return $alternate_emails;
      }

      return 0;
  }
}

  


?>