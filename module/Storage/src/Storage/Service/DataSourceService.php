<?php

namespace Storage\Service;

use Doctrine\ORM\EntityManager;
use Storage\Entity\Configurator;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query;
use Main\Helper\LogHelper;

class DataSourceService extends AbstractService {
    public function __construct(EntityManager $em) {
        parent::__construct($em);
        $this->entity = "Storage\Entity\Datasource";
    }
    
    public function getById($id) {
        try {
        	$repository=$this->em->getRepository($this->entity);
        	$criteria=array("dataId"=>$id);
        	$orderBy=null;
        	$aData=$repository->findOneBy($criteria);
        	return $aData;
    	} catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    	    return null;
    	}
    }
    
	public function getByDbName($dbName) {
        try {
        	$repository=$this->em->getRepository($this->entity);
        	$criteria=array("dbName"=>$dbName);
        	$orderBy=null;
        	$aData=$repository->findOneBy($criteria);
        	return $aData;
    	} catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    	    return null;
    	}
    }
}