<?php

namespace LightHttpClient;

use SimplePHPAdapters\ProxiesProviderInterface;

class HttpClientProxy extends HttpClient
{
    /**
     * Count of access attempts through the same proxy
     *
     * @var int
     */
    public $attempts = 1;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var ProxiesProvider
     */
    private $proxiesProvider;

    private $useProxies = false;

    /**
     * runs in as single process, so here proxy should not be set
     * because all instances will have the same proxy
     *
     * @param HttpClient $httpClient
     * @param ProxiesProvider $proxiesProvider
     */
    function __construct(HttpClient $httpClient, ProxiesProviderInterface $proxiesProvider)
    {
        $this->httpClient = $httpClient;
        $this->httpClient->setCurlOption(CURLOPT_FOLLOWLOCATION, true);
        $this->httpClient->setCurlOption(CURLOPT_MAXREDIRS, 1);
        $this->httpClient->setCurlOption(CURLOPT_CONNECTTIMEOUT, 5);
        $this->httpClient->setCurlOption(CURLOPT_TIMEOUT, 5);
//        $this->httpClient->setCurlOption(CURLOPT_COOKIEFILE, null);
//        $this->httpClient->setCurlOption(CURLOPT_COOKIEJAR, null);
        $this->proxiesProvider = $proxiesProvider;
    }

    public function __destruct()
    {
        $this->proxiesProvider->releaseProxy();
    }

    /**
     * @param bool $val
     */
    public function setUseProxies($val)
    {
        $this->useProxies = $val;
    }

    public function get($url, $data = null)
    {
        if ($this->useProxies) {
            $response = $this->getWithProxy($url, $data);
        } else {
            $response = $this->getWithoutProxy($url, $data);
        }

        return $response;
    }

    private function getWithoutProxy($url, $data)
    {
        $attempts = 0;
        do {
            $attempts++;
            $this->httpClient->resetCookie();
            $response = $this->httpClient->get($url, $data);
        } while ($attempts < $this->attempts && !$response);

        return $response;
    }

    private function getWithProxy($url, $data = null)
    {
        $response = null;
        $attempts = 0;

        try {
            do {
                $attempts++;

                if (!isset($proxy)) {
                    $proxy = $this->proxiesProvider->provideProxy();
                } else {
                    $proxy = $this->proxiesProvider->nextProxy();
                }

                $this->changeCurrentProxy($proxy);
                $this->httpClient->resetCookie();
                $response = $this->httpClient->get($url, $data);
            } while ($attempts < $this->attempts && !$response);
        } catch (NoMoreProxiesException $e) {
            // no more proxies in redis cache
        }

        return $response;
    }

    private function changeCurrentProxy($proxy)
    {
        $this->httpClient->setCurlOption(CURLOPT_PROXY, $proxy);
    }
}