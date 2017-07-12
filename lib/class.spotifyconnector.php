<?php

require_once('./lib/class.oauth2connector.php');

class SpotifyConnector extends OAuth2Connector{

    public function __construct(){

        parent::__construct('spotify');

//        self::$authorize_url = "https://slack.com/oauth/authorize";

    }


}

