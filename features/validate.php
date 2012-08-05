<?php

	function authenticate_secret_code($user_id, $secret_code){

		global $DBH;

		# Get user_id
		$STH = $DBH->prepare("SELECT `user_id` FROM users WHERE secret_code = ? AND user_id = ?");
		
		$STH->bindParam(1, $secret_code);
		$STH->bindParam(2, $user_id);  
		$STH->execute(); 
		
		# setting the fetch mode  
		$STH->setFetchMode(PDO::FETCH_ASSOC);  
		  
		$returned_row = $STH->fetch();
		$is_authenticated = $returned_row["user_id"];
	  
	  
		if(!$is_authenticated){
			
			return false;

		}else{
			
			return true;
		}

	}


?>