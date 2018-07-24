<?php

	//Prepare
    $host = htmlspecialchars($_GET["hostname"]);
    $ipv4 = htmlspecialchars($_GET["ipv4"]);
    $ipv6 = htmlspecialchars($_GET["ipv6"]);
	require '../private/config.inc.php';


	//Create Database Connection
	try 
	{
		$conn = new PDO("mysql:host=$dbserver;dbname=admin_ddns", $dbuser, $dbpass);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch(PDOException $e) {}


	//check whether we require an update
	try
	{
		$current = $conn->query("SELECT * FROM current where hostname = '{$host}' limit 1")->fetchObject(); 
		if( !empty($current) ){
			if( $current->ipv4 != $ipv4 || $current->ipv6 != $ipv6 ) { $update_required = true; }
		} else { $update_required = true; $new_entry = true;};
	} catch(PDOException $e) {}


	//Insert or update
	if ($update_required == true){
		try 
		{
			if( $new_entry == true ) {
				$conn->exec(
					"INSERT INTO current (hostname, ipv4, ipv6, update_required) 
					VALUES ('{$host}', '{$ipv4}', '{$ipv6}', true)" );
				
			} else {
				$conn->exec(
					"UPDATE current
					SET ipv4 = '{$ipv4}', ipv6 = '{$ipv6}', timestamp = now(), update_required = true
					where hostname = '{$host}'"	);
			}
		} catch(PDOException $e) {}
	}


	//close connection
	$conn = null;
?>