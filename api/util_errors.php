<?php
/**
 * @author      Xiaotian Le
 * @version     1.0
 * @since       2015-11-10
 */
require_once "util_errors.php";

define("MAX_NAME_LEN", 20);
define("MAX_DESCR_LEN", 1000);
define("MAX_PASSWORD_LEN", 64);
define("MAX_EMAIL_LEN", 100);

define("DEFAULT_TICKET_PRICE", 1);

define("endl", "<br>");

function hashPassword($password) {
	return hash('sha256', $password);;
}

function purifyInput($data) {
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}

function validateInput($data, $format) {
	switch ($format) {
		case "NAME":
			return $data != "" && strlen($data) <= MAX_NAME_LEN && preg_match("/^[a-zA-Z ]*$/", $data);
		case "DESCR":
			return strlen($data) <= MAX_DESCR_LEN;
		case "EMAIL":
			return $data != "" && strlen($data) <= MAX_EMAIL_LEN && filter_var($data, FILTER_VALIDATE_EMAIL);
		case "PASSWORD":
			return $data != "";
	}
	return false;
}

function encodeOuput($obj) {
	return json_encode($obj);
}

?>
