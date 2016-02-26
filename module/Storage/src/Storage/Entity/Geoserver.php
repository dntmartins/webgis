<?php

namespace Storage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Geoserver
 *
 * @ORM\Table(name="geoserver", indexes={@ORM\Index(name="fk_prj_1_idx", columns={"prj_id"})})
 * @ORM\Entity
 */
class Geoserver
{
    /**
     * @var integer
     *
     * @ORM\Column(name="geoserver_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $geoserverId;

    /**
     * @var string
     *
     * @ORM\Column(name="login", type="string", length=255, nullable=false)
     */
    private $login;

    /**
     * @var string
     *
     * @ORM\Column(name="pass", type="string", length=255, nullable=false)
     */
    private $pass;

    /**
     * @var string
     *
     * @ORM\Column(name="host", type="string", length=255, nullable=false)
     */
    private $host;

    /**
     * @var \Storage\Entity\Project
     *
     * @ORM\ManyToOne(targetEntity="Storage\Entity\Project")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="prj_id", referencedColumnName="prj_id")
     * })
     */
    private $prj;

    public function __set($name, $value) {
    	$this->$name = $value;
    }
    public function __get($name) {
    	return $this->$name;
    }
}

