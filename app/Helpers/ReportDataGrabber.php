<?php
namespace App\Helpers;

class ReportDataGrabber
{
    static private $curl;
    static private $status;

    function __construct($headers) {
        
        self::$curl = curl_init();
        curl_setopt(self::$curl, CURLOPT_POST, true);
        curl_setopt(self::$curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt(self::$curl, CURLOPT_RETURNTRANSFER, true);
        
        return self::$curl;
    }

    private static function runCurl($url, $payload)
    {
        curl_setopt(self::$curl, CURLOPT_URL, $url);
        curl_setopt(self::$curl, CURLOPT_POSTFIELDS, $payload);
        $msg = curl_exec(self::$curl);
        $resp = json_decode($msg);
        
        if($resp) self::$status = $resp->message == 'success';
        return $resp;
    }

    public static function getDataFromHO($data, $category)
    {
        $url = 'https://trucking-ho.jmp-logistic.co.id/service/api/vehicle-cost';
        self::$status = false;
        
        $data->dateStart = $data->dateStart.'T17:00:00.000Z';
        $data->dateEnd = $data->dateEnd.'T17:00:00.000Z';
        $data->category = $category ?? 'Semua';
        $payload = json_encode($data);

        $responses = self::runCurl($url, $payload);
        if(self::$status) return $responses->data;
        
        $data = array();
        return $data;
    }
    
    public static function getDataFromKlari($data, $entity = ["id" => 1,"name" =>"Saldo JMP"], $kategori = 'K')
    {
        $url = 'https://klari.jmp-logistic.co.id/service/api/undirect';
        
        self::$status = false;
        
        $data->category = $kategori;
        $data->entity = (object) $entity;
        $payload = json_encode($data);
        
        $responses = self::runCurl($url, $payload);
        if(self::$status) return $responses->data;
        
        $data = array();
        return $data;
    }
    
    public static function getDataFromCdp($data, $group = 'K')
    {
        $url = 'https://cdp-kontrak.jmp-logistic.co.id/service/api/undirect';
        
        self::$status = false;
        
        $data->group = $group;
        $payload = json_encode($data);
        
        $responses = self::runCurl($url, $payload);
        if(self::$status) return $responses->data;

        $data = array();
        return $data;
    }
}