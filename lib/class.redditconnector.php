<?php

require_once('./lib/class.oauth2connector.php');

class RedditConnector extends OAuth2Connector{

    protected $user_agent;
    protected $subreddit;

    /**
     * RedditConnector constructor.
     */
    public function __construct(){

        parent::__construct('reddit');

        $this->user_agent = $this->xpath_query->get_value('//settings/group[@type="reddit"]/item[@name="user_agent"]');
        $this->client->setCurlOption(CURLOPT_USERAGENT, $this->user_agent);

        $this->subreddit = $this->xpath_query->get_value('//settings/group[@type="reddit"]/item[@name="subreddit"]');
        if(!$this->subreddit) die('subreddit not set!');

        $this->authorize_url = 'https://ssl.reddit.com/api/v1/authorize';
        $this->accesstoken_url = 'https://ssl.reddit.com/api/v1/access_token';
        $this->scope = "read submit history";
        $this->state = "redditauth123456789";
        $this->extra_params = ["duration" => "permanent"];
    }

    /**
     * @throws \OAuth2\Exception
     */
    public function doTestCall(){
        $this->client->setAccessToken($this->access_token);
        $this->client->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_BEARER);

        $response = $this->client->fetch("https://oauth.reddit.com/api/v1/me.json");

        echo('<strong>Response for fetch me.json:</strong><pre>');
        print_r($response);
        echo('</pre>');
    }

    /**
     * @param $params
     * @return array
     * @throws \OAuth2\Exception
     */
    public function postLink($params){
        $c = $this->client;
        $postdata = ['api_type' => 'json', 'title' => $params['title'], 'url' => $params['url'], 'sr' => $this->subreddit, 'kind' => 'link'];
        $c->setAccessToken($this->access_token);
        $c->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_BEARER);
        $res = $c->fetch("https://oauth.reddit.com/api/submit", $postdata, $c::HTTP_METHOD_POST);
        return $res;
    }

    /**
     * @param $params
     * @return array
     * @throws \OAuth2\Exception
     */
    public function postText($params){
        $c = $this->client;
        $postdata = ['api_type' => 'json', 'title' => $params['title'], 'text' => $params['text'], 'sr' => $this->subreddit, 'kind' => 'self'];
        $c->setAccessToken($this->access_token);
        $c->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_BEARER);
        $res = $c->fetch("https://oauth.reddit.com/api/submit", $postdata, $c::HTTP_METHOD_POST);
        return $res;
    }

}
