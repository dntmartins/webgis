<?php

namespace Storage\Service;

use Doctrine\ORM\EntityManager;
use Storage\Entity\Configurator;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query;
use Main\Helper\LogHelper;

class UserService extends AbstractService {

    public function __construct(EntityManager $em) {
        parent::__construct($em);
        $this->entity = "Storage\Entity\User";
    }
    
    public function getById($id) {
        try {
        	$repository=$this->em->getRepository('Storage\Entity\User');
        	$criteria=array("useId"=>$id);
        	$orderBy=null;
        	$aUse=$repository->findOneBy($criteria);
        	return $aUse;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    	    return null;
    	}
    }

    public function updateUser($data) {
        $sql = 'UPDATE user SET '. 
        		'rol_id='.$data->rol->rolId.
        		',name="'.$data->name.'"'.
        		',email="'.$data->email.'"'.
        		',login="'.$data->login.'"'.
        		',reset_token="'.$data->resetToken.'"';
        if($data->password)
        	$sql .= ',password="'.$data->password.'"';
		$sql .= ' WHERE use_id='.$data->useId;
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
   
    public function updateLastAccess($userId) {
    	try{
    		$entity = $this->em->getReference($this->entity, $userId);
    		$data = new \DateTime("now", new \DateTimeZone("America/Sao_Paulo"));
    		$entity->lastAccess = $data;
    		$this->em->persist($entity);
    		$this->em->flush();
    		return $entity;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return null;
    	}
    }
    
    public function clearResetToken($userId) {
    	try{
    		$entity = $this->em->getReference($this->entity, $userId);
    		$entity->resetToken = null;
    		$this->em->persist($entity);
    		$this->em->flush();
    
    		return $entity;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return null;
    	}
    }
    
    public function listAll(){
        try {
        	$repository=$this->em->getRepository($this->entity);
        	$orderBy = array("name"=>"ASC");
        	$users = $repository->findBy(array(),$orderBy);
        	return $users;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    	    return null;
    	}
    }
    
    public function checkIfEmailExists($email, $id=null){
        try {
        	
        	$user = $this->identifyUserByEmail($email);
        	
            if($user){
            	if($id){
            		if($user->useId != $id){
            			return true;
            		}
            	}else{ 
            		return true;
            	}
            }
            return false;
        }catch (\Exception $e){
        	LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
            return false;
        }
    }

    public function identifyUserByEmail($email){
    	try {
    		$sql="select us.* from user us WHERE us.email = '".$email."'";
    		 
    		$rsm=new ResultSetMapping();
    		$rsm->addEntityResult('Storage\Entity\User', 'us');
    		$rsm->addFieldResult('us','use_id','useId');
    		$rsm->addMetaResult("us", "rol_id", "rol_id");
    		$rsm->addFieldResult('us','name','name');
    		$rsm->addFieldResult('us','email','email');
    		$rsm->addFieldResult('us','login','login');
    		$rsm->addFieldResult('us','last_access','lastAccess');
    		$rsm->addFieldResult('us','reset_token','resetToken');
    		$rsm->addFieldResult('us','active','active');
    	
    		$query = $this->em->createNativeQuery($sql, $rsm);
    		$user=$query->getOneOrNullResult();
    		$this->em->clear();
    		return $user;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return null;
    	}
    }
    
    public function getByEmailAndToken($email, $token){
    	try {
    		$sql="select us.* from user us WHERE us.email = '".$email. "' AND us.reset_token='".$token."'";
    		 
    		$rsm=new ResultSetMapping();
    		$rsm->addEntityResult('Storage\Entity\User', 'us');
    		$rsm->addFieldResult('us','use_id','useId');
    		$rsm->addMetaResult("us", "rol_id", "rol_id");
    		$rsm->addFieldResult('us','name','name');
    		$rsm->addFieldResult('us','email','email');
    		$rsm->addFieldResult('us','login','login');
    		$rsm->addFieldResult('us','last_access','lastAccess');
    		$rsm->addFieldResult('us','reset_token','resetToken');
    		$rsm->addFieldResult('us','active','active');
    	
    		$query = $this->em->createNativeQuery($sql, $rsm);
    		$user=$query->getOneOrNullResult();
    		$this->em->clear();
    		return $user;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return null;
    	}
    }
    
    public function setActive($user, $active){
    	$sql = "UPDATE user set active=" . $active. " " . " WHERE use_id=" . $user->useId;
    	
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
    
    public function listByRole($rolId){
    	try {
    		$sql="select us.* from user us WHERE us.rol_id = ".$rolId;
    		 
    		$rsm=new ResultSetMapping();
    		$rsm->addEntityResult('Storage\Entity\User', 'us');
    		$rsm->addFieldResult('us','use_id','useId');
    		$rsm->addMetaResult("us", "rol_id", "rol_id");
    		$rsm->addFieldResult('us','name','name');
    		$rsm->addFieldResult('us','email','email');
    		$rsm->addFieldResult('us','login','login');
    		$rsm->addFieldResult('us','last_access','lastAccess');
    		$rsm->addFieldResult('us','active','active');
    	
    		$query = $this->em->createNativeQuery($sql, $rsm);
    		$users=$query->getResult();
    		$this->em->clear();
    		return $users;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return null;
    	}
    }
    
    public function updateRole($roleId, $user) {
    	$sql = "UPDATE user set rol_id=" . $roleId. " WHERE use_id=" . $user->useId;
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
}