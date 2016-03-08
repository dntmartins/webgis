<?php

namespace Storage\Service;

use Doctrine\ORM\EntityManager;
use Main\Helper\LogHelper;
class PrivilegeService extends AbstractService {

    public function __construct(EntityManager $em) {
        parent::__construct($em);
        $this->entity = "Storage\Entity\Privilege";
    }
    
    public function getById($id) {
        try{
            $repository=$this->em->getRepository($this->entity);
            $criteria=array("priId"=>$id);
            $privilege=$repository->findOneBy($criteria);
            return $privilege;
        }catch (\Exception $e){
        	LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
            return null;
        }
    }
}