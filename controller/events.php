<?php
	
$this->respond('GET','/?',function($request, $response, $service){
	checkLogin();
	
	$service->events = R::findAll('event',' archived IS NOT 1 ORDER BY date');
	$service->render('views/events.phtml');
	
});

$this->respond('POST','/?', function ($request, $response, $service,$app) {
		
		checkLogin();
		
		$service->validateParam('name')->notNull();
		$service->validateParam('date')->notNull();
		
		$event = R::dispense( 'event' );
		
		foreach($request->paramsPost() as $key => $val){
			if($key == "date"){
				$val = @strtotime($val);	
			}
			$event[$key] = $val;
		}

		R::store( $event );

		$service->back();

	});

$this->respond('GET','/[i:id]/?', function ($request, $response, $service) {
		
		checkLogin();
		
		$id = $request->id;
		$event = R::load('event',$id);

		$service->event = $event;

		$q =  	"SELECT player.name as name, player.id as id , response.time as time
				FROM player 
				JOIN response 
				ON player.id = response.player_id 
				WHERE response.event_id = :event  
				AND response.status_id = 1 
				AND player.sex = :sex
				ORDER BY time";
	
		$service->positive_m = R::getAll($q, array( ':event' => $event->id,':sex' => 'm' ) );
		$service->positive_w = R::getAll($q, array( ':event' => $event->id,':sex' => 'w' ) );
		
		$service->negative = R::findAll('response',' WHERE event_id = :event AND status_id = :status ORDER BY time', array(':event'=>$event->id, ':status' => R::enum('status:no')->id ));
		
		$service->maxplayers = $event->girls_max + $event->boys_max;
		
		$service->playercount = count($service->positive_m) + count($service->positive_w);
		$service->negativecount = count($service->negative);
		
		$service->costpp = $service->playercount > 0 ? (intval($event->teamfee) / min($service->maxplayers,$service->playercount)) + $event->playersfee . "€" : "-";
	
		$service->girls_status = "default";
		if( count($service->positive_w) < $event->girls_min ){
			$service->girls_status = "danger";
		} else if( count($service->positive_w) > $event->girls_min ){
			$service->girls_status = "success";
		}
		
		$service->boys_status = "default";
		if( count($service->positive_m) < $event->boys_min ){
			$service->boys_status = "danger";
		} else if( count($service->positive_m) > $event->boys_min ){
			$service->boys_status = "success";
		}
	
		$service->costmin = $service->maxplayers > 0 ? (intval($event->teamfee) / $service->maxplayers) + $event->playersfee . "€" : "-";

		$service->render('./views/event.phtml');
		
	});

$this->respond('POST','/[i:id]/delete/?', function ($request, $response, $service) {
	
		checkLogin();

		$id = $request->id;
		$event = R::load('event',$id);

		$event->archived = true;
		
		R::store($event);
		
		$service->flash("Der Termin wurde archiviert","success");
		
		$response->redirect(getBase());
	});
	
$this->respond('GET','/[i:id]/edit/?', function ($request, $response, $service) {
		
		checkLogin();
		
		$id = $request->id;
		$event = R::load('event',$id);
		
		$service->event = $event;
		
		$service->render('./views/event_edit.phtml');
	});
	
$this->respond('POST','/[i:id]/edit/?', function ($request, $response, $service) {
	
		checkLogin();
		
		
		$id = $request->id;
		$event = R::load('event',$id);
		
		foreach($request->paramsPost() as $key => $val){
			if($key == "date"){
				$val = @strtotime($val);	
			}
			$event[$key] = $val;
		}
		
		R::store( $event );

		$response->redirect(getBase() . 'events/' . $event->id);
	});

$this->respond('POST','/[i:id]/comment/?', function ($request, $response, $service) {
		
		checkLogin();
		
		$id = $request->id;
		$event = R::load('event',$id);
		
		$author = $request->param('author');
		$message = $request->param('message');

		$comment = R::dispense('comment');
		$comment->author = $author;
		$comment->message = $message;
		$comment->time = time();
		
		$event->ownCommentList[] = $comment;
		
		R::store( $event );

		$response->redirect(getBase() . 'events/' . $event->id);
	});
	
$this->respond('POST','/[i:id]/notify/?', function ($request, $response, $service) {
	
		checkLogin();

		$id = $request->id;
		$event = R::load('event',$id);
		
		$author = $request->param('author');
		$message = $request->param('message');

		$players = R::getAll( 'SELECT player.name as name, player.mail as mail FROM player JOIN response ON response.player_id = player.id WHERE response.event_id = "' . $id . '" AND response.status_id = "1"' );
		
		$mail = createMailer();
		
		foreach($players as $player){
			if( !empty($player['mail']) ){
				$mail->addAddress($player['mail'], $player->name);
			}
		}
		
		$mail->Subject = $request->param('subject');
		$mail->Body    = $request->param('message');
		$mail->AltBody = $request->param('message');
		
		if( $mail->send() ){
			$service->flash("Deine Nachricht wurde verschickt","success");
		} else {
			$service->flash("Deine Nachricht konnte leider nicht verschickt werden","danger");
		}
		
		
		$service->back();
	});				
	
	
$this->respond('POST','/[i:id]/splitfee/?', function ($request, $response, $service) {
	
		checkLogin();

		$id = $request->id;
		$event = R::load('event',$id);
	

		$players = R::getAll( 'SELECT player.id as id, player.name as name, player.mail as mail FROM player JOIN response ON response.player_id = player.id WHERE response.event_id = "' . $id . '" AND response.status_id = "1"' );
		
		$count = count($players);
		$val = $event->teamfee / $count;
		
		foreach($players as $player){
			$p = R::load('player',$player['id']);
			$transaction = R::dispense("payment");
			$transaction->value = $val * -1;
			$transaction->date = time();
			$transaction->description = "Teamfee: " . $event->name;
			$p->ownPaymentList[] = $transaction;
			
			R::store($p);
		}
		
		$event->billed = time();
		R::store($event);
		
		$service->flash("Die Teamfee wurde abgerechnet","success");
		$service->back();
	});				
	
	
$this->respond('POST','/[:id]/addPlayer/?', function ($request, $response, $service) {
	
		checkLogin();
		
		$id = $request->id;
		$event = R::load('event',$id);
		
		$pid = $request->param('playerName');
		
		$player = R::getRow("SELECT * FROM player WHERE name LIKE '%". trim( $pid )."%'");
		
		$service->validate($player,'Diese/r Spieler*In existiert nicht. Bitte lege ihn/sie zuerst an.')->notNull();
		
		$player = R::load('player',$player['id']);
		
		$r = R::findOrCreate('response',array(
			'event_id' 	=> $event->id,
			'player_id'	=> $player->id
		));
		$r->event = $event;
		$r->player = $player;
		$r->time = $r->status->equals( R::enum('status:' . $request->param('status') ) ) ? $r->time : time();
		$r->status = R::enum('status:' . $request->param('status') );
		
		R::store($r);
		
		$service->back();
	});	
	