<?php

require_once "db.php";

/** Setup the database */
try {
	$conn = connectHost();
	echo "Connected to host successfully<br>";

	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
	$sql = "CREATE DATABASE $db_dbname";
	$conn->exec($sql);
	echo "Database created successfully<br>";

	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$sql = "USE $db_dbname";
	$conn->exec($sql);
	echo "Database selected successfully<br>";

	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
	$sql = "CREATE TABLE $db_table_bowlers (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	firstname VARCHAR(" . MAX_NAME_LEN . ") NOT NULL,
	lastname VARCHAR(" . MAX_NAME_LEN . ") NOT NULL,
	email VARCHAR(" . MAX_EMAIL_LEN . ") NOT NULL,
	password VARCHAR(" . MAX_PASSWORD_LEN . ") NOT NULL,
	reg_date TIMESTAMP,
	payouts INT NOT NULL DEFAULT 0
	)";
	$conn->exec($sql);
	
	$sql = "CREATE TABLE $db_table_leagues (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(" . MAX_NAME_LEN . ") NOT NULL,
	ticket_price INT NOT NULL,
	estab_date TIMESTAMP,
	descr VARCHAR(" . MAX_DESCR_LEN . "),
	capacity INT NOT NULL DEFAULT 0,
	lottery_pool INT DEFAULT 0,
	lottery_id INT,
	lottery_winner INT
	)";
	$conn->exec($sql);
	
	$sql = "CREATE TABLE $db_table_league_bowlers (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	league_id INT NOT NULL,
	bowler_id INT NOT NULL,
	join_date TIMESTAMP
	)";
	$conn->exec($sql);
	
	$sql = "CREATE TABLE $db_table_lotteries (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	league_id INT NOT NULL,
	bowler_id INT NOT NULL,
	lottery_id INT NOT NULL,
	tickets INT NOT NULL DEFAULT 0,
	pins_knocked INT
	)";
	$conn->exec($sql);
	
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$sql = "SELECT 1 FROM $db_table_bowlers LIMIT 1"; $stmt = $conn->prepare($sql); $stmt->execute();
	$sql = "SELECT 1 FROM $db_table_leagues LIMIT 1"; $stmt = $conn->prepare($sql); $stmt->execute();
	$sql = "SELECT 1 FROM $db_table_league_bowlers LIMIT 1"; $stmt = $conn->prepare($sql); $stmt->execute();
	$sql = "SELECT 1 FROM $db_table_lotteries LIMIT 1"; $stmt = $conn->prepare($sql); $stmt->execute();
	echo "Tables created successfully<br>";
}
catch(PDOException $e) {
	processError($e);
}

?>
