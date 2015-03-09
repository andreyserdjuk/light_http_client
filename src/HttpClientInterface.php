<?php

namespace LightHttpClient;

interface HttpClientInterface
{
    public function get($url, $data = null);

    public function post($url, $data);

    public function postFile($url, $pathToFile, $fileName=false);

    public function setCurlOption($key, $value);

    public function resetCookie();
}