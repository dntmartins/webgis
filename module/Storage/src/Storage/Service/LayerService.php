<?php
namespace Storage\Service;

use Doctrine\ORM\EntityManager;
use Storage\Entity\Configurator;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query;

class LayerService extends AbstractService {

    public function __construct(EntityManager $em) {
        parent::__construct($em);
        $this->entity = "Storage\Entity\Layer";
    }
    
    public function addLayer($layer) {
	    try {
	    		$sql="INSERT INTO layer (sld_id,prj_id, datasource_id, official, publicacao_oficial, projection) values(".(($layer->sld)?($layer->sld->sldId):('null')).",".$layer->prj->prjId.",".$layer->datasource->dataId.",". 0 .",null, " . $layer->projection . ")";
	    		$conn=$this->em->getConnection();
	    		$stmt = $conn->prepare ($sql);
	    		$stmt->execute();
	    		$id = $conn->lastInsertId();
	    		return $id;
	    	}catch (\Doctrine\DBAL\ConnectionException $e){
	    		return false;
	    	}catch (\Doctrine\DBAL\DBALException $dbalExc){
	    		return false;
	    	}catch (\Exception $e){
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
    	    return null;
    	}
    }
    
    public function setSld($prj_id, $sld_id) {
    	$sql = 'update layer set sld_id = '.$sld_id.' where prj_id = '.$prj_id.';';
    	$conn = $this->em->getConnection ();
    	try {
    		$conn->beginTransaction ();
    		$resultExec = $conn->exec ( $sql );
    		$conn->commit ();
    		return true;
    	} catch ( \Doctrine\DBAL\DBALException $dbalExc ) {
    		$conn->rollBack ();
    		return false;
    	} catch ( \Exception $e ) {
    		$conn->rollBack ();
    		return false;
    	}
    }
    
    public function listByPrj($prjId) {
    	try{
    		$sql="SELECT l.* FROM layer l WHERE l.prj_id = ".$prjId.";";
    
    		$rsm=new ResultSetMapping();
    		$rsm->addEntityResult('Storage\Entity\Layer', 'l');
    		$rsm->addFieldResult('l','layer_id','layerId');
    		$rsm->addMetaResult('l','sld_id','sld_id');
    		$rsm->addMetaResult('l','prj_id','prj_id');
    		$rsm->addMetaResult('l','datasource_id','data_id');
    		$rsm->addFieldResult('l','official','official');
    		$rsm->addFieldResult('l','publicacao_oficial','publicacaoOficial');
    		 
    		$query = $this->em->createNativeQuery($sql, $rsm);
    		$layers=$query->getResult();
    		$this->em->clear();
    		return $layers;
    	}catch (\Exception $e){
    		return null;
    	}
    }
    
    public function getSldByPrj($prjId) {
    	try{
    		$sql="SELECT s.* FROM layer l, sld s WHERE l.prj_id = ".$prjId." AND s.sld_id = l.sld_id";
    
    		$rsm=new ResultSetMapping();
    		$rsm->addEntityResult('Storage\Entity\Sld', 's');
    		$rsm->addFieldResult('s','sld_id','sldId');
    		$rsm->addFieldResult('s','sld_name','sldName');
    		$rsm->addFieldResult('s','sld_date','sldDate');
    		$rsm->addFieldResult('s','disk_location','diskLocation');
    		$rsm->addFieldResult('s','registered','registered');

    		$query = $this->em->createNativeQuery($sql, $rsm);
    		$sld=$query->getResult();
    		$this->em->clear();
    		if(count($sld) > 0)
    			return $sld[0];
    		return null;
    	}catch (\Exception $e){
    		return null;
    	}
    }
    
    public function getByDb($dbId) {
    	try {
    		$repository=$this->em->getRepository($this->entity);
    		$criteria=array("datastore"=>$dbId);
    		$orderBy=null;
    		$aData=$repository->findOneBy($criteria);
    		return $aData;
    	} catch (\Exception $e){
    		return null;
    	}
    }
    
    public function getByPrjID($id){
    	try {
    		$repository=$this->em->getRepository($this->entity);
    		$criteria=array("prj"=>$id);
    		$orderBy=null;
    		$aData=$repository->findOneBy($criteria);
    		return $aData;
    	} catch (\Exception $e){
    		return null;
    	}
    }
}