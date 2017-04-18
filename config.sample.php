<?php

return (object) array(
    'appname' => 'Team Manager',
    'base' => 'http://team.parkscheibe-ultimate.de',
    
    'password' => '', //leave empty or delete for public access
    
    'playerlist' => (object) array(
	    'mail'		=>	true,
	    'balance'	=>	true
    ),
    
    'mail' => (object) array(
	    
	    'name'		=> 	"Carl Callahan",
	    'address'	=>	"carl@callahan.de",
	    
	    'host' 		=> 	"mail.mailserver.com",
	    'user' 		=> 	"username",
	    'password' 	=>	"************",
	    'security'	=> 	"tls",	//tls or ssl
	    'port'		=>	"587",	//110 or 995
	    
	    
	    'prefix'		=>	"[YOUR TEAM]",
	    'archiveFolder' =>	"MailingListArchive",
	    
	    'trap'			=> true,
	    'public'		=> false
    )
);

?>