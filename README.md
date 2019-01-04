#Whois-Domain-Expiration-Checker

A simple PHP script that checks recent domains that have expired, not been paid for, etc and are available against search terms you put in. You can limit the search by TLDR's (the thing after the period on a domain, like .com or .net), by name, by number of characters of the domain name, or even by when it was released.


1. Create database using the supplied "whois_one.sql.zip" file by importing it directly to your database
2. Unzip the "whois-complete.zip" to your server.
3. Edit the "config.php" file in the root folder and change the following parameters:
	$approot = "https://your.domain/whois"; // URL to the directory....not required
	$dbhost  = 'localhost';    // Unlikely to require changing
	$dbname  = 'whois_one';    // DATABASE NAME
	$dbuser  = 'doorman';      // DATABASE USER
	$dbpass  = 'doorman123';     // DATABASE PASSWORD 
4. Edit the "config" text file in the root folder and change the following:
	# email message
	send_mail_to_name = Doorman
	send_mail_to = d@omit.io
	mail_subject = Expiring / Deleted domains email report
	mail_body = Report for today is found in the attached text file. <br> Thanks.

	# send email configuration
	mailer_mailhost = smtp.gmail.com
	mailer_userid = yourgmailaccount@gmail.com
	mailer_password = yourgmailpassword
	mailer_secure = tls
	mailer_port = 587
	mailer_from = yourgmailacount@gmail.com
	mailer_from_name = Host Info
5. Delete the "log.txt" file in the root folder and make root folder writable by apache
6. Make sure to give correct permissions to email folder so it is writable by apache
7. Setup cron jobs to run "index.php" twice a week and "email.php" daily at any time convenient to you.


Enjoy!

Doorman
d@omit.io
https://github.com/doorman1/
