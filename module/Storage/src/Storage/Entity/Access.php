<?php

namespace Storage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Access
 *
 * @ORM\Table(name="access", indexes={@ORM\Index(name="access_project_fk", columns={"prj_id"}), @ORM\Index(name="access_user_fk", columns={"use_id"})})
 * @ORM\Entity(repositoryClass="Storage\Entity\AccessRepository")
 */
class Access
{
    /**
     * @var integer
     *
     * @ORM\Column(name="acc_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $accId;

    /**
     * @var \Storage\Entity\Project
     *
     * @ORM\ManyToOne(targetEntity="Storage\Entity\Project")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="prj_id", referencedColumnName="prj_id")
     * })
     */
    private $prj;

    /**
     * @var \Storage\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Storage\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="use_id", referencedColumnName="use_id")
     * })
     */
    private $use;

    public function __set($name, $value) {
    	$this->$name = $value;
    }

    public function __get($name) {
    	return $this->$name;
    }

}
