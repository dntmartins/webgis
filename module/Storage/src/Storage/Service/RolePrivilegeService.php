<?php

namespace Storage\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;

class RolePrivilegeService extends AbstractService {

    public function __construct(EntityManager $em) {
        parent::__construct($em);
        $this->entity = "Storage\Entity\RolePrivilege";
    }
    
    public function addAll($rolPriList){
    	$conn=$this->em->getConnection();
    	 
    	$conn->beginTransaction();
    	foreach ($rolPriList as $rolPri) {
    		$sql="INSERT INTO role_privilege (rol_id,pri_id) values(".$rolPri->rol->rolId.",". $rolPri->pri->priId .")";
    		try {
    			$resultExec = $conn->exec($sql);
    	   
    			if($resultExec==0) {
    				$conn->rollBack();
    				return false;
    			}
    		}catch (\Doctrine\DBAL\DBALException $dbalExc){
    			$conn->rollBack();
    			return false;
    		}catch (\Exception $e){
    			$conn->rollBack();
    			return false;
    		}
    	}
    	try {
    		$conn->commit();
    		return true;
    	}catch (\Doctrine\DBAL\ConnectionException $e){
    		$conn->rollBack();
    		return false;
    	}
    }
    
    public function getById($id) {
        try{
            $repository=$this->em->getRepository($this->entity);
            $criteria=array("priId"=>$id);
            $privilege=$repository->findOneBy($criteria);
            return $privilege;
        }catch (\Exception $e){
            return null;
        }
    }
    
    public function listPriIdByRole($role){
        try {
            $sql = "SELECT rolPri.rolPriId, rolPri.rol_id, rolPri.pri_id FROM role_privilege rolPri where rolPri.rol_id = ".$role->rolId.";";
				
			$rsm=new ResultSetMapping();
			$rsm->addEntityResult('Storage\Entity\RolePrivilege','rolPri');
			$rsm->addFieldResult("rolPri", "rolPriId", "rolpriid");
			$rsm->addMetaResult("rolPri", "rol_id", "rol");
			$rsm->addMetaResult("rolPri", "pri_id", "pri");
		
			$query = $this->em->createNativeQuery($sql,$rsm);
			$rolePris = $query->getResult();
			$this->em->clear();
			
			$rolePriIds = array();
			foreach ($rolePris as $rolePri){
				array_push($rolePriIds, $rolePri->pri->priId);
			}
			return $rolePriIds;
        }catch (\Exception $e){
            return null;
        }
    }
    public function removeAllByRole($role){
    	$sql = 'DELETE FROM role_privilege WHERE rol_id='.$role->rolId;
    	
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
}