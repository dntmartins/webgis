<?php

namespace Storage\Service;

use Doctrine\ORM\EntityManager;
use Storage\Entity\Configurator;
use Main\Helper\LogHelper;

abstract class AbstractService {
	
	/**
	 * @var EntityManager
	 */
	protected $em;
	protected $entity;
	public function __construct(EntityManager $em) {
		$this->em = $em;
	}
	
	private static function getEntityManager() {
		if (!self::$entityManager->isOpen()) {
			self::$entityManager = self::$entityManager->create(
					self::$entityManager->getConnection(), self::$entityManager->getConfiguration());
		}
	
		return self::$entityManager;
	}
	
	public function add($data) {
	    try {
	        $this->em->persist($data);
	        $this->em->flush();
	        return true;
	    }catch (\Exception $e){
	    	LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
	        return false;
	    }
	}
    
    public function update(array $data) {
    	try {
	        $entity = $this->em->getReference($this->entity, $data['id']);
	        $entity = Configurator::configure($entity, $data);
	        
	        $this->em->persist($entity);
	        $this->em->flush();
	        
	        return $entity;
        } catch ( \Exception $e ) {
        	LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
        	return NULL;
        }
    }
    
    public function delete($id) {
        try {
            $entity = $this->em->getReference($this->entity, $id);
            if($entity) {
                $this->em->remove($entity);
                $this->em->flush();
                return $entity;
            }
            return null;
        }catch (\Exception $e){
        	LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
            return null;
        }
    }
    public function listAll(){
    	try {
    		$repository=$this->em->getRepository($this->entity);
    		$entities=$repository->findAll();
    		LogHelper::writeOnLog("Listou ".$this->entity." no mysql");
    		return $entities;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return null;
    	}
    }
    public function begin() {
    	$this->em->beginTransaction();
    }
    public function commit() {
    	$this->em->commit();
    }
    public function rollback(){
    	$this->em->rollback();
    }
}