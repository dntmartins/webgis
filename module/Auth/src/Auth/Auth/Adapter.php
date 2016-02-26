<?php
namespace Auth\Auth;

use Zend\Authentication\Adapter\AdapterInterface,
    Zend\Authentication\Result;
use Storage\Entity\User;
use Storage\Entity\Project;

use Doctrine\ORM\EntityManager;
use Zend\Mail\Storage;
use Storage\Entity\Role;

class Adapter implements AdapterInterface {
    /**
     *
     * @var EntityManager
     */
    protected $em;
    protected $username;
    protected $password;
    
    public function __construct(EntityManager $em) {
        $this->em = $em;
    }
    
    public function getUsername() {
        return $this->username;
    }

    public function setUsername($username) {
        $this->username = $username;
        return $this;
    }

    public function getPassword() {
        return $this->password;
    }

    public function setPassword($password) {
        $this->password = $password;
        return $this;
    }
   
   public function authenticate() {
        $repository = $this->em->getRepository("Storage\Entity\User");
        $user = $repository->findByEmailAndPassword($this->getUsername(),$this->getPassword());
       
        if($user) {
        	if(!$user->active)
        		return new Result(Result::FAILURE, null, array('Inativo'));
        	$auth_user=new User();
        	$auth_user->email=$user->email;
        	$auth_user->name=$user->name;
        	$auth_user->useId=$user->useId;
        	$auth_user->resetToken=$user->resetToken;
        	$auth_user->password=$user->password;
        	$auth_user->lastAccess=$user->lastAccess;
        	$auth_user->active = $user->active;
        	$auth_user->rol=new Role();
        	$auth_user->rol->rolId=$user->rol->rolId;
        	$auth_user->rol->name=$user->rol->name;
        	$auth_user->rol->isAdmin=$user->rol->isAdmin;
        	
            return new Result(Result::SUCCESS, array('user'=>$auth_user), array('OK'));
        }
        else
            return new Result(Result::FAILURE_CREDENTIAL_INVALID, null, array());
    }
}