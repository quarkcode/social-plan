<?php 
	
	setlocale(LC_TIME, "es_ES");

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	
	if(isset($_COOKIE['cookieControl']) || true) session_start();
	
	
	define("DOMAINPATH", $_SERVER['HTTP_HOST']);
	define("RELPATH", "../");
	define("APPPATH", RELPATH."application/");
	
	include APPPATH.'libs/main.php';

	/* *********** SISTEMA TRADUCCION **************** */
		$webLanguajes = array("es");

		if(!isset($_SESSION['language']))  $_SESSION['language'] = $webLanguajes[0];
		
		if(in_array($_GET['page'], $webLanguajes)) {
			$_SESSION['language']=$_GET['page'];
			$_GET['page'] = "index";
		}

		if( in_array($_GET['page'], $webLanguajes) && $_GET['val1'] != "" ) {
			$_SESSION['language']=$_GET['page'];
			$_GET['page'] = $_GET['val1'];
		}
		
		#GUARDO LA PAG ACTUAL
		if($_GET['page'] != "error" && !in_array($_GET['page'], $webLanguajes) ){
			$_SESSION['page_current'] = $_GET['page'];
			if($_SESSION['page_current']=="index") $_SESSION['page_current'] = "";
		}
	/* *********** FIN SISTEMA TRADUCCION **************** */

	include RELPATH . ( ( isset($_GET['f']) ) ? "admin/" : "" ) . ( ($_GET['page'] == '') ? 'index' : $_GET['page'] ) . ".phtml";

	/* PAGE NOT FOUND */
	if( !is_object($web) && !in_array($_GET['page'], $webLanguajes) ) {
		header("Location: /error/404");
		exit();
	}
	
	$web->launch();
	
?>

