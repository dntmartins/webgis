<?php
namespace Storage\Service;

use Doctrine\ORM\EntityManager;
use Storage\Entity\Configurator;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query;
use Main\Helper\LogHelper;
class GeoServerService extends AbstractService {
    public function __construct(EntityManager $em) {
        parent::__construct($em);
        $this->entity = "Storage\Entity\Geoserver";
    }
    public function addGeoserver($geoserver){
    	try {
    		$sql = "INSERT INTO geoserver(prj_id,login,pass,host) VALUES(".$geoserver->prj->prjId.",'".$geoserver->login."','".$geoserver->pass."','".$geoserver->host."')";
    		$conn=$this->em->getConnection();
    		$stmt = $conn->prepare ($sql);
    		$stmt->execute();
    		$id = $conn->lastInsertId();
    		return $id;
    	}catch (\Doctrine\DBAL\ConnectionException $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return false;
    	}catch (\Doctrine\DBAL\DBALException $dbalExc){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$dbalExc->getMessage()." Linha: " . __LINE__);
    		return false;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return false;
    	}
    }
    
    public function getById($id) {
        try {
        	$repository=$this->em->getRepository($this->entity);
        	$criteria=array("layerId"=>$id);
        	$orderBy=null;
        	$layer=$repository->findOneBy($criteria);
        	return $layer;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    	    return null;
    	}
    }
    
    public function getByPrj($prjId) {
    	try {
    		$repository=$this->em->getRepository($this->entity);
    		$criteria=array("prj"=>$prjId);
    		$orderBy=null;
    		$aData=$repository->findOneBy($criteria);
    		return $aData;
    	} catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return false;
    	}
    }
}