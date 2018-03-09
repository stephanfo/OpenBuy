<?php

namespace APIDigikeyBundle\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

/*
 * Stephanfo (Stéphane RATELET)
 *
 * This class provide the connection to the Digikey API
 *
 * It is important to understand the difference between the config and the parameters.
 *
 * Config is the parameters requested for the 3rd party requester (please refer to OAuth2.0 and the Digi-Key API portal)
 *   the config should be common for a platform and independent to the users
 *   the config is especially used for the auth process to get the code and manage the tokens
 * Parameters are specific to users and suppliers, and are used for the header of the requests
 *   this can change the language, site, devise, ...
 *   you should use one parameters per user/supplier
 */

class ApiDigikey
{
    // Constants
    const loginPage = "https://sso.digikey.com/as/authorization.oauth2";
    const tokenPage = "https://sso.digikey.com/as/token.oauth2";
    const keywordSearchUri = "https://api.digikey.com/services/partsearch/v2/keywordsearch";
    const partDetailsUri = "https://api.digikey.com/services/partsearch/v2/partdetails";
    const packageTypeUri = "https://api.digikey.com/services/packagetypebyquantity/v2/search";

    // Config datas - common to any users in a single platform
    private $redirectUri;
    private $clientId;
    private $clientSecret;

    // Paramters - specific user by user
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

    // Constructor that load the config and parameters if they are sent
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

    // Load config in the class
    public function setConfig($config)
    {
        $this->redirectUri = $config['redirectUri'];
        $this->clientId = $config['clientId'];
        $this->clientSecret = $config['clientSecret'];
    }

    // Generate the default parameters
    public function getDefaultParameters()
    {
        return [
            'customerId' => null,
            'code' => null,
            'token' => null,
            'expiration' => null,
            'refreshToken' => null,
            'localSite' => "FR",
            'localLanguage' => "fr",
            'localCurrency' => "EUR",
            'localShipToCountry' => "FR",
            'partnerId' => null,
        ];
    }

    // Send the current parameters
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

    // Load the parameters sent
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

    /*
     * Return the link to the Digikey SSO to allow the user to enter his digikey.com credentials
     * see this page for more details: https://api-portal.digikey.com/app_overview#authorizationCode
     */
    public function linkLoginPage()
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
        ];

        return self::loginPage . "?" . http_build_query($params);
    }

    /*
     * Revoke the current connection data
     */
    public function revoke()
    {
        $this->code = null;
        $this->token = null;
        $this->expiration = null;
        $this->refreshToken = null;

        $this->paramsUpdated = true;

        return $this->getParameters();
    }

    /*
     * Store the authorisation code
     */
    public function setCode($code)
    {
        $this->code = $code;
        $this->paramsUpdated = true;

        return $this->getParameters();
    }

    /*
     * Exchange the authorisation code for a Token, its expiration and Refresh Token
     * This is a simple http post
     * In case of fail, the function return the PSR7 error string
     *
     * Check this page for more details: https://api-portal.digikey.com/app_overview#accessToken
     */
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

    /*
     * Exchange the Refresh Token for a fresh Token/Refresh token
     * This is a simple http post
     * In case of fail, the function return the PSR7 error string
     *
     * Check this page for more details: https://api-portal.digikey.com/app_overview#refreshToken
     */
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

    /*
     * Check if the token is still valid
     * This is based on the expiration only
     */
    private function isTokenValid() {
        $now = new \DateTime();

        if ($this->expiration < $now)
            return false;

        return true;
    }

    /*
     * Refresh the token if case it expired
     * This should be executed before every requests
     */
    private function handleConnection($userAgent)
    {
        if(!$this->isTokenValid())
            $this->refreshToken($userAgent);

        return true;
    }

    /*
     * Perform a Keyword search and return the result in php array or a string in case of error
     *
     * Check this page for more details: https://api-portal.digikey.com/node/3287
     */
    public function keywordSearch($userAgent, $keyword, $recordCount = 20)
    {
        $searchRequest = array(
            'Keywords' => $keyword,
            'RecordCount' => $recordCount,
            'RecordStartPosition' => 0,
        );

        return $this->sendRequest($userAgent, self::keywordSearchUri, $searchRequest);
    }

    /*
     * Perform a PartDetail request and return the result in php array or a string in case of error
     *
     * Check this page for more details: https://api-portal.digikey.com/node/3287
     */
    public function partDetails($userAgent, $keyword)
    {
        $searchRequest = array(
            'Part' => $keyword,
        );

        return $this->sendRequest($userAgent, self::partDetailsUri, $searchRequest);

    }

    /*
     * Perform a package search based on quantity and return the result in php array or a string in case of error
     *
     * Check this page for more details: https://api-portal.digikey.com/node/3287
     */
    public function packageTypeByQuantity($userAgent, $keyword, $packagingPreference, $quantity)
    {
        $searchRequest = array(
            'PartNumber' => $keyword,
            'Quantity' => $quantity,
            'PartPreference' => $packagingPreference,
        );

        return $this->sendRequest($userAgent, self::packageTypeUri, $searchRequest);

    }

    /*
     * Manage the APIs requests, then return the JSON response converted in a PHP array
     *
     * Return PSR7 string in case of an error occurs
     */
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

    /*
     * Name od the class
     */
    public function getClassName()
    {
        return "ApiDigikey";
    }
}
