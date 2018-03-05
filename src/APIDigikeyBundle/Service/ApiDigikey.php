<?php

namespace APIDigikeyBundle\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

class ApiDigikey
{
    const loginPage = "https://sso.digikey.com/as/authorization.oauth2";
    const tokenPage = "https://sso.digikey.com/as/token.oauth2";
    const keywordSearchUri = "https://api.digikey.com/services/partsearch/v2/keywordsearch";
    const partDetailsUri = "https://api.digikey.com/services/partsearch/v2/partdetails";
    const packageTypeUri = "https://api.digikey.com/services/packagetypebyquantity/v2/search";

    private $redirectUri;
    private $clientId;
    private $clientSecret;

    private $customerId;
    private $code;
    private $token;
    private $expiration;
    private $refreshToken;
    private $localSite;
    private $localLanguage;
    private $localCurrency;
    private $localShipToCountry;
    private $partnerId;

    public $parametersUpdated;

    function __construct($config = null, $parameters = null)
    {
        if (is_array($config)){
            $this->setConfig($config);
        }

        if (is_array($parameters)) {
            $this->setParameters($parameters);
            $this->parametersUpdated = false;
        } else {
            $this->setParameters($this->getDefaultParameters());
            $this->parametersUpdated = true;
        }
    }

    public function setConfig($config)
    {
        $this->redirectUri = $config['redirectUri'];
        $this->clientId = $config['clientId'];
        $this->clientSecret = $config['clientSecret'];
    }

    public function getDefaultParameters()
    {
        return [
            'customerId' => null,
            'code' => null,
            'token' => null,
            'expiration' => null,
            'refreshToken' => null,
            'localSite' => "fr",
            'localLanguage' => "fr",
            'localCurrency' => "EUR",
            'localShipToCountry' => "FR",
            'partnerId' => null,
        ];
    }

    public function getParameters()
    {
        return [
            'customerId' => $this->customerId,
            'code' => $this->code,
            'token' => $this->token,
            'expiration' => $this->expiration,
            'refreshToken' => $this->refreshToken,
            'localSite' => $this->localSite,
            'localLanguage' => $this->localLanguage,
            'localCurrency' => $this->localCurrency,
            'localShipToCountry' => $this->localShipToCountry,
            'partnerId' => $this->partnerId,
        ];
    }

    public function setParameters($parameters)
    {
        $this->customerId = $parameters['customerId'];
        $this->code = $parameters['code'];
        $this->token = $parameters['token'];
        $this->expiration = $parameters['expiration'];
        $this->refreshToken = $parameters['refreshToken'];
        $this->localSite = $parameters['localSite'];
        $this->localLanguage = $parameters['localLanguage'];
        $this->localCurrency = $parameters['localCurrency'];
        $this->localShipToCountry = $parameters['localShipToCountry'];
        $this->partnerId = $parameters['partnerId'];

        $this->paramsUpdated = true;
    }

    public function linkLoginPage()
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
        ];

        return self::loginPage . "?" . http_build_query($params);
    }

    public function revoke()
    {
        $this->code = null;
        $this->token = null;
        $this->expiration = null;
        $this->refreshToken = null;

        $this->paramsUpdated = true;

        return $this->getParameters();
    }

    public function setCode($code)
    {
        $this->code = $code;
        $this->paramsUpdated = true;

        return $this->getParameters();
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
            $response = $clientHttp->request('POST', self::tokenPage, [
                'form_params' => $form_params,
            ]);
        } catch (RequestException $e) {
            return Psr7\str($e->getResponse());
        }

        $content = json_decode($response->getBody()->getContents(), true);

        $this->token = $content["access_token"];
        $this->expiration = new \DateTime('+ ' . $content["expires_in"] . ' seconds');
        $this->refreshToken = $content["refresh_token"];

        $this->paramsUpdated = true;

        return $this->getParameters();
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
            $response = $clientHttp->request('POST', self::tokenPage, [
                'form_params' => $form_params,
            ]);
        } catch (RequestException $e) {
            return Psr7\str($e->getResponse());
        }

        $content = json_decode($response->getBody()->getContents(), true);

        $this->token = $content["access_token"];
        $this->expiration = new \DateTime('+ ' . $content["expires_in"] . ' seconds');
        $this->refreshToken = $content["refresh_token"];

        $this->paramsUpdated = true;

        return $this->getParameters();
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
        $searchRequest = array(
            'Keywords' => $keyword,
            'RecordCount' => $recordCount,
            'RecordStartPosition' => 0,
        );

        return $this->sendRequest($userAgent, self::keywordSearchUri, $searchRequest);
    }

    public function partDetails($userAgent, $keyword)
    {
        $searchRequest = array(
            'Part' => $keyword,
        );

        return $this->sendRequest($userAgent, self::partDetailsUri, $searchRequest);

    }

    public function packageTypeByQuantity($userAgent, $keyword, $packagingPreference, $quantity)
    {
        $searchRequest = array(
            'PartNumber' => $keyword,
            'Quantity' => $quantity,
            'PartPreference' => $packagingPreference,
        );

        return $this->sendRequest($userAgent, self::packageTypeUri, $searchRequest);

    }

    private function sendRequest($userAgent, $apiUri, $request)
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

        try {
            $response = $clientHttp->request('POST', $apiUri, ['headers' => $headers, 'json' => $request]);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return Psr7\str($e->getResponse());
            }
            return Psr7\str($e->getRequest());
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    public function getClassName()
    {
        return "ApiDigikey";
    }
}
