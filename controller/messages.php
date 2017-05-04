<?php

$this->respond('GET', '/?', function ($request, $response, $service) {

    checkLogin();

    $imap = imap_setup(false);

    $MC = imap_check($imap->handle);

    $status = imap_search($imap->handle, 'ALL UNDELETED');

    $mails = array();

    foreach($status as $mid) {
        $mails[] = imap_fetch_overview($imap->handle, $mid, 0)[0];
    }

    function cmp($a, $b)
    {   
        return $a->date < $b->date ? -1 : 1;
    }

    usort($mails, "cmp");

    $service->mails = $mails;

    $service->render('./views/messages.phtml');
});

$this->respond('GET', '/[i:uid]/?', function ($request, $response, $service) {

    checkLogin();

    $message_id = $request->uid;

    $imap = imap_setup(false);

    $header = imap_header($imap->handle, $message_id);
    $struct = imap_fetchstructure($imap->handle, $message_id);

    $htmlmsg     = $plainmsg     = $charset     = '';
    $attachments = array();

    if (!$struct->parts) {
        imap_getpart($imap->handle, $message_id, $struct, 0, $htmlmsg, $plainmsg, $charset, $attachments);
    } else {
        foreach ($struct->parts as $pid => $part) {
            imap_getpart($imap->handle, $message_id, $part, $pid + 1, $htmlmsg, $plainmsg, $charset, $attachments);
        }
    }

    $service->fromName = $header->from[0]->personal;
    $service->fromMail = $header->from[0]->mailbox . "@" . $header->from[0]->host;



    $service->header = $header;
    $service->html   = $htmlmsg;

    $service->render('./views/message.phtml');
});

$this->respond('POST', '/?', function ($request, $response, $service) {

    checkLogin();

    $players = R::loadAll('player', $request->param('playerid'));
    $sender  = R::load('player', $request->param('senderid'));

    keepUser($sender->id);

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

$this->respond('GET', '/subscriber/?', function ($request, $response, $service) {

    checkLogin();

    $subs = R::findAll('subscriber');
    if( !$subs){
        $subs = array();
    }

    $service->subscriber = $subs;

    $service->render('./views/subscriber.phtml');
});

$this->respond('POST', '/subscriber/add?', function ($request, $response, $service) {

    checkLogin();

    $mails = $request->param('newmails');

    $values = preg_split("/[\n\r\,]/", $mails, 0, PREG_SPLIT_NO_EMPTY);

    foreach ($values as $value) {
        $s = R::dispense('subscriber');
        $s->mail = $value;
        R::store($s);
    }

    $service->flash( count($values) . ' AbonnentIn(nen) wurden hinzugefügt','success');
    $service->back();
});
