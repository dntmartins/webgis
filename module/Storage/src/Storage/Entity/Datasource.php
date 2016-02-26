<?php
namespace Storage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Datasource
 *
 * @ORM\Table(name="datasource")
 * @ORM\Entity
 */
class Datasource
{
    /**
     * @var integer
     *
     * @ORM\Column(name="data_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $dataId;

    /**
     * @var string
     *
     * @ORM\Column(name="db_name", type="string", length=45, nullable=false)
     */
    private $dbName;

    /**
     * @var string
     *
     * @ORM\Column(name="host", type="string", length=45, nullable=false)
     */
    private $host;

    /**
     * @var integer
     *
     * @ORM\Column(name="port", type="integer", nullable=false)
     */
    private $port;

    /**
     * @var string
     *
     * @ORM\Column(name="login", type="string", length=255, nullable=false)
     */
    private $login;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=45, nullable=false)
     */
    private $password;

    public function __set($name, $value) {
    	$this->$name = $value;
    }
    public function __get($name) {
    	return $this->$name;
    }
}