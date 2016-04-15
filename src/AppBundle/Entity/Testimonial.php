<?php

namespace AppBundle\Entity;

/**
 * Testimonial
 */
class Testimonial
{
    /**
     * @var string
     */
    private $value;    

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \AppBundle\Entity\Person
     */
    private $sender;

    /**
     * @var \AppBundle\Entity\Person
     */
    private $receiver;


    /**
     * Set value
     *
     * @param string $value
     *
     * @return Testimonial
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set lastUpdate
     *
     * @param \DateTime $lastUpdate
     *
     * @return Testimonial
     */
    public function setLastUpdate($lastUpdate)
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }

    /**
     * Get lastUpdate
     *
     * @return \DateTime
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * Set id
     *
     * @return Testimonial
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
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
     * Set sender
     *
     * @param \AppBundle\Entity\Person $sender
     *
     * @return Testimonial
     */
    public function setSender(\AppBundle\Entity\Person $sender = null)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Get sender
     *
     * @return \AppBundle\Entity\Person
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Set receiver
     *
     * @param \AppBundle\Entity\Person $receiver
     *
     * @return Testimonial
     */
    public function setReceiver(\AppBundle\Entity\Person $receiver = null)
    {
        $this->receiver = $receiver;

        return $this;
    }

    /**
     * Get receiver
     *
     * @return \AppBundle\Entity\Person
     */
    public function getReceiver()
    {
        return $this->receiver;
    }
}
