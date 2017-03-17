<?php
mb_internal_encoding("UTF-8");

function send_pic($tgtid, $icon) {
	global $access_token, $host;

	$url = 'https://graph.facebook.com/v2.6/me/messages?access_token=' .
		$access_token;
	$ch = curl_init($url);
	$json = '{ "recipient":{ "id":"'. $tgtid .  '" }, '.
		'"message":{ '.
			'"attachment":{ '.
				'"type":"image",'.
				'"payload":{'.
					"\"url\":\"https://$host/fbhook/icons/$icon\"" .
				'}}}}';
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
	curl_setopt($ch, CURLOPT_HTTPHEADER,
		array('Content-Type: application/json'));
	return curl_exec($ch);
}

function send_mesg($tgtid, $mesg) {
	global $access_token;

	$url = 'https://graph.facebook.com/v2.6/me/messages?access_token=' .
		$access_token;
	$ch = curl_init($url);
	$json = '{ "recipient":{ "id":"'. $tgtid .  '" }, "message":{ "text":"' .
		$mesg . '" } }';
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
	curl_setopt($ch, CURLOPT_HTTPHEADER,
		array('Content-Type: application/json'));
	return curl_exec($ch);
}

openlog("fbhook", LOG_PID | LOG_PERROR, LOG_LOCAL0);
$meid = YOUR_APPLICATION_ID;
$host = "";
$access_token = YOUR_APPLICATION_TOKEN;
$verify_token = YOUR_VERIFY_TOKEN;
$hub_verify_token = null;

if (isset($_REQUEST['hub_challenge'])) {
    $challenge = $_REQUEST['hub_challenge'];
    $hub_verify_token = $_REQUEST['hub_verify_token'];
}
if ($hub_verify_token === $verify_token) {
    echo $challenge;
}

$content = file_get_contents('php://input');
$input = json_decode($content, true);
$sender = $input['entry'][0]['messaging'][0]['sender']['id'];
$recipient = $input['entry'][0]['messaging'][0]['recipient']['id'];
$message = $input['entry'][0]['messaging'][0]['message']['text'];
if (strlen($message) <= 0)
	exit;

$mesg_reply = $message;
if ($sender == $recipient || $sender == $meid || !$sender) {
	die("Error in sender:$sender == $recipient");
}

if ($sender == $meid)
	syslog(LOG_ERR, "$sender-|>$message-|>$recipient");
if (mb_strlen($mesg_reply) > 2048) {
	send_mesg($sender, "Hi, I am just a kid");
	send_pic($sender, "surprise.gif");
	die("Error in sender:$sender mesg too long");
}

$msg_size = 256;
for ($i = mb_strlen($mesg_reply); $i > 0; $i -= $msg_size) {
	$mesg = mb_substr($mesg_reply, 0, $msg_size);
	$mesg = addslashes($mesg);
	send_mesg($sender, str_replace("\n", "\u000A", $mesg));
	syslog(LOG_INFO, "$sender($i)-|>$message-|>$recipient");
	sleep(1);
	$mesg_reply = mb_substr($mesg_reply, $msg_size);
}

