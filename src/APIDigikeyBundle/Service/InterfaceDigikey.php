<?php

namespace APIDigikeyBundle\Service;

use APIDigikeyBundle\Entity\Transaction;
use AppBundle\AppBundle;
use AppBundle\Entity\Article;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Supplier;
use AppBundle\Entity\Config;

class InterfaceDigikey
{
    private $api;
    private $em;
    public $supplier;

    /*
     * Load the Supplier if Id submitted and load the config
     */
    function __construct(EntityManager $em, ApiDigikey $api, $supplierId = null)
    {
        $this->api = $api;
        $this->em = $em;

        if (!is_null($supplierId)) {
            $this->loadSupplier($supplierId);
        }

        $config = $this->em->getRepository(Config::class)->findOneBy(array('name' => $this->getClassName()));
        $this->api->setConfig($config->getParameters());
    }

    /*
     * Retrive the Supplier Parameters configuration from the ORM
     */
    private function loadSupplier($supplierId)
    {
        $this->supplier = $this->em->getRepository(Supplier::class)->find($supplierId);
        $this->api->setParameters($this->supplier->getParameters());
    }

    /*
     * Store Supplier Parameters in the ORM
     */
    private function updateSupplier()
    {
        if ($this->api->parametersUpdated) {
            $this->supplier->setParameters($this->api->getParameters());
            $this->em->flush();
        }
    }

    public function getParameters($supplierId) {
        if (!is_null($supplierId)) {
            $this->loadSupplier($supplierId);
        }

        if(is_null($this->supplier->getParameters())) {
            return $this->api->getDefaultParameters();
        } else {
            return $this->api->getParameters();
        }
    }

    public function setParameters($parameters) {
        $this->api->setParameters($parameters);
        $this->updateSupplier();
    }

    public function getTransactionDetails()
    {
        return $this->api->getTransactionDetails();
    }

    public function revoke($supplierId = null)
    {
        if(!is_null($supplierId))
        {
            $this->loadSupplier($supplierId);
        }

        $this->api->revoke();

        $this->updateSupplier();
    }

    public function linkLoginPage($supplierId = null)
    {
        if(!is_null($supplierId))
        {
            $this->loadSupplier($supplierId);
        }

        return $this->api->linkLoginPage();
    }

    /*
     * Store the Authorisation code and retrieve the Token, expiration and Refresh Token
     */
    public function setCodeAndToken($userAgent, $code, $supplierId = null)
    {
        if(!is_null($supplierId))
        {
            $this->loadSupplier($supplierId);
        }

        $this->api->setCode($code);

        $response = $this->api->retrieveToken($userAgent);

        if (!is_array($response)) {
            return $response;
        } else {
            $this->updateSupplier();
            return true;
        }
    }

    public function retrieveToken($userAgent, $supplierId = null)
    {
        if(!is_null($supplierId))
        {
            $this->loadSupplier($supplierId);
        }

        $response = $this->api->retrieveToken($userAgent);

        if (!is_array($response)) {
            return $response;
        } else {
            $this->updateSupplier();
            return true;
        }
    }

    public function refreshToken($userAgent, $supplierId = null)
    {
        if(!is_null($supplierId))
        {
            $this->loadSupplier($supplierId);
        }

        $response = $this->api->refreshToken($userAgent);

        if (!is_array($response)) {
            return $response;
        } else {
            $this->updateSupplier();
            return true;
        }
    }

    public function keywordSearch($userAgent, $keyword, $supplierId = null) {
        if(!is_null($supplierId))
        {
            $this->loadSupplier($supplierId);
        }

        $response = $this->api->keywordSearch($userAgent, $keyword);

        $this->storeTransactionDetails();
        $this->updateSupplier();

        return $response;
    }

    public function partDetails($userAgent, $keyword, $supplierId = null) {
        if(!is_null($supplierId))
        {
            $this->loadSupplier($supplierId);
        }

        $response = $this->api->partDetails($userAgent, $keyword);

        $this->storeTransactionDetails();
        $this->updateSupplier();

        return $response;
    }

    public function packageTypeByQuantity($userAgent, $keyword, $packagingPreference, $quantity, $supplierId = null) {
        if(!is_null($supplierId))
        {
            $this->loadSupplier($supplierId);
        }

        $response = $this->api->packageTypeByQuantity($userAgent, $keyword, $packagingPreference, $quantity);

        $this->storeTransactionDetails();
        $this->updateSupplier();

        return $response;
    }

    public function packageTypeByQuantityInArticle($userAgent, $keyword, $packagingPreference, $quantity, $supplierId = null) {
        $result = $this->packageTypeByQuantity($userAgent, $keyword, $packagingPreference, $quantity, $supplierId);

        if(is_string($result))
            return $result;

        return $this->convertPartsInArticles($result);
    }

    /*
     * Return a list of Articles/Variables after performing a keywordSearch and looping in partDetails on all results
     */
    public function search($userAgent, $keyword, $supplierId = null) {
        $keywordResults = $this->keywordSearch($userAgent, $keyword, $supplierId);

        if(is_string($keywordResults))
            return $keywordResults;

        $detailResults = array();
        foreach ($keywordResults["Parts"] as $part) {
            $detailResult = $this->partDetails($userAgent, $part["DigiKeyPartNumber"]);

            if(is_string($detailResult))
                return $detailResult;

            $detailResults[] = $this->convertPartInArticle($detailResult['PartDetails']);
        }

        return $detailResults;
    }

    /*
     * Conversion of multiple parts answer to a set of Articles/Variables
     * This format is aligned with the answer of packageTypeByQuantity & keywordSearch APIs
     */
    private function convertPartsInArticles($resultArray) {
        $articleArray = array();
        foreach ($resultArray['Parts'] as $part) {
            $article = $this->convertPartInArticle($part);
            $articleArray[] = $article;
        }

        return $articleArray;
    }

    /*
     * Convert a single part from Digikey API to an Article/Variable entity
     *
     * If the entity Article already exists, this will update the data and attach a new variable entity
     * If the entity Article does not exist, this will create both Article and Variable entity
     *
     * Entities are retrieved and flushed in the function
     * Return the Article entity and the Variables
     */
    private function convertPartInArticle ($part) {
        $article = $this->em->getRepository(Article::class)->findOneBy(array(
            'supplier' => array($this->supplier),
            'sku' => array($part['DigiKeyPartNumber']),
        ));

        if(is_null($article)) {
            $article = new Article();
            $article->setSku($part['DigiKeyPartNumber']);
            $article->setSupplier($this->supplier);
        }

        $article->setMfrName($part["ManufacturerName"]["Text"]);
        $article->setMfrPn($part["ManufacturerPartNumber"]);
        $article->setPackage($part["Packaging"]["Value"]);
        $article->setMoq($part["MinimumOrderQuantity"]);
        $article->setLink($part["PartUrl"]);
        $article->setDescription($part["ProductDescription"]);
        $article->setPicture($part["PrimaryPhoto"]);

        $attributes = array();
        foreach ($part["Parameters"] as $parameter) {
            $attributes[] = array(
                "parameter" => $parameter["Parameter"],
                "value" => $parameter["Value"]
            );
        }

        $article->setExtra(array(
            'families' => array(
                'l1' => $part["Category"]["Text"] ? $part["Category"]["Text"] : null,
                'l2' => $part["Family"]["Text"] ? $part["Family"]["Text"] : null,
            ),
            'rohsStatus' => $part["RohsInfo"] ? $part["RohsInfo"] : null,
            'leadStatus' => $part["LeadStatus"] ? $part["LeadStatus"] : null,
            'partStatus' => $part["PartStatus"] ? $part["PartStatus"] : null,
            'obsolete' => $part["Obsolete"] ? $part["Obsolete"] : null,
            'nonStock' => $part["NonStock"] ? $part["NonStock"] : null,
            'datasheet' => $part["PrimaryDatasheet"] ? $part["PrimaryDatasheet"] : null,
            'attributes' => count($attributes) > 0 ? array("attribute" => $attributes) : null,
        ));

        $pricingArray = array();
        foreach ($part["StandardPricing"] as $price) {
            $pricingArray[$price["BreakQuantity"]] = $price["UnitPrice"];
        }
        foreach ( $part["MyPricing"] as $price) {
            $pricingArray[$price["BreakQuantity"]] = $price["UnitPrice"];
        }
        ksort($pricingArray);

        $previousPrice = null;
        foreach ($pricingArray as $quantity => $price) {
            if(!is_null($previousPrice))
            {
                if($previousPrice < $price)
                {
                    unset($pricingArray[$quantity]);
                }
                else if ($price < $previousPrice)
                {
                    $previousPrice = $price;
                }
            }
            else
            {
                $previousPrice = $price;
            }
        }

        $article->createVariable((int)$part["ManufacturerLeadWeeks"] * 7, $part["QuantityOnHand"], 2, new \DateTime("now + 1 day"), null, $pricingArray);

        $this->em->persist($article);
        $this->em->flush();

        return $article;
    }

    public function storeTransactionDetails()
    {
        $details = $this->api->getTransactionDetails();

        $transaction = new Transaction();
        $transaction->setSupplier($this->supplier);
        $transaction->setRequestHeaders($details['requestHeaders']);
        $transaction->setRequestUri($details['requestUri']);
        $transaction->setRequestBody($details['requestBody']);
        $transaction->setResponseTime($details['responseTime']);
        $transaction->setResponseStatusCode($details['responseStatusCode']);
        $transaction->setResponseReasonPhrase($details['responseReasonPhrase']);
        $transaction->setResponseHeaders($details['responseHeaders']);
        $transaction->setResponseErrorMessage($details['responseErrorMessage']);
        $transaction->setResponseErrorDetails($details['responseErrorDetails']);
        $transaction->setResponseBody($details['responseBody']);

        $this->em->persist($transaction);
        $this->em->flush();
    }

    public function getClassName(){
        return "InterfaceDigikey";
    }
}
