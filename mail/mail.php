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

  $mail = new PHPMailer(true); //defaults to using php "mail()"; the true param means it will throw exceptions on errors, which we need to catch

  $html_message = "One of your friends, ". $refer_name . " just added you to " . $group_name . " using Share Bear. Click here http://www.sharebearapp.com/download to download the app and view the pictures. <br/><br>";

  try {

      $mail->AddReplyTo('invite@sharebearapp.com', 'Share Bear');
      $mail->AddAddress($email);
      $mail->SetFrom('invite@sharebearapp.com', 'Share Bear');
      $mail->Subject = 'Share Bear App - Invite';
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

  $html_message = "Please click the link below to verify your email address and activate your account <br/><br>";
  $html_message = $html_message . '<a href="http://www.sharebearapp.com/mail/validate_email.php?vid=' . $validate_code . '&uid=' . $user_id . '">Validate Email Now!</a><br/><br>';

  try {

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