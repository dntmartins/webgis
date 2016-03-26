<?php

namespace Storage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Layer
 *
 * @ORM\Table(name="layer", indexes={@ORM\Index(name="fk_layer_project1_idx", columns={"prj_id"}), @ORM\Index(name="fk_layer_datasource1_idx", columns={"datasource_id"}), @ORM\Index(name="fk_layer_sld", columns={"sld_id"})})
 * @ORM\Entity
 */
class Layer
{
    /**
     * @var integer
     *
     * @ORM\Column(name="layer_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $layerId;
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
    /**
     * @var integer
     *
     * @ORM\Column(name="projection", type="integer", nullable=false)
     */
    private $projection;

    public function __set($name, $value) {
    	$this->$name = $value;
    }
    public function __get($name) {
    	return $this->$name;
    }
}