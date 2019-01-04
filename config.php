<?php
//Whois Domain Expiration-Checker by Doorman
//d@omit.io
//https://github.com/doorman1
//
//
//
//date_default_timezone_set('Asia/Calcutta');
$approot = "https://your.domain/whois"; 
$dbhost  = 'localhost';    // Unlikely to require changing
$dbname  = 'whois_one';    // Modify these...
$dbuser  = 'doorman';      // ...variables according  
$dbpass  = 'doorman123';     // ...to your installation  
$appname = "Whois Report"; // ...and preference

$mailer_mailhost = "";
$mailer_userid = '';
$mailer_password = '';
$mailer_secure = 'tls';
$mailer_port = 587;
$mailer_from = "";
$mailer_from_name = "Host Info";

/* Load configuration */

# time interval (in days) between consecutive checks for a single WORD
$word_check_interval = 30;

# time interval (in seconds) between consecutive checks for a single TLD
$tld_check_interval = 10;

# time interval (in days) between consecutive checks for a single DOMAIN
$domain_check_interval = 30;

# retrieve domains expiring in N number of days
$domain_expiring_days = 30;

# Max Length of a domain name
$max_domain_length = 5;

# include domains in email which expired in the past N number of days
$email_days_before = 2;

# include domains in email which will expire in the coming N number of days
$email_days_after = 2;

$send_mail_to_name = "";
$send_mail_to = "whateveryouwant@your.domain";
$mail_subject = "Expiring / Deleted domains email report";
$mail_body = "This is a report mailer for expiring / deleted domains";

$log_file = "log.txt";

$connection = new mysqli($dbhost, $dbuser, $dbpass, $dbname);  
if ($connection->connect_error) die($connection->connect_error);

$salt1 = "qm&h*";
$salt2 = "pg!@";
$salt3 = 24324;   //' default pass : stepupAC for usertype=3, bromilow for usertype=2

  function createTable($name, $query)
  {
    queryMysql("CREATE TABLE IF NOT EXISTS $name($query)");
    echo "Table '$name' created or already exists.<br>";
  }

  function queryMysql($query)
  {
    global $connection;
    $result = $connection->query($query);
    if (!$result) die($connection->error);
    return $result;
  }
  
  function multiQueryMysql($query)
  {
    global $connection;
    $result = $connection->multi_query($query);
    if (!$result) die($connection->error);
    return $result;
  }

  function destroySession()
  {
    $_SESSION=array();

    if (session_id() != "" || isset($_COOKIE[session_name()]))
      setcookie(session_name(), '', time()-2592000, '/');

    session_destroy();
  }

  function sanitizeString($var)
  {
    global $connection;
    $var = strip_tags($var);
    $var = htmlentities($var);
    $var = stripslashes($var);
    return $connection->real_escape_string($var);
  } 
  
  function escapeString($var)
  {
    global $connection;
    //$var = strip_tags($var);
    return $connection->real_escape_string($var);
  } 
  
  function lastInsertID()
  {
      global $connection;
      return mysqli_insert_id($connection);
  }
  
  function affectedRows()
  {
      global $connection;
      return mysqli_affected_rows($connection);
  }
  
  function mysqlErrorNumber()
  {
      global $connection;
      return mysqli_errno($connection);
  }

  function readConfigFile()
  {
    global $word_check_interval, $tld_check_interval, $domain_check_interval, $domain_expiring_days, $max_domain_length;
    global $send_mail_to, $send_mail_to_name, $mail_subject, $mail_body, $email_days_after, $email_days_before;
    global $mailer_mailhost, $mailer_userid, $mailer_password, $mailer_secure, $mailer_port, $mailer_from, $mailer_from_name;
    
    $handle = fopen("config", "r") or die("Can't find the configuration file."); 

    while (!feof($handle)) // Loop till end of file.
    {
        $buffer = fgets($handle, 2048); // Read a line.

        $buffer = trim($buffer);
        if(substr($buffer,0,1) <> "#" and strpos($buffer, "=") !== false) {
          list($str, $val) = explode("=", $buffer);
          
          $str = trim($str);
          $val = trim($val);
          if(strlen($str) > 1) {
            switch($str) {
              case "word_check_interval":
                $word_check_interval = $val;
                break;
              case "tld_check_interval":
                $tld_check_interval = $val;
                break;
              case "domain_check_interval":
                $domain_check_interval = $val;
                break;
              case "domain_expiring_days":
                $domain_expiring_days = $val;
                break;
              case "send_mail_to_name":
                $send_mail_to_name = $val;
                break;
              case "send_mail_to":
                $send_mail_to = $val;
                break;
              case "mail_subject":
                $mail_subject = $val;
                break;
              case "mail_body":
                $mail_body = $val;
                break;
              case "max_domain_length":
                $max_domain_length = $val;
                break;
              case "mailer_mailhost":
                $mailer_mailhost = $val;
                break;
              case "mailer_userid":
                $mailer_userid = $val;
                break;
              case "mailer_password":
                $mailer_password = $val;
                break;
              case "mailer_secure":
                $mailer_secure = $val;
                break;
              case "mailer_port":
                $mailer_port = $val;
                break;
              case "mailer_from":
                $mailer_from = $val;
                break;
              case "mailer_from_name":
                $mailer_from_name = $val;
                break;
              case "email_days_before":
                $email_days_before = $val;
                break;
              case "email_days_after":
                $email_days_after = $val;
                break;
            }
          }
        }
    }
    fclose($handle);
  }

?>
