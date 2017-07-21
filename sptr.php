<?php

require('./lib/xpath.query.php');
require("./vendor/autoload.php");
require("./lib/class.redditconnector.php");
require("./lib/class.slackconnector.php");

$linted_services = ['spotify' => 'open.spotify.com','soundcloud' => 'soundcloud.com', 'youtube' => 'youtube.com', 'youtube2' => 'youtu.be'];
$output_debug = false;
$debug_data = [];

$rc = new RedditConnector();
$sc = new SlackConnector();

if (isset($_GET["newtoken"])) {
    $ret = $rc->getTokenAuth();
}elseif (isset($_GET["refreshtoken"])) {
    $ret = $rc->getAccessToken('refresh_token');
    die($ret);
}elseif (isset($_GET["code"])) {
    $ret = $rc->getAccessToken();
    die($ret);
}

$data = null;

$payload = file_get_contents('php://input');
if(!$payload)
    OAuth2Connector::logerror('no_payload');

if(strpos($payload, 'debugdata')!==false)
    $output_debug = true;

$data = json_decode($payload);
$event = $data->event;
$channel = $event->channel;
$system_message = in_array($event->subtype, ['channel_join', 'channel_leave', 'channel_topic', 'channel_name', 'channel_purpose'];

if(!$event)
    $debug_data[] = OAuth2Connector::logerror('missing_event_data',!$output_debug);

$token = $sc->getToken();

if(!$token)
    $debug_data[] = OAuth2Connector::logerror('slack_token_missing_from_settings', !$output_debug);

if(!$data->token)
    $debug_data[] = OAuth2Connector::logerror('no_token', !$output_debug);

if($token != $data->token)
    $debug_data[]= OAuth2Connector::logerror('token_mismatch',!$output_debug);

if(!$sc->checkChannelId($channel))
    die('wrong_source_channel');

$ret = $rc->checkToken();
if($ret !== true) {
    $out = [0 => 'reddit_token_check_fail', 1 => $ret];
    $sc->doBotMessage($ret, $channel);
    $debug_data[] = OAuth2Connector::logerror($out, !$output_debug);
}

if($event->type != 'message' && !$output_debug)
    die($event->type.' eventtype_not_supported');

if($event->deleted_ts)
    die('skip_delete_event');


if(count($debug_data) && !$system_message){
    $debug_data[] = $data;
    $sc->doBotMessage($debug_data, $channel);
    die();
}

$title = 'Slack Message received '.$data->event_id;
$url = null;
$ignore_post = false;

$message = $event->message;
OAuth2Connector::log($event);
if($message->attachments){
    $attachment = $message->attachments[0];
    if($attachment->service_name && in_array(strtolower($attachment->service_name), array_keys($linted_services))){
        $title = $attachment->title . " [posted by ".$sc->getUsername($message->user)."]";
        $url = $attachment->title_link;
    }
    if($event->previous_message)
        $previous_message = $event->previous_message->ts;
}else{
    $text = $event->text;
    $matches = [];
    if(preg_match('/.*(https?:\/\/[a-z0-9]+\.([a-z0-9]+\.)?([a-z]{2,3})(\/[^\s>]+)?).*/', $text, $matches)){
        $m = $matches[1];
        foreach($linted_services as $service){
            if(strpos($m, $service)) {
                $ignore_post = true;
                break;
            }
        }
        if(!$ignore_post){
            $url = $matches[1];
            $title = $url;
        }
    }

}

if($url) {
    $postdata = ['title' => $title, 'url' => $url];
    $res = $rc->postLink($postdata);

    //if($previous_message)
    //    $res = $rc->deleteLink($postdata);
}elseif($output_debug && !$system_message) {
    $sc->doBotMessage($data,$event->channel);
}else{
    if(!$event->event_ts || !$event->channel || $system_message || $ignore_post)
        return;
    $sc->deleteMessage($event->event_ts, $event->user, $event->channel);
//    $postdata = ['title' => $title, 'text' => print_r($data, true)];
//    $res = $rc->postText($postdata);
}

print_r($res);

//$response = $client->fetch("https://www.reddit.com/r/pics/search.json?q=kittens&sort=new");


//curl 'https://reddit.com/r/deuntjesdumpert/search.json?sort=new' -H 'Content-Type: application/json' --data-binary '{}'
//curl 'https://notbrony.bsolut.com/apiv2/user/pendingbets' -H 'Content-Type: application/json' --data-binary '{"page":1,"perPage":100,"session_id":"cibfn0sct2bj33e9v6aook3oq6"}'

//curl -X GET -L https://www.reddit.com/r/deuntjesdumpert/top/.json?count=20