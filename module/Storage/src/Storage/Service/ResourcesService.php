<?php

namespace Storage\Service;

use Doctrine\ORM\EntityManager;

class ResourcesService extends AbstractService {

    public function __construct(EntityManager $em) {
        parent::__construct($em);
        $this->entity = "Storage\Entity\Resource";
    }
}