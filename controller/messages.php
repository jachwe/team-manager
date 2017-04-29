<?php

$this->respond('GET', '/?', function ($request, $response, $service) {

    checkLogin();

    $service->players = R::getAll('SELECT name,id,mail FROM player ORDER BY name');

    $service->render('./views/messages.phtml');
});

$this->respond('POST', '/?', function ($request, $response, $service) {

    checkLogin();

    $players = R::loadAll('player', $request->param('playerid'));
    $sender = R::load('player', $request->param('senderid'));

    $mail = createMailer();

    $conf = getConfig('mail');

    $mail->AddReplyTo($sender->mail, $sender->name);
    $mail->SetFrom($conf->address, $conf->name);


    foreach ($players as $player) {
        if (!empty($player['mail'])) {
            $mail->addAddress($player['mail'], $player->name);
        }
    }

    $msg = $request->param('message');

    $mail->isHTML(true);
    $mail->Subject = $request->param('subject');
    $mail->Body    = $msg;

    if ($mail->send()) {
        $service->flash("Deine Nachricht wurde verschickt. " . $mail->ErrorInfo, "success");
    } else {
        $service->flash("Deine Nachricht konnte leider nicht verschickt werden", "danger");
    }

    $service->back();
});
