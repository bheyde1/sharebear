<?php
// utility functions
// util.php


/* Error Functions */
function return_errors($error_message, $error_code){
	
	$errors = array('response_status' => "failed",'response_message' => $error_message,'response_code' => $error_code );
	$errors = json_encode($errors);
	echo $errors;
	exit();
}


//debug function

function debug($data){

	file_put_contents('request.txt', html_entity_decode($data), FILE_APPEND);
	echo "JSON Data String: " . html_entity_decode($data) . "<br>";
}

function process_JSON($data){
	
	return json_decode( ( stripslashes( html_entity_decode( $data ) ) ), true);
	
}

function generate_random_string($length){

		$str = "";
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";	

		$size = strlen( $chars );
		for( $i = 0; $i < $length; $i++ ) {
			$str .= $chars[ rand( 0, $size - 1 ) ];
		}

		return $str;
	}

function split_by_comma($delimited_string){
	return explode(",",$delimited_string);
}

function clean_phone_number($phone_number){
	
	//remove dashes
	$clean_phone = str_replace("-", "", $phone_number);

	//remove spaces
	$clean_phone = str_replace(" ", "", $clean_phone);

  //remove left parenthesis
  $clean_phone = str_replace("(", "", $clean_phone);

  //remove right parenthesis
  $clean_phone = str_replace(")", "", $clean_phone);

	return $clean_phone;
}


function send_multiple_responses($response_array){
	
	$status = "success";


	foreach ($response_array as $response){
		
		if($response == '0' || $response == '' || is_null($response)){
			
			$status = "failed";
		}
	}


	$response = array('response_status' => $status,'response_message' => $response_array,'response_code' => 'MULTIPLE_RESPONSE' );
	$response = json_encode($response);
	echo $response;

}


function data_uri($file, $mime) {

  $contents = file_get_contents($file);

  if($contents == FALSE){
  	return 0;
  }else{

	  $base64   = base64_encode($contents); 
	  return ('data:' . $mime . ';base64,' . $base64);
  }
}



/** 
     * Count the number of bytes of a given string. 
     * Input string is expected to be ASCII or UTF-8 encoded. 
     * Warning: the function doesn't return the number of chars 
     * in the string, but the number of bytes. 
     * 
     * @param string $str The string to compute number of bytes 
     * 
     * @return The length in bytes of the given string. 
     */ 
    function strBytes($str) 
    { 
      // STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT 
      
      // Number of characters in string 
      $strlen_var = strlen($str); 
  
      // string bytes counter 
      $d = 0; 
      
     /* 
      * Iterate over every character in the string, 
      * escaping with a slash or encoding to UTF-8 where necessary 
      */ 
      for ($c = 0; $c < $strlen_var; ++$c) { 
          
          $ord_var_c = ord($str{$d}); 
          
          switch (true) { 
              case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)): 
                  // characters U-00000000 - U-0000007F (same as ASCII) 
                  $d++; 
                  break; 
              
              case (($ord_var_c & 0xE0) == 0xC0): 
                  // characters U-00000080 - U-000007FF, mask 110XXXXX 
                  // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8 
                  $d+=2; 
                  break; 
  
              case (($ord_var_c & 0xF0) == 0xE0): 
                  // characters U-00000800 - U-0000FFFF, mask 1110XXXX 
                  // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8 
                  $d+=3; 
                  break; 
  
              case (($ord_var_c & 0xF8) == 0xF0): 
                  // characters U-00010000 - U-001FFFFF, mask 11110XXX 
                  // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8 
                  $d+=4; 
                  break; 
  
              case (($ord_var_c & 0xFC) == 0xF8): 
                  // characters U-00200000 - U-03FFFFFF, mask 111110XX 
                  // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8 
                  $d+=5; 
                  break; 
  
              case (($ord_var_c & 0xFE) == 0xFC): 
                  // characters U-04000000 - U-7FFFFFFF, mask 1111110X 
                  // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8 
                  $d+=6; 
                  break; 
              default: 
                $d++;    
          } 
      } 
      
      return $d; 
    } 

    function return_byte_lenght($string){

    	//get string length in bytes
    	$bytes = strBytes($string);

		//add leading 0's so return value is always 5 characters/bytes
		$message_length = sprintf("%05d", $bytes); 

		return $message_length;
    }

    function get_update($data){

      $current_version = $data["current_version"];


    $response_data = "up_to_date"; // hard coded for now. Real logic to be added later.
    $data = array('response_status' => "success",'response_message' => $response_data, 'response_code' => 'GET_UPDATE_RESPONSE');
    $data = json_encode($data);
    echo $data;
    exit();
    }

    function delete_file($path){
      
      if (file_exists($path)) { 
        
        $result = unlink ($path); 
        if($result == true){

          return true;

        }else{

          return false;

        }

      }else{

        return false;
        
      }

    }

?>
