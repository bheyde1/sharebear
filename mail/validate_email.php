<?php

//includes
//config file 
include "../config.php";
include "../util/util.php";
include "../features/database.php";
include "../features/users.php";
include "../features/groups.php";


$validate_code = htmlentities($_GET['vid']);
$user_id = htmlentities($_GET['uid']);

if(!is_null($validate_code) && !is_null($user_id) ){
  
  validate_user($validate_code,$user_id);

}else{
  

}

function validate_user(&$validate_code, &$user_id){

  global $DBH;
  
  
    $STH = $DBH->prepare("UPDATE contact_methods SET validated = '1' WHERE user_id = ? AND validate_code = ?");
    
    $STH->bindParam(1, $user_id);
    $STH->bindParam(2, $validate_code);  
    $STH->execute();
    
    //get number of rows affected
    $success = $STH->rowCount();
    
  if($success){

    $contact_method = lookup_contact_method($validate_code, $user_id);
  

    if($contact_method != "0"){

      User::validate_contact_method($contact_method, $user_id);

      $message = "Thank You. Your account has been validated.";

    }else{

      $message = "There was a problem validating your account. Please contact us at info@sharebearapp.com";
    }

    
    outputPage($message);
     

  }    

}

function outputPage($message){

  echo '

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>ShareBear App - Instant Photo Sharing</title>
</head>

<body>

  <h3 class="center">' . $message . '</h3>


</body>

<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(["_setAccount", "UA-33617657-1"]);
  _gaq.push(["_trackPageview"]);

  (function() {
    var ga = document.createElement("script"); ga.type = "text/javascript"; ga.async = true;
    ga.src = ("https:" == document.location.protocol ? "https://ssl" : "http://www") + ".google-analytics.com/ga.js";
    var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>


  ';


}


function lookup_contact_method($validate_code, $user_id){

    global $DBH;

    $STH = $DBH->prepare("SELECT * FROM contact_methods WHERE user_id = ? AND validate_code = ?");
    
    $STH->bindParam(1, $user_id);
    $STH->bindParam(2, $validate_code);  
    $STH->execute();
    
    # setting the fetch mode  
    $STH->setFetchMode(PDO::FETCH_ASSOC);  
    $returned_row = $STH->fetch();
    
    if(!is_null($returned_row["contact_method"])){

      return $returned_row["contact_method"];

    }else{
        
      return 0;
    }

}




?>