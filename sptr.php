<?php

require("./lib/class.redditconnector.php");
require("./lib/class.slackconnector.php");


$data = null;

$payload = file_get_contents('php://input');
if(!$payload)
    die('no_payload');

$rc = new RedditConnector();
$rc->checkToken();

$data = json_decode($payload);
if(!$data->token)
    die('no_token');

$sc = new SlackConnector();
$token = $sc->getToken();

if($token != $data->token)
    die('token_mismatch');

if(!$data->event)
    die('missing_event_data');

$event = $data->event;

if($event->type != 'message')
    die($event->type.' eventtype_not_supported');

//if($event->channel != 'C63M3RX9T')
//    die('wrong_source_channel');


$title = 'Slack Event received '.$data->event_id;
$url = null;

$message = $event->message;
if($message->attachments){
    $attachment = $attachments[0];
    if($attachment->service_name && $attachment->service_name == 'Spotify'){
        $title = $attachment->title . " [posted by ".$sc->getUsername($message->user)."]";
        $url = $attachment->title_link;
    }

    if(!$url)
        die('no_url_found');

    $postdata = ['title' => $title, 'url' => $url];
    $res = $rc->postLink($postdata);

}else{
    $postdata = ['title' => $title, 'text' => print_r($data, true)];
    $res = $rc->postText($postdata);
}

print_r($res);


//$response = $client->fetch("https://www.reddit.com/r/pics/search.json?q=kittens&sort=new");


//curl 'https://reddit.com/r/deuntjesdumpert/search.json?sort=new' -H 'Content-Type: application/json' --data-binary '{}'
//curl 'https://notbrony.bsolut.com/apiv2/user/pendingbets' -H 'Content-Type: application/json' --data-binary '{"page":1,"perPage":100,"session_id":"cibfn0sct2bj33e9v6aook3oq6"}'

//curl -X GET -L https://www.reddit.com/r/deuntjesdumpert/top/.json?count=20