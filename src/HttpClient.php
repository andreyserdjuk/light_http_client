<?php

namespace LightHttpClient;

/**
 * Class HttpClient
 * cURL adapter
 */
class HttpClient
    implements HttpClientInterface
{
    const CURLE_OPERATION_TIMEDOUT = 28;

    protected $curlOptions = [
        CURLOPT_URL => null,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_COOKIEFILE => null,
        CURLOPT_COOKIEJAR => null,
        CURLOPT_HEADER => true,
        CURLOPT_COOKIESESSION => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.114 Safari/537.36'
    ];


    public function __construct($cookiesDir)
    {
        if (!is_dir($cookiesDir)) {
            if(!mkdir($cookiesDir, 0755)){
                throw new \RuntimeException("Cannot create dir for cookies: $cookiesDir");
            }
        }

        $this->curlOptions[CURLOPT_COOKIEFILE] = $cookiesDir . '/cookie_file.txt';
        $this->curlOptions[CURLOPT_COOKIEJAR] = $cookiesDir . '/cookie_file.txt';
    }

    public function post($url, $data)
    {
        return $this->http_request($url, $data, true);
    }

    public function postFile($url, $pathToFile, $fileName=false)
    {
        if($fileName){
            $pathToFile .= ';filename=' . $fileName;
        }

        return $this->http_request(
            $url,
            array('file' => '@' . $pathToFile),
            true
        );
    }

    public function get($url, $data = null)
    {
        return $this->http_request($url, $data);
    }

    protected function http_request($url, $data = null, $isPost = false, $extraData=array())
    {
        $curlOptions = $this->curlOptions;
        $curlOptions += $extraData;

        $request_data = array();
        if (is_array($data) && !empty($data)) {
            foreach ($data as $key => $value) {
                $request_data[] = "$key=" . urlencode($value);
            }
            $request_data = join('&', $request_data);
        } else {
            $request_data = $data;
        }

        if (!empty($request_data)) {
            if ($isPost) {
                $curlOptions[CURLOPT_POST] = 1;
                $curlOptions[CURLOPT_POSTFIELDS] = $data;
            } else {
                $url = $url . '?' . $request_data;
            }
        }

        $curlOptions[CURLOPT_URL] = $url;
        $ch = curl_init();
        $ch = self::parseCurlOptions($ch, $curlOptions);
        $response = curl_exec($ch);
        
        if (curl_errno($ch) == self::CURLE_OPERATION_TIMEDOUT) {
            new \RuntimeException('Timeout is up', self::CURLE_OPERATION_TIMEDOUT);
        }

        curl_close( $ch );
        return $response;
    }

    public function setCurlOption($key, $value)
    {
        $this->curlOptions[$key] = $value;
    }

    public function resetCookie()
    {
        @unlink($this->curlOptions[CURLOPT_COOKIEFILE]);
    }

    protected static function parseCurlOptions($ch, $curlOptions)
    {
        foreach ($curlOptions as $key => $val) {
            if($val !== null){
                curl_setopt($ch, $key, $val);
            }
        }
        return $ch;
    }
}