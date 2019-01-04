<?php
//Whois Domain Expiration-Checker by Doorman
//
    require 'vendor/autoload.php';
    require 'config.php';

    function loadValuesFromFile($file_name)
    {
        global $max_domain_length;

        $handle = fopen($file_name, "r") or die("Can't find the $file_name file."); //read line one by one
        $values = ""; $first = true;
    
        while (!feof($handle)) // Loop till end of file.
        {
            $buffer = fgets($handle, 4096); // Read a line.
    
            $buffer = trim($buffer);
            $len = strlen($buffer);
            if($len > 0 && $len <= $max_domain_length && !preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $buffer)) {
                if($first) {
                    $first = false;
                }
                else {
                    $values = $values.",";
                }
                $values = $values."('".$buffer."')"; 
            }
        }
        fclose($handle);

        return($values);
    }

    set_time_limit (0);
    file_put_contents($log_file, date("Y-m-d H:i:s")." => Start\n", FILE_APPEND);
    //echo "============Execution Started==============<br/>";

    readConfigFile();

    use MallardDuck\Whois\Client;

    $client = new Client;

    /* Load dictionary */
    $values = loadValuesFromFile("words.txt");

    $queryStr = "INSERT IGNORE INTO words(word) VALUES $values";
    //echo $queryStr;
    $res = queryMysql($queryStr);
    file_put_contents($log_file, date("Y-m-d H:i:s")." => Total Words Added = ".affectedRows()."\n", FILE_APPEND);
    //echo "<br/> Total Words Added = ".affectedRows();

    $values = loadValuesFromFile("tlds.txt");

    $queryStr = "INSERT IGNORE INTO tlds(tld_name) VALUES $values";
    
    $res = queryMysql($queryStr);
    file_put_contents($log_file, date("Y-m-d H:i:s")." => Total TLDs Added = ".affectedRows()."\n", FILE_APPEND);
    //echo "<br/> Total TLDs Added = ".affectedRows();

    /* Check dictionary words */
    $res_1 = queryMysql("SELECT word from words WHERE last_checked < NOW() - INTERVAL $word_check_interval DAY");
    while($wordRows = mysqli_fetch_row($res_1)) {
        $res_2 = queryMysql("SELECT tld_name from tlds where last_checked < NOW() - INTERVAL $tld_check_interval SECOND");
        /* Check each TLD */

        while($tldRows = mysqli_fetch_row($res_2)) {
           $domain = $wordRows[0] . "." . $tldRows[0];
           $res_3 =  queryMysql("SELECT domain_name from domains where domain_name='$domain' AND (last_checked > NOW() - INTERVAL $domain_check_interval DAY OR expiry_date > NOW() + INTERVAL $domain_expiring_days DAY)");
           if($domRows = mysqli_fetch_row($res_3)) {
               // do Nothing
           }
           else {
                /* Check if IP allotted to the domain */
                if ( gethostbyname($domain) != $domain ) {
                    try {
                        $results = $client->lookup($domain);

                        $count = preg_match ( '/Expir.*?\n/' , $results, $matches );
                        $res = queryMysql("UPDATE tlds SET last_checked=NOW() WHERE tld_name='{$tldRows[0]}'");
                        
                        if(count($matches) > 0) {
                            $darray = explode(": ", $matches[0]);
                            //echo $results;
                            //echo "<pre>";
                            //print_r($matches);
                            if(count($darray) > 1) {
                                $exp = strtotime($darray[1], time());
                                $exp = gmdate("Y-m-d H:i:s", $exp);
                                //$exp = strftime("&#37;D", $exp);
                                file_put_contents($log_file, date("Y-m-d H:i:s")." => Domain: $domain | Expiry : $exp \n", FILE_APPEND);
                                echo "\nDomain: $domain | Time : $exp";
                                $queryStr = "INSERT INTO domains (domain_name, domain_status, expiry_date, last_checked) VALUES ('$domain', 'Unknown', '$exp', NOW()) ".
                                    " ON DUPLICATE KEY UPDATE domain_status = 'Unknown', expiry_date='$exp', last_checked = NOW()";
                                $res = queryMysql($queryStr);
                            }
                        }
                    }
                    catch(Exception $e) {
                        echo "\n$domain: " .$e->getMessage(). "\n";
                    }

                }
                else {
                    $queryStr = "UPDATE domains SET domain_status='Deleted', last_checked = NOW() WHERE domain_name='$domain'";
                    $res = queryMysql($queryStr);
                }
        
           }
           mysqli_free_result($res_3);
        }
        mysqli_free_result($res_2);
        $res = queryMysql("UPDATE words SET last_checked = NOW() WHERE word='" . $wordRows[0]. "'");
    }
    mysqli_free_result($res_1);
     
    file_put_contents($log_file, date("Y-m-d H:i:s")." => End\n", FILE_APPEND);
?>
