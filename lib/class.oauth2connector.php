<?php

class OAuth2Connector{

    protected $xpath_query;
    protected $access_token;
    protected $refresh_token;
    protected $client_id;
    protected $client_secret;
    protected $redirect_url;
    protected $authorize_url;
    protected $accesstoken_url;
    protected $group;
    protected $client;
    protected $scope;
    protected $state;
    protected $extra_params = [];

    /**
     * OAuth2Connector constructor.
     * @param $group
     */
    public function __construct($group){
        $this->group = $group;
        $this->xpath_query = new XPath_Query('./data/settings.xml');

        $this->access_token = $this->xpath_query->get_value('//settings/group[@type="'.$this->group.'"]/item[@name="access_token"]');
        $this->refresh_token = $this->xpath_query->get_value('//settings/group[@type="'.$this->group.'"]/item[@name="refresh_token"]');

        $this->client_id = $this->xpath_query->get_value('//settings/group[@type="'.$this->group.'"]/item[@name="client_id"]');
        if(!$this->client_id) die($group.' client ID not set!');
        $this->client_secret = $this->xpath_query->get_value('//settings/group[@type="'.$this->group.'"]/item[@name="client_secret"]');
        if(!$this->client_secret) die($group.' client secret not set!');
        $this->redirect_url = $this->xpath_query->get_value('//settings/group[@type="'.$this->group.'"]/item[@name="redirect_url"]');
        if(!$this->redirect_url) die($group.' redirect url not set!');

        $this->client = new OAuth2\Client($this->client_id, $this->client_secret, OAuth2\Client::AUTH_TYPE_AUTHORIZATION_BASIC);
    }

    /**
     *
     */
    public function getTokenAuth(){
        $params = ["scope" => $this->scope, "state" => $this->state];
        $params = array_merge($params, $this->extra_params);
        $authUrl = $this->client->getAuthenticationUrl($this->authorize_url, $this->redirect_url, $params);
        header("Location: ".$authUrl);
        die("Redirect");
    }

    /**
     * @param string $grant_type
     * @return bool|string
     * @throws \OAuth2\Exception
     */
    public function getAccessToken($grant_type = 'authorization_code'){

        if($grant_type=='refresh_token')
            $params = ["refresh_token" => $this->refresh_token];
        else
            $params = ["code" => $_GET["code"], "redirect_uri" => $this->redirect_url];

        self::log($params);
        $response = $this->client->getAccessToken($this->accesstoken_url, $grant_type, $params);
        echo('<strong>Response for access token:</strong><pre>');
        echo "refresh_token:".$this->refresh_token;
        print_r($response);
        echo('</pre>');
        self::log(print_r($response, true));
        $accessTokenResult = $response["result"];
        if(array_key_exists('error', $accessTokenResult))
            return 'error getting token! url: '.$this->accesstoken_url.' granttype:'.$grant_type.' error: '.$accessTokenResult['error'] . 'details:'.print_r($accessTokenResult, true);

        $new_access_token = $accessTokenResult["access_token"];
        if($grant_type=='refresh_token' && !$new_access_token)
            self::sptr_logerror('error in getting access token!!');

        if($new_access_token == $this->access_token) {
            return true;
        }

        $token_node = $this->xpath_query->get_node('//settings/group[@type="'.$this->group.'"]/item[@name="access_token"]');
        $this->xpath_query->set_value($token_node, $new_access_token);
        $this->xpath_query->set_attribute($token_node, 'updated_at', time());

        $refresh_token = $accessTokenResult["refresh_token"];
        if($grant_type == 'authorization_code' && $refresh_token && $refresh_token != $this->refresh_token) {
            $refresh_node = $this->xpath_query->get_node('//settings/group[@type="'.$this->group.'"]/item[@name="refresh_token"]');
            $this->xpath_query->set_value($refresh_node, $refresh_token);
            $this->xpath_query->set_attribute($refresh_node, 'updated_at', time());
        }

        $this->xpath_query->saveFile();

        $this->access_token = $new_access_token;
        return true;
    }

    /**
     * @return bool|string
     */
    public function refreshToken(){
        return $this->getAccessToken('refresh_token');
    }

    /**
     * @return bool|string
     */
    public function checkToken(){
        $token_node = $this->xpath_query->get_node('//settings/group[@type="'.$this->group.'"]/item[@name="access_token"]');
        $updated_at = $token_node->getAttribute('updated_at');

        if(time() - $updated_at >= 3600){
            return $this->getAccessToken('refresh_token');
        }
        return true;
    }

    public static function log($data){
        $dt = new DateTime();
        if(is_array($data) || is_object($data))
            $data = print_r($data, true);
        $fs = file_put_contents('data/log.txt', $data ." Datetime:".$dt->format('Y-m-d H:i:s')."\r\n", FILE_APPEND);
    }

    public static function logerror($data, $halt=true){
        $dt = new DateTime();
        if(is_array($data) || is_object($data))
            $data = print_r($data, true);
        $fs = file_put_contents('data/error_log.txt', "ERROR: ". $data ." Datetime:".$dt->format('Y-m-d H:i:s')."\r\n", FILE_APPEND);
        if($halt)
            die($data);
        return $data;
    }
}