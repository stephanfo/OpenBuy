<?php

namespace APIDigikeyBundle\Service;

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

    function loadSupplier($supplierId)
    {
        $this->supplier = $this->em->getRepository(Supplier::class)->find($supplierId);
        $this->api->setConfig($this->supplier->getParameters());
    }

    function updateSupplier()
    {
        if ($this->api->configUpdated) {
            $this->supplier->setParameters($this->api->getConfig());
            $this->em->flush();
        }
    }

    function getConfig($supplierId) {
        if (!is_null($supplierId)) {
            $this->loadSupplier($supplierId);
        }

        if(is_null($this->supplier->getParameters())) {
            return $this->api->getDefaultConfig();
        } else {
            return $this->api->getConfig();
        }
    }

    function setConfig($config) {
        $this->api->setConfig($config);
        $this->updateSupplier();
    }

    function revoke($supplierId = null)
    {
        if(!is_null($supplierId))
        {
            $this->loadSupplier($supplierId);
        }

        $this->api->revoke();

        $this->updateSupplier();
    }

    function linkLoginPage($supplierId = null)
    {
        if(!is_null($supplierId))
        {
            $this->loadSupplier($supplierId);
        }

        return $this->api->linkLoginPage();
    }

    function retrieveToken($userAgent, $supplierId = null)
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

    function refreshToken($userAgent, $supplierId = null)
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

    function keywordSearch($userAgent, $keyword, $supplierId = null) {
        if(!is_null($supplierId))
        {
            $this->loadSupplier($supplierId);
        }

        $response = $this->api->keywordSearch($userAgent, $keyword);

        $this->updateSupplier();

        return $response;
    }

    function partDetailSearch($userAgent, $keyword, $supplierId = null) {
        if(!is_null($supplierId))
        {
            $this->loadSupplier($supplierId);
        }

        $response = $this->api->partDetailSearch($userAgent, $keyword);

        $this->updateSupplier();

        return $response;
    }

    public function getClassName()
    {
        return "InterfaceDigikey";
    }
}
