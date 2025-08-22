<?php
include_once("system/pg.php");
$hash = trim($_GET['hash']);
$login = trim($_GET['login']);
$exists = pgQuery("SELECT * FROM accounts WHERE login = '$login' and password = '$hash';");
if (count($exists)) {
	session_start();
	$_SESSION['authed'] = true;
	$_SESSION['user_id'] = $exists[0]["id"];
	$_SESSION['username'] = $exists[0]["login"];
	var_dump($_SESSION['authed']);
	echo "<script>window.location.href='index.php'</script>";
} else {
	header("Location: landing.html");
}
?>