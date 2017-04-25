<?php

require 'vendor/autoload.php';
class_alias('\RedBeanPHP\R','R');

$conf = include('config.php');
include('inc/common.php');
include('db/setup.php');


$mailbox = gethostbyname($conf->mail->host);
$username = $conf->mail->user;
$password = $conf->mail->password;
$encryption = $conf->mail->security;

function getpart($mbox,$mid,$p,$partno,&$htmlmsg,&$plainmsg,&$charset,&$attachments) {

	$data = ($partno)? imap_fetchbody($mbox,$mid,$partno): imap_body($mbox,$mid);

	if ($p->encoding==4){
		$data = quoted_printable_decode($data);
	} elseif ($p->encoding==3) {
		$data = base64_decode($data);
	}

	$params = array();
	if ($p->parameters){
		foreach ($p->parameters as $x){
			$params[strtolower($x->attribute)] = $x->value;
		}
	}
	if ($p->dparameters){
		foreach ($p->dparameters as $x){
			$params[strtolower($x->attribute)] = $x->value;
		}
	}

	if ($params['filename'] || $params['name']) {

		$filename = ($params['filename'])? $params['filename'] : $params['name'];
		$attachments[$filename] = $data;
	}

	if ($p->type==0 && $data) {

		if (strtolower($p->subtype)=='plain'){
			$plainmsg .= trim($data) ."\n\n";
		}  else{
			$htmlmsg .= $data ."<br><br>";
		}

		$charset = $params['charset'];

	} elseif ($p->type==2 && $data) {

		$plainmsg .= $data."\n\n";

	}

	if ($p->parts) {
		foreach ($p->parts as $partno0=>$p2){
			getpart($mbox,$mid,$p2,$partno.'.'.($partno0+1),$htmlmsg,$plainmsg,$charset,$attachments);
		}
	}
}

$connection = "{".$mailbox."/imap/".$encryption."/novalidate-cert}";

$imap = @imap_open($connection, $username , $password);

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

		if( !$struct->parts ){         //simple message

			getpart($imap,$message_id,$struct,0,$htmlmsg,$plainmsg,$charset,$attachments);
		} else {              //multipart
			foreach($struct->parts as $pid=>$part){

				getpart($imap,$message_id,$part,$pid+1,$htmlmsg,$plainmsg,$charset,$attachments);
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

				$mail->addAddress($fromMail,$fromName);

				foreach($receiver as $p){
					if($fromMail != $p['mail']){
						$mail->addBCC($p['mail'], $p['name']);
					}
				}


				$mail->isHTML(true);
				$mail->CharSet = $charset;
				$mail->Subject = $conf->mail->prefix . " ". $header->subject . " (".$fromName.")";
				$mail->Body    = $htmlmsg;
				$mail->AltBody    = $plainmsg;

				if( !$conf->mail->trap ){
					$mail->addReplyTo($fromMail,$fromName);
				} else {
					$mail->addReplyTo($conf->mail->address,$conf->mail->name);
				}

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
	imap_mail_move($imap, implode(',', $mids) , 'INBOX.'.$conf->mail->archiveFolder);
	imap_expunge($imap);
} catch(Exception $e){
	var_dump($e);
}

die('OK');
