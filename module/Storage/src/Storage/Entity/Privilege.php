<?php

namespace Storage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Privilege
 *
 * @ORM\Table(name="privilege", indexes={@ORM\Index(name="resource_privilege_fk", columns={"res_id"})})
 * @ORM\Entity(repositoryClass="Storage\Entity\PrivilegeRepository")
 */
class Privilege
{
    /**
     * @var integer
     *
     * @ORM\Column(name="pri_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $priId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var Storage\Entity\Resource
     *
     * @ORM\ManyToOne(targetEntity="Storage\Entity\Resource")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="res_id", referencedColumnName="res_id")
     * })
     */
    private $res;

    public function __set($name, $value) {
    	$this->$name = $value;
    }
    public function __get($name) {
    	return $this->$name;
    }
}
