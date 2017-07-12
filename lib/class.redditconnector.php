<?php

require_once('./lib/xpath.query.php');
require("./lib/oauth2/Client.php");
require("./lib/oauth2/GrantType/IGrantType.php");
require("./lib/oauth2/GrantType/AuthorizationCode.php");
require("./lib/oauth2/GrantType/RefreshToken.php");

class RedditConnector{

    private static $xpath_query;
    private static $access_token;
    private static $refresh_token;
    private static $user_agent;
    private static $client_id;
    private static $client_secret;
    private static $redirect_url;
    private static $subreddit;
    private static $authorize_url;
    private static $accesstoken_url;
    private static $client;

    public function __construct(){

        self::$xpath_query = new XPath_Query('./data/settings.xml');

        self::$access_token = self::$xpath_query->get_value('//settings/group[@type="reddit"]/item[@name="access_token"]');
        self::$refresh_token = self::$xpath_query->get_value('//settings/group[@type="reddit"]/item[@name="refresh_token"]');

        self::$user_agent = self::$xpath_query->get_value('//settings/group[@type="reddit"]/item[@name="user_agent"]');
        self::$client_id = self::$xpath_query->get_value('//settings/group[@type="reddit"]/item[@name="client_id"]');
        if(!self::$client_id) die('client ID not set!');
        self::$client_secret = self::$xpath_query->get_value('//settings/group[@type="reddit"]/item[@name="client_secret"]');
        if(!self::$client_secret) die('client secret not set!');
        self::$redirect_url = self::$xpath_query->get_value('//settings/group[@type="reddit"]/item[@name="redirect_url"]');
        if(!self::$redirect_url) die('redirect url not set!');

        self::$subreddit = self::$xpath_query->get_value('//settings/group[@type="reddit"]/item[@name="subreddit"]');
        if(!self::$subreddit) die('subreddit not set!');

        self::$authorize_url = 'https://ssl.reddit.com/api/v1/authorize';
        self::$accesstoken_url = 'https://ssl.reddit.com/api/v1/access_token';

        self::$client = new OAuth2\Client(self::$client_id, self::$client_secret, OAuth2\Client::AUTH_TYPE_AUTHORIZATION_BASIC);
        self::$client->setCurlOption(CURLOPT_USERAGENT,self::$user_agent);
    }

    static function getTokenAuth(){

        $authUrl = self::$client->getAuthenticationUrl(self::$authorize_url, self::$redirect_url, array("scope" => "read submit history", "state" => "SomeUnguessableValue24", "duration" => "permanent"));
        header("Location: ".$authUrl);
        die("Redirect");

    }

    static function getAccessToken($grant_type = 'authorization_code'){

        if($grant_type=='refresh_token')
            $params = array("refresh_token" => self::$refresh_token);
        else
            $params = array("code" => $_GET["code"], "redirect_uri" => self::$redirect_url);

        $response = self::$client->getAccessToken(self::$accesstoken_url, $grant_type, $params);

        echo('<strong>Response for access token:</strong><pre>');
        print_r($response);
        echo('</pre>');

        $accessTokenResult = $response["result"];
        if(array_key_exists('error', $accessTokenResult))
            return 'error getting token! error: '.$accessTokenResult['error'];

        $new_access_token = $accessTokenResult["access_token"];
        if($new_access_token == self::$access_token) {
            return true;
        }

        $token_node = self::$xpath_query->get_node('//settings/group[@type="reddit"]/item[@name="access_token"]');
        self::$xpath_query->set_value($token_node, $new_access_token);
        self::$xpath_query->set_attribute($token_node, 'updated_at', time());
        self::$xpath_query->saveFile();

        self::$access_token = $new_access_token;
        return true;

    }

    static function doTestCall(){
        self::$client->setAccessToken(self::$access_token);
        self::$client->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_BEARER);

        $response = self::$client->fetch("https://oauth.reddit.com/api/v1/me.json");

        echo('<strong>Response for fetch me.json:</strong><pre>');
        print_r($response);
        echo('</pre>');
    }

    static function refreshToken(){
        return self::getAccessToken('refresh_token');
    }

    static function checkToken(){
        $token_node = self::$xpath_query->get_node('//settings/group[@type="reddit"]/item[@name="access_token"]');
        $updated_at = $token_node->getAttribute('updated_at');

        if(time() - $updated_at >= 3600){
            return self::getAccessToken('refresh_token');
        }
        return true;
    }

    static function postLink($params){
        $c = self::$client;
        $postdata = ['api_type' => 'json', 'title' => $params['title'], 'url' => $params['url'], 'sr' => self::$subreddit, 'kind' => 'link'];
        self::$client->setAccessToken(self::$access_token);
        self::$client->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_BEARER);
        $res = self::$client->fetch("https://oauth.reddit.com/api/submit", $postdata, $c::HTTP_METHOD_POST);
        return $res;
    }

    static function postText($params){
        $c = self::$client;
        $postdata = ['api_type' => 'json', 'title' => $params['title'], 'text' => $params['text'], 'sr' => self::$subreddit, 'kind' => 'self'];
        self::$client->setAccessToken(self::$access_token);
        self::$client->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_BEARER);
        $res = self::$client->fetch("https://oauth.reddit.com/api/submit", $postdata, $c::HTTP_METHOD_POST);
        return $res;
    }

}
