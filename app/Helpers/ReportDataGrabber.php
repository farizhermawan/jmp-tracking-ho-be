<?php
namespace App\Helpers;

class ReportDataGrabber
{
    const HO_URL    = 'https://trucking-ho.jmp-logistic.co.id/service/api/vehicle-cost';
    const CDP_URL   = 'https://cdp-kontrak.jmp-logistic.co.id/service/api/undirect';
    const KLARI_URL = 'https://klari.jmp-logistic.co.id/service/api/undirect';

    private $curl;
    private $status;

    function __construct($headers) {
        
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        
        return $this->curl;
    }

    private function runCurl($url, $payload)
    {
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $payload);
        $msg = curl_exec($this->curl);
        $resp = json_decode($msg);
        
        if($resp) $this->status = $resp->message == 'success';
        return $resp;
    }

    public function getDataFromHO($data, $category)
    {
        $this->status = false;
        
        $data->dateStart = $data->dateStart.'T17:00:00.000Z';
        $data->dateEnd = $data->dateEnd.'T17:00:00.000Z';
        $data->category = $category ?? 'Semua';
        $payload = json_encode($data);

        $responses = self::runCurl(self::HO_URL, $payload);
        if($this->status) return $responses->data;
        
        $data = array();
        return $data;
    }
    
    public function getDataFromKlari($data, $entity = ["id" => 1,"name" =>"Saldo JMP"], $kategori = 'K')
    {        
        $this->status = false;
        
        $data->category = $kategori;
        $data->entity = (object) $entity;
        $payload = json_encode($data);
        
        $responses = self::runCurl(self::KLARI_URL, $payload);
        if($this->status) return $responses->data;
        
        $data = array();
        return $data;
    }
    
    public function getDataFromCdp($data, $group = 'K')
    {
        
        $this->status = false;
        
        $data->group = $group;
        $payload = json_encode($data);
        
        $responses = self::runCurl(self::CDP_URL, $payload);
        if($this->status) return $responses->data;

        $data = array();
        return $data;
    }
}