<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Bom
 *
 * @ORM\Table(name="bom")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\BomRepository")
 *
 * @Gedmo\SoftDeleteable(fieldName="deleted", timeAware=false)
 */
class Bom
{

    const NB_PER_PAGE = 20;

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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

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
     * @ORM\ManyToOne(targetEntity="Ecu", inversedBy="boms", fetch="EAGER")
     * @ORM\JoinColumn(name="ecu_id", referencedColumnName="id", nullable=false)
     */
    private $ecu;

    /**
     * @ORM\OneToMany(targetEntity="Line", mappedBy="bom", cascade={"persist", "remove"})
     */
    private $lines;

    /**
     * @ORM\OneToMany(targetEntity="Quantity", mappedBy="bom", cascade={"persist", "remove"})
     */
    private $quantities;

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
     * Set name
     *
     * @param string $name
     *
     * @return Bom
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return Bom
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
     * @return Bom
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
     * @return Bom
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
        $this->lines = new \Doctrine\Common\Collections\ArrayCollection();
        $this->quantities = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set ecu
     *
     * @param \AppBundle\Entity\Ecu $ecu
     *
     * @return Bom
     */
    public function setEcu(\AppBundle\Entity\Ecu $ecu)
    {
        $this->ecu = $ecu;

        return $this;
    }

    /**
     * Get ecu
     *
     * @return \AppBundle\Entity\Ecu
     */
    public function getEcu()
    {
        return $this->ecu;
    }

    /**
     * Add line
     *
     * @param \AppBundle\Entity\Line $line
     *
     * @return Bom
     */
    public function addLine(\AppBundle\Entity\Line $line)
    {
        $this->lines[] = $line;

        return $this;
    }

    /**
     * Remove line
     *
     * @param \AppBundle\Entity\Line $line
     */
    public function removeLine(\AppBundle\Entity\Line $line)
    {
        $this->lines->removeElement($line);
    }

    /**
     * Get lines
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLines()
    {
        return $this->lines;
    }

    /**
     * Add quantity
     *
     * @param \AppBundle\Entity\Quantity $quantity
     *
     * @return Bom
     */
    public function addQuantity(\AppBundle\Entity\Quantity $quantity)
    {
        $this->quantities[] = $quantity;

        return $this;
    }

    /**
     * Remove quantity
     *
     * @param \AppBundle\Entity\Quantity $quantity
     */
    public function removeQuantity(\AppBundle\Entity\Quantity $quantity)
    {
        $this->quantities->removeElement($quantity);
    }

    /**
     * Get quantities
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getQuantities()
    {
        return $this->quantities;
    }
}
