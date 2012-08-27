<?php



//Brennan Heyde

//2/2/1012

//Andorid Picture App



//config file 

include "config.php";



// includes

include "util/util.php";

include "features/database.php";

include "features/validate.php";

include "features/users.php";

include "features/groups.php";

include "features/uploads.php";

require_once 'mail/mail.php';





//global variables

$errors = array();



//process post data and create global variables from them

foreach($_POST as $k=>$v) {

	

	$$k=htmlentities($v);

}



function create_action_array( $action_array ){

	return explode(",",$action_array);

}



if($debug == "true"){

	debug ($data);

}



//check and see if ation is set. If it is, determine which action to do

if (!is_null($action)){

	

	//check and see if there are multiple actions seperated by commas

	if ( strrpos ($action,",")){

		$actions = create_action_array($action);

	}else{	

		$actions = array($action);	

	}

	

	if($data){

		//decode any html entities and convert JSON to php array

		$decoded_data = process_JSON($data);



		if (is_null($decoded_data)){

			return_errors("There was an error parsing the JSON.","JSON_DECODE_ERROR");

		}



	}else{

		//no case was matched return error

		return_errors("The JSON object for Data was not found","MISSING_DATA_ERROR");

	}				



	foreach($actions as $action) {

	

		switch ($action) {

			case "create_user":



				//JSON valid, create user

				$user = new User($decoded_data);

				$user->create_user();

				send_email_validation($user);

						

				

				

				break;

			case "user_login":

				

				//static method to validate user login

				User::user_login($decoded_data["user_name"],$decoded_data["password"]);



			break;

			case "user_login_encrypt_passwords":

				

				//static method to validate user login

				User::encrypt_passwords();



			break;

			case "get_users":

				

				User::get_users($decoded_data);

				

			break;

			case "get_groups":

				

				Group::get_groups($decoded_data);



				break;

			case "create_group":

				

				$group = new Group($decoded_data);

				$group->create_group();





				break;



			case "upload_image":

				

				$upload = new Upload($decoded_data);

				$upload->upload_image();





				break;



			case "copy_image":

				

				$null = NULL;

				$upload = new Upload($null);

				$upload->copy_image($decoded_data);





				break;

					

			case "add_user_to_group":

				

				$response_array = array();

				

				$array_size = count($decoded_data);

				

				for($i = 1; $i < $array_size; $i++){ //start at 1 so it skips first array which is sender data

					$index = $i - 1;

				

					Group::add_user_to_group($decoded_data[$i]["person_email"],$decoded_data[$i]["phone_number"],$decoded_data[$i]["group_id"],$decoded_data[$i],$decoded_data[0],$response_array,$index);





				}





				//send response back with multiple statuses

				send_multiple_responses($response_array);



				break;



			case "get_thumbnails":



				Upload::get_thumbnails($decoded_data);





				break;



			case "get_image_ids":



				Upload::get_image_ids($decoded_data);





				break;



			case "get_fullsize":



				Upload::get_fullsize($decoded_data);





				break;

						

			case "get_update":



				//function location in util.php

				get_update($decoded_data);





				break;



			case "has_validated_email":



				//function location in util.php

				User::has_validated_email($decoded_data);





				break;



			case "delete_image":



				//function location in util.php

				Upload::delete_image($decoded_data);





				break;



			case "delete_group": // not yet implemented.



				//function location in util.php

				Group::delete_group($decoded_data);





				break;



			case "remove_user_from_group": // not yet implemented



				//function location in util.php

				Group::remove_user_from_group($decoded_data);





				break;



			case "sync_image_counts": // not yet implemented



				//function location in util.php

				Group::sync_image_counts();





				break;

						

							

			default:

			//no case was matched return error

			return_errors("The action submitted was not found.","UNMATCHED_ACTION_ERROR");



		}//end stwith

	}//end for each

}else{ //is null check

	

	return_errors("The incoming request did not contain an action.","MISSING_ACTION_ERROR");

} 







?> 