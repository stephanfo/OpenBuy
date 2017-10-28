<?php

namespace APIDigikeyBundle\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

class ApiDigikey
{
    private $loginPage;
    private $redirectUri;
    private $clientId;
    private $clientSecret;
    private $customerId;
    private $code;
    private $tokenPage;
    private $token;
    private $expiration;
    private $refreshToken;
    private $keywordSearchUri;
    private $partDetailsUri;
    private $localSite;
    private $localLanguage;
    private $localCurrency;
    private $localShipToCountry;
    private $partnerId;

    public $configUpdated;

    function __construct($config = null)
    {
        if (is_null($config)) {
            $this->setConfig($this->getDefaultConfig());
            $this->configUpdated = true;
        } else {
            $this->setConfig($config);
            $this->configUpdated = false;
        }
    }

    public function getDefaultConfig()
    {
        return [
            'loginPage' => "https://sso.digikey.com/as/authorization.oauth2",
            'redirectUri' => "https://openbuy.dev/api/digikey/code",
            'clientId' => null,
            'clientSecret' => null,
            'customerId' => null,
            'code' => null,
            'tokenPage' => "https://sso.digikey.com/as/token.oauth2",
            'token' => null,
            'expiration' => null,
            'refreshToken' => null,
            'keywordSearchUri' => "https://api.digikey.com/services/partsearch/v2/keywordsearch",
            'partDetailsUri' => "https://api.digikey.com/services/partsearch/v2/partdetails",
            'localSite' => "fr",
            'localLanguage' => "fr",
            'localCurrency' => "EUR",
            'localShipToCountry' => "FR",
            'partnerId' => null,
        ];
    }

    public function getConfig()
    {
        return [
            'loginPage' => $this->loginPage,
            'redirectUri' => $this->redirectUri,
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'customerId' => $this->customerId,
            'code' => $this->code,
            'tokenPage' => $this->tokenPage,
            'token' => $this->token,
            'expiration' => $this->expiration,
            'refreshToken' => $this->refreshToken,
            'keywordSearchUri' => $this->keywordSearchUri,
            'partDetailsUri' => $this->partDetailsUri,
            'localSite' => $this->localSite,
            'localLanguage' => $this->localLanguage,
            'localCurrency' => $this->localCurrency,
            'localShipToCountry' => $this->localShipToCountry,
            'partnerId' => $this->partnerId,
        ];
    }

    public function setConfig($config)
    {
        $this->loginPage = $config['loginPage'];
        $this->redirectUri = $config['redirectUri'];
        $this->clientId = $config['clientId'];
        $this->clientSecret = $config['clientSecret'];
        $this->customerId = $config['customerId'];
        $this->code = $config['code'];
        $this->tokenPage = $config['tokenPage'];
        $this->token = $config['token'];
        $this->expiration = $config['expiration'];
        $this->refreshToken = $config['refreshToken'];
        $this->keywordSearchUri = $config['keywordSearchUri'];
        $this->partDetailsUri = $config['partDetailsUri'];
        $this->localSite = $config['localSite'];
        $this->localLanguage = $config['localLanguage'];
        $this->localCurrency = $config['localCurrency'];
        $this->localShipToCountry = $config['localShipToCountry'];
        $this->partnerId = $config['partnerId'];

        $this->configUpdated = true;
    }

    public function linkLoginPage()
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
        ];

        return $this->loginPage . "?" . http_build_query($params);
    }

    public function revoke()
    {
        $this->code = null;
        $this->token = null;
        $this->expiration = null;
        $this->refreshToken = null;

        $this->configUpdated = true;

        return $this->getConfig();
    }

    public function setCode($code)
    {
        $this->code = $code;
        $this->configUpdated = true;

        return $this->getConfig();
    }

    public function retrieveToken($userAgent)
    {
        $clientHttp = new Client(
            array(
                'verify' => false,
                'cookies' => true,
                'headers' => array(
                    'User-Agent' => $userAgent
                )
            )
        );

        $form_params = [
            'code' => $this->code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        ];

        try {
            $response = $clientHttp->request('POST', $this->tokenPage, [
                'form_params' => $form_params,
            ]);
        } catch (RequestException $e) {
            return Psr7\str($e->getResponse());
        }

        $content = json_decode($response->getBody()->getContents(), true);

        $this->token = $content["access_token"];
        $this->expiration = new \DateTime('+ ' . $content["expires_in"] . ' seconds');
        $this->refreshToken = $content["refresh_token"];

        $this->configUpdated = true;

        return $this->getConfig();
    }

    public function refreshToken($userAgent)
    {
        $clientHttp = new Client(
            array(
                'verify' => false,
                'cookies' => true,
                'headers' => array(
                    'User-Agent' => $userAgent
                )
            )
        );

        $form_params = [
            'refresh_token' => $this->refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
        ];

        try {
            $response = $clientHttp->request('POST', $this->tokenPage, [
                'form_params' => $form_params,
            ]);
        } catch (RequestException $e) {
            return Psr7\str($e->getResponse());
        }

        $content = json_decode($response->getBody()->getContents(), true);

        $this->token = $content["access_token"];
        $this->expiration = new \DateTime('+ ' . $content["expires_in"] . ' seconds');
        $this->refreshToken = $content["refresh_token"];

        $this->configUpdated = true;

        return $this->getConfig();
    }

    private function isTokenValid() {
        $now = new \DateTime();

        if ($this->expiration < $now)
            return false;

        return true;
    }

    private function handleConnection($userAgent)
    {
        if(!$this->isTokenValid())
            $this->refreshToken($userAgent);

        return true;
    }

    public function keywordSearch($userAgent, $keyword, $recordCount = 20)
    {
        $this->handleConnection($userAgent);

        $clientHttp = new Client(
            array(
                'verify' => false,
                'cookies' => true,
                'headers' => array(
                    'User-Agent' => $userAgent
                )
            )
        );

        $headers = array(
            'X-DIGIKEY-Locale-Site' => $this->localSite,
            'X-DIGIKEY-Locale-Language' => $this->localLanguage,
            'X-DIGIKEY-Locale-Currency' => $this->localCurrency,
            'X-DIGIKEY-Locale-ShipToCountry' => $this->localShipToCountry,
            'X-DIGIKEY-Customer-Id' => $this->customerId,
            'X-DIGIKEY-Partner-Id' => $this->partnerId,
            'X-IBM-Client-Id' => $this->clientId,
            'Authorization' => $this->token,
        );

        $searchRequest = array(
            'Keywords' => $keyword,
            'RecordCount' => $recordCount,
            'RecordStartPosition' => 0,
        );

        try {
            $response = $clientHttp->request('POST', $this->keywordSearchUri, ['headers' => $headers, 'json' => $searchRequest]);
        } catch (RequestException $e) {
            return Psr7\str($e->getResponse());
        }

        return json_decode($response->getBody()->getContents());
    }

    public function partDetailSearch($userAgent, $keyword)
    {
        $this->handleConnection($userAgent);

        $clientHttp = new Client(
            array(
                'verify' => false,
                'cookies' => true,
                'headers' => array(
                    'User-Agent' => $userAgent
                )
            )
        );

        $headers = array(
            'X-DIGIKEY-Locale-Site' => $this->localSite,
            'X-DIGIKEY-Locale-Language' => $this->localLanguage,
            'X-DIGIKEY-Locale-Currency' => $this->localCurrency,
            'X-DIGIKEY-Locale-ShipToCountry' => $this->localShipToCountry,
            'X-DIGIKEY-Customer-Id' => $this->customerId,
            'X-DIGIKEY-Partner-Id' => $this->partnerId,
            'X-IBM-Client-Id' => $this->clientId,
            'Authorization' => $this->token,
        );

        $searchRequest = array(
            'Part' => $keyword,
        );

        try {
            $response = $clientHttp->request('POST', $this->partDetailsUri, ['headers' => $headers, 'json' => $searchRequest]);
        } catch (RequestException $e) {
            return Psr7\str($e->getResponse());
        }

        return json_decode($response->getBody()->getContents());
    }

    public function getClassName()
    {
        return "ApiDigikey";
    }
}
