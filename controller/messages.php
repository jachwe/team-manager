<?php

$this->respond('GET', '/?', function ($request, $response, $service) {

    checkLogin();

    $service->players = R::getAll('SELECT name,id,mail FROM player ORDER BY name');

    $imap = imap_setup();

    $status = @imap_status($imap->handle, "{" . $imap->mailbox . "}" . $imap->archiveFolder, SA_ALL);

    $result = imap_fetch_overview($imap->handle, "1:{$status->messages}", 0);

    // $result = array_reverse($result);

    $service->mails = $result;

    $service->render('./views/messages.phtml');
});

$this->respond('GET', '/[:uid]/?', function ($request, $response, $service) {

    checkLogin();

    $message_id = $request->uid;

    $imap = imap_setup();

    $status = @imap_status($imap->handle, "{" . $imap->mailbox . "}" . $imap->archiveFolder, SA_ALL);

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
