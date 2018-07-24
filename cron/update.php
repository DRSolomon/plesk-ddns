<?php
    
    //prepare
    require 'config.inc.php';
    $zone_ids   = array();  //Array for Zone-ID's to update in psa tables
    $domains    = array();  //Array for Domains to update via dnsmng
    $do_update  = false;

    //Create Database Connection
	try 
	{
		$conn = new PDO("mysql:host=$dbserver", $dbuser, $dbpass);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch(PDOException $e) {}

    //get all entries to update
	try
	{
        foreach( $conn->query("SELECT * FROM admin_ddns.current where update_required = true") as $current ) {
            if( empty($current['ipv4']) )     { continue; }
            if( empty($current['hostname'] )) { continue; }
			
            $conn->exec(
                "UPDATE psa.dns_recs
                SET val = '{$current['ipv4']}', displayVal = '{$current['ipv4']}', time_stamp = now()
                where type = 'A' and host = '{$current['hostname']}.'" );

            $zone_id = $conn->query("SELECT dns_zone_id FROM psa.dns_recs where type = 'A' and host = '{$current['hostname']}.' limit 1")->fetchColumn();
            $zone_ids[] = $zone_id;
            
            //should be done at a later stage. but is good for now
            $conn->exec(
                "UPDATE admin_ddns.current 
                set update_required = false
                where hostname = '{$current['hostname']}'" );
        }
    } catch(PDOException $e) {}
   
        
    //Now Update the DNS Serials
    try {
        $zone_ids = array_unique($zone_ids);
        foreach ( $zone_ids as $zone_id ) {
            $serial_new = ((int)date("Ymd"))*100;
            $serial_old = $conn->query("select serial from psa.dns_zone where id = '{$zone_id}'")->fetchColumn();
            $counter = 1;
            while ( $serial_new <= $serial_old ) {
                if ($counter == 99) { $serial_new = null; break; }
                $serial_new++;
                $counter++;
            }

            if (!empty($serial_new)) {
                $conn->exec(
                    "UPDATE psa.dns_zone
                    SET serial = '{$serial_new}'
                    where id = '{$zone_id}'" );

                $domain = $conn->query("select name from psa.dns_zone where id = '{$zone_id}'")->fetchColumn();
                $domains[] = $domain;

                $do_update = true;
            }
        }
    } catch(PDOException $e) {}

    //now run the update
    if( $do_update ) {
        $domains = array_unique($domains);
        foreach ( $domains as $domain ) { 
            shell_exec("/opt/psa/admin/bin/dnsmng --update {$domain}");
            //trigger buddy-ns synchronisation
            sleep(40); //30 seconds where sufficient, but let's give some more time
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Token {$buddynstoken}"));
            curl_setopt($curl, CURLOPT_URL, "https://www.buddyns.com/api/v2/sync/{$domain}");
            curl_exec($curl);
            curl_close($curl);
        }
    }

    //Close Database Connection
    $conn = null;
?>