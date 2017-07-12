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

        $response = $this->client->getAccessToken($this->accesstoken_url, $grant_type, $params);

        echo('<strong>Response for access token:</strong><pre>');
        print_r($response);
        echo('</pre>');

        $accessTokenResult = $response["result"];
        if(array_key_exists('error', $accessTokenResult))
            return 'error getting token! error: '.$accessTokenResult['error'];

        $new_access_token = $accessTokenResult["access_token"];
        if($new_access_token == $this->access_token) {
            return true;
        }

        $refresh_token = $accessTokenResult["refresh_token"];

        $token_node = $this->xpath_query->get_node('//settings/group[@type="'.$this->group.'"]/item[@name="access_token"]');
        $this->xpath_query->set_value($token_node, $new_access_token);
        $this->xpath_query->set_attribute($token_node, 'updated_at', time());

        if($refresh_token != $this->refresh_token) {
            $refresh_node = $this->xpath_query->get_node('//settings/group[@type="'.$this->group.'"]/item[@name="refresh_token"]');
            $this->xpath_query->set_value($refresh_node, $new_access_token);
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



}