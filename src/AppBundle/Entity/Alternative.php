<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Alternative
 *
 * @ORM\Table(name="alternative")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AlternativeRepository")
 */
class Alternative
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
     * @var string
     *
     * @ORM\Column(name="mfrName", type="string", length=255)
     */
    private $mfrName;

    /**
     * @var string
     *
     * @ORM\Column(name="mfrPn", type="string", length=255)
     */
    private $mfrPn;

    /**
     * @var string
     *
     * @ORM\Column(name="correctedPn", type="string", length=255, nullable=true)
     */
    private $correctedPn;

    /**
     * @var \DateTime $created
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    private $updated;

    /**
     * @var \DateTime $deleted
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deleted;

    /**
     * @ORM\ManyToOne(targetEntity="Line", inversedBy="alternatives")
     * @ORM\JoinColumn(name="line_id", referencedColumnName="id", nullable=false)
     */
    private $line;

    /**
     * @ORM\ManyToMany(targetEntity="Article", inversedBy="alternatives")
     */
    private $articles;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set mfrName
     *
     * @param string $mfrName
     *
     * @return Alternative
     */
    public function setMfrName($mfrName)
    {
        $this->mfrName = $mfrName;

        return $this;
    }

    /**
     * Get mfrName
     *
     * @return string
     */
    public function getMfrName()
    {
        return $this->mfrName;
    }

    /**
     * Set mfrPn
     *
     * @param string $mfrPn
     *
     * @return Alternative
     */
    public function setMfrPn($mfrPn)
    {
        $this->mfrPn = $mfrPn;

        return $this;
    }

    /**
     * Get mfrPn
     *
     * @return string
     */
    public function getMfrPn()
    {
        return $this->mfrPn;
    }

    /**
     * Set correctedPn
     *
     * @param string $correctedPn
     *
     * @return Alternative
     */
    public function setCorrectedPn($correctedPn)
    {
        $this->correctedPn = $correctedPn;

        return $this;
    }

    /**
     * Get correctedPn
     *
     * @return string
     */
    public function getCorrectedPn()
    {
        return $this->correctedPn;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return Alternative
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return Alternative
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set deleted
     *
     * @param \DateTime $deleted
     *
     * @return Alternative
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted
     *
     * @return \DateTime
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set line
     *
     * @param \AppBundle\Entity\Line $line
     *
     * @return Alternative
     */
    public function setLine(\AppBundle\Entity\Line $line)
    {
        $this->line = $line;

        return $this;
    }

    /**
     * Get line
     *
     * @return \AppBundle\Entity\Line
     */
    public function getLine()
    {
        return $this->line;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->articles = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add article
     *
     * @param \AppBundle\Entity\Article $article
     *
     * @return Alternative
     */
    public function addArticle(\AppBundle\Entity\Article $article)
    {
        $this->articles[] = $article;

        return $this;
    }

    /**
     * Remove article
     *
     * @param \AppBundle\Entity\Article $article
     */
    public function removeArticle(\AppBundle\Entity\Article $article)
    {
        $this->articles->removeElement($article);
    }

    /**
     * Get articles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getArticles()
    {
        return $this->articles;
    }
}
