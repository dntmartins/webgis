<?php

namespace Storage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RolePrivilege
 *
 * @ORM\Table(name="role_privilege", indexes={@ORM\Index(name="fk_role_id", columns={"rol_id"}), @ORM\Index(name="fk_pri_id", columns={"pri_id"})})
 * @ORM\Entity(repositoryClass="Storage\Entity\RolePrivilegeRepository")
 */
class RolePrivilege
{
    /**
     * @var integer
     *
     * @ORM\Column(name="rolPriId", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $rolpriid;
    
    /**
     * @var \Storage\Entity\Role
     *
     * @ORM\ManyToOne(targetEntity="Storage\Entity\Role")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="rol_id", referencedColumnName="rol_id")
     * })
     */
    private $rol;

    /**
     * @var \Storage\Entity\Privilege
     *
     * @ORM\ManyToOne(targetEntity="Storage\Entity\Privilege")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pri_id", referencedColumnName="pri_id")
     * })
     */
    private $pri;

    public function __set($name, $value) {
        $this->$name = $value;
    }
    public function __get($name) {
        return $this->$name;
    }
}