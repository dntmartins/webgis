<?php
namespace Storage\Service;

use Doctrine\ORM\EntityManager;
use Storage\Entity\Configurator;
use Doctrine\ORM\Query\ResultSetMapping;
use Main\Helper\LogHelper;

class AccessService extends AbstractService {
    public function __construct(EntityManager $em) {
        parent::__construct($em);
        $this->entity = "Storage\Entity\Access";
    }
    
    public function addAll($accessList){
    	$conn=$this->em->getConnection();
    	
    	foreach ($accessList as $access) {
	    	$sql="INSERT INTO access (prj_id,use_id) values(".$access->prj->prjId.",". $access->use->useId.")";
	    	try {
	    		$resultExec = $conn->exec($sql);
	    	}catch (\Doctrine\DBAL\DBALException $dbalExc){
	    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$dbalExc->getMessage()." Linha: " . __LINE__);
	    		return false;
	    	}catch (\Exception $e){
	    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
	    		return false;
	    	}
    	}
    	try {
    		return true;
    	}catch (\Doctrine\DBAL\ConnectionException $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return false;
    	}
    }
    
    /**
     * Get Users from database using the criteria with filter.
     * @param Storage\Entity\Project $prj, a Project instance
     * @param boolean $coord, filter to coordinator responsible (values are 1(true) or 0(false)) no effect if this filter is null.
     * @return Array(Storage\Entity\User), the Access instances whith the associations between User and Project.
     */
    
    public function getUseByProject($prj) {
        try {
        	$sql="select us.* from access ac, user us ".
        			"WHERE ac.use_id=us.use_id AND ac.prj_id = ".$prj->prjId." ";
        	
        	$rsm=new ResultSetMapping();
        	$rsm->addEntityResult('Storage\Entity\User', 'us');
        	$rsm->addFieldResult('us','use_id','useId');
        	$rsm->addMetaResult("us", "rol_id", "rol_id");
        	$rsm->addFieldResult('us','name','name');
        	$rsm->addFieldResult('us','login','login');
        	$rsm->addFieldResult('us','last_access','lastAccess');
        	$rsm->addFieldResult('us','active','active');
        	 
        	$query = $this->em->createNativeQuery($sql, $rsm);
        	$access=$query->getResult();
        	$this->em->clear();
        	return $access;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    	    return null;
    	}
    }
    /**
     * Get Project from database using the criteria with filter.
     * @param Storage\Entity\User $user, a User instance
     * @param boolean $coord, filter to coordinator responsible (values are 1(true) or 0(false)) no effect if this filter is null.
     * @return Array(Storage\Entity\Project), the Access instances whith the associations between User and Project.
     */
    public function getPrjByUser($user) {
        try{
        	$sql="select pr.* from access ac, project pr ".
        			"WHERE ac.prj_id=pr.prj_id AND ac.use_id = ".$user->useId." ";
    
        	$rsm=new ResultSetMapping();
        	$rsm->addEntityResult('Storage\Entity\Project', 'pr');
        	$rsm->addFieldResult('pr','prj_id','prjId');
        	$rsm->addFieldResult('pr','project_name','projectName');
        	$rsm->addFieldResult('pr','description','description');
        	$rsm->addFieldResult('pr','logo','logo');
        	$rsm->addFieldResult('pr','link','link');
        	$rsm->addFieldResult('pr','active','active');
        	$rsm->addFieldResult('pr','publicacao_oficial','publicacaoOficial');
        	
        	$query = $this->em->createNativeQuery($sql, $rsm);
        	$access=$query->getResult();
        	$this->em->clear();
        	return $access;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    	    return null;
    	}
    }
    
    public function removeAllByUser($user){
    	$sql="DELETE FROM access WHERE use_id = ".$user->useId;
    
    	$conn=$this->em->getConnection();
    	
    	try {
    		$conn->exec($sql);
    		return true;
    	}catch (\Doctrine\DBAL\DBALException $dbalExc){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$dbalExc->getMessage()." Linha: " . __LINE__);
    		return false;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return false;
    	}
    }
    
    
    public function removeByProject($prj){
    	$sql="DELETE FROM access WHERE prj_id = ".$prj->prjId;
    
    	$conn=$this->em->getConnection();
    	 
    	try {
    		$conn->exec($sql);
    		return true;
    	}catch (\Doctrine\DBAL\DBALException $dbalExc){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$dbalExc->getMessage()." Linha: " . __LINE__);
    		return false;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return false;
    	}
    }
    
    public function getPrjs($user) {
    	try{
    		$sql="SELECT pr.* FROM access a, project pr where a.use_id = ".$user->useId." and pr.prj_id = a.prj_id;";
    
    		$rsm=new ResultSetMapping();
    		$rsm->addEntityResult('Storage\Entity\Project', 'pr');
    		$rsm->addFieldResult('pr','prj_id','prjId');
    		$rsm->addFieldResult('pr','project_name','projectName');
    		$rsm->addFieldResult('pr','description','description');
    		$rsm->addFieldResult('pr','logo','logo');
    		$rsm->addFieldResult('pr','link','link');
    		$rsm->addFieldResult('pr','active','active');
    		$rsm->addFieldResult('pr','publicacao_oficial','publicacaoOficial');
    		 
    		$query = $this->em->createNativeQuery($sql, $rsm);
    		$access=$query->getResult();
    		$this->em->clear();
    		return $access;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return null;
    	}
    }
}