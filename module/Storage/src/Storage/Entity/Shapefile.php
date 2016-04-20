<?php

namespace Storage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Shapefile
 *
 * @ORM\Table(name="shapefile", indexes={@ORM\Index(name="prj_shapefile_fk", columns={"prj_id"})})
 * @ORM\Entity
 */
class Shapefile
{
    /**
     * @var integer
     *
     * @ORM\Column(name="shape_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $shapeId;

    /**
     * @var string
     *
     * @ORM\Column(name="file_name", type="string", length=255, nullable=false)
     */
    private $fileName;

    /**
     * @var string
     *
     * @ORM\Column(name="file_extension", type="string", length=4, nullable=false)
     */
    private $fileExtension;

    /**
     * @var string
     *
     * @ORM\Column(name="disk_location", type="string", length=255, nullable=false)
     */
    private $diskLocation;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="upload_date", type="datetime", nullable=false)
     */
    private $uploadDate;

    /**
     * @var string
     *
     * @ORM\Column(name="info", type="text", length=65535, nullable=true)
     */
    private $info;

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