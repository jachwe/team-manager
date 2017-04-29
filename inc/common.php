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

function isPrivate()
{
    global $conf;
    return isset($conf->password) && !empty($conf->password);
}

function isLoggedIn()
{
    return isset($_SESSION['loggedin']);
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
