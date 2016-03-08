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
    public function publish($project, $date, $datasource, $tableName, $serviceLocator, $template){
    	$geoserverRESTService = $serviceLocator->get ( 'Storage\Service\GeoServerRESTService' );
    	$geoserverService = $serviceLocator->get ( 'Storage\Service\GeoServerService' );
    	$layerService = $serviceLocator->get ( 'Storage\Service\LayerService' );
    	
    	$sql = "UPDATE layer set publicacao_oficial='" . $date . "' WHERE prj_id=" . $project->prjId;
    	$conn = $this->em->getConnection ();
    	try {
    		$columnName = null;
    		if($template){
	    		foreach ($template as $column){
	    			if ($column['type'] == 'date'){
	    				$columnName = $column['name'];
	    			}
	    		}
    		}
    		else{
    			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Template de validação do dbf não existe Linha: " . __LINE__);
    			return false;
    		}
    		
    		$conn->beginTransaction ();
    		$resultExec = $conn->exec ( $sql );
    		if($resultExec===false) {
    			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . ":" .__LINE__." FALHOU ao executar a query(mysql): ".$sql);
    			return false;
    		}
    		
    		$connect = pg_connect('host='.$datasource->host.' dbname='.$datasource->dbName.' user='.$datasource->login.' password='.$datasource->password.' connect_timeout=50');
			if($connect){
				$sql = 'CREATE OR REPLACE VIEW "'. $tableName.'_view" AS SELECT * FROM "'. $tableName.'" where "data"::timestamp <= \''.$date.'\'::timestamp;';			
				$query = pg_query($connect, $sql);
				if($query){
					$geoServer = $geoserverService->getByPrj($project->prjId);
					if($geoServer){
						$layer = $layerService->getByPrjID($project->prjId);
						if($layer){
							$sldName = null;
							if($layer->sld)
								$sldName = $layer->sld->sldName;
							$resultGeoserver = $geoserverRESTService->createLayer($geoServer->login.":".$geoServer->pass, $project->projectName, $tableName.'_view', $geoServer->host, $sldName);
							if($resultGeoserver){
								$conn->commit();
								return true;
							}
							else{
								$conn->rollBack();
								return false;
							}
						}
						else{
							LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Esse projeto não possui um layer Linha: " . __LINE__);
							$conn->rollBack ();
							return false;
						}
					}
					else{
						LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Dados de conexão do geoserver não foram recuperados Linha: " . __LINE__);
						$conn->rollBack ();
						return false;
					}
				}
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . ":" .__LINE__." FALHOU ao executar a query(postgres): ".$sql);
				$conn->rollBack();
				pg_close($connect);
			}
   			return false;
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