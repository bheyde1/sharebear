<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Sample Form</title>
</head>

<body>

<?php 

//$data = '{"user_id": "1","secret_code": "91ScWDJNXnXucFW", "group_name": "Wedding"}';

//Upload image / assign to multiple groups
//$data = '{"user_id": "1","secret_code": "91ScWDJNXnXucFW","group_id": [7,8,9,10,11,12,13] }';

// Create User
//$data = '{"person_fname": "Brennan","person_lname": "Heyde","person_email": "bheyde1@gmail.com", "phone_number": "222-554-4454", "user_name": "", "password": ""}';

// Get image ids
//$data = '{"user_id": "14","secret_code": "1zq08de1eTETUoo", "group_id": "56"}';

//Get thumbnails
//$data = '{"user_id": "1","secret_code": "91ScWDJNXnXucFW", "group_id": "7","thumbnail_ids":["52","54","55","56"] }';
//$data = '{"user_id": "1","secret_code": "91ScWDJNXnXucFW", "thumbnail_ids":["83"] }';

//$data = '{"group_id":103,"thumbnail_ids":[303,54],"secret_code":"Fq4hrw1kRWWxh2J","user_id":80}';

//get_fullsize
//$data = '{"user_id": "2","secret_code": "uaeyeVAozQzZaJW", "group_id":38, "image_id":"22" }';

//upload image

//$data = '{"user_id": "1","secret_code": "91ScWDJNXnXucFW","group_id": ""}';
 //get_groups
//$data = '{"user_id": "1","secret_code": "91ScWDJNXnXucFW", "get_user_groups": "1"}';

// get_users
//$data = '{"user_id": "1","secret_code": "91ScWDJNXnXucFW", "user_ids":["1","2","11","12"] }';

//get_update
//$data = '{"current_version": "1.0"}';

//delete_image
//$data = '{"user_id": "1","secret_code": "91ScWDJNXnXucFW", "image_id": "750"}';

//copy_image
//$data = '{"user_id": "1","secret_code": "91ScWDJNXnXucFW", "image_id": "377", "source_group_id": "24", "new_group_id": "132" }';

//has_valid_email
$data = '{"user_id": "157","secret_code": "NeGjpIdZs3VCr6A" }';

//Add User to Group

/*
$data = '[
{
"user_id":"2",
"secret_code":"uaeyeVAozQzZaJW"
},
{
"person_fname":"Temp",
"person_lname":"User",
"person_email":"bheyde1@gmail.com",
"phone_number": "665-667-0009",
"group_id":"144"
}]';



/*
$data = '
[
{
"user_id":"1",
"secret_code":"91ScWDJNXnXucFW"
},
{
"person_fname":"Brennan",
"person_lname":"Heyde",
"person_email":"bheyde444@aol.com",
"phone_number": "999-999-9999",
"contact_method":"760-809-4756",
"group_id":"29"
},
{
"person_fname":"Brennan",
"person_lname":"Heyde",
"person_email":"bheyde666@aol.com",
"phone_number": "888-555-5551",
"contact_method":"bheyde1@gmail.com",
"group_id":"29"
}
]';

*/



//$group_id_array = array(7,8,9,10,11,12,13);
//echo (json_encode($group_id_array));

/*
$post_fields = array( 'action' => 'create_user', 'data' => $data); 

$url = '/request.php';
 
$ch = curl_init($url);
 
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
 
$response = curl_exec($ch);
curl_close($ch);

if ($response){
	echo $response;	
}

*/

//echo file_get_contents("/home/bheyde1/public_html/uploads/01HruCiMVlu7NeKved1g.jpg");

?>
<!--<form action="request.php" method="post" name="sample-form">
	<input type="hidden" name="action" value="add_user_to_group"  />
    <input type="hidden" name="data" value="<?php echo(htmlentities($data)); ?>" />
    <input type="submit" value="Sumbit Form"  />

</form> -->

<form action="request.php" method="post" name="sample-form">
	<input type="hidden" name="action" value="has_validated_email"  />
    <input type="hidden" name="data" value="<?php echo(htmlentities($data)); ?>" />
    <input type="submit" value="Sumbit Form"  />

</form>

<!--<form action="https://secure1907.hostgator.com/~bheyde1/request.php" method="post" enctype="multipart/form-data">
<input type="hidden" name="action" value="upload_image"  />
<input type="hidden" name="data" value="<?php echo(htmlentities($data)); ?>" />
<label for="fullsize">Fullsize:</label>
<input type="file" name="fullsize" id="fullsize" /> 
<br />

<label for="thumbnail">Thumbnail:</label>
<input type="file" name="thumbnail" id="image" /> 
<br />
<input type="submit" name="submit" value="Submit" />
</form> -->
</body>
</html>
