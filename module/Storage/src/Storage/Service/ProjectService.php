<?php

namespace Storage\Service;

use Doctrine\ORM\EntityManager;

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
    		return $e->getMessage();
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
    		return false;
    	} catch ( \Exception $e ) {
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
    		$conn->rollBack ();
    		return false;
    	} catch ( \Exception $e ) {
    		$conn->rollBack ();
    		return false;
    	}
    }
    public function publish($project, $date, $datasource, $tableName){
    	$sql = "UPDATE project set publicacao_oficial='" . $date . "' WHERE prj_id=" . $project->prjId;

    	$conn = $this->em->getConnection ();

    	try {
    		$conn->beginTransaction ();
    		 
    		$resultExec = $conn->exec ( $sql );
    		$connect = pg_connect('host='.$datasource->host.' dbname='.$datasource->dbName.' user='.$datasource->login.' password='.$datasource->password.' connect_timeout=50');
			if($connect){
				$sql = 'CREATE OR REPLACE VIEW '. $tableName.'_view AS SELECT * FROM '. $tableName.' where "data"::timestamp <= \''.$date.'\'::timestamp;';			
				$query = pg_query($sql);
				if($query){
					$conn->commit();
					return true;
				}
				pg_close();
			}
   			return false;
    	} catch ( \Doctrine\DBAL\DBALException $dbalExc ) {
    		$conn->rollBack ();
    		return false;
    	} catch ( \Exception $e ) {
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
    		return $e->getMessage();
    	}
    
    }
}