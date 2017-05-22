<?php

$this->respond('GET', '/?', function ($request, $response, $service, $app) {

    checkLogin();

    $q = "SELECT *,(SELECT count(player.id) FROM player JOIN player_tag ON player_tag.player_id = player.id WHERE player_tag.tag_id = tag.id) as members FROM tag";

    $service->groups  = R::getAll($q);

    $service->render('./views/groups.phtml');

});

$this->respond('POST', '/?', function ($request, $response, $service, $app) {

    checkLogin();

    $players = R::loadAll('player', $request->param('playerid'));
    $name    = $request->param('name');
    foreach ($players as $player) {
        R::addTags($player, $name);
    }

    $service->back();

});

$this->respond('GET', '/[i:id]/?', function ($request, $response, $service) {
    checkLogin();

    $id  = $request->id;
    $tag = R::getCell('SELECT title FROM tag WHERE id = ' . $id);

    $service->tag   = $tag;
    $service->tagid = $id;


    $service->players = R::getAll('SELECT name,player.id FROM player JOIN player_tag ON player_tag.player_id = player.id WHERE player_tag.tag_id = ' . $id . ' ORDER BY name');

    $service->render('./views/group.phtml');

});

$this->respond('POST', '/[i:id]/delete/?', function ($request, $response, $service) {

    checkLogin();

    $id = $request->id;

    $q = "DELETE FROM tag WHERE id = " . $id . "; DELETE FROM player_tag WHERE tag_id = " . $is;
    R::exec($q);

    $response->redirect(getBase() . 'group');
});

$this->respond('POST', '/[i:id]/message/?', function ($request, $response, $service) {

    checkLogin();

    $id  = $request->id;
    $tag = R::getCell('SELECT title FROM tag WHERE id = ' . $id);

    $players = R::tagged('player', $tag);

    $sender = R::load('player', $request->param('senderid'));
    keepUser($sender->id);

    $conf   = getConfig('mail');

    $mail = createMailer();

    $mail->AddReplyTo($sender->mail, $sender->name);
    $mail->SetFrom($conf->address, $sender->name . " | " . $conf->name);

    

    foreach ($players as $player) {
        $mail->addAddress($player->mail, $player->name);
    }

    $mail->isHTML(true);

    $mail->Subject = $request->param('subject');
    $mail->Body    = $request->param('message');

    if ($mail->send()) {
        $service->flash('Deine Nachricht an die Gruppe ' . $tag . ' wurde versendet.', 'success');
        $service->back();
    } else {
        $service->flash($mail->ErrorInfo, 'danger');
        $service->back();

    }

});

$this->respond('POST', '/[:id]/add/?', function ($request, $response, $service) {

    checkLogin();

    $id  = $request->id;
    $tag = R::getCell('SELECT title FROM tag WHERE id = ' . $id);

    $players = R::loadAll('player', $request->param('playerid'));

    foreach ($players as $player) {
        R::addTags($player, $tag);
    }

    $service->back();
});

$this->respond('POST', '/[:id]/remove/?', function ($request, $response, $service) {

    checkLogin();

    $id  = $request->id;
    $tag = R::getCell('SELECT title FROM tag WHERE id = ' . $id);

    $player = R::load('player', $request->param('playerid'));

    R::untag($player, $tag);

    $service->back();
});
