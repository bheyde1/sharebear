<?php
// users class and methods
// users.php

class User  {

	private $firstname;
	private $lastname;
	private $email;
	private $email_array;
	private $phone_number;
	private $username;
	private $password;
	private $secret_code;
	private $user_id;


	public function __construct($decoded_data) {
		

			$this->firstname = $decoded_data["person_fname"];
			$this->lastname = $decoded_data["person_lname"];
			$this->email = $decoded_data["person_email"];
			$this->phone_number = $decoded_data["phone_number"];
			$this->username = $decoded_data["user_name"];
			$this->password = $decoded_data["password"];


			
			//does email contain multiple email addresses
			if( strpos( $this->email, ',' ) != FALSE ){
				
				$this->email_array = explode(",",$this->email);
				
				//strip white space and force to lowercase
				$this->email = trim (strtolower($this->email_array[0]));

			}else{
				
				$this->email_array[0] = trim (strtolower($this->email));
			}

	}

	public function get($value){
		return $this->$value;
	}

	public function set($variable,$value){
		$this->$variable = $value;
	}

 	/* User Functions */
	public function create_user($supress_output = "false"){
		
		//check that only a single phone number is passed
		if(strpos($this->phone_number, ",")){
			return_errors("Only a single phone number can be passed when creating a new user","PHONE_NUMBER_ERROR");
		}

		if(!empty($this->username)){	

			$username_exists = $this->username_exists();

			//Username Already Exists
			if($username_exists == 1){
				return_errors("Username already exists","USERNAME_EXISTS_ERROR");
			}
		}

		//check to see if email or phone numbers are already in database attached to existing user
		$user_already_exists = $this->does_user_exist($this->email_array,$this->phone_number);

	
		//user exist 
		if( $user_already_exists != 0 ){

			return_errors("Your email adress or phone number is already tied to an existing account. Please use the account recovery tool to login", "ACCOUNT_EXISTS_ERROR");
 
		//User name is available or no username passed and user does not currently exist. Create User.
		}else {
				
				//set secret code 
				$this->secret_code = generate_random_string(15);
				
				//do user insert
				$created_user_id = $this->create_user_low_level();
					

				//error checking
				if ($created_user_id == FALSE){
					
					return_errors("Unable to create user. Database insert error.","CREATE_USER_INSERT_ERROR");

				}else{

					//update object properties
					$this->user_id = $created_user_id;

					//user successfully created
					if ($supress_output != "true"){
						$this->return_user_success($created_user_id);
					}
				}

		}	
	
	}


	private function create_user_low_level(){

		global $DBH;

		# insert 
		$STH = $DBH->prepare("INSERT INTO users (date_created,first_name, last_name, user_name, password, secret_code) values ( Now(), ?, ?, ?, ?, ?)");


		# assign variables to each place holder 
		$STH->bindParam(1, $this->firstname);  
		$STH->bindParam(2, $this->lastname);    
		$STH->bindParam(3, $this->username);  
		$STH->bindParam(4, $this->password);
		$STH->bindParam(5, $this->secret_code);

		$STH->execute(); 
		
		 if($STH){
		 	
		 	$user_id = $this->get_user_id();
		 	$this->insert_contact_methods($user_id);
			return $user_id;

		 }else{
		 	
		 	return false;
		 }
	 
	}
 	
 	//Need to add error returns to this function
	private function insert_contact_methods($user_id){

		global $DBH;

		//loop though emails add to contact methods
		foreach($this->email_array as $email_address) {

			
			$STH = $DBH->prepare("INSERT INTO contact_methods (user_id, contact_method, validate_code ) values (?, ?, ?)");
			$STH->bindParam(1, $user_id);  
			$STH->bindParam(2, $email_address);  
			$STH->bindParam(3, generate_random_string(10));

			$STH->execute();
					
			
		}

		//insert phone number
		
		//strip spaces and dashes
		$clean_number = clean_phone_number($this->phone_number);
		$validated = "1";

		$STH = $DBH->prepare("INSERT INTO contact_methods (user_id, contact_method, validate_code, validated ) values (?, ?, ?, ?)");
		$STH->bindParam(1, $user_id);  
		$STH->bindParam(2, $clean_number);
		$STH->bindParam(3, generate_random_string(10)); // never used. Populate value for data consistancy.
		$STH->bindParam(4, $validated); // set validated equal to true for phone number 

		$STH->execute();
				
		if($STH){

			User::validate_contact_method($clean_number,$user_id);
		}	
				

	}


	private function username_exists(){


		global $DBH;

		# Check and see if username exists
		$STH = $DBH->prepare("SELECT `user_id` FROM users WHERE user_name = ?");
			
		$STH->bindParam(1, $this->username); 
		$STH->execute(); 

		# setting the fetch mode  
		$STH->setFetchMode(PDO::FETCH_ASSOC);  
		$returned_row = $STH->fetch();
		
		if(!is_null($returned_row["user_id"])){

			return 1;

		}else{
				
			return 0;
		}
	}

	//success output functions
	public function return_user_success($created_user_id){

		$response_data = array('user_id' => $created_user_id,'secret_code' => $this->secret_code );
		$data = array('response_status' => "success",'response_message' => $response_data, 'response_code' => 'USER_CREATE_SUCCESS');
		$data = json_encode($data);
		echo $data;
	}

	public function get_user_id(){
		
		global $DBH;

		# Get user_id
		$STH = $DBH->prepare("SELECT `user_id` FROM users WHERE secret_code = ?");
		
		$STH->bindParam(1, $this->secret_code); 
		$STH->execute(); 
		
		# setting the fetch mode  
		$STH->setFetchMode(PDO::FETCH_ASSOC);  
		  
		$returned_row = $STH->fetch();
		$user_id = $returned_row["user_id"];
	  
		if($user_id == FALSE){
			
			return_errors("Unable to get user_id.","USER_ID_ERROR");

		}else{
			
			return $user_id;
		}
	
	}

	private function load_user($user_id){
		
		global $DBH;

		# Check and see if username exists
		$STH = $DBH->prepare("SELECT * FROM users WHERE user_id = ?");
			
		$STH->bindParam(1, $user_id); 
		$STH->execute(); 

		# setting the fetch mode  
		$STH->setFetchMode(PDO::FETCH_ASSOC);  
		$returned_row = $STH->fetch();

			$this->firstname 		= $returned_row["first_name"];
			$this->lastname 		= $returned_row["last_name"];
			$this->email 			= $returned_row["email"];
			$this->phone_number 	= $returned_row["main_phone"];
			$this->username 		= $returned_row["user_name"];
			$this->password 		= $returned_row["password"];
			$this->referred_by 		= $returned_row["referred_by"];
			$this->contact_method 	= $returned_row["contact_method"];
			$this->temp_user 		= $returned_row["temp_user"];
			$this->secret_code 		= $returned_row["secret_code"]; 
			$this->validate_code 	= $returned_row["validate_code"];
	
	}


	


	// ********** Static Methods ********************


	static function is_validated_user($user_id, $secret_code){

		global $DBH;

		# Get user_id
		$STH = $DBH->prepare("SELECT cm.validated FROM users u, contact_methods cm 
							  WHERE (u.secret_code = ? AND u.user_id =  ?) 
							  AND cm.user_id = u.user_id AND cm.validated = 1");
		
		$STH->bindParam(1, $secret_code);
		$STH->bindParam(2, $user_id);  
		$STH->execute(); 
		
		# setting the fetch mode  
		$STH->setFetchMode(PDO::FETCH_ASSOC);  
		  
		$returned_row = $STH->fetch();
		$is_validated = $returned_row["validated"];
	  
	  
		if(!$is_validated){
			
			return 0;

		}else{
			
			return 1;
		}

	}

	static function has_validated_email($data){

		global $DBH;
		$user_id 		= $data["user_id"];
		$secret_code 	= $data["secret_code"];

		# Get user_id
		$STH = $DBH->prepare("SELECT cm.validated FROM users u, contact_methods cm 
							  WHERE (u.secret_code = ? AND u.user_id =  ?) 
							  AND cm.user_id = u.user_id AND cm.validated = 1 AND cm.contact_method LIKE '%@%'");
		
		$STH->bindParam(1, $secret_code);
		$STH->bindParam(2, $user_id);  
		$STH->execute(); 
		
		# setting the fetch mode  
		$STH->setFetchMode(PDO::FETCH_ASSOC);  
		  
		$returned_row = $STH->fetch();
		$is_validated = $returned_row["validated"];
	  
	  
		if(!$is_validated){
			
			return_errors("User does not have a validated email address","NO_VALID_EMAIL");

		}else{
			
		$data = array('response_status' => "success",'response_message' => 'User has a validated email address', 'response_code' => 'VALIDATED_EMAIL');
		$data = json_encode($data);
		echo $data;
		}

	}

	static function user_login($username,$password){

		global $DBH;
		
		$STH = $DBH->prepare("SELECT user_id,secret_code FROM users WHERE user_name = ? AND password = ?");
		
		$STH->bindParam(1, $username);
		$STH->bindParam(2, $password);  
		$STH->execute(); 
		
		# setting the fetch mode  
		$STH->setFetchMode(PDO::FETCH_ASSOC);  
		  
		$returned_row = $STH->fetch();
		$user_id = $returned_row["user_id"];
		$secret_code = $returned_row["secret_code"];
	  
	  
		if(!$user_id && !$secret_code){
			
			return_errors("Username or password is incorrect","INCORRECT_LOGIN_ERROR");

		}else{

		$response_data = array('user_id' => $user_id,'secret_code' => $secret_code );	
		$data = array('response_status' => "success",'response_message' => $response_data, 'response_code' => 'USER_LOGIN_SUCCESS');
		$data = json_encode($data);
		echo $data;
			
			
		}
	}

	//static method used to determine if the user already has an account. 
	//Used by add_user_to_group in groups.php
	static function does_user_exist($emails,$phone_numbers){
		
		// defaut is 0, no match. If match will contain user_id
		$user_match = 0;


		if(is_array($phone_numbers)){

		//loop through each phone number
			foreach($phone_numbers as $phone) {
				
				if ($user_match == 0){
					
					//clean phone number before comparison
					$phone = clean_phone_number($phone);

					User::user_exists_low_level($user_match,$phone);
				}
			}

		}else{ //single phone address passed

			$phone_numbers = clean_phone_number($phone_numbers);
			User::user_exists_low_level($user_match,$phone_numbers);
		}

		//loop through each email address
		foreach($emails as $email) {
			
			if ($user_match == 0){

				User::user_exists_low_level($user_match,$email);
			}	
		}

		return $user_match;
	}

static function user_exists_low_level(&$user_match,$contact_method){

	global $DBH;

  
    $STH = $DBH->prepare("SELECT * FROM contact_methods WHERE contact_method = ? AND validated = '1'");
    
    $STH->bindParam(1, $contact_method); 
    $STH->execute();
    
    $returned_row = $STH->fetch();

    
	if(empty($returned_row["user_id"])){
		//no validated contact method found match
		$user_match = 0;
	}else{
		
		$user_match = $returned_row["user_id"];
	}

}


static function add_user_to_group_low_level($user_id, $group_id){

 		global $DBH;

 		# insert 
		$STH = $DBH->prepare("INSERT INTO usersxgroups (group_id, user_id) values ( ?, ? )");

		# assign variables to each place holder, indexed 1-6  
		$STH->bindParam(1, $group_id);  
		$STH->bindParam(2, $user_id);  
						 
		$STH->execute(); 
				
		if($STH){

			return true;
				
		}else{
					
			return false;	
		}	
 		

 	}

 	// Not used ... Keep around for later
 	private function update_user_profile($user_id){

 		global $DBH;

 		$clean_number = clean_phone_number($this->phone_number);

 		# insert 
		$STH = $DBH->prepare("UPDATE users SET first_name 	= ?,
											   last_name 	= ?,
											   email 		= ?,
											   main_phone	= ?,
											   user_name	= ?,
											   password 	= ?,
											   secret_code 	= ?
											   
							 WHERE user_id = ?");
 
		$STH->bindParam(1, $this->firstname);
		$STH->bindParam(2, $this->lastname);
		$STH->bindParam(3, $this->email);
		$STH->bindParam(4, $clean_number);
		$STH->bindParam(5, $this->username);
		$STH->bindParam(6, $this->password);
		$STH->bindParam(7, $this->secret_code);
		$STH->bindParam(8, $user_id);
   
		$STH->execute(); 
				
		if($STH){

			return true;
			
		}else{
				
			return false;	
		}	
 		

 	}
 	



 	static function is_member($user_id, $group_id){

 		global $DBH;

 		# insert 
		$STH = $DBH->prepare("SELECT * FROM usersxgroups WHERE group_id = ? AND user_id = ?");

		# assign variables to each place holder, indexed 1-6  
		$STH->bindParam(1, $group_id);  
		$STH->bindParam(2, $user_id);  
						 
		$STH->execute(); 
				
		$returned_row = $STH->fetch();
		
		if($returned_row == FALSE){
			return false;
		}else{

			return true;
		}
 		

 	}


 



static function get_users($data){

		//set variables
		$user_id 			= $data["user_id"];
		$secret_code 		= $data["secret_code"];
		$users_requested	= $data["user_ids"];
		$users_sent 		= array();

		//verify secret code matches user id
 		if(!authenticate_secret_code($user_id,$secret_code)){
 			return_errors("Unable to authenticate user","AUTHENTICATION_ERROR");
 		}


		foreach ($users_requested as $user_id) {

			$user_data = User::get_users_low_level($user_id);
	
			$users_sent[$user_id]["first_name"] 				= $user_data["first_name"];
			$users_sent[$user_id]["last_name"] 					= $user_data["last_name"];

		}

		
		send_multiple_responses($users_sent);


	}

static function get_users_low_level($user_id){

		global $DBH;
		 
		$STH = $DBH->prepare("SELECT * FROM users WHERE user_id = ?");

		# assign variables to each place holder 
		$STH->bindParam(1, $user_id);  
  			 
		$STH->execute(); 

		# setting the fetch mode  
		$STH->setFetchMode(PDO::FETCH_ASSOC);  
		$returned_row = $STH->fetch();

      return $returned_row;
	}

static function convert_pending_to_group_assignments($contact_method, $user_id){

		global $DBH;
		 
		$STH = $DBH->prepare("SELECT * FROM pending_usersxgroups WHERE contact_method = ?");

		# assign variables to each place holder 
		$STH->bindParam(1, $contact_method); 
  			 
		$STH->execute(); 

		# setting the fetch mode  
		$STH->setFetchMode(PDO::FETCH_ASSOC);  
		$returned_row = $STH->fetch();

        while ($returned_row = $STH->fetch(PDO::FETCH_ASSOC)) {

        	$group_id = $returned_row["group_id"];

        	//check and see if user is already in group
        	if(User::is_member($user_id,$group_id) == FALSE){

        		User::add_user_to_group_low_level($user_id,$group_id);
        	}
        	
        	

        }
        

}



static function validate_contact_method($contact_method, $user_id){

		//Convert any pending group assignments to real group asignments
		User::convert_pending_to_group_assignments($contact_method, $user_id);

		//clean up contact_methods table
		User::clean_up_contact_methods($contact_method);

		//delete all pending group assignments using that contact method
		global $DBH;
		 
		$STH = $DBH->prepare("DELETE FROM pending_usersxgroups WHERE contact_method = ?");

		# assign variables to each place holder 
		$STH->bindParam(1, $contact_method);  
  			 
		$STH->execute(); 
     
     //need to do error checking

}

static function clean_up_contact_methods($contact_method){

	
		global $DBH;
		 
		$STH = $DBH->prepare("DELETE FROM contact_methods WHERE contact_method = ? AND validated = 0");

		# assign variables to each place holder 
		$STH->bindParam(1, $contact_method);  
  			 
		$STH->execute(); 
     

}

static function get_user_fullname($refer_id){

		global $DBH;
		 
		$STH = $DBH->prepare("SELECT * FROM users WHERE user_id = ?");

		# assign variables to each place holder 
		$STH->bindParam(1, $refer_id);
	     
		$STH->execute();

		$STH->setFetchMode(PDO::FETCH_ASSOC);  
		$returned_row = $STH->fetch();

		$fullname = $returned_row["first_name"] . " " . $returned_row["last_name"];

		return $fullname;
}


} //end class

?>
