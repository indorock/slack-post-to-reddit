<?php

require_once('./lib/xpath.query.php');

class SlackConnector
{

    private static $xpath_query;
    private static $client_id;
    private static $client_secret;
    private static $verification_token;
    private static $allowed_channels;

    public function __construct(){

        self::$xpath_query = new XPath_Query('./data/settings.xml');
        self::$client_id = self::$xpath_query->get_value('//settings/group[@type="slack"]/item[@name="client_id"]');
        self::$client_secret = self::$xpath_query->get_value('//settings/group[@type="slack"]/item[@name="client_secret"]');
        self::$verification_token = self::$xpath_query->get_value('//settings/group[@type="slack"]/item[@name="verification_token"]');
        self::$allowed_channels = self::$xpath_query->get_value('//settings/group[@type="slack"]/item[@name="allowed_channel_ids"]');
    }

    public function getToken(){
        return self::$verification_token;
    }

    public function checkChannelId($channel_id){
        $allowed_channel_ids = explode(',',self::$allowed_channels);
        return in_array($channel_id, $allowed_channel_ids);
    }

    public function getUsername($user_id){
        return $user_id;
    }
}

