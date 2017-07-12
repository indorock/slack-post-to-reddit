<?php

require('./lib/xpath.query.php');
require("./vendor/autoload.php");
require("./lib/class.redditconnector.php");
require("./lib/class.slackconnector.php");

function sptr_log($data){
    $dt = new DateTime();
    $fs = file_put_contents('data/log.txt', $data ." Datetime:".$dt->format('Y-m-d H:i:s')."\r\n", FILE_APPEND);
}

function sptr_logerror($data, $halt=true){
    $dt = new DateTime();
    $fs = file_put_contents('data/error_log.txt', "ERROR: ". $data ." Datetime:".$dt->format('Y-m-d H:i:s')."\r\n", FILE_APPEND);
    if($halt)
        die($data);
}

$rc = new RedditConnector();

if (isset($_GET["newtoken"])) {
    $ret = $rc->getTokenAuth();
}elseif (isset($_GET["code"])) {
    $ret = $rc->getAccessToken();
    die($ret);
}

$data = null;

$payload = file_get_contents('php://input');
if(!$payload)
    sptr_logerror('no_payload');

$ret = $rc->checkToken();
if($ret !== true)
    sptr_logerror($ret);


$data = json_decode($payload);
if(!$data->token)
    sptr_logerror('no_token');


$sc = new SlackConnector();
$token = $sc->getToken();
if(!$token)
    sptr_logerror('cannot_get_slack_token');


if($token != $data->token)
    sptr_logerror('token_mismatch');


if(!$data->event)
    sptr_logerror('missing_event_data');


$event = $data->event;

if($event->type != 'message')
    die($event->type.' eventtype_not_supported');


if($event->deleted_ts)
    die('skip_delete_event');

if(!$sc->checkChannelId($event->channel))
    die('wrong_source_channel');


$title = 'Slack Message received '.$data->event_id;
$url = null;

$message = $event->message;
if($message->attachments){
    $attachment = $message->attachments[0];
    if($attachment->service_name && strtolower($attachment->service_name) == 'spotify'){
        $title = $attachment->title . " [posted by ".$sc->getUsername($message->user)."]";
        $url = $attachment->title_link;
    }
}else{
    $text = $event->text;
    $matches = [];
//    if(preg_match('/(https:\/\/open\.spotify\.com\/track\/[a-zA-Z0-9]+)/', $text, $matches)){
//        $url = $matches[1];
//    }
}
if($url){
    $postdata = ['title' => $title, 'url' => $url];
    $res = $rc->postLink($postdata);
}else{
//    $postdata = ['title' => $title, 'text' => print_r($data, true)];
//    $res = $rc->postText($postdata);
}

print_r($res);

//$response = $client->fetch("https://www.reddit.com/r/pics/search.json?q=kittens&sort=new");


//curl 'https://reddit.com/r/deuntjesdumpert/search.json?sort=new' -H 'Content-Type: application/json' --data-binary '{}'
//curl 'https://notbrony.bsolut.com/apiv2/user/pendingbets' -H 'Content-Type: application/json' --data-binary '{"page":1,"perPage":100,"session_id":"cibfn0sct2bj33e9v6aook3oq6"}'

//curl -X GET -L https://www.reddit.com/r/deuntjesdumpert/top/.json?count=20