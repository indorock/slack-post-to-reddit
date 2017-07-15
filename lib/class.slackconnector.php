<?php

require_once('./lib/class.oauth2connector.php');

class SlackConnector extends OAuth2Connector{

    private $allowed_channels;
    private $verification_token;

    public function __construct(){

        parent::__construct('slack');

        $this->allowed_channels = $this->xpath_query->get_value('//settings/group[@type="slack"]/item[@name="allowed_channel_ids"]');
        $this->verification_token = $this->xpath_query->get_value('//settings/group[@type="slack"]/item[@name="verification_token"]');

        $this->authorize_url = "https://slack.com/oauth/authorize";
        $this->accesstoken_url = "https://slack.com/oauth/authorize";
        $this->scope = "identity.basic";
        $this->state = "redditauth123456789";
    }

    public function getToken(){
        return $this->verification_token;
    }

    public function checkChannelId($channel_id){
        $allowed_channel_ids = explode(',',$this->allowed_channels);
        return in_array($channel_id, $allowed_channel_ids);
    }

    public function getUsername($user_id){
        self::log('get username for '.$user_id);
        $ret = $this->client->fetch('https://slack.com/api/users.info?token='.$this->access_token.'&user='.$user_id);
        if(!$ret || $ret['result']['error']) {
            self::logerror('cannot fetch slack user info!', false);
            return $user_id;
        }
        return $ret['result']['user']['name'];
    }

    public function deleteMessage($ts, $user_id, $channel_id){
        if(!$this->checkChannelId($channel_id))
            return;
        $ret = $this->client->fetch('https://slack.com/api/chat.delete?token='.$this->access_token.'&ts='.$ts.'&channel='.$channel_id);
        $name = $this->getUsername($user_id);
        return $this->doBotMessage("Yo [name], ik moest je laatste bericht verwijderen, omdat het blijkbaar geen link is. Alléén deuntjes hier dumpen, gesnopen?", $channel_id, ['name' => $name]);
    }

    public function doBotMessage($msg, $channel_id, $params=[]){
        if(!$this->checkChannelId($channel_id))
            return;

        if(is_object($msg) || is_array($msg))
            $msg = var_export($msg, true);

        if(strpos($msg, 'bot_message')!==false) // we don't output payload from bot_message to prevent endless loop
            return;

        foreach($params as $key => $val)
            $msg = str_replace('['.$key.']', $val, $msg);

        $url = 'https://slack.com/api/chat.postMessage?token='.$this->access_token.'&channel='.$channel_id.'&text='.urlencode($msg);
        return $this->client->fetch($url);
    }
}
