<?php

// groups class and methods

// groups.php



class Group  {



	private $date_created;

	private $name;

	private $owner_id;

	private $user_count;

	private $photo_count;

	private $secret_code;



	public function __construct($decoded_data) {

		$this->name = $decoded_data["group_name"];

		$this->owner_id = $decoded_data["user_id"]; //user_id is the owner of group

		$this->secret_code = $decoded_data["secret_code"];

		$this->user_count = 1;

		$this->photo_count = 0;

	

	

	}



	public function get($value){

		return $this->$value;

	}



	public function set($variable,$value){

		$this->$variable = $value;

	}



 	

 	public function create_group(){



 		//verify secret code matches user id

 		if(!authenticate_secret_code($this->owner_id,$this->secret_code)){

 				return_errors("Unable to authenticate user","AUTHENTICATION_ERROR");

 		}

 		

 			

 			// Check to see if group already exists (owner_id and name). 0 means is does not exist

 			if ($this->group_exists() == 0){

 				

 				$created_group_id = $this->create_group_low_level();



 				if(!$created_group_id == false){

 					

 				$this->return_group_success($created_group_id);



 				}else{

 					

 					return_errors("Unable to create group","CREATE_GROUP_ERROR");

 				}



 			}else{

 				//group already Exists

 				return_errors("Group Already Exists","DUPLICATE_GROUP_ERROR");



 			}	



 	}



 	private function create_group_low_level(){

 		

 		global $DBH;



 		# insert 

		$STH = $DBH->prepare("INSERT INTO groups (date_created,owner_id, name, user_count, photo_count) values ( Now(), ?, ?, ?, ? )");



		# assign variables to each place holder, indexed 1-6  

		$STH->bindParam(1, $this->owner_id);  

		$STH->bindParam(2, $this->name);  

		$STH->bindParam(3, $this->user_count);

		$STH->bindParam(4, $this->photo_count);  		  

		

		$STH->execute();  





		

		 if($STH){

		 	//group created. Update usersxgroups table.



		 	$group_id = $this->get_group_id();

		 	

			# insert 

			$STH = $DBH->prepare("INSERT INTO usersxgroups (group_id, user_id) values ( ?, ? )");



			# assign variables to each place holder, indexed 1-6  

			$STH->bindParam(1, $group_id);  

			$STH->bindParam(2, $this->owner_id);  

					 

			$STH->execute(); 

			

			if($STH){



				return $group_id;

			

			}else{

				

				return false;	

			}	





		 }else{

		 	

		 	return false;

		 }

	 

 	}



 



 	private function get_group_id(){



 		global $DBH;



 		# Get group id

		$STH = $DBH->prepare("SELECT `id` FROM groups WHERE owner_id = ? AND name = ?");

		

		$STH->bindParam(1, $this->owner_id);

		$STH->bindParam(2, $this->name);  

		$STH->execute(); 

		

		# setting the fetch mode  

		$STH->setFetchMode(PDO::FETCH_ASSOC);  

		  

		$returned_row = $STH->fetch();

		$group_id = $returned_row["id"];

	  

		if(is_null($group_id)){

			

			return_errors("Unable to get group_id.","GROUP_ID_ERROR");



		}else{

			

			return $group_id;

		}

	

 		

 	}



 	



	private function group_exists(){



		global $DBH;



		# Check and see if username exists

		$STH = $DBH->prepare("SELECT `id` FROM groups WHERE owner_id = ? AND name = ?");

			

		$STH->bindParam(1, $this->owner_id);

		$STH->bindParam(2, $this->name);  

		$STH->execute(); 



		# setting the fetch mode  

		$STH->setFetchMode(PDO::FETCH_ASSOC);  

		$returned_row = $STH->fetch();

		

		if(!is_null($returned_row["id"])){



			return 1;



		}else{

				

			return 0;

		}

	}



	//success output functions

	private function return_group_success($group_id){

	

		$response_data = array('group_id' => $group_id );

		$data = array('response_status' => "success",'response_message' => $response_data, 'response_code' => 'GROUP_CREATE_SUCCESS');

		$data = json_encode($data);

		echo $data;

	}



	

// ********** Static Methods ********************



	static function add_user_to_group($emails, $phone_numbers, $group_id, &$userdata, &$accessdata, &$response_array, $index){



		//HACK Remove

		$send_email = FALSE;



		$refer_id 	= $accessdata["user_id"];

		

		//verify secret code matches user id

 		if(!authenticate_secret_code($accessdata["user_id"],$accessdata["secret_code"])){

 			return_errors("Unable to authenticate user","AUTHENTICATION_ERROR");

 		}



 		//Verify User has validated their email address

 		if(!User::is_validated_user($accessdata["user_id"],$accessdata["secret_code"])){

 			return_errors("User Email Address Not Validated","EMAIL_VALIDATION_ERROR");

 		}



 		$emails 		= explode(",",$emails);

 		$phone_numbers 	= explode(",",$phone_numbers);



		//check and see if user exists

		$user_id = User::does_user_exist($emails,$phone_numbers);



		if( $user_id == 0){ //user does not exist

			

			

			//add to pending group assignments table

			Group::add_to_pending_group($group_id,$emails,$phone_numbers,$refer_id);







			

			//set response array

			$response_array[$index]["user_message"] 		= "User added to pending group. Invites sent to all contact methods.";

			$response_array[$index]["user_message_code"] 	= "No_User_Account_Invites_Sent";







		}else{ //user does exist, add to group

			

			

			if(User::is_member($user_id,$group_id) == TRUE){



				//User Already in Group

				$response_array[$index]["user_id"] 				= $user_id;

				$response_array[$index]["user_message"] 		= "User already in group.";

				$response_array[$index]["user_message_code"] 	= "User_Already_In_Group";



				

			}else{



				User::add_user_to_group_low_level($user_id, $group_id);

				Group::update_group_stats($group_id);



				//set response array

				$response_array[$index]["user_id"] 				= $user_id;

				$response_array[$index]["user_message"] 		= "User added to group";

				$response_array[$index]["user_message_code"] 	= "User_Added_To_Group";



			}

			

		}



	}



	static function add_to_pending_group($group_id,$emails,$phone_numbers, $refer_id){



		global $DBH;



		//loop though emails add to contact methods

		foreach($emails as $email_address) {



			$STH = $DBH->prepare("INSERT INTO pending_usersxgroups (group_id, contact_method ) values (?, ?)");

			$STH->bindParam(1, $group_id);  

			$STH->bindParam(2, $email_address);  



			$STH->execute();



			send_email_invite($email_address,$group_id,$refer_id);

					

			

		}



		foreach($phone_numbers as $phone) {

			//strip spaces and dashes

			$clean_number = clean_phone_number($phone);



			$STH = $DBH->prepare("INSERT INTO pending_usersxgroups (group_id, contact_method ) values (?, ?)");

			$STH->bindParam(1, $group_id);  

			$STH->bindParam(2, $clean_number);

			

			$STH->execute();



			send_text_message_invite($clean_number,$group_id,$refer_id);

				

			}

		

	}





    function update_group_stats($group_id){

		

		global $DBH;

		 

		$STH = $DBH->prepare("UPDATE groups SET user_count = user_count + 1 WHERE id = ?");



		# assign variables to each place holder, indexed 1-6  

		$STH->bindParam(1, $group_id);  

  			 

		$STH->execute(); 

			

		if($STH){



			return true;

			

		}else{

				

			return false;	

		}	





	}





	static function get_groups($accessdata){



	 	$groups = array();



	 	//verify secret code matches user id

 		if(!authenticate_secret_code($accessdata["user_id"],$accessdata["secret_code"])){

 			return_errors("Unable to authenticate user","AUTHENTICATION_ERROR");

 		}



 		//Verify User has validated their email address

 		if(!User::is_validated_user($accessdata["user_id"],$accessdata["secret_code"])){

 			return_errors("User Email Address Not Validated","EMAIL_VALIDATION_ERROR");

 		}





 		$user_id = $accessdata["user_id"];

		

		global $DBH;

		 

		$STH = $DBH->prepare("SELECT * FROM usersxgroups ug, groups g WHERE g.id = ug.group_id AND user_id = ?");



		# assign variables to each place holder 

		$STH->bindParam(1, $user_id);  

  			 

		$STH->execute(); 



		$index = 0;

		while ($returned_row = $STH->fetch(PDO::FETCH_ASSOC)) {

        	$groups[$index]["group_id"] 		= $returned_row["group_id"];

        	$groups[$index]["date_created"] 	= $returned_row["date_created"];

        	$groups[$index]["owner_id"] 		= $returned_row["owner_id"];

        	$groups[$index]["name"] 			= $returned_row["name"];

        	$groups[$index]["user_count"] 		= $returned_row["user_count"];

        	$groups[$index]["photo_count"] 		= $returned_row["photo_count"];



        	$index++;	

    	}



    	send_multiple_responses($groups);

	}



	static function remove_image_from_group($image_id){

		

		global $DBH;

		 

		$STH = $DBH->prepare("SELECT * FROM imagesxgroups WHERE image_id = ?");



		# assign variables to each place holder 

		$STH->bindParam(1, $image_id);  

  			 

		$STH->execute(); 



		

		while ($returned_row = $STH->fetch(PDO::FETCH_ASSOC)) {

        	

        	Group::remove_image_from_group_low_level( $returned_row["group_id"], $image_id );

        	Upload::decrement_stats($returned_row["group_id"]);



    	}



	}



	static function remove_image_from_group_low_level($group_id,$image_id){



		global $DBH;

		 

		$STH = $DBH->prepare("DELETE FROM imagesxgroups WHERE image_id = ? AND group_id = ?");



		# assign variables to each place holder 

		$STH->bindParam(1, $image_id);

		$STH->bindParam(2, $group_id);   

  			 

		$STH->execute(); 



		

	}





	static function sync_image_counts(){



		global $DBH;

		 

		$STH = $DBH->prepare("SELECT * FROM groups"); 

  			 

		$STH->execute();







		while ($returned_row = $STH->fetch(PDO::FETCH_ASSOC)) {

        	

        	$group_id = $returned_row["id"];



        	$image_count = Group::get_number_images_in_group($group_id);



        	Group::update_group_image_count($image_count, $group_id);

    	}



    	echo "Success";

		

	}



	static function get_number_images_in_group($group_id){



		global $DBH;

		 

		$STH = $DBH->prepare("SELECT COUNT(DISTINCT image_id) FROM imagesxgroups WHERE group_id = ?");



		# assign variables to each place holder 

		$STH->bindParam(1, $group_id);   

  			 

		$STH->execute(); 



		$returned_row = $STH->fetchColumn();



		echo "Group Id: " . $group_id . " - " . $returned_row . "<br>";



		return $returned_row;



		



	}



	static function update_group_image_count($image_count, $group_id){



		global $DBH;

		 

		$STH = $DBH->prepare("UPDATE groups SET photo_count = ? WHERE id = ?");



		# assign variables to each place holder 

		$STH->bindParam(1, $image_count);

		$STH->bindParam(2, $group_id);    

  			 

		$STH->execute(); 







	}





	static function get_group_name($group_id){



		global $DBH;

		 

		$STH = $DBH->prepare("SELECT name FROM groups WHERE id = ?");



		# assign variables to each place holder 

		$STH->bindParam(1, $group_id);

	     

		$STH->execute();



		$returned_row = $STH->fetchColumn();



		return $returned_row;





	}

	static function get_thumbnails_for_email($group_id){

		global $DBH;

		$STH = $DBH->prepare("SELECT * FROM images i, imagesxgroups ig WHERE i.image_id = ig.image_id AND ig.group_id = ? ORDER BY date_uploaded");

		# assign variables to each place holder 
		$STH->bindParam(1, $group_id);  

		$STH->execute(); 
		$rows = $STH->fetchAll();
		$num_rows = count($rows);

		if($num_rows >= 6){
			$num_rows = 6;
		}

		if ($num_rows){

		for ($i = 0; $i < $num_rows; $i++) { // loop though and return first 6 images in group

			$images[$i] = $rows[$i]["thumbnail_path"];	

    		}
    	}

    	return $images;

	}







} //end class

?>