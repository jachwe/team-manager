<?php

$this->respond('GET','/?', function ($request, $response, $service) {
	
		checkLogin();
		
		$service->players = R::getAll('SELECT name,id FROM player ORDER BY name');

		$service->render('./views/messages.phtml');
	});
	
$this->respond('POST','/?', function ($request, $response, $service) {
	
		checkLogin();
		
		$players = R::loadAll('player',$request->param('playerid'));
		
		$mail = createMailer();
		
		foreach($players as $player){
			if( !empty($player['mail']) ){
				$mail->addAddress($player['mail'], $player->name);
			}
		}
		
		$msg = $request->param('message');
		
		$mail->Subject = $request->param('subject');
		$mail->Body    = $msg;
		$mail->AltBody = strip_tags($msg);
		
		if( $mail->send() ){
			$service->flash("Deine Nachricht wurde verschickt","success");
		} else {
			$service->flash("Deine Nachricht konnte leider nicht verschickt werden","danger");
		}
		
		
		$service->back();
	});	