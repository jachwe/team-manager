<?php

$this->respond('GET', '/?', function ($request, $response, $service) {
    checkLogin();

    // $service->events = R::findAll('event',' archived IS NOT 1 ORDER BY date');

    $q = "SELECT
            e.name,
            e.id,
            e.location,
            e.date,
            e.status,
            e.girls_min,
            e.girls_max,
            e.boys_min,
            e.boys_max,
                (SELECT COUNT(response.id)
                FROM response
                JOIN player
                ON player.id = response.player_id
                WHERE response.event_id = e.id
                AND player.sex = 'w'
                AND response.status_id = 1) as girls,
                (SELECT COUNT(response.id)
                FROM response
                JOIN player
                ON player.id = response.player_id
                WHERE response.event_id = e.id
                AND player.sex = 'm'
                AND response.status_id = 1) as boys
            FROM event as e WHERE archived IS NOT 1";

    $service->events = R::getAll($q);
    $service->render('views/events.phtml');

});

$this->respond('POST', '/?', function ($request, $response, $service, $app) {

    checkLogin();

    $service->validateParam('name')->notNull();
    $service->validateParam('date')->notNull();

    $event = R::dispense('event');

    foreach ($request->paramsPost() as $key => $val) {
        if ($key == "date") {
            $val = @strtotime($val);
        }
        $event[$key] = $val;
    }

    R::store($event);

    $service->back();

});

$this->respond('GET', '/[i:id]/?', function ($request, $response, $service) {

    checkLogin();

    $id    = $request->id;
    $event = R::load('event', $id);

    $service->event = $event;

    $q = "SELECT player.name as name, player.id as id , response.time as time
                FROM player
                JOIN response
                ON player.id = response.player_id
                WHERE response.event_id = :event
                AND response.status_id = 1
                AND player.sex = :sex
                ORDER BY time";

    $service->positive_m = R::getAll($q, array(':event' => $event->id, ':sex' => 'm'));
    $service->positive_w = R::getAll($q, array(':event' => $event->id, ':sex' => 'w'));

    $service->negative = R::findAll('response', ' WHERE event_id = :event AND status_id = :status ORDER BY time', array(':event' => $event->id, ':status' => R::enum('status:no')->id));

    $service->maxplayers = $event->girls_max + $event->boys_max;

    $service->playercount   = count($service->positive_m) + count($service->positive_w);
    $service->negativecount = count($service->negative);

    $service->costpp = $service->playercount > 0 ? (intval($event->teamfee) / min($service->maxplayers, $service->playercount)) + $event->playersfee . "€" : "-";

    $service->allplayers = R::findAll('player', ' ORDER BY name');

    $service->girls_status = "default";
    if (count($service->positive_w) < $event->girls_min) {
        $service->girls_status = "danger";
    } else if (count($service->positive_w) > $event->girls_min) {
        $service->girls_status = "success";
    }

    $service->boys_status = "default";
    if (count($service->positive_m) < $event->boys_min) {
        $service->boys_status = "danger";
    } else if (count($service->positive_m) > $event->boys_min) {
        $service->boys_status = "success";
    }

    $service->costmin = $service->maxplayers > 0 ? (intval($event->teamfee) / $service->maxplayers) + $event->playersfee . "€" : "-";

    $service->render('./views/event.phtml');

});

$this->respond('POST', '/[i:id]/delete/?', function ($request, $response, $service) {

    checkLogin();

    $id    = $request->id;
    $event = R::load('event', $id);

    $event->archived = true;

    R::store($event);

    $service->flash("Der Termin wurde archiviert", "success");

    $response->redirect(getBase());
});

$this->respond('GET', '/[i:id]/edit/?', function ($request, $response, $service) {

    checkLogin();

    $id    = $request->id;
    $event = R::load('event', $id);

    $service->event = $event;

    $service->render('./views/event_edit.phtml');
});

$this->respond('POST', '/[i:id]/edit/?', function ($request, $response, $service) {

    checkLogin();

    $id    = $request->id;
    $event = R::load('event', $id);

    foreach ($request->paramsPost() as $key => $val) {
        if ($key == "date") {
            $val = @strtotime($val);
        }
        $event[$key] = $val;
    }

    R::store($event);

    $response->redirect(getBase() . 'events/' . $event->id);
});

$this->respond('POST', '/[i:id]/comment/?', function ($request, $response, $service) {

    checkLogin();

    $id    = $request->id;
    $event = R::load('event', $id);

    $author  = $request->param('author');
    $message = $request->param('message');

    $comment          = R::dispense('comment');
    $comment->author  = $author;
    $comment->message = $message;
    $comment->time    = time();

    $event->ownCommentList[] = $comment;

    R::store($event);

    $response->redirect(getBase() . 'events/' . $event->id);
});

$this->respond('POST', '/[i:id]/notify/?', function ($request, $response, $service) {

    checkLogin();

    $id    = $request->id;
    $event = R::load('event', $id);

    $sender = R::load('player', $request->param('senderid'));
    $conf = getConfig('mail');

    $mail = createMailer();

    $mail->AddReplyTo($sender->mail, $sender->name);
    $mail->SetFrom($conf->address, $conf->name);

    $message = $request->param('message');

    $players = R::getAll('SELECT player.name as name, player.mail as mail FROM player JOIN response ON response.player_id = player.id WHERE response.event_id = "' . $id . '" AND response.status_id = "1"');

    $service->allplayers = R::findAll('player', ' ORDER BY name');

    foreach ($players as $player) {
        if (!empty($player['mail'])) {
            $mail->addAddress($player['mail'], $player['name']);
        }
    }

    $mail->isHTML(true);
    $mail->Subject = $request->param('subject');
    $mail->Body    = $request->param('message');

    if ($mail->send()) {
        $service->flash("Deine Nachricht wurde verschickt", "success");
    } else {
        $service->flash("Deine Nachricht konnte leider nicht verschickt werden", "danger");
    }

    $service->back();
});

$this->respond('POST', '/[i:id]/splitfee/?', function ($request, $response, $service) {

    checkLogin();

    $id    = $request->id;
    $event = R::load('event', $id);

    $players = R::getAll('SELECT player.id as id, player.name as name, player.mail as mail FROM player JOIN response ON response.player_id = player.id WHERE response.event_id = "' . $id . '" AND response.status_id = "1"');

    $count = count($players);
    $val   = $event->teamfee / $count;

    foreach ($players as $player) {
        $p                        = R::load('player', $player['id']);
        $transaction              = R::dispense("payment");
        $transaction->value       = $val * -1;
        $transaction->date        = time();
        $transaction->description = "Teamfee: " . $event->name;
        $p->ownPaymentList[]      = $transaction;

        R::store($p);
    }

    $event->billed = time();
    R::store($event);

    $service->flash("Die Teamfee wurde abgerechnet", "success");
    $service->back();
});

$this->respond('POST', '/[:id]/addPlayer/?', function ($request, $response, $service) {

    checkLogin();
    // R::fancyDebug( TRUE );
    $id    = $request->id;
    $event = R::load('event', $id);

    $pids = $request->param('playerid');

    $status = $request->param('status');
    $enum   = R::enum('status:' . $status);

    $q = 'DELETE FROM response WHERE player_id IN (' . implode(',', $pids) . ') AND event_id = ' . $event->id . ';';

    R::exec($q);

    $q = 'INSERT INTO response (event_id,player_id,status_id,time) VALUES ';

    $r = [];
    foreach ($pids as $pid) {

        $r[] = '(' . $event->id . ',' . $pid . ',' . $enum->id . ',' . time() . ')';
    }
    $q = $q . implode(",", $r);

    R::exec($q);

    $service->back();
});
