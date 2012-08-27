<?php
// uploads class and methods
// uploads.php

class Upload  {

	private $date_created;
	private $filename;
	private $owner_id;
	private $group_id;
	private $fullsize_path;
	private $thumbnail_path;
	private $secret_code;


	public function __construct($decoded_data) {

		if(is_array($decoded_data)){
			$this->filename = generate_random_string(20); //does not have extention
			$this->owner_id = $decoded_data["user_id"]; //user_id is the owner of group
			$this->secret_code = $decoded_data["secret_code"];
			$this->group_id = $decoded_data["group_id"];
			$this->fullsize_path = image_path . $this->filename . ".jpg";
			$this->thumbnail_path = image_path . $this->filename . "_tn.jpg";
			$this->secret_code = $decoded_data["secret_code"];

		}else{ // allow object to be created without any properties


		}	
		
	}

	public function get($value){
		return $this->$value;
	}

	public function set($variable,$value){
		$this->$variable = $value;
	}

 	
 	public function upload_image(){

 		//verify secret code matches user id
 		if(!authenticate_secret_code($this->owner_id,$this->secret_code)){
 				return_errors("Unable to authenticate user","AUTHENTICATION_ERROR");
 		}

			if ((($_FILES["fullsize"]["type"] == "image/gif") || 
				($_FILES["fullsize"]["type"] == "image/jpeg") || 
				($_FILES["fullsize"]["type"] == "image/pjpeg")|| 
				($_FILES["fullsize"]["type"] == "image/png")) && 
				($_FILES["fullsize"]["size"] < max_upload_size)){
			  
			  if ($_FILES["fullsize"]["error"] > 0){

			    	return_errors("Unable to upload image: " . $_FILES["fullsize"]["error"] ,"IMAGE_UPLOAD_ERROR");
			    
			  }else{

			  	//output detailed info if debug is turned on
			  	//if($debug == "true"){
				   // echo "Upload: " . $_FILES["fullsize"]["name"] . "<br />";
				   // echo "Type: " . $_FILES["fullsize"]["type"] . "<br />";
				   // echo "Size: " . ($_FILES["fullsize"]["size"] / 1024) . " Kb<br />";
				   // echo "Temp file: " . $_FILES["fullsize"]["tmp_name"] . "<br />";
				//}

			    //check to see if file exists and give it a new random name until it is unique
			    while(file_exists(image_path . $this->filename . ".jpg")){

			    	$this->filename = generate_random_string(20);

			    }

			     	$moved = move_uploaded_file($_FILES["fullsize"]["tmp_name"], image_path . $this->filename . ".jpg");

				     if ($moved == FALSE){
				     	
				     	return_errors("Unable to move uploaded file to uploads folder." ,"MOVE_UPLOAD_ERROR");

				     }else{

				     	//upload success of fullsize image. Name and move thumbnail
				     	$thumbnail_upload = $this->upload_thumbnail();
				     	if($thumbnail_upload == false){
				     		$this->thumbnail_path = NULL;
				     	}

				     	//save image data in database
				     	$image_id = $this->save_image_data();

				     	//return success 
				     	$this->return_image_success($image_id);
				     }
			      
			          
			      
			    }
			  
			}else{

				return_errors("Image type not supported","INVALID_IMAGE_TYPE_ERROR");
			 
			}
		
	}

	private function upload_thumbnail(){

	if($_FILES["thumbnail"]["size"] > 0){	

			if ((($_FILES["thumbnail"]["type"] == "image/gif") || 
				($_FILES["thumbnail"]["type"] == "image/jpeg") || 
				($_FILES["thumbnail"]["type"] == "image/pjpeg")|| 
				($_FILES["thumbnail"]["type"] == "image/png")) && 
				($_FILES["thumbnail"]["size"] < max_upload_size)){
				  
				if ($_FILES["thumbnail"]["error"] > 0){

				    	return_errors("Unable to upload image: " . $_FILES["thumbnail"]["error"] ,"IMAGE_UPLOAD_ERROR");
				    
				}else{

					$moved = move_uploaded_file($_FILES["thumbnail"]["tmp_name"], image_path . $this->filename . "_tn.jpg");

					if ($moved == FALSE){
					     	
					    return_errors("Unable to move uploaded thumbnail to uploads folder." ,"MOVE_UPLOAD_ERROR");

					}else{
						
						return true;
					}
				}
			}

		}else{
			//no thumbnail was uploaded

			return false;


		}
		
	}

	private function save_image_data(){

		global $DBH;

 		# insert 
		$STH = $DBH->prepare("INSERT INTO images (date_uploaded, owner_id, filename, fullsize_path,thumbnail_path) values ( Now(), ?, ?, ?, ? )");

		# assign variables to each place holder, indexed 1-5 
		$filename_with_extention = $this->filename . ".jpg";

		$STH->bindParam(1, $this->owner_id);  
		$STH->bindParam(2, $filename_with_extention);
		$STH->bindParam(3, $this->fullsize_path);
		$STH->bindParam(4, $this->thumbnail_path);   		  
		
		$STH->execute();  


		
		 if($STH){
		 	//Image data saved.
		 	
		 	$image_id = $this->get_image_id();
		 	
		 	//add images to groups, update group stats 
		 	$this->add_image_to_group($this->group_id,$image_id);

		 	return $image_id;
		 	
		 }else{
		 	
		 	return false;
		 }
	 
	}


	private function get_image_id(){

 		global $DBH;

 		# Get group id
		$STH = $DBH->prepare("SELECT `image_id` FROM images WHERE owner_id = ? AND filename = ?");
		
		$filename_with_extention = $this->filename . ".jpg";

		$STH->bindParam(1, $this->owner_id);
		$STH->bindParam(2, $filename_with_extention);  
		$STH->execute(); 
		
		# setting the fetch mode  
		$STH->setFetchMode(PDO::FETCH_ASSOC);  
		  
		$returned_row = $STH->fetch();
		$image_id = $returned_row["image_id"];
	  
		if(is_null($image_id)){
			
			return_errors("Unable to get image_id.","IMAGE_ID_ERROR");

		}else{
			
			return $image_id;
		}
	}
	
	private function update_stats($group_id){
		
		global $DBH;
		 
		$STH = $DBH->prepare("UPDATE groups SET photo_count = photo_count + 1 WHERE id = ?");

		# assign variables to each place holder, indexed 1-6  
		$STH->bindParam(1, $group_id);  
  			 
		$STH->execute(); 
			
		if($STH){

			return true;
			
		}else{
				
			return false;	
		}	


	}



	private function add_image_to_group($group_id,$image_id){
		 
		//multiple group ids
		if(is_array($group_id)){

			foreach($group_id as $id) {
	
				$this->add_image_to_group_low_level($id,$image_id);

				//update group stats
				$this->update_stats($id);
			}

		//single group id
		}else{
			
			$this->add_image_to_group_low_level($group_id,$image_id);

			//update group stats
			$this->update_stats($group_id);
		}

	}

	private function add_image_to_group_low_level($group_id,$image_id){
		
		global $DBH;

		$STH = $DBH->prepare("INSERT INTO imagesxgroups (image_id, group_id ) values (?, ?)");
		$STH->bindParam(1, $image_id);  
		$STH->bindParam(2, $group_id);  

		$STH->execute();
			
		if($STH){

			return true;
			
		}else{
				
			return false;	
		}	
	}

	private function return_image_success($image_id){
	
		$response_data = array('image_id' => $image_id );
		$data = array('response_status' => "success",'response_message' => $response_data, 'response_code' => 'IMAGE_UPLOAD_SUCCESS');
		$data = json_encode($data);
		echo $data;
	}


	public function copy_image($data){

		//set variables
		$user_id 				= $data["user_id"];
		$image_id 				= $data["image_id"];
		$new_group_id 			= $data["new_group_id"];
		$source_group_id 		= $data["source_group_id"];
		$secret_code 			= $data["secret_code"];

		//verify secret code matches user id
 		if(!authenticate_secret_code($user_id,$secret_code)){
 			return_errors("Unable to authenticate user","AUTHENTICATION_ERROR");
 		}

 		//verify requestor is memeber of group
 		if(User::is_member($user_id,$source_group_id) == FALSE){
 			return_errors("User is not in both groups","GROUP_ACCESS_ERROR");
 		}

 		//verify requestor is memeber of group
 		if(User::is_member($user_id,$new_group_id) == FALSE){
 			return_errors("User is not in both groups","GROUP_ACCESS_ERROR");
 		}


 		//verify Image is not already in group  
		 if(Upload::is_image_in_group($image_id,$new_group_id) == TRUE){
		 	return_errors("Image is already in group","IMAGE_ALREADY_IN_GROUP");
		 }

 		$this->add_image_to_group($new_group_id, $image_id);

 		//no errors, return success
		$data = array('response_status' => "success",'response_message' => 'Image successfully copied to group', 'response_code' => 'IMAGE_COPY_SUCCESS');
		$data = json_encode($data);
		echo $data;

	}

	/* Static Functions*/

	

	static function get_image_ids($data){

		//set variables
		$group_id = $data["group_id"];
		$user_id = $data["user_id"];
		$secret_code = $data["secret_code"];
		global $DBH;

		//verify secret code matches user id
 		if(!authenticate_secret_code($user_id,$secret_code)){
 			return_errors("Unable to authenticate user","AUTHENTICATION_ERROR");
 		}

 		//verify requestor is memeber of group
 		if(User::is_member($user_id,$group_id) == FALSE){
 			return_errors("User is not in Group","GROUP_ACCESS_ERROR");
 		}

		
		$STH = $DBH->prepare("SELECT i.image_id FROM images i, imagesxgroups ig WHERE ig.image_id = i.image_id AND ig.group_id = ?");

		# assign variables to each place holder 
		$STH->bindParam(1, $group_id);  
  			 
		$STH->execute(); 

		$images = array();
		while ($returned_row = $STH->fetch(PDO::FETCH_ASSOC)) {
        	$images[count($images)] = $returned_row["image_id"];
        	
    	}

    	send_multiple_responses($images);
	}



	static function get_thumbnails($data){

		//set variables
		$group_id	 		= $data["group_id"];
		$user_id 			= $data["user_id"];
		$secret_code 		= $data["secret_code"];
		$thumbs_requested	= $data["thumbnail_ids"];
		$thumbs_sent 		= array();

		//verify secret code matches user id
 		if(!authenticate_secret_code($user_id,$secret_code)){
 			return_errors("Unable to authenticate user","AUTHENTICATION_ERROR");
 		}

 		//Verify User has validated their email address
 		if(!User::is_validated_user($data["user_id"],$data["secret_code"])){
 			return_errors("User Email Address Not Validated","EMAIL_VALIDATION_ERROR");
 		}

 		//verify requestor is memeber of group  
 		if(User::is_member($user_id,$group_id) == FALSE){
 			return_errors("User is not in Group","GROUP_ACCESS_ERROR");
 		}


 		$index = 0;
		foreach ($thumbs_requested as $thumbnail_id) {


				//verify Image is in group  
		 		if(Upload::is_image_in_group($thumbnail_id,$group_id) == FALSE){
		 			$thumbs_sent[$index] = "0";
		 		}else{

					$image_data = Upload::get_thumbnails_low_level($thumbnail_id);
					
					$thumbs_sent[$index]["thumbnail_id"] 			= $thumbnail_id;
					$thumbs_sent[$index]["thumbnail_data"] 			= data_uri( $image_data["thumbnail_path"], 'image/jpeg' );
					$thumbs_sent[$index]["owner_id"] 				= $image_data["owner_id"];
					$thumbs_sent[$index]["date_uploaded"] 			= $image_data["date_uploaded"];
				}

			$index++;	
		}//end foreach

		
		send_multiple_responses($thumbs_sent);


	}

	static function get_thumbnails_low_level($image_id){

		global $DBH;
		 
		$STH = $DBH->prepare("SELECT * FROM images WHERE image_id = ?");

		# assign variables to each place holder 
		$STH->bindParam(1, $image_id);  
  			 
		$STH->execute(); 

		# setting the fetch mode  
		$STH->setFetchMode(PDO::FETCH_ASSOC);  
		$returned_row = $STH->fetch();

      return $returned_row;
	}

	static function is_image_in_group($image_id,$group_id){

		global $DBH;
		 
		$STH = $DBH->prepare("SELECT * FROM imagesxgroups WHERE image_id = ? AND group_id = ?");

		# assign variables to each place holder 
		$STH->bindParam(1, $image_id);
		$STH->bindParam(2, $group_id);  
  			 
		$STH->execute(); 
		$returned_row = $STH->fetch();
		
		if($returned_row == FALSE){
			return false;
		}else{

			return true;
		}
 		
	}


	static function get_fullsize($data){

		$image_id 			= $data["image_id"]; 
		$group_id	 		= $data["group_id"];
		$user_id 			= $data["user_id"];

		//verify secret code matches user id
 		if(!authenticate_secret_code($data["user_id"],$data["secret_code"])){

 			$error_message = "Unable to authenticate user";
 			$error_code = "AUTHENTICATION_ERROR";
 			$errors = array('response_status' => "failed",'response_message' => $error_message,'response_code' => $error_code );
			$errors = json_encode($errors);
		
			echo (return_byte_lenght($errors) . $errors);
			exit();
 			
 		}

 		//Verify User has validated their email address
 		if(!User::is_validated_user($data["user_id"],$data["secret_code"])){

 			$error_message = "User Email Address Not Validated";
 			$error_code = "EMAIL_VALIDATION_ERROR";
 			$errors = array('response_status' => "failed",'response_message' => $error_message,'response_code' => $error_code );
			$errors = json_encode($errors);
		
			echo (return_byte_lenght($errors) . $errors);
			exit();


 		}


 		//verify requestor is memeber of group  
 		if(User::is_member($user_id,$group_id) == FALSE){

 			//Custom Error function to pass number of characters

 			$error_message = "User is not in Group";
 			$error_code = "GROUP_ACCESS_ERROR";
 			$errors = array('response_status' => "failed",'response_message' => $error_message,'response_code' => $error_code );
			$errors = json_encode($errors);
		
			echo (return_byte_lenght($errors) . $errors);
	
			exit();
 			
 		}

 		//verify image is part of group  
 		if(Upload::is_image_in_group($image_id,$group_id) == FALSE){

 			//Custom Error function to pass number of characters

 			$error_message = "Image is not in group";
 			$error_code = "IMAGE_ACCESS_ERROR";
 			$errors = array('response_status' => "failed",'response_message' => $error_message,'response_code' => $error_code );
			$errors = json_encode($errors);
		
			echo (return_byte_lenght($errors) . $errors);
	
			exit();
 			
 		}

		global $DBH;
		 
		$STH = $DBH->prepare("SELECT fullsize_path FROM images WHERE image_id = ?");

		# assign variables to each place holder 
		$STH->bindParam(1, $image_id);  
  			 
		$STH->execute(); 

		# setting the fetch mode  
		$STH->setFetchMode(PDO::FETCH_ASSOC);  
		$returned_row = $STH->fetch();

      	$image = file_get_contents($returned_row["fullsize_path"]);

	    // setup JSON resonse
	    $response_data = "Picture Data Immediately Following JSON";
	   	$response = array('response_status' => 'success','response_message' => $response_data,'response_code' => 'FULLSIZE_IMAGE_SUCCESS' );
		$response = json_encode($response);
		
		//Get response length
		$response_len = return_byte_lenght($response);

		$content_length = mb_strlen($response_len . $response . $image);
		
		//Set Content Length Header
		header("Content-Length:" . $content_length);

		echo $response_len . $response . $image;
	}

	static function is_image_owner($user_id, $image_id){

		global $DBH;
		 
		$STH = $DBH->prepare("SELECT * FROM images WHERE owner_id = ? AND image_id = ?");

		# assign variables to each place holder 
		$STH->bindParam(1, $user_id);
		$STH->bindParam(2, $image_id);  
  			 
		$STH->execute(); 
		$returned_row = $STH->fetch();
		
		if($returned_row == FALSE){
			return false;
		}else{

			return true;

		}	

	}

	static function delete_image($data){

		//verify secret code matches user id
 		if(!authenticate_secret_code($data["user_id"],$data["secret_code"])){
 			return_errors("Unable to authenticate user","AUTHENTICATION_ERROR");
 		}

 		//Verify User has validated their email address / Phone number
 		if(!User::is_validated_user($data["user_id"],$data["secret_code"])){
 			return_errors("User Email Address Not Validated","EMAIL_VALIDATION_ERROR");
 		}


 		//Does Image Exist?
 		if(!Upload::does_image_exist($data["image_id"]) ){
 			return_errors("Image does not exist on server","IMAGE_DOES_NOT_EXIST");
 		}

 		//Verify User is the owner of the image they are trying to delete
 		if(!Upload::is_image_owner($data["user_id"],$data["image_id"]) ){
 			return_errors("User does not own the image","USER_DOES_NOT_OWN_IMAGE");
 		}

 		//Remove image from all groups, update image counts in each group
 		Group::remove_image_from_group($data["image_id"]);


 		//delete files, return error if not successfull
 		if(!Upload::delete_file_from_server($data["image_id"]) ){
 			return_errors("Image could not be deleted","FILE_DELETE_ERROR");
 		}

 		//delete image data from database
 		if(!Upload::delete_image_data($data["image_id"]) ){
 			return_errors("Could not delete image data","IMAGE_DATA_ERROR");
 		}

 		//no errors, return success
		$data = array('response_status' => "success",'response_message' => 'Image deleted successfully', 'response_code' => 'IMAGE_DELETE_SUCCESS');
		$data = json_encode($data);
		echo $data;

	}


	static function does_image_exist($image_id){

		global $DBH;
		 
		$STH = $DBH->prepare("SELECT * FROM images WHERE image_id = ?");

		# assign variables to each place holder 
		$STH->bindParam(1, $image_id);  
  			 
		$STH->execute(); 
		$returned_row = $STH->fetch();

		if($returned_row == FALSE){
			return false;
		}else{

			return true;

		}	

	}

	static function delete_file_from_server($image_id){

		global $DBH;
		 
		$STH = $DBH->prepare("SELECT * FROM images WHERE image_id = ?");

		# assign variables to each place holder 
		$STH->bindParam(1, $image_id);  
  			 
		$STH->execute(); 
		$returned_row = $STH->fetch();
		
		if(empty($returned_row["fullsize_path"]) || empty($returned_row["thumbnail_path"]) ){
			return false;
		}else{

		$full_success 	=	delete_file($returned_row["fullsize_path"]);
		$thumb_success 	=	delete_file($returned_row["thumbnail_path"]);
		
			if($full_success && $thumb_success){
				return true;
			}else{
				return false;
			}
			

		}


	}

	static function delete_image_data($image_id){


		global $DBH;
		 
		$STH = $DBH->prepare("DELETE FROM images WHERE image_id = ?");

		# assign variables to each place holder 
		$STH->bindParam(1, $image_id);
		  	 
		$STH->execute();

		//get number of rows affected
    	$success = $STH->rowCount();

    	if($success){

    		return true;

    	}else{

    		return false;
    	}

	}

	static function decrement_stats($group_id){
		
		global $DBH;
		 
		$STH = $DBH->prepare("UPDATE groups SET photo_count = photo_count - 1 WHERE id = ?");

		# assign variables to each place holder, indexed 1-6  
		$STH->bindParam(1, $group_id);  
  			 
		$STH->execute(); 
			
		if($STH){

			return true;
			
		}else{
				
			return false;	
		}	


	}

} //end class

?>