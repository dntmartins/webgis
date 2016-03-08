<?php

namespace Storage\Service;

use Doctrine\ORM\EntityManager;
use Main\Helper\LogHelper;
class RoleService extends AbstractService {

    public function __construct(EntityManager $em) {
        parent::__construct($em);
        $this->entity = "Storage\Entity\Role";
    }
    public function updateRole($data) {
    	$sql = 'UPDATE role SET name="'.$data->name.'", is_admin='.$data->isAdmin.' WHERE rol_id='.$data->rolId;
    	$conn = $this->em->getConnection ();
    	try {
    		$conn->exec ( $sql );
    		return true;
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
    public function remove($roleId){
    	$sql = 'DELETE FROM role WHERE rol_id='.$roleId;
    	
    	$conn = $this->em->getConnection ();
    	
    	try {
    		$conn->beginTransaction ();
    	
    		$resultExec = $conn->exec ( $sql );
    	
    		$conn->commit ();
    		return true;
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
    public function getById($id) {
        try {
            $repository=$this->em->getRepository($this->entity);
            $criteria=array("rolId"=>$id);
            $role=$repository->findOneBy($criteria);
            return $role;
        }catch (\Exception $e){
        	LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
            return null;
        }
    }
    
    public function getByName($name){
    	try {
    		$repository=$this->em->getRepository($this->entity);
    		$criteria=array("name"=>$name);
    		$role=$repository->findOneBy($criteria);
    		return $role;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return null;
    	}
    }
}