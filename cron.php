<?php

require 'vendor/autoload.php';
class_alias('\RedBeanPHP\R','R');

$conf = include('config.php');
include('inc/common.php');
include('db/setup.php');

if($conf->dev){
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
} else {
	error_reporting(0);
	ini_set('display_errors', 0);
}

$_imap = imap_setup(true);
$imap = $_imap->handle;

$unseen = imap_search($imap, 'UNSEEN');

if(!$unseen){
	die('NO MESSAGES');
}

$receiver = R::getAll('SELECT name, mail FROM player WHERE receive_mail = 1 AND mail IS NOT NULL');

$mids = array();

foreach($unseen as $message_id){

	try{

		$header = imap_header($imap,$message_id);

		$fromName = $header->from[0]->personal;
		$fromMail = $header->from[0]->mailbox . "@" . $header->from[0]->host;

		$member = R::findOne( 'player', ' mail = ? ', array( $fromMail ) );

		$struct = imap_fetchstructure($imap,$message_id);

		$htmlmsg = $plainmsg = $charset = '';
		$attachments = array();

		if( !isset($struct->parts) || ! $struct->parts){         //simple message

			imap_getpart($imap,$message_id,$struct,0,$htmlmsg,$plainmsg,$charset,$attachments);
		} else {              //multipart
			foreach($struct->parts as $pid=>$part){

				imap_getpart($imap,$message_id,$part,$pid+1,$htmlmsg,$plainmsg,$charset,$attachments);
			}
		}

		if( empty($htmlmsg) ){
			$htmlmsg = nl2br($plainmsg);
		}

		$htmlmsg = preg_replace("/<img[^>]+\>/i", "", $htmlmsg);

		$isAllowed = ($member != NULL && $conf->mail->public == false) || $conf->mail->public == true;

		if( count($receiver) > 0 && $isAllowed ){

			try{
				$mail = createMailer();


				if( $conf->mail->trap ){
					$mail->AddReplyTo($conf->mail->address,$conf->mail->name);
				} else {
					$mail->AddReplyTo($sender->mail, $fromName);
				}

				
    			$mail->SetFrom($conf->mail->address,$conf->mail->name);

    			$mail->addAddress($fromMail,$fromName);

				foreach($receiver as $p){
					if($fromMail != $p['mail']){
						$mail->addBCC($p['mail'], $p['name']);
					}
				}

				$subject = $header->subject;
				$subject = str_replace($conf->mail->prefix, '', $subject);
				$subject = $conf->mail->prefix . " " . $subject;


				$mail->isHTML(true);
				$mail->CharSet = $charset;
				$mail->Subject = $subject;
				$mail->Body    = $htmlmsg;
				$mail->AltBody    = $plainmsg;

				foreach($attachments as $aname=>$attachment){

					$mail->AddStringAttachment($attachment, $aname);

				}

				$result = $mail->send();

			} catch(Exception $e2){
				var_dump($e2);
			}


		} elseif( !$isAllowed ){

			$mail = createMailer();

			$mail->addAddress($fromMail,$fromName);

			$msg = "Hallo " . $fromName . ",\n\ndeine Mail an den Verteiler\"". $conf->appname ."\" konnte leider nicht verschickt werden, da Du kein Mitglied dieser Liste bist.\nBitte melde dich zuerst an, oder bitte ein Mitglied der Liste die Mail für dich zu verschicken.\n\nViele Grüße!";

			$mail->isHTML(true);
			$mail->CharSet = $charset;
			$mail->Subject = "Error: " . $header->subject;
			$mail->Body    = nl2br($msg);
			$mail->AltBody    = $msg;

			$result = $mail->send();
		}

		$mids[] = $message_id;

	} catch(Exception $e){
		var_dump($e);
	}

}

try{
	imap_mail_move($imap, implode(',', $mids) , $_imap->archiveFolder);
	imap_expunge($imap);
} catch(Exception $e){
	var_dump($e);
}


imap_close($imap);

die('OK');
