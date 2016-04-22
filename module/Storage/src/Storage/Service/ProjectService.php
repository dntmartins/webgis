<?php

namespace Storage\Service;

use Doctrine\ORM\EntityManager;
use Main\Helper\LogHelper;
use Storage\Entity\Layer;
class ProjectService extends AbstractService {

    public function __construct(EntityManager $em) {
        parent::__construct($em);
        $this->entity = "Storage\Entity\Project";
    }

    public function getById($id) {
        try {
        	$repository=$this->em->getRepository($this->entity);
    
        	// find the last document, if exist
        	$criteria=array("prjId"=>$id);
        	$orderBy=null;
        	$aProj=$repository->findOneBy($criteria,$orderBy);
        	return $aProj;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    	    return null;
    	}
    }
    
    public function getByName($prjName) {
    	try {
    		$repository=$this->em->getRepository($this->entity);
    		$criteria=array("projectName"=>$prjName);
    		$aProj=$repository->findOneBy($criteria);
    		return $aProj;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return null;
    	}
    }
    
    public function updateProject($data) {
    	$date = $data->publicacaoOficial?$data->publicacaoOficial->format(\DateTime::ISO8601):"0000-00-00 00:00:00";
    	$sql = 'UPDATE project SET '.
    			'project_name="'.$data->projectName.'"'.
    			',description="'.$data->description.'"'.
    			',logo="'.$data->logo.'"'.
    			',link="'.$data->link.'"'.
    			',active='.$data->active.
    			',publicacao_oficial="'.$date.'"';
    	$sql .= ' WHERE prj_id='.$data->prjId;
    
    	$conn = $this->em->getConnection ();
    	
    	try {
    		$resultExec = $conn->exec ( $sql );
    		return true;
    	} catch ( \Doctrine\DBAL\DBALException $dbalExc ) {
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$dbalExc->getMessage()." Linha: " . __LINE__);
    		return false;
    	} catch ( \Exception $e ) {
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return false;
    	}
    }
    public function setActive($project, $active){
    	$sql = "UPDATE project set active=" . $active. " " . " WHERE prj_id=" . $project->prjId;
    	 
    	$conn = $this->em->getConnection ();
    	 
    	try {
    		$conn->beginTransaction ();
    		 
    		$resultExec = $conn->exec ( $sql );
    		 
    		if ($resultExec == 0) {
    			$conn->rollBack ();
    			return false;
    		} else {
    			$conn->commit ();
    			return true;
    		}
    	} catch ( \Doctrine\DBAL\DBALException $dbalExc ) {
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$dbalExc->getMessage()." Linha: " . __LINE__);
    		$conn->rollBack ();
    		return false;
    	} catch ( \Exception $e ) {
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		$conn->rollBack ();
    		return false;
    	}
    }
    
    public function updateLogo($prjId, $logo) {
    	try{
    		$entity = $this->em->getReference($this->entity, $prjId);
    		$entity->logo = $logo;
    		$this->em->persist($entity);
    		$this->em->flush();
    
    		return $entity;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return null;
    	}
    }
}