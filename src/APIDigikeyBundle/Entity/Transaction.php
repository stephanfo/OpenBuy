<?php

namespace APIDigikeyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Transaction
 *
 * @ORM\Table(name="transaction")
 * @ORM\Entity(repositoryClass="APIDigikeyBundle\Repository\TransactionRepository")
 */
class Transaction
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var array|null
     *
     * @ORM\Column(name="requestHeaders", type="array", nullable=true)
     */
    private $requestHeaders;

    /**
     * @var string
     *
     * @ORM\Column(name="requestUri", type="string", length=255)
     */
    private $requestUri;

    /**
     * @var array|null
     *
     * @ORM\Column(name="requestBody", type="array", nullable=true)
     */
    private $requestBody;

    /**
     * @var int|null
     *
     * @ORM\Column(name="responseTime", type="integer", nullable=true)
     */
    private $responseTime;

    /**
     * @var int|null
     *
     * @ORM\Column(name="responseStatusCode", type="integer", nullable=true)
     */
    private $responseStatusCode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="responseReasonPhrase", type="string", length=255, nullable=true)
     */
    private $responseReasonPhrase;

    /**
     * @var array|null
     *
     * @ORM\Column(name="responseHeaders", type="array", nullable=true)
     */
    private $responseHeaders;

    /**
     * @var string|null
     *
     * @ORM\Column(name="responseBody", type="string", length=255, nullable=true)
     */
    private $responseBody;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Supplier")
     * @ORM\JoinColumn(name="supplier_id", referencedColumnName="id", nullable=false)
     */
    private $supplier;

    /**
     * @var \DateTime $created
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    private $created;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set requestHeaders.
     *
     * @param array|null $requestHeaders
     *
     * @return Transaction
     */
    public function setRequestHeaders($requestHeaders = null)
    {
        $this->requestHeaders = $requestHeaders;

        return $this;
    }

    /**
     * Get requestHeaders.
     *
     * @return array|null
     */
    public function getRequestHeaders()
    {
        return $this->requestHeaders;
    }

    /**
     * Set requestUri.
     *
     * @param string $requestUri
     *
     * @return Transaction
     */
    public function setRequestUri($requestUri)
    {
        $this->requestUri = $requestUri;

        return $this;
    }

    /**
     * Get requestUri.
     *
     * @return string
     */
    public function getRequestUri()
    {
        return $this->requestUri;
    }

    /**
     * Set requestBody.
     *
     * @param array|null $requestBody
     *
     * @return Transaction
     */
    public function setRequestBody($requestBody = null)
    {
        $this->requestBody = $requestBody;

        return $this;
    }

    /**
     * Get requestBody.
     *
     * @return array|null
     */
    public function getRequestBody()
    {
        return $this->requestBody;
    }

    /**
     * Set responseTime.
     *
     * @param int|null $responseTime
     *
     * @return Transaction
     */
    public function setResponseTime($responseTime = null)
    {
        $this->responseTime = $responseTime;

        return $this;
    }

    /**
     * Get responseTime.
     *
     * @return int|null
     */
    public function getResponseTime()
    {
        return $this->responseTime;
    }

    /**
     * Set responseStatusCode.
     *
     * @param int|null $responseStatusCode
     *
     * @return Transaction
     */
    public function setResponseStatusCode($responseStatusCode = null)
    {
        $this->responseStatusCode = $responseStatusCode;

        return $this;
    }

    /**
     * Get responseStatusCode.
     *
     * @return int|null
     */
    public function getResponseStatusCode()
    {
        return $this->responseStatusCode;
    }

    /**
     * Set responseReasonPhrase.
     *
     * @param string|null $responseReasonPhrase
     *
     * @return Transaction
     */
    public function setResponseReasonPhrase($responseReasonPhrase = null)
    {
        $this->responseReasonPhrase = $responseReasonPhrase;

        return $this;
    }

    /**
     * Get responseReasonPhrase.
     *
     * @return string|null
     */
    public function getResponseReasonPhrase()
    {
        return $this->responseReasonPhrase;
    }

    /**
     * Set responseHeaders.
     *
     * @param array|null $responseHeaders
     *
     * @return Transaction
     */
    public function setResponseHeaders($responseHeaders = null)
    {
        $this->responseHeaders = $responseHeaders;

        return $this;
    }

    /**
     * Get responseHeaders.
     *
     * @return array|null
     */
    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    /**
     * Set responseBody.
     *
     * @param string|null $responseBody
     *
     * @return Transaction
     */
    public function setResponseBody($responseBody = null)
    {
        $this->responseBody = $responseBody;

        return $this;
    }

    /**
     * Get responseBody.
     *
     * @return string|null
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }

    /**
     * Set supplier.
     *
     * @param \AppBundle\Entity\Supplier $supplier
     *
     * @return Transaction
     */
    public function setSupplier(\AppBundle\Entity\Supplier $supplier)
    {
        $this->supplier = $supplier;

        return $this;
    }

    /**
     * Get supplier.
     *
     * @return \AppBundle\Entity\Supplier
     */
    public function getSupplier()
    {
        return $this->supplier;
    }

    /**
     * Set created.
     *
     * @param \DateTime|null $created
     *
     * @return Transaction
     */
    public function setCreated($created = null)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return \DateTime|null
     */
    public function getCreated()
    {
        return $this->created;
    }
}
