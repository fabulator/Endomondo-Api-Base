<?php

namespace Fabulator\Endomondo;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Cookie\CookieJar;
use Psr\Http\Message\ResponseInterface;

/**
 * Class EndomondoAPIBase
 * @package Fabulator\Endomondo
 */
class EndomondoAPIBase
{

    /**
     * @var Client
     */
    private $client;

    /**
     * CSFR endomondo token.
     *
     * @var string
     */
    private $csrf = '-first-';

    /**
     * @var string
     */
    protected $userId;

    /**
     * EndomondoAPIBase constructor.
     */
    public function __construct() {
        $this->client = new Client([
            'base_uri' => 'https://www.endomondo.com/',
            'cookies' => true,
        ]);
    }

    /**
     * Login user to Endomondo.
     *
     * @param $username string
     * @param $password string
     * @return ResponseInterface
     */
    public function login($username, $password)
    {
        return $this->send('POST', 'rest/session', [
            'email' => $username,
            'password' => $password,
            'remember' => true,
        ]);
    }

    /**
     * Generate Endomondo csfr token for update actions
     */
    protected function generateCSRFToken()
    {
        $response = $this->client->get('/users/' . $this->userId);

        foreach ($response->getHeaders()['Set-Cookie'] as $item) {
            $cookie = SetCookie::fromString($item);
            if ($cookie->getName() === 'CSRF_TOKEN') {
                $this->csrf = $cookie->getValue();
            }
        }
    }

    /**
     * Is user logged in?
     *
     * @return bool
     */
    private function isUserLoggedIn()
    {
        /** @var CookieJar $cookies */
        $cookies = $this->client->getConfig()['cookies'];
        return (bool) count($cookies->toArray());
    }

    /**
     * @param $method string http method
     * @param $endpoint string Endomondo endpoint
     * @param array $data
     * @return ResponseInterface;
     */
    public function send($method, $endpoint, $data = [])
    {
        $method = strtolower($method);

        if ($method !== 'get' && $this->isUserLoggedIn()) {
            $this->generateCSRFToken();
        }

        // set auth data and post data
        $options = [
            'body' => $method === 'post' || $method === 'put' ? json_encode($data) : null,
            'headers' => [
                'Content-Type' => 'application/json',
                'Cookie' => 'CSRF_TOKEN=' . $this->csrf,
                'X-CSRF-TOKEN' => $this->csrf,
            ]
        ];

        return $this->client->$method($endpoint, $options);
    }

    /**
     * @param $endpoint string
     * @return ResponseInterface
     */
    public function get($endpoint)
    {
        return $this->send('GET', $endpoint);
    }

    /**
     * @param $endpoint string
     * @param $data array
     * @return ResponseInterface
     */
    public function post($endpoint, $data)
    {
        return $this->send('POST', $endpoint, $data);
    }

    /**
     * @param $endpoint string
     * @param $data array
     * @return ResponseInterface
     */
    public function put($endpoint, $data)
    {
        return $this->send('PUT', $endpoint, $data);
    }

    /**
     * @param $endpoint string
     * @return ResponseInterface
     */
    public function delete($endpoint)
    {
        return $this->send('DELETE', $endpoint);
    }

    /**
     * @param $id string
     */
    public function setUserId($id)
    {
        $this->userId = $id;
    }

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

}