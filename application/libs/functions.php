<?php

	/* FUNCTION: TO ENCRYPT STRING */
	function encrypt($key, $plain_text) {
		
		$c_t = crypt($plain_text,$key);
		return base64_encode($c_t);
		
	}		
	
	/* FUNCTION: TO CLEAN STRING */
	function cleanString($string) {
		
		$string = str_replace('"', "''", $string);

		global $db;
		$string = mysqli_real_escape_string($db->connection,$string);
	
		return $string;
		
	}
	
	/* FUNCTION: TO GENERATE A RANDOM PASS */
	function randomPassword() {
	    $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
	    $pass = array();
	    $alphaLength = strlen($alphabet) - 1;
	    for ($i = 0; $i < 8; $i++) {
	        $n = rand(0, $alphaLength);
	        $pass[] = $alphabet[$n];
	    }
	    return implode($pass);
	}
	
?>