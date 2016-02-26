<?php

namespace Storage\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;

class UserRepository extends EntityRepository {
	
	public function findByEmailAndPassword($email, $password) {
        $user = $this->findOneByEmail($email);
     
        if($user && $user->encryptPassword($password) == $user->password)
        	return $user;
        else
            return false;
    }
		
	public function findArray() {
		$users = $this->findAll ();
		$a = array ();
		foreach ( $users as $user ) {
			$a [$user->useId] ['id'] = $user->useId;
			$a [$user->useId] ['nome'] = $user->name;
			$a [$user->useId] ['email'] = $user->email;
			
		}
	
		return $a;
	}
	
}