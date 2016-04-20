<?php

namespace Storage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Sld
 *
 * @ORM\Table(name="sld")
 * @ORM\Entity
 */
class Sld
{
    /**
     * @var integer
     *
     * @ORM\Column(name="sld_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $sldId;

    /**
     * @var string
     *
     * @ORM\Column(name="sld_name", type="string", length=255, nullable=false)
     */
    private $sldName;

    /**
     * @var string
     *
     * @ORM\Column(name="disk_location", type="string", length=255, nullable=false)
     */
    private $diskLocation;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sld_date", type="datetime", nullable=false)
     */
    private $sldDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="registered", type="boolean", nullable=false)
     */
    private $registered;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="admin_uploaded", type="boolean", nullable=false)
     */
    private $adminUploaded;

    public function __set($name, $value) {
    	$this->$name = $value;
    }
    public function __get($name) {
    	return $this->$name;
    }
}