<?php

namespace Storage\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;

class UserRepository extends EntityRepository {

	public function findByLoginAndPassword($login, $password) {
        $user = $this->findOneByLogin($login);
     
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
			$a [$user->useId] ['login'] = $user->login;
		}
	
		return $a;
	}
	
}