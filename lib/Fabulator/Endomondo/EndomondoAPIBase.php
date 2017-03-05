<?php

namespace Fabulator\Endomondo;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\SetCookie;
use Psr\Http\Message\ResponseInterface;

class EndomondoAPIBase
{

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $csrf = '-first-';

    /**
     * @var string
     */
    private $userId;

    public function __construct() {
        $this->client = new Client([
            'base_uri' => 'https://www.endomondo.com/',
            'cookies' => true,
        ]);
    }

    /**
     * Login user to endomondo.
     *
     * @param $username string
     * @param $password string
     * @return ResponseInterface
     */
    public function login($username, $password)
    {
        $response = $this->request('POST', 'rest/session', [
            'email' => $username,
            'password' => $password,
            'remember' => true,
        ]);

        $this->userId = json_decode((string) $response->getBody(), true)['id'];

        return $response;
    }

    /**
     * Generate Endomondo csfr token for update actions
     */
    private function generateCSRFToken()
    {
        $response = $this->request('GET', '/users/' . $this->userId);

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
        return (bool) count($this->client->getConfig()['cookies']->toArray());
    }

    /**
     * @param $method string http method
     * @param $endpoint string Endomondo endpoint
     * @param array $data
     * @return ResponseInterface;
     */
    public function request($method, $endpoint, $data = [])
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
        return $this->request('GET', $endpoint);
    }

    /**
     * @param $endpoint string
     * @param $data array
     * @return ResponseInterface
     */
    public function post($endpoint, $data)
    {
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * @param $endpoint string
     * @param $data array
     * @return ResponseInterface
     */
    public function put($endpoint, $data)
    {
        return $this->request('PUT', $endpoint, $data);
    }

    /**
     * @param $endpoint string
     * @return ResponseInterface
     */
    public function delete($endpoint)
    {
        return $this->request('DELETE', $endpoint);
    }

}