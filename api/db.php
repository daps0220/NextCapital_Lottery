<?php
/**
 * @author      Xiaotian Le
 * @version     1.0
 * @since       2015-11-10
 */
//require_once "util.php";

$db_servername = "localhost";					// Database server
$db_dbname = "bowling";      					// Scheme name
$db_username = "root";    					// Database user name 
$db_password = "darpanpatel";     					// Database user password

$db_table_bowlers = "bowlers";
$db_table_leagues = "leagues";
$db_table_league_bowlers = "league_members";
$db_table_lotteries = "lotteries";

function connectHost() {
	global $db_servername, $db_username, $db_password;
	try {

		
		return new PDO("mysql:host=$db_servername", $db_username, $db_password);
	}
	catch(PDOException $e) {
		echo $e->getMessage() . "<br>";
	}
}

function connectDb() {
	global $db_dbname;
	try {
		$conn = connectHost();
		
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$sql = "USE $db_dbname";
		$conn->exec($sql);
		return $conn;
	}
	catch(PDOException $e) {
		echo $e->getMessage() . "<br>";
	}
}
?>
