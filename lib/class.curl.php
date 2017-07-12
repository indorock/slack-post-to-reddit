<?php

class Curl{

    private static $curl_info = null;

    static $default_opts = array(
        CURLOPT_HEADER => 0,
        CURLOPT_HTTPHEADER => [],
        CURLOPT_FRESH_CONNECT => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FORBID_REUSE => 1,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERPWD => null,
        CURLOPT_VERBOSE => 0,
        CURLOPT_POST => 0,
    );

    static $options = array();

    static function getOptions(){
        return self::$options + self::$default_opts;
    }

    static function getCurlInfo() {
        return self::$curl_info;
    }

    static function call($url, $opts = array(), $postdata = NULL, $headers = array(), $get_info = false) {

        $options = self::getOptions();

        $options[CURLOPT_URL] = $url;
        if(!isset($opts[CURLOPT_PORT])) {
            $parsedUrl = parse_url($url);
            if($parsedUrl === false)
                throw new Exception("Curl->call() cant parse url");
            if (isset($parsedUrl['port']))
                $options[CURLOPT_PORT] = $parsedUrl['port'];
        }

        if (is_array($postdata)) {
            if(@$opts[CURLOPT_CUSTOMREQUEST]!='PUT')
                $options[CURLOPT_POST] = 1;
            $options[CURLOPT_POSTFIELDS] = http_build_query($postdata);
        } else if($postdata !== null) {
            $options[CURLOPT_POSTFIELDS] = $postdata;
        }

        $ch = curl_init();
        if($opts)
            $options = $opts + $options;
        if(is_array($headers)){
            $headers = array_merge(self::$default_opts[CURLOPT_HTTPHEADER],$headers);
		}

        curl_setopt_array($ch, $options);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers);


        //ob_start();
print_r($url);
echo "<br>";
print_r($postdata);
        $result = curl_exec($ch);
        if ($get_info)
            self::$curl_info = curl_getinfo($ch);

        //ob_end_clean();
        if($result === false) {
            $error_no = curl_errno($ch);
            curl_close ($ch);
            $suffix = '';
            throw new Exception_Curl('Curl->call() error #'. $error_no.$suffix,$error_no);
        }
        curl_close ($ch);

        return $result;
    }
}