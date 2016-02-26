<?php

namespace Storage\Service;

use Doctrine\ORM\EntityManager;
use Storage\Entity\Shapefile;
use Doctrine\ORM\Query\ResultSetMapping;

class ShapefileService extends AbstractService {
    public function __construct(EntityManager $em) {
        parent::__construct($em);
        $this->entity = "Storage\Entity\Shapefile";
    }
    
    public function add($shape){
    	try {
    		$date = $shape->uploadDate->format(\DateTime::ISO8601);
    		$sql="INSERT INTO shapefile (prj_id, file_name, file_extension, disk_location, upload_date, info) values(".$shape->prj->prjId.",'". $shape->fileName."','".$shape->fileExtension."','".$shape->diskLocation."','". $date ."','".$shape->info."')";
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
    
    public function getLayerTableName($prjId){
    	try{
    		$sql = "SELECT shape.* FROM shapefile shape where shape.prj_id = ".$prjId.";";
    		$rsm=new ResultSetMapping();
    		$rsm->addEntityResult('Storage\Entity\Shapefile', 'shape');
    		$rsm->addFieldResult('shape','shape_id','shapeId');
    		$rsm->addFieldResult('shape','file_name','fileName');
    		$rsm->addMetaResult('shape','prj_id','prj_id');
    		$rsm->addFieldResult('shape','file_extension','fileExtension');
    		$rsm->addFieldResult('shape','disk_location','diskLocation');
    		$rsm->addFieldResult('shape','info','info');
    		$rsm->addFieldResult('shape','upload_date','uploadDate');
    		$query = $this->em->createNativeQuery($sql, $rsm);
    		$shapes=$query->getResult();
    		$this->em->clear();
    		if($shapes)
    			return $shapes[0]->fileName;
    		return null;
    	} catch ( \Doctrine\DBAL\DBALException $dbalExc ) {
    		return null;
    	} catch ( \Exception $e ) {
    		return null;
    	}
    }
    
    public function listByProjectId($prj_id, $page=null, $limit=null){
        try{
        	$sql = "SELECT shape.* FROM shapefile shape WHERE shape.prj_id =".$prj_id;
        	if($limit){
        		$sql .= " LIMIT ".$limit;
        	}
        		
        	if($page){
        		$start = ($limit*$page)-$limit;
        		$sql .= " OFFSET ".$start;
        	}
        	$rsm=new ResultSetMapping();
        	$rsm->addEntityResult('Storage\Entity\Shapefile', 'shape');
        	$rsm->addFieldResult('shape','shape_id','shapeId');
        	$rsm->addFieldResult('shape','file_name','fileName');
        	$rsm->addMetaResult('shape','prj_id','prj_id');
        	$rsm->addFieldResult('shape','file_extension','fileExtension');
        	$rsm->addFieldResult('shape','disk_location','diskLocation');
        	$rsm->addFieldResult('shape','info','info');
        	$rsm->addFieldResult('shape','upload_date','uploadDate');
        	$query = $this->em->createNativeQuery($sql, $rsm);
        	$shapes=$query->getResult();
        	$this->em->clear();
        	return $shapes;
    	}catch (\Exception $e){
    	    return null;
    	}
    }
    public function removeZips($from, $to, $prjId){
    	try{
    		if(!$from)
    			$from = '1970/01/01';
    		if(!$to)
    			$to = 'CURDATE()';
    		
    		$sql = "DELETE FROM shapefile ";
    		$sql .= " WHERE date_format(upload_date, '%Y-%m-%d')>='".$from."'";
    		$sql .= " AND date_format(upload_date, '%Y-%m-%d')<='".$to."'";
    		$sql .= " AND prj_id = ".$prjId;
    		
    		$conn=$this->em->getConnection();
    		
    		try {
    			$conn->exec($sql);
    			return true;
    		}catch (\Doctrine\DBAL\DBALException $dbalExc){
    			return false;
    		}catch (\Exception $e){
    			return false;
    		}
    	}catch (\Exception $e){
    		return false;
    	}
    }
    public function getByName($shapeName, $id){
    	try{
    		$sql = "SELECT shape.* FROM shapefile shape WHERE shape.file_name = '".$shapeName . "' and prj_id = " . $id;
    		
    		$rsm=new ResultSetMapping();
    		$rsm->addEntityResult('Storage\Entity\Shapefile', 'shape');
    		$rsm->addFieldResult('shape','shape_id','shapeId');
    		$rsm->addFieldResult('shape','file_name','fileName');
    		$rsm->addMetaResult('shape','prj_id','prj');
    		$rsm->addFieldResult('shape','file_extension','fileExtension');
    		$rsm->addFieldResult('shape','disk_location','diskLocation');
    		$rsm->addFieldResult('shape','info','info');
    		$rsm->addFieldResult('shape','upload_date','uploadDate');
    		$query = $this->em->createNativeQuery($sql, $rsm);
    		$shapes=$query->getResult();
    		$this->em->clear();
    		$numberElements = count($shapes);
    		if($numberElements >= 1){
    			return true;
    		}else{
    			return false;
    		}
    	}catch (\Exception $e){
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