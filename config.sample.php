<?php

return (object) array(

	'dev'	=> false
	//if true, debug output is enabled and uncompressed files are included

    'appname' => 'Team Manager',
    // The title and heading of your webpage
    
    'base' => 'http://team.your-host.com',
    // The absolute URL of the installation directory. Can be a subdomain as well. 

    'database' => 'sqlite:db/data.db',
    //The connection string for you database. 
    // Use sqlite:</path/to/file> for sqlite
    
    'password' => '', //Leave empty or delete for public access
    
    'playerlist' => (object) array(
    	//Set if the email and balance fields are visible in the playerlist
	    'mail'		=>	true,
	    'balance'	=>	true
    ),
    
    
    'mail' => (object) array(
	    
	    // These options are for outgoing mails via SMTP
	    // The same Server is used for the optional mailing list
	    // Don't forget to register a Cronjob for cron.php if you are using the mailinglist feature

	    'name'		=> 	"Carl Callahan",
	    'address'	=>	"carl@callahan.de",
	    
	    'host' 		=> 	"mail.mailserver.com",
	    'user' 		=> 	"username",
	    'password' 	=>	"************",
	    'security'	=> 	"tls",	//tls or ssl
	    'port'		=>	"587",	//110 or 995
	    
	    
	    'prefix'		=>	"[YOUR TEAM]", // will be prepended to all mailing list subjects
	    'archiveFolder' =>	"MailingListArchive", // is the IMAP folder where processed mails are stored
	    
	    'trap'			=> true,	// if true, the reply-to address is in every mail is the address of the mailing list
	    'public'		=> false    // if true, everyone can send mails to the list. Otherwise only players can send.
    )


);

?>