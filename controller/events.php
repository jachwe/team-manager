<?php

$this->respond('GET', '/?', function ($request, $response, $service) {
    checkLogin();

    $q = "SELECT
    e.name,
    e.id,
    e.location,
    e.date,
    strftime('%s',date, 'unixepoch','+' || ifnull(days,2) || ' days','-1 day') as end,
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
    FROM event as e WHERE archived IS NOT 1
    ORDER by date";

    $service->events = R::getAll($q);
    $service->render('views/events.phtml');

});

$this->respond('GET', '/new/?', function ($request, $response, $service, $app) {

    checkLogin();

    $service->title = "Neuer Termin";

    $service->event = (object) array(
        'name'        => '',
        'location'    => '',
        'description' => '',
        'date'        => time(),
        'days'        => 2,
        'teamfee'     => 0,
        'playersfee'  => 0,
        'boys_min'    => 0,
        'boys_max'    => 99,
        'girls_min'   => 0,
        'girls_max'   => 99,
        'status'      => 'optional',

        );

    $service->render('./views/event_edit.phtml');

});

$this->respond('POST', '/new/?', function ($request, $response, $service, $app) {

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

    $id = R::store($event);

    $response->redirect(getBase() . 'events/' . $id);

});

$this->respond('GET', '/[i:id]/?', function ($request, $response, $service) {

    checkLogin();

    $id    = $request->id;
    $event = R::load('event', $id);

    // R::fancyDebug( TRUE );

    $service->event = $event;

    $service->start = date('d.m.Y', $event->date);
    $service->days  = isset($event->days) ? $event->days : 2;
    $endts = strtotime('+' . ($service->days - 1) . ' days', $event->date);
    $service->end   = date('d.m.Y', $endts);

    $q = "SELECT player.name,player.id, 0 as isPickup, response.time as time
    FROM player
    JOIN response
    ON player.id = response.player_id
    WHERE response.event_id = :event
    AND response.status_id = 1
    AND player.sex = :sex
    UNION ALL
    SELECT name,id, 1 as isPickup, time
    FROM pickup
    WHERE sex = :sex
    AND event_id = :event";

    $service->positive_m = R::getAll($q, array(':event' => $event->id, ':sex' => 'm'));
    $service->positive_w = R::getAll($q, array(':event' => $event->id, ':sex' => 'w'));

    $service->negative = R::findAll('response', ' WHERE event_id = :event AND status_id = :status ORDER BY time', array(':event' => $event->id, ':status' => R::enum('status:no')->id));

    $service->maxplayers = $event->girls_max + $event->boys_max;

    $service->playercount   = count($service->positive_m) + count($service->positive_w);
    $service->negativecount = count($service->negative);

    $service->costpp = $service->playercount > 0 ? (intval($event->teamfee) / min($service->maxplayers, $service->playercount)) + $event->playersfee . "€" : "-";

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
    $service->title = "Termin bearbeiten";

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
    keepUser($sender->id);

    $conf   = getConfig('mail');

    $mail = createMailer();

    $mail->AddReplyTo($sender->mail, $sender->name);
    $mail->SetFrom($conf->noreply, $sender->name . " | " . $conf->name);

    $msg = $request->param('message');

    $msg .= '<br/><br/><p>-------------</p><p>Diese Nachricht wurde verschickt von <a href="'.getBase().'player/'. $sender->id .'">'. $sender->name .'</a> an alle Teilnehmer*Innen von <a href="'.getBase().'event/'. $id .'">'. $event->name .'</a>.</p>';

    $players = R::getAll('SELECT player.name as name, player.mail as mail FROM player JOIN response ON response.player_id = player.id WHERE response.event_id = "' . $id . '" AND response.status_id = "1"');

    foreach ($players as $player) {
        if (!empty($player['mail'])) {
            $mail->addAddress($player['mail'], $player['name']);
        }
    }

    $mail->isHTML(true);
    $mail->Subject = $request->param('subject');
    $mail->Body    = $msg;

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

    keepUsers($pids);

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

$this->respond('POST', '/[:id]/addPickup/?', function ($request, $response, $service) {

    checkLogin();
    // R::fancyDebug( TRUE );
    $id    = $request->id;
    $event = R::load('event', $id);

    $name = $request->param('pickupname');
    $sex = $request->param('pickupsex');

    $pickup = R::dispense('pickup');
    $pickup->name = $name;
    $pickup->sex = $sex;
    $pickup->time = time();
    $pickup->event = $event;

    R::store($pickup);

    $service->back();
});

$this->respond('POST', '/[:id]/removePickup/[:pickup]/?', function ($request, $response, $service) {

    checkLogin();
    // R::fancyDebug( TRUE );
    $id    = $request->id;
    $pickup    = $request->pickup;
    
    $p = R::load('pickup',$pickup);

    R::trash($p);

    $service->back();
});

$this->respond('POST', '/[:id]/order?', function ($request, $response, $service) {

    // checkLogin();
    // R::fancyDebug( TRUE );
    $id    = $request->id;
    $event = R::load('event', $id);

    $pids = $request->param('playerid');

    foreach ($pids as $order => $pid) {   

        $r = R::findOne('response',' event_id = :eid AND player_id = :pid', array(
            ":eid"  => $id,
            ":pid" => $pid
            ));
        
        if($r){
            $r->spotorder = $order;
            $r->lastupdate = time();
            R::store($r);
        }
    }

    $response->json($pids);




});

$this->respond('/calendar.ics/?', function ($request, $response, $service) {

    $vCalendar = new \Eluceo\iCal\Component\Calendar(getBase() . "events/calendar.ics");

    $events = R::getAll('SELECT * FROM event WHERE archived IS NOT 1 AND date > strftime("%s","now") ORDER by date');

    foreach ($events as $event) {
        $vEvent = new \Eluceo\iCal\Component\Event();

        $startDT = new DateTime();
        $startDT->setTimestamp($event['date']);

        $days = $event['days'] ? $event['days'] : 2;

        $endDT = new DateTime();
        $endDT->setTimestamp($event['date']);
        $endDT->modify('-1 day');
        $endDT->modify('+' . $days . ' day');

        $vEvent
        ->setDtStart($startDT)
        ->setDtEnd($endDT)
        ->setNoTime(true)
        ->setSummary($event['name'])
        ->setLocation($event['location']);

        $vCalendar->addComponent($vEvent);

    }

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="cal.ics"');

    echo $vCalendar->render();

});
