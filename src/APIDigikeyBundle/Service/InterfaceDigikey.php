<?php

namespace APIDigikeyBundle\Service;

use AppBundle\Entity\Article;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Supplier;

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
    }

    private function loadSupplier($supplierId)
    {
        $this->supplier = $this->em->getRepository(Supplier::class)->find($supplierId);
        $this->api->setConfig($this->supplier->getParameters());
    }

    private function updateSupplier()
    {
        if ($this->api->configUpdated) {
            $this->supplier->setParameters($this->api->getConfig());
            $this->em->flush();
        }
    }

    public function getConfig($supplierId) {
        if (!is_null($supplierId)) {
            $this->loadSupplier($supplierId);
        }

        if(is_null($this->supplier->getParameters())) {
            return $this->api->getDefaultConfig();
        } else {
            return $this->api->getConfig();
        }
    }

    public function setConfig($config) {
        $this->api->setConfig($config);
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

    public function partDetailSearch($userAgent, $keyword, $supplierId = null) {
        if(!is_null($supplierId))
        {
            $this->loadSupplier($supplierId);
        }

        $response = $this->api->partDetailSearch($userAgent, $keyword);

        $this->updateSupplier();

        return $response;
    }

//    public function search($userAgent, $keyword, $supplierId = null) {
//        $keywordResults = $this->keywordSearch($userAgent, $keyword, $supplierId);
//
//        if(is_string($keywordResults))
//            return $keywordResults;
//
//        $detailResults = array();
//        foreach ($keywordResults["Parts"] as $result) {
//            $detailResult = $this->partDetailSearch($userAgent, $result["DigiKeyPartNumber"]);
//
//            if(is_string($detailResult))
//                return $detailResult;
//
//            $detailResults[] = $detailResult;
//        }
//
//        return $this->convertInArticles($detailResults);
//    }

    public function search($userAgent, $keyword, $supplierId = null) {
        $keywordResults = $this->keywordSearch($userAgent, $keyword, $supplierId);

        if(is_string($keywordResults))
            return $keywordResults;

        $detailResults = array();
        foreach ($keywordResults["Parts"] as $result) {
            if($result["ManufacturerPartNumber"] == $keyword)
            {
                $detailResult = $this->partDetailSearch($userAgent, $result["DigiKeyPartNumber"]);

                if(is_string($detailResult))
                    return $detailResult;

                $detailResults[] = $detailResult;
            }
        }

        return $this->convertInArticles($detailResults);
    }

    private function convertInArticles($resultArray) {

        $articleArray = array();
        foreach ($resultArray as $result) {
            $part = $result['PartDetails'];

            $article = $this->em->getRepository('AppBundle:Article')->findOneBy(array(
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

            $article->createVariable((int)$part["ManufacturerLeadWeeks"] * 7, $part["QuantityOnHand"], 2, new \DateTime("now + 1 month"), null, $pricingArray);

            $this->em->persist($article);
            $this->em->flush();

            $articleArray[] = $article;
        }

        return $articleArray;
    }

    public function getClassName()
    {
        return "InterfaceDigikey";
    }
}
