<?php

namespace APIDigikeyBundle\Service;

use AppBundle\AppBundle;
use AppBundle\Entity\Article;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Supplier;
use AppBundle\Entity\Config;

class InterfaceDigikey
{
    private $api;
    private $em;
    private $supplier;

    function __construct(EntityManager $em, ApiDigikey $api, $supplierId = null)
    {
        $this->api = $api;
        $this->em = $em;

        if (!is_null($supplierId)) {
            $this->loadSupplier($supplierId);
        }

        $config = $this->em->getRepository(Config::class)->findOneBy(array('name' => $this->getClassName()));
        $parameters = $config->getParameters();

        $this->api->setConfig($parameters);
    }

    private function loadSupplier($supplierId)
    {
        $this->supplier = $this->em->getRepository(Supplier::class)->find($supplierId);
        $this->api->setParameters($this->supplier->getParameters());
    }

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

        $this->updateSupplier();

        return $response;
    }

    public function partDetails($userAgent, $keyword, $supplierId = null) {
        if(!is_null($supplierId))
        {
            $this->loadSupplier($supplierId);
        }

        $response = $this->api->partDetails($userAgent, $keyword);

        $this->updateSupplier();

        return $response;
    }

    public function packageTypeByQuantity($userAgent, $keyword, $packagingPreference, $quantity, $supplierId = null) {
        if(!is_null($supplierId))
        {
            $this->loadSupplier($supplierId);
        }

        $response = $this->api->packageTypeByQuantity($userAgent, $keyword, $packagingPreference, $quantity);

        $this->updateSupplier();

        return $response;
    }

    public function packageTypeByQuantityInArticle($userAgent, $keyword, $packagingPreference, $quantity, $supplierId = null) {
        $result = $this->packageTypeByQuantity($userAgent, $keyword, $packagingPreference, $quantity, $supplierId);

        if(is_string($result))
            return $result;

        return $this->convertPartsInArticles(array($result));
    }

    public function search($userAgent, $keyword, $supplierId = null) {
        $keywordResults = $this->keywordSearch($userAgent, $keyword, $supplierId);

        if(is_string($keywordResults))
            return $keywordResults;

        $detailResults = array();
        foreach ($keywordResults["Parts"] as $result) {
            $detailResult = $this->partDetails($userAgent, $result["DigiKeyPartNumber"]);

            if(is_string($detailResult))
                return $detailResult;

            $detailResults[] = $detailResult;
        }

        return $this->convertPartDetailsInArticles($detailResults);
    }

    private function convertPartDetailsInArticles($resultArray) {
        $articleArray = array();
        foreach ($resultArray as $result) {
            $part = $result['PartDetails'];
            $article = $this->convertPartInArticle($part);
            $articleArray[] = $article;
        }

        return $articleArray;
    }

    private function convertPartsInArticles($resultArray) {
        $articleArray = array();
        foreach ($resultArray as $result) {
            foreach ($result['Parts'] as $part) {
                $article = $this->convertPartInArticle($part);
                $articleArray[] = $article;
            }
        }

        return $articleArray;
    }

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

    public function getClassName(){
        return "InterfaceDigikey";
    }
}
