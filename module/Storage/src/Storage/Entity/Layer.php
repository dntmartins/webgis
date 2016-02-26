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
     * @var boolean
     *
     * @ORM\Column(name="official", type="boolean", nullable=false)
     */
    private $official;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="publicacao_oficial", type="datetime", nullable=true)
     */
    private $publicacaoOficial;

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
     * @var \Storage\Entity\Datasource
     *
     * @ORM\ManyToOne(targetEntity="Storage\Entity\Datasource")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="datasource_id", referencedColumnName="data_id")
     * })
     */
    private $datasource;

    /**
     * @var \Storage\Entity\Sld
     *
     * @ORM\ManyToOne(targetEntity="Storage\Entity\Sld",  cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sld_id", referencedColumnName="sld_id", nullable=true)
     * })
     */
    private $sld;
    
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

