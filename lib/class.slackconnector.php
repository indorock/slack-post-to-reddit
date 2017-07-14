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
        self::sptr_log('get username for '.$user_id);
        $ret = $this->client->fetch('https://slack.com/api/users.info?token='.$this->access_token.'&user='.$user_id);
        if(!$ret || $ret['result']['error']) {
            self::sptr_logerror('cannot fetch slack user info!', false);
            return $user_id;
        }
        return $ret['result']['user']['name'];
    }

    public function deleteMessage($ts, $user_id, $channel_id){
        if(!$this->checkChannelId($channel_id))
            return;
        $ret = $this->client->fetch('https://slack.com/api/chat.delete?token='.$this->access_token.'&ts='.$ts.'&channel='.$channel_id);
        $name = $this->getUsername($user_id);
        $ret = $this->client->fetch('https://slack.com/api/chat.postMessage?token='.$this->access_token.'&channel='.$channel_id.'&text='.urlencode('Yo '.$name.', ik moest je laatste bericht verwijderen, omdat het blijkbaar geen link is. Alléén deuntjes hier dumpen, gesnopen?'));
    }
}
