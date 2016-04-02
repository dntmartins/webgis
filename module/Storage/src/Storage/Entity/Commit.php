<?php

namespace Storage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Commit
 *
 * @ORM\Table(name="commit", indexes={@ORM\Index(name="commit_user_fk", columns={"use_id"})})
 * @ORM\Entity
 */
class Commit
{
    /**
     * @var integer
     *
     * @ORM\Column(name="commit_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $commitId;

    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", length=255, nullable=false)
     */
    private $hash;

    /**
     * @var string
     *
     * @ORM\Column(name="msg", type="string", length=255, nullable=false)
     */
    private $msg;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="date", type="string", length=255, nullable=false)
     */
    private $date;

    /**
     * @var \Storage\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Storage\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="use_id", referencedColumnName="use_id")
     * })
     */
    private $use;
    
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

