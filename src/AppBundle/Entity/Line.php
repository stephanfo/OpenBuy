<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Line
 *
 * @ORM\Table(name="line")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LineRepository")
 *
 * @Gedmo\SoftDeleteable(fieldName="deleted", timeAware=false)
 */
class Line
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
     * @ORM\Column(name="ecuPn", type="string", length=255)
     * @Assert\Length(min=1)
     * @Assert\NotBlank()
     * @Assert\NotNull()
     */
    private $ecuPn;

    /**
     * @var int
     *
     * @ORM\Column(name="multiplier", type="integer")
     * @Assert\Type(type="integer")
     * @Assert\Range(min=0)
     */
    private $multiplier;

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
     * @ORM\ManyToOne(targetEntity="Bom", inversedBy="lines")
     * @ORM\JoinColumn(name="bom_id", referencedColumnName="id", nullable=false)
     */
    private $bom;

    /**
     * @ORM\OneToMany(targetEntity="Alternative", mappedBy="line", cascade={"persist", "remove"})
     */
    private $alternatives;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ecuPn
     *
     * @param string $ecuPn
     *
     * @return Line
     */
    public function setEcuPn($ecuPn)
    {
        $this->ecuPn = $ecuPn;

        return $this;
    }

    /**
     * Get ecuPn
     *
     * @return string
     */
    public function getEcuPn()
    {
        return $this->ecuPn;
    }

    /**
     * Set multiplier
     *
     * @param integer $multiplier
     *
     * @return Line
     */
    public function setMultiplier($multiplier)
    {
        $this->multiplier = $multiplier;

        return $this;
    }

    /**
     * Get multiplier
     *
     * @return integer
     */
    public function getMultiplier()
    {
        return $this->multiplier;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return Line
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
     * @return Line
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
     * @return Line
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
     * Constructor
     */
    public function __construct()
    {
        $this->alternatives = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set bom
     *
     * @param \AppBundle\Entity\Bom $bom
     *
     * @return Line
     */
    public function setBom(\AppBundle\Entity\Bom $bom)
    {
        $this->bom = $bom;

        return $this;
    }

    /**
     * Get bom
     *
     * @return \AppBundle\Entity\Bom
     */
    public function getBom()
    {
        return $this->bom;
    }

    /**
     * Add alternative
     *
     * @param \AppBundle\Entity\Alternative $alternative
     *
     * @return Line
     */
    public function addAlternative(\AppBundle\Entity\Alternative $alternative)
    {
        $this->alternatives[] = $alternative;

        return $this;
    }

    /**
     * Remove alternative
     *
     * @param \AppBundle\Entity\Alternative $alternative
     */
    public function removeAlternative(\AppBundle\Entity\Alternative $alternative)
    {
        $this->alternatives->removeElement($alternative);
    }

    /**
     * Get alternatives
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAlternatives()
    {
        return $this->alternatives;
    }

    /**
     * Create alternative
     *
     * @return Line
     */
    public function createAlternative(string $mfrName, string $mfrPn)
    {
        $alternative = new Alternative();

        $alternative->setLine($this);
        $alternative->setMfrName($mfrName);
        $alternative->setMfrPn($mfrPn);

        $this->alternatives[] = $alternative;

        return $this;
    }

    /**
     * Test alternative
     *
     * @return boolean
     */
    public function testAlternative(string $mfrName, string $mfrPn)
    {
        foreach ($this->alternatives as $alternative)
        {
            if ($alternative->getMfrName() == $mfrName && $alternative->getMfrPn() == $mfrPn){
                return true;
            }
        }

        return false;
    }
}
