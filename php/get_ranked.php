<?php

include 'global.php';
include 'validate_token.php';
$offsetModulo = 2;

$userID = $_GET['userID'];
$mode = $_GET['mode'];
$language = $_GET['language'];

// USING ROOT IS A SECURITY CONCERN
$user = 'root';
$pass = '';
$db = 'kamusi';

$con = mysqli_connect('localhost', $user, $pass, $db);

if (!$con) {
	die('Could not connect: ' . mysqli_error($con));
}

if(!in_array($mode, $acceptedModes)) {
	die("Got a strange mode as input!". $mode);
}

$mysqli = new mysqli('localhost', $user, $pass, $db);


$results_array = FALSE;

while($results_array === FALSE) {
	$word_id =lookForWord($userID, $mysqli); 
	$results_array = getDefinitions($word_id, $mysqli);
}

$jsonData = json_encode($results_array);
echo $jsonData;

function lookForWord($userID, $mysqli) {
	global $offsetModulo, $mode, $language;


//fetch the user in order to see which word is for him
	$stmt = $mysqli->prepare("SELECT * FROM game". $mode . " WHERE userid = ? AND language = ? ");
	$stmt->bind_param("si", $userID, $language );
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();

	$user_position = $row["position"];
	$user_offset = $row["offset"];

	$stmt->close();

//fetch the word that has as rank user s position+offset
	$sql =  "SELECT ID As ID, DefinitionID As DefinitionID, Rank As Rank FROM (";
	$sql.=	"SELECT w.ID, w.DefinitionID, r.Rank FROM rankedwords As r LEFT JOIN words As w ON r.Word = w.Word";
	$sql.=	") As sq WHERE sq.ID IS NOT NULL AND sq.DefinitionID IS NOT NULL AND sq.ID NOT IN (SELECT WordID FROM seengame".$mode." WHERE userid=? AND language = ?) AND sq.Rank = ? LIMIT 1;";

	$sum = intval($user_position) + intval($user_offset);

	$stmt = $mysqli->prepare($sql);

	echo "Tu m affiches Sa quand meme e espece de merde a ";
	if ($stmt === FALSE) {
		die ("Mysql Error: " . $mysqli->error);
	}

	$stmt->bind_param("sii", $userID, $language, $sum);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	$word_id = $row["ID"];

	echo "This is the word_id " . $word_id;
	return 232323;
	$stmt->close();
	if($result-> num_rows === 0){
		if($user_offset == 0) {
			$stmt = $mysqli->prepare("UPDATE game".$mode." SET position = position + 1 WHERE userid=? AND language = ?;");
			$stmt->bind_param("si", $userID, $language);
			$stmt->execute();
			$stmt->close();

			//Clean up the DB that stores the encountered words, else it become too big

			$stmt = $mysqli->prepare("DELETE FROM seengame".$mode." WHERE userid=? AND language = ? AND rank < ? ;");
			$stmt->bind_param("sii", $userID, $language, $sum);
			$stmt->execute();
			$stmt->close();

		}
		else {
			$stmt = $mysqli->prepare("UPDATE game".$mode." SET offset = offset + 1 WHERE userid=? AND language = ?;");
			$stmt->bind_param("si", $userID, $language);
			$stmt->execute();
			$stmt->close();		
		}
		return lookForWord($userID, $mysqli);
	}
	else {
		$stmt = $mysqli->prepare("INSERT INTO seengame".$mode." (userid ,wordid, language, rank) VALUES (?,?,?,?);");
		$stmt->bind_param("siii", $userID, $word_id, $language, $sum);
		$stmt->execute();
		$stmt->close();	
		if($user_offset > $offsetModulo){
			$stmt = $mysqli->prepare("UPDATE game".$mode." SET offset = 0 WHERE userid=? AND language = ?;");
			$stmt->bind_param("si", $userID, $language);
			$stmt->execute();
			$stmt->close();
		}
		else {

			$stmt = $mysqli->prepare("UPDATE game".$mode." SET offset = offset + 1 WHERE userid=? AND language = ?;");
			$stmt->bind_param("si", $userID, $language);
			$stmt->execute();
			$stmt->close();	
		}	
		return $word_id;
	}
}

function getDefinitions($word_id, $mysqli){
	$sql =  "SELECT sq.ID As WordID, sq.Word, sq.PartOfSpeech, d.ID As DefinitionID, d.Definition, d.GroupID, d.UserID As Author ";
	$sql .= "FROM (SELECT * FROM words WHERE ID=?) AS sq ";
	$sql .= "LEFT JOIN definitions As d ON sq.DefinitionID = d.GroupID WHERE d.GroupID IS NOT NULL";
	$sql .= " ORDER BY Votes desc;";

	$stmt = $mysqli->prepare($sql);
	if ($stmt === FALSE) {
		die ("Mysql Error: " . $mysqli->error);
	}

	$stmt->bind_param("i",  $word_id);
	$stmt->execute();
	$result = $stmt->get_result();

	if($result-> num_rows === 0){
		return FALSE;
	}
	else {

		while ($row = $result->fetch_assoc()) {
			$results_array[] = $row;
		}


		$stmt->close();
		return $results_array;
	}
}

?>
