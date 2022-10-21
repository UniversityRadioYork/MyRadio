<?php

namespace MyRadio\MyRadio;

use MyRadio\Config;
use MyRadio\MyRadioException;
use MyRadio\ServiceAPI\MyRadio_User;

class LoggerNGAPI
{
    private string $baseURL;
    private $ch;

    private function __construct($baseURL)
    {
        $this->baseURL = $baseURL;
        $this->ch = curl_init();
    }

    private static self $instance;
    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self(Config::$loggerng_url);
        }
        return self::$instance;
    }

    public function make(string $title, int $startTime, int $endTime, string $format = 'mp3', int $memberid = null)
    {
        if ($memberid === null) {
            $memberid = MyRadio_User::getCurrentOrSystemUser()->getID();
        }
        try {
            return $this->request('make', [
                'user' => $memberid,
                'start' => $startTime,
                'end' => $endTime,
                'format' => $format,
                'title' => $title
            ]);
        } catch (MyRadioException $e) {
            if ($e->getCode() === 403 && strpos($e->getMessage(), 'this user is requesting a log that they have already requested') !== false) {
                return true;
            }
            throw $e;
        }
    }

    public function download(string $title, int $startTime, int $endTime, string $format = 'mp3', int $memberid = null)
    {
        if ($memberid === null) {
            $memberid = MyRadio_User::getCurrentOrSystemUser()->getID();
        }
        return $this->request('download', [
            'user' => $memberid,
            'start' => $startTime,
            'end' => $endTime,
            'format' => $format,
            'title' => $title
        ]);
    }

    private function request(string $action, array $params, int $expected = 200): array
    {
        $url = $this->baseURL;
        if ($url[strlen($url) - 1] !== '/') {
            $url .= '/';
        }
        $url = $url.$action.'?'.http_build_query($params);
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($this->ch);
        if ($response === false) {
            throw new MyRadioException('LoggerNG API Error: '.curl_error($this->ch));
        }
        $code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        if ($code !== $expected) {
            throw new MyRadioException('LoggerNG API Error: ' . $response, $code);
        }
        $response = json_decode($response, true);
        if ($response === null) {
            throw new MyRadioException('LoggerNG API Error: '.json_last_error_msg());
        }
        return $response;
    }
}
