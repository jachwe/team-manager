<?php

function getBase()
{
    global $conf;
    return $conf->base . (substr($conf->base, -1) == '/' ? '' : '/');
}

function getName()
{
    global $conf;
    return $conf->appname;
}

function getConfig($prop)
{
    global $conf;
    if ($prop == null) {
        return $conf;
    }

    return $conf->$prop;
}

function createMailer()
{
    global $conf;
    $mail = new PHPMailer;

    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ),
        'tls' => array(
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ),
    );

    $mail->isSMTP();
    $mail->Host       = $conf->mail->host;
    $mail->Username   = $conf->mail->user;
    $mail->Password   = $conf->mail->password;
    $mail->SMTPAuth   = true;
    $mail->SMTPSecure = $conf->mail->security;
    $mail->Port       = $conf->mail->port;

    // $mail->setFrom($conf->mail->address, $conf->mail->name);
    $mail->CharSet = 'UTF-8';

    return $mail;
}

function getPlayerSingleOptions($preselect = true){
    return getPlayerListOptions($preselect,false);
}

function getPlayerListOptions($preselect = true, $multi = false){
    $players = R::findAll('player',' ORDER BY name');
    $rstr = "";
    foreach ($players as $player) {
        $cu = $multi ? getCurrentPlayers() : getCurrentPlayer();
        $pid = $player->id;
        $name = $player->name;

        $selected = $preselect;
        if( $selected && $multi ){
            $selected = in_array($pid, $cu);
        } else if( $selected && !$multi ){
            $selected = $pid == $cu;
        }
        
        if($selected){
            $rstr .= "<option value='$pid' selected>$name</option>";
        } else {
            $rstr .= "<option value='$pid'>$name</option>";
        }
    }
    return $rstr;
}

function getCurrentPlayer(){
    return isset($_COOKIE['tmsid']) ? $_COOKIE['tmsid'] : -1;
}

function getCurrentPlayers(){
    return isset($_COOKIE['tmmids']) ? explode(',',$_COOKIE['tmmids']) : array();
}

function keepUser($uid){
    setcookie('tmsid', $uid, time() + (10 * 365 * 24 * 60 * 60), "/");
    $_SESSION['tmsid'] = $uid;
}

function keepUsers($uids){
    setcookie('tmmids', implode(",",$uids), time() + (10 * 365 * 24 * 60 * 60), "/");
    $_SESSION['tmmids'] = $uids;
}

function isPrivate()
{
    global $conf;
    return isset($conf->password) && !empty($conf->password);
}

function doLogin(){
    setcookie('tmli', true, time() + (10 * 365 * 24 * 60 * 60), "/");
    $_SESSION['loggedin'] = true;
}

function doLogout(){
    setcookie('tmli', false, time() - 3600);
    unset($_SESSION['loggedin']);
}

function isLoggedIn()
{
    return isset($_SESSION['loggedin']) || isset($_COOKIE['tmli']);
}

function checkPass($pass)
{
    global $conf;
    return $pass === $conf->password;
}

function checkLogin()
{
    if (isPrivate() && !isLoggedIn()) {
        header("Location: " . getBase() . "login");
        die();
    }
}

function status2text($status)
{
    switch ($status) {
        case "safe":
            return "bestätigt";
        case "unsafe":
            return "unbestätigt";
        case "optional":
            return "nicht gemeldet";
        case "canceled":
            return "abgesagt";
        default:
            return "unbekannt";
    }
}

function status2class($status)
{
    switch ($status) {
        case "safe":
            return "success";
        case "unsafe":
            return "warning";
        case "optional":
            return "default";
        case "canceled":
            return "danger";
        default:
            return "default";
    }
}

function imap_setup($returnInbox = false){

    $conf = getConfig("mail");

    $mailbox    = gethostbyname($conf->host);
    $username   = $conf->user;
    $password   = $conf->password;
    $encryption = $conf->security;

    $inbox = $conf->inbox;
    $archiveFolder = $conf->archiveFolder;


    $connection = "{".$mailbox."/imap/".$encryption."/novalidate-cert}";

    $conn = $connection.$inbox;
    $imap = imap_open($connection.$inbox, $username , $password);
    
    imap_errors();

    if( $returnInbox === false ){
        $conn = $connection.$archiveFolder;
        imap_createmailbox($imap, imap_utf7_encode("{".$mailbox."}".$archiveFolder));
        imap_errors();
        $imap = imap_open($connection.$archiveFolder, $username , $password);
    }

    return (object) array(
        'handle' => $imap,
        'mailbox'=> $mailbox,
        'archiveFolder'=> $archiveFolder,
        'connection' => $conn
    );
}

function imap_getpart($mbox,$mid,$p,$partno,&$htmlmsg,&$plainmsg,&$charset,&$attachments) {

    $data = ($partno)? imap_fetchbody($mbox,$mid,$partno): imap_body($mbox,$mid);

    if ($p->encoding==4){
        $data = quoted_printable_decode($data);
    } elseif ($p->encoding==3) {
        $data = base64_decode($data);
    }

    $params = array();
    if (isset($p->parameters)){
        foreach ($p->parameters as $x){
            $params[strtolower($x->attribute)] = $x->value;
        }
    }
    if (isset($p->dparameters)){
        foreach ($p->dparameters as $x){
            $params[strtolower($x->attribute)] = $x->value;
        }
    }

    if (isset($params['filename']) || isset($params['name'])) {

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

    if (isset($p->parts)) {
        foreach ($p->parts as $partno0=>$p2){
            imap_getpart($mbox,$mid,$p2,$partno.'.'.($partno0+1),$htmlmsg,$plainmsg,$charset,$attachments);
        }
    }
}
