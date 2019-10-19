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
        $ho = (object)$data;
        $ho->dateStart = $ho->dateStart.'T17:00:00.000Z';
        $ho->dateEnd = $ho->dateEnd.'T17:00:00.000Z';
        $ho->category = $category ?? 'Semua';
        $payload = json_encode($ho);

        $responses = self::runCurl($url, $payload);
        if(self::$status) return $responses->data;
        
        $data = array();
        return $data;
    }
    
    public static function getDataFromKlari($data, $entity = ["id" => 1,"name" =>"Saldo JMP"], $kategori = 'K')
    {
        $url = 'https://klari.jmp-logistic.co.id/service/api/undirect';
        
        self::$status = false;
        $klari = (object) $data;
        $klari->category = $kategori;
        $klari->entity = (object) $entity;
        $payload = json_encode($klari);
        
        $responses = self::runCurl($url, $payload);
        if(self::$status) return $responses->data;
        
        $data = array();
        return $data;
    }
    
    public static function getDataFromCdp($data, $group = 'K')
    {
        $url = 'https://cdp-kontrak.jmp-logistic.co.id/service/api/undirect';
        
        self::$status = false;
        $cdp = (object) $data;
        $cdp->group = $group;
        $payload = json_encode($cdp);
        
        $responses = self::runCurl($url, $payload);
        if(self::$status) return $responses->data;

        $data = array();
        return $data;
    }
}