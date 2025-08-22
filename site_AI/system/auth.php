<?php 
session_start();
if (!isset($_SESSION['authed']) || !$_SESSION['authed']){
	header('Location: https://qna.habr.com/q/660864');
}

?>