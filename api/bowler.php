<?php 

require_once "db.php";
define("BAD_LOTTERY_ID", 0);









//////////////////////////////

define("ERROR_LEAGUE_NOT_FOUND", "League not found.");
define("ERROR_BOWLERS_NOT_IN_LEAGUE", "Bowler not in the league.");
define("ERROR_NO_BOWLER_IN_LOTTERY", "No bowlers entered the lottery in the league.");
define("ERROR_NON_WINNER_ATTEMPTING", "The user attempting is not the winner.");
define("ERROR_BOWLER_NOT_IN_LOTTERY", "Bowler not in current lottery.");
define("ERROR_INVALID_NAME", "Invalid name format.");
define("ERROR_INVALID_DESCRIPTION", "Invalid description format.");
define("ERROR_INVALID_CAPACITY", "Invalid capacity.");
define("ERROR_INVALID_EMAIL", "Invalid email format.");
define("ERROR_EMAIL_ALREADY_EXIST", "Email already exists.");
define("ERROR_BOWLER_NOT_FOUND", "Bowler not found.");
define("ERROR_BOWLER_ALREADY_JOIN_LEAGUE", "Bowler has already joined the league.");
define("ERROR_LEAGUE_REACH_CAPACITY", "League has reached its capacity.");
define("ERROR_NO_BOWLER_IN_LEAGUE", "No bowlers in the league.");
define("ERROR_NO_LEAGUE", "No leagues found.");
define("ERROR_NO_BOWLER", "No bowlers found.");



/////////////////////////////////////


//require_once "extra_function.php"
//echo "Inside Bowler.php";
if ($_SERVER["REQUEST_METHOD"] == "POST") {

	if($_POST["url"] == "createBowler"){
	
	$firstname = $_POST["firstname"];
	$lastname = $_POST["lastname"];
	$email = $_POST["email"];
	$password = hash('sha256',$_POST["password"]);

	//echo "Details ..... " . $firstname . $lastname .$email . $password;	
	 //exit;
	try {
		$conn = connectDb();
		
		$sql = "SELECT * FROM $db_table_bowlers WHERE email = '$email'";
		//echo "<br>" . $sql;
		$stmt = $conn->prepare($sql);
		//$stmt->bindParam(':email', $email);
		$stmt->execute();
		if ($stmt->rowCount() > 0){
			echo encodeError("Email is Already exist");
			return true;
		}
		$sql = "INSERT INTO $db_table_bowlers (firstname, lastname, email, password, reg_date) VALUES ('$firstname', '$lastname', '$email', '$password', NOW())";
		$conn->exec($sql);
		echo json_encode(array('id' => $conn->lastInsertId("id")."  ",'bowler name' => $firstname ." ".$lastname."  ",'Email' => $email));
	}
	catch(PDOException $e) {
		echo $e->getMessage() . "<br>";
	}

	}


	elseif($_POST["url"] == "createLeague"){
	
		$name = $_POST["name"];
	//$ticket_price = (int) purifyInput($_POST["ticket_price"]);
		$ticket_price = 1; #DEFAULT_TICKET_PRICE;									//DEFAULT_TICKET_PRICE = $1
		$descr = $_POST["descr"];
		$capacity = (int)$_POST["capacity"];
		


		try {
		$conn = connectDb();
		$sql = "INSERT INTO $db_table_leagues (name, ticket_price, capacity, estab_date, descr) VALUES ('$name', $ticket_price, $capacity, NOW(), '$descr')";
		$conn->exec($sql);
		echo json_encode(array("id" => $conn->lastInsertId("id")."  ", 'League Name' => $name."  ",'Description' => $descr));
	}
	catch(PDOException $e) {
		echo $e->getMessage() . "<br>";
	}
	


	}

	elseif($_POST["url"] == "addBowler"){
	
		$leagueId = (int) $_POST["league_id"];
		$bowlerId = (int) $_POST["bowler_id"];
		//echo "<br>" . "RE in TRY............"."<br>";
			
	
	try {
		$conn = connectDb();
		$record = findLeagueRecord($conn, $leagueId);
		if (is_null($record))
			return echoError("Leage Not Found");
		$record = findBowlerRecord($conn, $bowlerId);
		if (is_null($record))
			return echoError("Bowler Not found");
		
		$record = findLeagueBowlerRecord($conn, $leagueId, $bowlerId);
		if (!is_null($record))
			return echoError("Bowler already joined league");
		/*
		$count = findLeagueBowlersCount($conn, $leagueId);
		if ($capacity != 0 && $count >= $capacity)
			//return echoError(ERROR_LEAGUE_REACH_CAPACITY);
		*/
		$sql = "INSERT INTO $db_table_league_bowlers (league_id, bowler_id, join_date) VALUES ($leagueId, $bowlerId, NOW())";
		$conn->exec($sql);
		echo json_encode(array("id" => $conn->lastInsertId("id"), 'League Id' =>$leagueId."  ",'Bowler Id' => $bowlerId));
	}
	catch(PDOException $e) {
		echo $e->getMessage() . "<br>";
	}

	}
	
	elseif($_POST["url"] == "lottery_ticket"){
		$leagueId = (int) $_POST["league_id"];
		$bowlerId = (int) $_POST["bowler_id"];
		$ticketChange = (int)$_POST["tickets"];

	try {
		$conn = connectDb();
		$lotteryId = updateLeagueLottery($conn, $leagueId);
		if ($lotteryId == BAD_LOTTERY_ID)
			return echoError(ERROR_LEAGUE_NOT_FOUND);
			
		$bowlerRecord = findLeagueBowlerRecord($conn, $leagueId, $bowlerId);
		if (is_null($bowlerRecord))
			return echoError(ERROR_BOWLERS_NOT_IN_LEAGUE);
		
		$lotteryRecord = findLotteryRecord($conn, $leagueId, $bowlerId, $lotteryId);
		$curTickets = $lotteryRecord["tickets"];
		$curTickets += $ticketChange;
		
		$leagueRecord = findLeagueRecord($conn, $leagueId);
		$lotteryPool = $leagueRecord["lottery_pool"];
		$lotteryPool += $ticketChange;
		
		$sql = "UPDATE $db_table_lotteries SET tickets = $curTickets WHERE league_id = $leagueId AND bowler_id = $bowlerId AND lottery_id = $lotteryId";
		$conn->exec($sql);
		
		$sql = "UPDATE $db_table_leagues SET lottery_pool = $lotteryPool WHERE id = $leagueId";
		$conn->exec($sql);
		echo json_encode(array("lotteryId" => $lotteryId, "newTickets" => $curTickets, "newLotteryPool" => $lotteryPool));
	}
	catch(PDOException $e) {
		processError($e);
	}
	}

	elseif($_POST["url"] == "lottery_winner"){
	
		$leagueId = (int)$_POST["league_id"];

	try {
		$conn = connectDb();
		$lotteryId = updateLeagueLottery($conn, $leagueId);
		if ($lotteryId == BAD_LOTTERY_ID)
			return echoError(ERROR_LEAGUE_NOT_FOUND);
		
		$leagueRecord = findLeagueRecord($conn, $leagueId);
		$lotteryPool = $leagueRecord["lottery_pool"];
		
		$lotteryTickets = findLotterySum($conn, $leagueId, $lotteryId);
		if ($lotteryTickets <= 0)
			return echoError(ERROR_NO_BOWLER_IN_LOTTERY);
		$ticketId = mt_rand(1, $lotteryTickets);
		
		$lotteryWinner = findLotteryWinner($conn, $leagueId, $lotteryId, $ticketId);

		$lotteryWinnerId = $lotteryWinner["bowler_id"];
		$sql = "UPDATE $db_table_leagues SET lottery_winner = $lotteryWinnerId WHERE id = $leagueId";
		$conn->exec($sql);
		echo json_encode(array("lotteryId" => $lotteryId, "lotteryPool" => $lotteryPool, "lotteryWinner" => $lotteryWinnerId, ));
	}
	catch(PDOException $e) {
		processError($e);
	}

	}

	elseif($_POST["url"] == "lottery_attemp"){

		$leagueId = (int) $_POST["league_id"];
		$bowlerId = (int) $_POST["bowler_id"];
		$pins_knocked = (int)$_POST["pins_knocked"];

	try {
		$conn = connectDb();
		$lotteryId = updateLeagueLottery($conn, $leagueId);
		if ($lotteryId == BAD_LOTTERY_ID)
			return echoError(ERROR_LEAGUE_NOT_FOUND);
		
		$leagueRecord = findLeagueRecord($conn, $leagueId);
		$lotteryPool = $leagueRecord["lottery_pool"];
		$lotteryWinnerId = $leagueRecord["lottery_winner"];
		
		if ($bowlerId != $lotteryWinnerId)
			return echoError(ERROR_NON_WINNER_ATTEMPTING);
		
		$bowlerRecord = findBowlerRecord($conn, $bowlerId);
		$bowlerPayouts = $bowlerRecord["payouts"];
		
		if ($pins_knocked >= TARGET_PINS)
			$earned = $lotteryPool;
		else
			$earned = (int) floor($lotteryPool / 10);
		$bowlerPayouts += $earned;
		$lotteryPool -= $earned;
		
		$sql = "UPDATE $db_table_lotteries SET pins_knocked = $pins_knocked WHERE league_id = $leagueId AND bowler_id = $bowlerId AND lottery_id = $lotteryId";
		$conn->exec($sql);
		
		$sql = "UPDATE $db_table_bowlers SET payouts = $bowlerPayouts WHERE id = $bowlerId";
		$conn->exec($sql);
		
		$sql = "UPDATE $db_table_leagues SET lottery_pool = $lotteryPool, lottery_winner = NULL WHERE id = $leagueId";
		$conn->exec($sql);
		echo json_encode(array("lotteryId" => $lotteryId, "newLotteryPool" => $lotteryPool, "newPayouts" => $bowlerPayouts, "earned" => $earned));
	}
	catch(PDOException $e) {
		processError($e);
	}
		
	}



}


if ($_SERVER["REQUEST_METHOD"] == "GET") {

	if($_GET["url"] == "allBowler"){

	try {
		$conn = connectDb();
		$sql = "SELECT id, firstname, lastname, email, reg_date, payouts FROM $db_table_bowlers";
		$stmt = $conn->prepare($sql);
		$stmt->execute();

		$result = $stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() == 0)
			return echoError("No Bowler.");

		echo json_encode($stmt->fetchAll());
	}
	catch(PDOException $e) {
		processError($e);
	}
	}

	elseif($_GET["url"] == "allLeague"){

		//echo "Inside Leaguee...........";exit;
		try {
		$conn = connectDb();
		$sql = "SELECT * FROM $db_table_leagues";
		$stmt = $conn->prepare($sql);
		$stmt->execute();

		$result = $stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() == 0)
			return echoError(ERROR_NO_LEAGUE);

		echo json_encode($stmt->fetchAll());
	}
	catch(PDOException $e) {
		processError($e);
	}	
	}


	elseif($_GET["url"] == "idBowler"){
		$bowlerId = (int) $_GET["bowler_id"];
	
	try {
		$conn = connectDb();
		$record = findBowlerRecord($conn, $bowlerId);
		if (is_null($record))
			return echoError(ERROR_BOWLER_NOT_FOUND);
		echo json_encode($record);
	}
	catch(PDOException $e) {
		processError($e);
	}
	}

	elseif($_GET["url"] == "idLeague"){
	
		$leagueId = (int)$_GET["league_id"];
	
		try {
			$conn = connectDb();
			$record = findLeagueRecord($conn, $leagueId);
		
			if (is_null($record))
				return echoError(ERROR_LEAGUE_NOT_FOUND);
			echo json_encode($record);
		}
		catch(PDOException $e) {
			processError($e);
		}
	
	}

	elseif($_GET["url"] == "allBowlerId"){
		$leagueId = (int)$_GET["league_id"];

	try {
		$conn = connectDb();
		$sql = "SELECT * FROM $db_table_league_bowlers WHERE league_id = :league_id";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':league_id', $leagueId, PDO::PARAM_INT);
		$stmt->execute();
		
		$result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
		
		if ($stmt->rowCount() == 0)
			return echoError(ERROR_NO_BOWLER_IN_LEAGUE);
		
		echo json_encode($stmt->fetchAll());
	}
	catch(PDOException $e) {
		processError($e);
	}
	}

	elseif($_GET["url"] == 'lottery_winner'){
		$leagueId = (int) $_GET["league_id"];

	try {
		$conn = connectDb();
		$lotteryId = updateLeagueLottery($conn, $leagueId);
		if ($lotteryId == BAD_LOTTERY_ID)
			return echoError(ERROR_LEAGUE_NOT_FOUND);
		
		$leagueRecord = findLeagueRecord($conn, $leagueId);
		$lotteryPool = $leagueRecord["lottery_pool"];
		$lotteryWinnerId = $leagueRecord["lottery_winner"];
		echo json_encode(array("lotteryId" => $lotteryId, "lotteryPool" => $lotteryPool, "lotteryWinner" => $lotteryWinnerId, ));
	}
	catch(PDOException $e) {
		processError($e);
	}
	}

	elseif($_GET["url"] == 'lottery_ticket'){
		$leagueId = (int) $_GET["league_id"];
		$bowlerId = (int) $_GET["bowler_id"];
	
	try {
		$conn = connectDb();
		$lotteryId = updateLeagueLottery($conn, $leagueId);
		if ($lotteryId == BAD_LOTTERY_ID)
			return echoError(ERROR_LEAGUE_NOT_FOUND);
		
		$bowlerRecord = findLeagueBowlerRecord($conn, $leagueId, $bowlerId);
		if (is_null($bowlerRecord))
			return echoError(ERROR_BOWLERS_NOT_IN_LEAGUE);
		
		$leagueRecord = findLeagueRecord($conn, $leagueId);
		
		$lotteryRecord = findLotteryRecord($conn, $leagueId, $bowlerId, $lotteryId);
		$ticketPrice = $leagueRecord["ticket_price"];
		$lotteryPool = $leagueRecord["lottery_pool"];
		$curTickets = $lotteryRecord["tickets"];
	
		echo json_encode(array("ticketPrice" => $ticketPrice, "lotteryId" => $lotteryId, "tickets" => $curTickets, "lotteryPool" => $lotteryPool));
	}
	catch(PDOException $e) {
		processError($e);
	}
	}

	elseif($_GET["url"] == 'lottery_attemp'){
		
		$leagueId = (int) $_GET["league_id"];
		$bowlerId = (int) $_GET["bowler_id"];

	try {
		$conn = connectDb();
		$lotteryId = updateLeagueLottery($conn, $leagueId);
		if ($lotteryId == BAD_LOTTERY_ID)
			return echoError(ERROR_LEAGUE_NOT_FOUND);
		
		$sql = "SELECT pins_knocked FROM $db_table_lotteries WHERE league_id = $leagueId AND bowler_id = $bowlerId AND lottery_id = $lotteryId";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(":bowler_id", $bowlerId);
		$stmt->execute();
		
		$result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
		
		if ($stmt->rowCount() == 0)
			return echoError(ERROR_BOWLER_NOT_IN_LOTTERY);
		
		echo json_encode(array('League Id'=>$leagueId." ",'Bowler Id'=>$bowlerId." ",$stmt->fetch()));
	}
	catch(PDOException $e) {
		processError($e);
	}


	}


}

/**
 * Find the record for a specific league
 * @param PDO $conn PDO object to the database
 * @param int $leagueId League's id
 * @return array An row of league data
 */

function findLeagueRecord($conn, $leagueId) {
	global $db_table_leagues;
	$sql = "SELECT * FROM $db_table_leagues WHERE id = $leagueId";
	
	$stmt = $conn->prepare($sql);
	$stmt->execute();
	
	$result = $stmt->setFetchMode(PDO::FETCH_ASSOC);

	if ($stmt->rowCount() == 0)
		return null;

	return $stmt->fetch();
}

/**
 * Find the total number of bowlers in a specific league
 * @param PDO $conn PDO object to the database
 * @param int $leagueId League's id
 * @return int The total number
 */
function findLeagueBowlersCount($conn, $leagueId) {
	global $db_table_league_bowlers;

	$sql = "SELECT COUNT(league_id) AS totalBowlers FROM $db_table_league_bowlers WHERE league_id = :league_id";
	$stmt = $conn->prepare($sql);
	$stmt->bindParam(':league_id', $leagueId, PDO::PARAM_INT);
	$stmt->execute();

	$result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
	return $stmt->fetch()["totalBowlers"];
}

/**
 * Find the record for a specific bowler in a specific league
 * @param PDO $conn PDO object to the database
 * @param int $leagueId League's id
 * @param int $bowlerId Bowler's id
 * @return array An row of bowler data in the league
 */
function findLeagueBowlerRecord($conn, $leagueId, $bowlerId) {
	global $db_table_league_bowlers;

	$sql = "SELECT * FROM $db_table_league_bowlers WHERE league_id = :league_id AND bowler_id = :bowler_id";
	$stmt = $conn->prepare($sql);
	$stmt->bindParam(':league_id', $leagueId, PDO::PARAM_INT);
	$stmt->bindParam(':bowler_id', $bowlerId, PDO::PARAM_INT);
	$stmt->execute();

	$result = $stmt->setFetchMode(PDO::FETCH_ASSOC);

	if ($stmt->rowCount() == 0)
		return null;

	return $stmt->fetch();
}


/**
 * Find the record for a specific bowler
 * @param PDO $conn PDO object to the database
 * @param int $bowlerId Bowler's id
 * @return array An row of bowler data
 */
function findBowlerRecord($conn, $bowlerId) {
	global $db_table_bowlers;
	
	$sql = "SELECT id, firstname, lastname, email, reg_date, payouts FROM $db_table_bowlers WHERE id = :id";
	$stmt = $conn->prepare($sql);
	$stmt->bindParam(':id', $bowlerId, PDO::PARAM_INT);
	$stmt->execute();

	$result = $stmt->setFetchMode(PDO::FETCH_ASSOC);

	if ($stmt->rowCount() == 0)
		return null;

	return $stmt->fetch();
}



/////////////////////////////////////////////



/**
 * Get the current id for a lottery event
 * @return int An id unique to current week
 */
function lotteryId() {
	$date = new DateTime();
	return ((int) $date->format("Y")) * 100 + ((int) $date->format("W"));		//e.g. 201501 = 1st Week of 2015
}

/**
 * Get the current id for a lottery event
 * Update league data if they are outdated
 * @param PDO $conn PDO object to the database
 * @param int $leagueId League's id
 * @return int Current lottery event week id
 */
function updateLeagueLottery($conn, $leagueId) {
	global $db_table_leagues;
	
	$record = findLeagueRecord($conn, $leagueId);
	if (is_null($record)) {
		return BAD_LOTTERY_ID;
	}
	
	$lastLotteryId = $record["lottery_id"];
	$curLotteryId = lotteryId();
	if ($lastLotteryId != $curLotteryId) {
		//Update League Lottery
		$sql = "UPDATE $db_table_leagues SET lottery_id = $curLotteryId, lottery_winner = NULL WHERE id = $leagueId";
		$conn->exec($sql);
	}
	return $curLotteryId;
}

/**
 * Create a lottery record for a specific bowler in a specific league
 * @param PDO $conn PDO object to the database
 * @param int $leagueId League's id
 * @param int $bowlerId Bowler's id
 * @param int $lotteryId Lottery event's id
 * @return array The row created
 */
function createLotteryRecord($conn, $leagueId, $bowlerId, $lotteryId) {
	global $db_table_lotteries;
	
	$sql = "INSERT INTO $db_table_lotteries (league_id, bowler_id, lottery_id) VALUES ($leagueId, $bowlerId, $lotteryId)";
	$conn->exec($sql);
	$lastId = $conn->lastInsertId("id");
	
	$sql = "SELECT * FROM $db_table_lotteries WHERE id = :id";
	$stmt = $conn->prepare($sql);
	$stmt->bindParam(':id', $lastId, PDO::PARAM_INT);
	$stmt->execute();
	
	$result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
	return $stmt->fetch();
}

/**
 * Find the current lottery record for a specific bowler in a specific league
 * @param PDO $conn PDO object to the database
 * @param int $leagueId League's id
 * @param int $bowlerId Bowler's id
 * @param int $lotteryId Lottery event's id
 * @return array The row of current lottery data
 */
function findLotteryRecord($conn, $leagueId, $bowlerId, $lotteryId) {
	global $db_table_lotteries;
	
	$sql = "SELECT * FROM $db_table_lotteries WHERE league_id = :league_id AND bowler_id = :bowler_id AND lottery_id = :lottery_id";
	$stmt = $conn->prepare($sql);
	$stmt->bindParam(':league_id', $leagueId, PDO::PARAM_INT);
	$stmt->bindParam(':bowler_id', $bowlerId, PDO::PARAM_INT);
	$stmt->bindParam(':lottery_id', $lotteryId, PDO::PARAM_INT);
	$stmt->execute();
	
	$result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
	
	if ($stmt->rowCount() == 0)
		return createLotteryRecord($conn, $leagueId, $bowlerId, $lotteryId);
	
	return $stmt->fetch();
}

/**
 * Find the total amount of tickets of all the bowlers in the current lottery event
 * @param PDO $conn PDO object to the database
 * @param int $leagueId League's id
 * @param int $lotteryId Lottery event's id
 * @return int The total amount of tickets
 */
function findLotterySum($conn, $leagueId, $lotteryId) {
	global $db_table_lotteries;

	$sql = "SELECT SUM(tickets) AS totalTickets FROM $db_table_lotteries WHERE league_id = :league_id AND lottery_id = :lottery_id";
	$stmt = $conn->prepare($sql);
	$stmt->bindParam(':league_id', $leagueId, PDO::PARAM_INT);
	$stmt->bindParam(':lottery_id', $lotteryId, PDO::PARAM_INT);
	$stmt->execute();

	$result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
	return $stmt->fetch()["totalTickets"];
}

/**
 * Find the record of the owner of the winning ticket
 * Choose the first bowler with ticket partial sum larger than or equal to $ticketId
 * Use SQL arithmetics to save bandwidth
 * @param PDO $conn PDO object to the database
 * @param int $leagueId League's id
 * @param int $lotteryId Lottery event's id
 * @param int $ticketId The winning ticket id (1 <= id <= findLotterySum)
 * @return array The row of the owner
 */
function findLotteryWinner($conn, $leagueId, $lotteryId, $ticketId) {
	global $db_table_lotteries;

	$sql = "
	SELECT SummedLotteries.* FROM (
		SELECT
			RawLotteries.*,
			@prevTickets := @prevTickets + RawLotteries.tickets AS PartialTicketSum
		FROM
			$db_table_lotteries AS RawLotteries, (SELECT @prevTickets := 0) AS InitVars
		WHERE league_id = :league_id AND lottery_id = :lottery_id
	) As SummedLotteries
	WHERE
		SummedLotteries.PartialTicketSum >= :ticket_id
	LIMIT 1";
	
	$stmt = $conn->prepare($sql);
	$stmt->bindParam(':league_id', $leagueId, PDO::PARAM_INT);
	$stmt->bindParam(':lottery_id', $lotteryId, PDO::PARAM_INT);
	$stmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_INT);
	$stmt->execute();

	$result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
	return $stmt->fetch();
}


//////////////////////////////////////////////////




















function processError($e) {
	echo $e->getMessage() . "<br>";
}


function echoError($desc) {
	echo encodeError($desc);
	return true;
}


function encodeError($desc) {
	return json_encode(array("error" => $desc));
}




?>
