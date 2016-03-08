<?php

namespace Storage\Service;

use Doctrine\ORM\EntityManager;
use Main\Helper\LogHelper;
class ResourcesService extends AbstractService {

    public function __construct(EntityManager $em) {
        parent::__construct($em);
        $this->entity = "Storage\Entity\Resource";
    }
}