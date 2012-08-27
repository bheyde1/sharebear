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

    /******************** Encryption Functions ******************/


/*
 * Password hashing with PBKDF2.
 * Author: havoc AT defuse.ca
 * www: https://defuse.ca/php-pbkdf2.htm
 */

// These constants may be changed without breaking existing hashes.
define("PBKDF2_HASH_ALGORITHM", "sha256");
define("PBKDF2_ITERATIONS", 1000);
define("PBKDF2_SALT_BYTES", 24);
define("PBKDF2_HASH_BYTES", 24);

define("HASH_SECTIONS", 4);
define("HASH_ALGORITHM_INDEX", 0);
define("HASH_ITERATION_INDEX", 1);
define("HASH_SALT_INDEX", 2);
define("HASH_PBKDF2_INDEX", 3);

function create_hash($password)
{
    // format: algorithm:iterations:salt:hash
    $salt = base64_encode(mcrypt_create_iv(PBKDF2_SALT_BYTES, MCRYPT_DEV_URANDOM));
    return PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" .  $salt . ":" . 
        base64_encode(pbkdf2(
            PBKDF2_HASH_ALGORITHM,
            $password,
            $salt,
            PBKDF2_ITERATIONS,
            PBKDF2_HASH_BYTES,
            true
        ));
}

function validate_password($password, $good_hash)
{
    $params = explode(":", $good_hash);
    if(count($params) < HASH_SECTIONS)
       return false; 
    $pbkdf2 = base64_decode($params[HASH_PBKDF2_INDEX]);
    return slow_equals(
        $pbkdf2,
        pbkdf2(
            $params[HASH_ALGORITHM_INDEX],
            $password,
            $params[HASH_SALT_INDEX],
            (int)$params[HASH_ITERATION_INDEX],
            strlen($pbkdf2),
            true
        )
    );
}

// Compares two strings $a and $b in length-constant time.
function slow_equals($a, $b)
{
    $diff = strlen($a) ^ strlen($b);
    for($i = 0; $i < strlen($a) && $i < strlen($b); $i++)
    {
        $diff |= ord($a[$i]) ^ ord($b[$i]);
    }
    return $diff === 0; 
}

/*
 * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
 * $algorithm - The hash algorithm to use. Recommended: SHA256
 * $password - The password.
 * $salt - A salt that is unique to the password.
 * $count - Iteration count. Higher is better, but slower. Recommended: At least 1000.
 * $key_length - The length of the derived key in bytes.
 * $raw_output - If true, the key is returned in raw binary format. Hex encoded otherwise.
 * Returns: A $key_length-byte key derived from the password and salt.
 *
 * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
 *
 * This implementation of PBKDF2 was originally created by https://defuse.ca
 * With improvements by http://www.variations-of-shadow.com
 */
function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false)
{
    $algorithm = strtolower($algorithm);
    if(!in_array($algorithm, hash_algos(), true))
        die('PBKDF2 ERROR: Invalid hash algorithm.');
    if($count <= 0 || $key_length <= 0)
        die('PBKDF2 ERROR: Invalid parameters.');

    $hash_length = strlen(hash($algorithm, "", true));
    $block_count = ceil($key_length / $hash_length);

    $output = "";
    for($i = 1; $i <= $block_count; $i++) {
        // $i encoded as 4 bytes, big endian.
        $last = $salt . pack("N", $i);
        // first iteration
        $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
        // perform the other $count - 1 iterations
        for ($j = 1; $j < $count; $j++) {
            $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
        }
        $output .= $xorsum;
    }

    if($raw_output)
        return substr($output, 0, $key_length);
    else
        return bin2hex(substr($output, 0, $key_length));
}


?>