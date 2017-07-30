<?php

$this->respond('GET', '/?', function ($request, $response, $service, $app) {

    checkLogin();

    $q = "SELECT player.id, player.name, player.number, player.mail, player.sex,
			  	(SELECT sum(DISTINCT payment.value) FROM payment  WHERE player.id = payment.player_id) AS 'balance'
			  FROM player ORDER BY player.name";

    $service->players = R::getAll($q);
    if ($service->players == null) {
        $service->players = R::getAll("SELECT player.id, player.name, player.number, mail, player.sex, 0 as balance FROM player ORDER BY player.name");
    }

    $service->render('./views/players.phtml');

});

$this->respond('GET', '/json/?', function ($request, $response, $service, $app) {

    checkLogin();

    $q       = $request->param('q');
    $c       = "SELECT name,id FROM player WHERE name LIKE '%" . $q . "%'";
    $players = R::getAll($c);

    $response->json($players);

});

$this->respond('POST', '/?', function ($request, $response, $service, $app) {

    checkLogin();

    $service->validateParam('name')->notNull();
    $service->validateParam('mail')->notNull();

    $player = R::dispense('player');

    foreach ($request->paramsPost() as $key => $val) {
        $player[$key] = $val;
    }

    $player['receiveMail'] = $request->param('receiveMail') != null;

    R::store($player);

    $service->back();

});

$this->respond('GET', '/[i:id]/?', function ($request, $response, $service) {

    checkLogin();

    $id     = $request->id;
    $player = R::load('player', $id);

    $service->player  = $player;

    $service->events  = R::getAll('SELECT event.id as id, event.name as name, response.status_id as status FROM event JOIN response ON response.event_id = event.id WHERE response.player_id = ' . $player->id . " AND event.archived IS NOT 1");
    $service->balance = R::getCell('SELECT sum(value) as balance FROM payment WHERE player_id = ' . $player->id);

    $service->groups = R::getAll('SELECT tag.title, tag.id FROM tag JOIN player_tag ON player_tag.tag_id = tag.id WHERE player_tag.player_id = ' . $id);

    $service->render('./views/player.phtml');

});

$this->respond('POST', '/[i:id]/delete/?', function ($request, $response, $service) {

    checkLogin();

    $id     = $request->id;
    $player = R::load('player', $id);

    $responses = R::findAll('response', ' player_id = ' . $id);
    R::trashAll($responses);

    R::trash($player);

    $response->redirect(getBase() . 'player');
});

$this->respond('GET', '/[i:id]/edit/?', function ($request, $response, $service) {

    checkLogin();

    $id     = $request->id;
    $player = R::load('player', $id);

    $service->player = $player;

    $service->render('./views/player_edit.phtml');
});

$this->respond('POST', '/[i:id]/edit/?', function ($request, $response, $service) {

    checkLogin();

    $id     = $request->id;
    $player = R::load('player', $id);

    foreach ($request->paramsPost() as $key => $val) {
        $player[$key] = $val;
    }
    $player['receiveMail'] = $request->param('receiveMail') != null;

    R::store($player);

    $response->redirect(getBase() . 'player/' . $player->id);

});

$this->respond('POST', '/[i:id]/message/?', function ($request, $response, $service) {

    checkLogin();

    $id     = $request->id;
    $player = R::load('player', $id);
    
    $sender = R::load('player', $request->param('senderid'));

    keepUser($sender->id);

    $conf = getConfig('mail');

    $mail = createMailer();

    $mail->AddReplyTo($sender->mail, $sender->name);
    $mail->SetFrom($conf->noreply, $sender->name . " | " . $conf->name);

    $mail->addAddress($player->mail, $player->name);

    $mail->isHTML(true);
    $mail->Subject = $request->param('subject');
    $mail->Body    = $request->param('message');

    if ($mail->send()) {
        $service->flash('Deine Nachricht an ' . $player->name . ' wurde versendet.', 'success');
        $service->back();
    } else {
        $service->flash($mail->ErrorInfo, 'error');
        $service->back();

    }

});

$this->respond('POST', '/[i:id]/transaction/?', function ($request, $response, $service) {

    checkLogin();

    $id     = $request->id;
    $player = R::load('player', $id);

    $payment              = R::dispense('payment');
    $payment->value       = $request->param('value');
    $payment->description = $request->param('description');
    $payment->date        = time();

    $player->ownPaymentList[] = $payment;

    R::store($player);

    $service->flash("Die Transaktion wurde erfolgreich ausgeführt", 'success');
    $service->back();

});
