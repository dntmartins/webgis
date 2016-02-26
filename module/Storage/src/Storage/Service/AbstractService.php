<?php

namespace Storage\Service;

use Doctrine\ORM\EntityManager;
use Storage\Entity\Configurator;

abstract class AbstractService {
	
	/**
	 *
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
	    	$erro = $e->getMessage();
	        // TODO: Gravar log de erro...
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
            return null;
        }
    }
    public function listAll(){
    	try {
    		$repository=$this->em->getRepository($this->entity);
    		$entities=$repository->findAll();
    		return $entities;
    	}catch (\Exception $e){
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