<?php

namespace AppBundle\Entity;

/**
 * Skill
 */
class Skill
{
    /**
     * Constructor
     *
     * @param string name
     *
     */
    public function __construct($name)
    {
        $this->vacancy = new \Doctrine\Common\Collections\ArrayCollection();
        $this->volunteer = new \Doctrine\Common\Collections\ArrayCollection();
        $this->name = $name;
    }

    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $id;


    /**
     * Set name
     *
     * @param string $name
     *
     * @return Skill
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * The __toString method allows a class to decide how it will react when it is converted to a string.
     *
     * @return string
     * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.tostring
     */
    function __toString()
    {
        return "id: ".$this->getId().
        ", name: ".$this->getName();
    }
}
