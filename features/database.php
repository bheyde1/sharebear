<?php
//Database Connection Class
//Using PHP Data Objects (PDO)

//name database class
class Connection  {

	private $connection;
	private $username;
	private $password;
	private $dsn;

	public function __construct($dbname,$host,$user,$pass) {
		$this->dsn = "mysql:host=" . $host . ";dbname=" . $dbname;
		$this->username = $user;
		$this->password = $pass;
	}

	public function setConnection($conn) {
		$this->connection = $conn;
	}

	public function getConnection() {
		return $this->connection;
	}

	public function connect() {
		
		try {  

			$DBH = new PDO($this->dsn, $this->username, $this->password);
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION ); 
			$this->connection = $DBH;
			return $this->connection;

		}catch(PDOException $e) {  

		    return_errors("There was an error connecting to the database.","DATABASE_CONNECTION_ERROR");
		    $today = date("D M j G:i:s T Y");
		    file_put_contents('log/errors/PDOErrors.txt', $today ." ". $e->getMessage(), FILE_APPEND); 
		    return false; 
		}  
			
	}

	public function close(){
		
		$this->connection = null;
	}
}

//Instantiate new Connection Object. Connection parameters defined in config.php
$conn = new Connection(dbname,host,user,pass);
$DBH = $conn->connect();


?>