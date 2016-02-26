<?php
namespace Admin\Controller;

use Zend\Session\Container;
use Storage\Entity\Role;
use Storage\Entity\RolePrivilege;
use Zend\I18n\Validator\Alnum;
use Zend\Validator\StringLength;
use Main\Controller\MainController;

class RoleController extends MainController
{
    private function verifyDuplicateName($name, $ignore=NULL){
    	try {
    		$serviceLocator = $this->getServiceLocator();
    		$roleService = $serviceLocator->get ('Storage\Service\RoleService');
    		$roles = $roleService->listAll();
    		if($roles){
    			foreach ($roles as $role){
    				if($ignore && $ignore == $role->rolId)continue;
    				if (strtolower($role->name) == strtolower($name))
    					return false;
    			}
    		}
    		return true;
    	} catch (\Exception $e) {
    		return false;
    	}
    }
    
    public function checkDuplicateNameAction(){
    	try {
    		if ($this->verifyUserSession()) {
    			$response = $this->getResponse();
    			$acl = $this->getServiceLocator()->get('Admin\Permissions\Acl');
    			if ($acl->isAllowed($this->session->user->rol->name, "Administração", "Administrar usuários e permissões")) {
    				$formData = $this->getFormData();
    				$ignoreId = null;
    				$name = null;
    				if($formData['name'])
	    				$name = $formData['name'];
    				if($formData['id'])
    					$ignoreId = $formData['id'];
    				if($name){
	    				if(!$this->verifyDuplicateName($name, $ignoreId)){
	    					$response->setContent(\Zend\Json\Json::encode(array(
    							'status' => true,
    							'isLogged' => true
	    					)));
	    					return $response;
	    				}
	    				else{
	    					$response->setContent(\Zend\Json\Json::encode(array(
    							'status' => false,
    							'isLogged' => true
	    					)));
	    					return $response;
	    				}
    				}
    			}
    		}
    	} catch (\Exception $e) {
    		$this->showMessage('Não foi possível verificar o nome', 'admin-error');
    		$response->setContent(\Zend\Json\Json::encode(array(
    				'status' => false,
    				'isLogged' => true
    		)));
    		return $response;
    	}
    }

    public function indexAction()
    {
        try {
            if ($this->verifyUserSession()) {
                $acl = $this->getServiceLocator()->get('Admin\Permissions\Acl');
                if ($acl->isAllowed($this->session->user->rol->name, "Administração", "Administrar usuários e permissões")) {
                    $serviceLocator = $this->getServiceLocator();
                    $roleService = $serviceLocator->get('Storage\Service\RoleService');
                    
                    $roles = $roleService->listAll();
                    return array("roles" => $roles);
                }
                return $this->showMessage('Você não possui permissões para realizar essa operação', 'home-error', '/');
            }
            return $this->showMessage('Você precisa fazer o login para realizar essa operação', 'home-error', '/');
        } catch (\Exception $e) {
        	return $this->showMessage('Não foi possível recuperar os perfis cadastrados', 'home-error', '/');
        }
    }
    
    public function formAction(){
    	try{
    		if ($this->verifyUserSession()) {
    			$serviceLocator = $this->getServiceLocator();
    			$acl = $this->getServiceLocator()->get('Admin\Permissions\Acl');
    			if ($acl->isAllowed($this->session->user->rol->name, "Administração", "Administrar usuários e permissões")) {
    				$formData = $this->getFormData();
    				$request = $this->getRequest();
    		
    				$roleService = $serviceLocator->get('Storage\Service\RoleService');
    				$privilegeService = $serviceLocator->get('Storage\Service\PrivilegeService');
    				$rolePrivilegeService = $serviceLocator->get('Storage\Service\RolePrivilegeService');
    				$id = NULL;
    				if($formData['id'])
    					$id = $formData['id'];
    				if($request->isPost()){
    					if($id) // O id indica que o perfil está sendo modificado
    						$role = $roleService->getById($id);
    					else
    						$role = new Role();
    					
    					$stringValidator = new Alnum( true );
    					$stringLengthValidator = new StringLength ( array ('min' => 4,'max' => 50) );
    					
    					$name = trim($formData['name']);
    					if(!$this->verifyDuplicateName($name, $id) || !$stringValidator->isValid($name) || !$stringLengthValidator->isValid($name)){
    						$url = '/role/form';
    						if($id)
    							$url .= '?id='.$role->rolId;
    						return $this->showMessage("O campo nome é obrigatório, não pode conter caracteres especiais e deve ter no minimo 4 e no máximo 50 caracteres.", 'admin-error', $url);
    					}
    					$role->name = $name;
    					$role->isAdmin = 0;
    					
    					$privileges = $formData['privileges'];
    					if ($privileges) {
    						if($id){
    							$roleService->begin();
    							if($roleService->updateRole($role)){
    								if(!$rolePrivilegeService->removeAllByRole($role)){
    									$roleService->rollback();
    									return $this->showMessage('Não foi possível editar o perfil.', 'admin-error', '/role/form?id='.$role->rolId);
    								}
    							}
    							else
    								return $this->showMessage('Não foi possível editar o perfil.', 'admin-error', '/role/form?id='.$role->rolId);
    						}
    						else {
    							if($roleService->add($role))
    								$role = $roleService->getByName($role->name);
    							else
    								return $this->showMessage('Não foi possível criar o perfil.', 'admin-error', '/role/form');
    						}
    						$rolPriList = array();
    						foreach ($privileges as $privilegeId) {
    							$privilege = $privilegeService->getById($privilegeId);
    							$rolePrivilege = new RolePrivilege();
    							$rolePrivilege->pri = $privilege;
    							$rolePrivilege->rol = $role;
    							array_push($rolPriList, $rolePrivilege);
    						}
    						if($rolePrivilegeService->addAll($rolPriList)){
    							if(!$id)
    								return $this->showMessage('Perfil criado com sucesso!', 'admin-success', '/role');
    							else{
    								$roleService->commit();
    								return $this->showMessage('As informações do perfil foram modificadas com sucesso!', 'admin-success', '/role');
    							}
    						}
    						else{
    							if(!$id){
    								$roleService->remove($role->rolId);
    								return $this->showMessage('Não foi possível criar o perfil', 'admin-error', '/role/form');
    							}
    							else
    								return $this->showMessage('Não foi possível modificar as informações do perfil', 'admin-error', '/role/form?id='.$id);
    						}
    					}
    					else{
    						$url = '/role/form';
    						if($id)
    							$url .= '?id='.$role->rolId;
    						return $this->showMessage('Selecione ao menos um privilégio.', 'admin-error', $url);
    					}
    				}
    				$role = null;
    				$errorMessage = '';
    				$privilegeIds = null;
    				if ($formData['id']) {
    					$role = $roleService->getById ( $formData ['id'] );
    					if ($role)
    						$privilegeIds = $rolePrivilegeService->listPriIdByRole($role);
    				}
    				$privileges = $privilegeService->listAll();
    				return array(
    					'privileges' => $privileges,
    					'role' => $role,
    					'privilegeIds' => $privilegeIds
    				);
    			}
				$this->showMessage('Você não possui permissões para realizar essa operação.', 'home-error');
				$response->setContent ( \Zend\Json\Json::encode ( array ('status' => false, 'isLogged' => true) ) );
				return $response;
			}
			$this->showMessage('Você precisa fazer o login para realizar essa operação', 'home-error');
			$response->setContent ( \Zend\Json\Json::encode ( array ('status' => false, 'isLogged' => false, 'permitted' => true,) ) );
			return $response;
    	} catch (\Exception $e) {
			return $this->showMessage('Não foi possível realizar essa operação', 'home-error', '/');
    	}
    }

    public function removeAction()
    {
        try {
        	$response = $this->getResponse();
            if ($this->verifyUserSession()) {
                $acl = $this->getServiceLocator()->get('Admin\Permissions\Acl');
                if ($acl->isAllowed($this->session->user->rol->name, "Administração", "Administrar usuários e permissões")) {
                    $formData = $this->getFormData();
                    $role_id = $formData['id'];
                    
                    $serviceLocator = $this->getServiceLocator();
                    $roleService = $serviceLocator->get('Storage\Service\RoleService');
                    $rolePrivilegeService = $serviceLocator->get('Storage\Service\RolePrivilegeService');
                    $role = $roleService->getById($role_id);
                    if ($roleService->remove($role_id) != null) {
                    	$this->showMessage('O perfil foi removido com sucesso', 'admin-success');
                        $response->setContent(\Zend\Json\Json::encode(array(
                        	'status' => true,
                        	'isLogged' => true
                        )));
					} else {
                    	$errorMessage = 'O perfil <b>'. $role->name .'</b> não pode ser removido, pois ';
                    	$userService = $serviceLocator->get('Storage\Service\UserService');
                    	$associatedUsers = $userService->listByRole($role->rolId);
                    	if($associatedUsers){
                    		$usersCount = count($associatedUsers);
                    		if($usersCount > 1)
                    			$errorMessage .= 'os usuários ';
                    		if($usersCount == 1)
                    			$errorMessage .= 'o usuário ';
                    		foreach ($associatedUsers as $i => $user){
                    			if($usersCount == 1)
                    				$errorMessage .= '<b>'.$user->name.'</b>';
                    			else if($i == $usersCount-1)
                    				$errorMessage .= ' e <b>'.$user->name.'</b>';
                    			else if($usersCount > 1){
                    				$errorMessage .= '<b>'.$user->name.'</b>';
                    				if($i+1 != $usersCount-1)
                    					$errorMessage .= ', ';
                    			}
                    		}
                    		if($usersCount > 1)
                    			$errorMessage .= ' estão associados a ele.';
                    		if($usersCount == 1)
                    			$errorMessage .= ' está associado a ele.';
                    		$errorMessage .= ' Remova a associação destes usuários a este perfil e tente novamente.';
                    	}
                        $response->setContent(\Zend\Json\Json::encode(array(
                        	'status' => false,
                            'msg' => $errorMessage,
                        	'isLogged' => true
                        )));
                    }
                    return $response;
                }
				$this->showMessage('Você não possui permissões para realizar essa operação.', 'home-error');
				$response->setContent ( \Zend\Json\Json::encode ( array ('status' => false, 'isLogged' => true) ) );
				return $response;
			}
			$this->showMessage('Você precisa fazer o login para realizar essa operação', 'home-error');
			$response->setContent ( \Zend\Json\Json::encode ( array ('status' => false, 'isLogged' => false, 'permitted' => true,) ) );
			return $response;
        } catch (\Exception $e) {
        	$this->showMessage('Não foi possível remover a permissão', 'admin-error');
            $response->setContent(\Zend\Json\Json::encode(array(
                'status' => false,
            	'isLogged' => true
            )));
            return $response;
        }
    }
}