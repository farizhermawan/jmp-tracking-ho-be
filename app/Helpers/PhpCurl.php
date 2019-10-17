<?php
namespace App\Helpers;

use Curl\Curl;

class PhpCurl
{
    static private $curl;
    static private $status;
    function __construct($headers) {
        self::$curl = new Curl();
        self::$curl->setHeaders($headers);
        return self::$curl;
    }

    private static function runCurl($url, $data)
    {
        self::$curl->setOpt(CURLOPT_POSTFIELDS, $data);
        $msg = self::$curl->post($url, $data);
        if($msg) self::$status = $msg->message == 'success';
        return $msg;
    }

    public static function getData($url, $data = null, $source = 'HO')
    {
        self::$status = false;
        $responses = self::runCurl($url, $data);
        $data = array();
        if(self::$status){
            foreach ($responses->data as $value) {
                $value->source = $source;
                $data [] = $value;
            }
        }
        return $data;
    }
}