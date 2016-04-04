<?php
namespace Admin\Controller;

use Storage\Entity\User;
use Storage\Service\UserService;
use Storage\Entity\Access;
use Zend\Validator\StringLength;
use Main\Controller\MainController;
use Main\Helper\LogHelper;
use Storage\Entity\Layer;

class UserController extends MainController {
	public function indexAction() {
		try {
			if ($this->verifyUserSession ()) {
				$errorMessage = '';
				$acl = $this->getServiceLocator ()->get ( 'Admin\Permissions\Acl' );
				if ($acl->isAllowed ( $this->session->user->rol->name, "Administração", "Administrar usuários e permissões" )) {
					$serviceLocator = $this->getServiceLocator ();
					$userService = $serviceLocator->get ( 'Storage\Service\UserService' );
					
					$users = $userService->listAll ();
					return array (
							'users' => $users,
							"loggedUser"=>$this->session->user
							
					);
				}
				return $this->showMessage('Você não possui permissões para realizar essa operação', 'home-error', '/');
			}
			return $this->showMessage('Você precisa fazer o login para realizar essa operação', 'home-error', '/');
		} catch ( \Exception $e ) {
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
			return $this->showMessage('Não foi possível recuperar os usuários cadastrados', 'home-error', '/');
		}
	}
	public function formAction() {
		try {
			if ($this->verifyUserSession ()) {
				$request = $this->getRequest ();
				$serviceLocator = $this->getServiceLocator ();
				$acl = $this->getServiceLocator ()->get ( 'Admin\Permissions\Acl' );
				if ($acl->isAllowed ( $this->session->user->rol->name, "Administração", "Administrar usuários e permissões" )) {
					$userService = $serviceLocator->get ( 'Storage\Service\UserService' );
					$roleService = $serviceLocator->get ( 'Storage\Service\RoleService' );
					$accessService = $serviceLocator->get ( 'Storage\Service\AccessService' );
					
					$roles = $roleService->listAll ();
					$formData = $this->getFormData ();
					
					$id = null;
					$isResponsible = 0; // 0 = criação de usuário, não precisa verificar
					$us = null;
					if($formData ['id']){
						$id = $formData ['id'];
						$us = $userService->getById ( $id);
					}
					if ($request->isPost ()) {
						$url = '/user/form';
						if ($id)
							$url .= '?id='.$us->useId;
						
						if (!$us){
							$us = new User ();
						}else{
							LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Variável us vazia. - Linha: " . __LINE__);		
						}
						
						$nameLengthValidator = new StringLength ( array (
							'min' => 4,
							'max' => 50
						) );
						$loginLengthValidator = new StringLength ( array (
								'min' => 4,
								'max' => 10
						) );
						$passwordLengthValidator = new StringLength ( array (
							'min' => 6,
							'max' => 20
						) );
						
						$name = trim ( $formData ['name'] );
						if ($nameLengthValidator->isValid ( $name ))
							$us->name = $name;
						else {
							return $this->showMessage('O campo nome é obrigatório e deve conter no minimo 4 e no máximo 50 caracteres', 'admin-error', $url);
						}
						
						$login = trim($formData['login']);
						if ($loginLengthValidator->isValid($login))
							$us->login = $login;
						else {
							return $this->showMessage('O campo login é obrigatório e deve conter no minimo 4 e no máximo 10 caracteres', 'admin-error', $url);
						}
						
						$email = trim ( $formData ['email'] );
						if (isset($email)) {
							if (! $userService->checkIfEmailExists ( $formData ['email'], $id ))
								$us->email = $email;
							else {
								return $this->showMessage('O email digitado já existe', 'admin-error', $url);
							}
						} else {
							return $this->showMessage('O email digitado é inválido ou já está cadastrado no sistema', 'admin-error', $url);
						}
						$password = trim ( $formData ['password'] );
						if ($formData ['password']) {
							if ($passwordLengthValidator->isValid ( $password ))
								$us->definePassword ( $password );
							else {
								return $this->showMessage('A senha deve conter no minimo 6 e no máximo 20 caracteres', 'admin-error', $url);
							}
						}else{
							LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Variável password vazia. - Linha: " . __LINE__);		
						}
						
						$roleId = $formData ['role'];
						$role = $roleService->getById ( $roleId );
						if ($role) {
							$us->rol = $role;
						} else {
							return $this->showMessage('Selecione um perfil', 'admin-error', $url);
						}
						if ($id) {
							$response = $userService->updateUser ($us);
							if ($response) {
								return $this->showMessage('Usuário alterado com sucesso!', 'admin-success', '/user/associateProjects?id=' . $us->useId);
							} else
								return $this->showMessage('Não foi possível alterar as informações do usuário', 'admin-error', $url);
						} else {
							$response = $userService->add ( $us );
							if ($response)
								return $this->showMessage('Usuário criado com sucesso!', 'admin-success', '/user/associateProjects?id=' . $us->useId);
							else
								return $this->showMessage('Não foi possível inserir o usuário', 'admin-error', $url);
						}
					} else {
						$errorMessage = '';
						return array (
							'roles' => $roles,
							'us' => $us,
							'loggedUser' => $this->session->user
						);
					}
				}
				return $this->showMessage('Você não possui permissões para realizar essa operação', 'home-error', '/');
			}
			return $this->showMessage('Você precisa fazer o login para realizar essa operação', 'home-error', '/');
		} catch ( \Exception $e ) {
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
			return $this->showMessage('Não foi possível realizar essa operação', 'home-error', '/');
		}
	}
	public function associateProjectsAction() {
		try {				
			$request = $this->getRequest ();
			if ($this->verifyUserSession ()) {
				$serviceLocator = $this->getServiceLocator ();
				$acl = $this->getServiceLocator ()->get ( 'Admin\Permissions\Acl' );
				if ($acl->isAllowed ( $this->session->user->rol->name, "Administração", "Administrar usuários e permissões" )) {
					$userService = $serviceLocator->get ( 'Storage\Service\UserService' );
					$projectService = $serviceLocator->get ( 'Storage\Service\ProjectService' );
					$accessService = $serviceLocator->get ( 'Storage\Service\AccessService' );
					$projects = $projectService->listAll ();
					$users = $userService->listAll ();
					$formData = $this->getFormData ();
					$user = null;
					$url = '/user/associateProjects';
					if($formData['id']){
						$user = $userService->getById($formData['id']);
						$url .= '?id='.$user->useId;
					}
					$cont = 0;
					$coord = null;
					$prjs = null;
					if ($request->isPost ()) {
						$prjResps = null;
						if ($formData ['prjs'])
							$prjs = $formData ['prjs'];
						$user = null;
						if ($formData ['users']){
							$userId = $formData ['users'];
							$user = $userService->getById ( $userId );
						}
						else if ($formData ['userId']) {
							$userId = $formData ['userId'];
							$user = $userService->getById ( $userId );
						}
						try {
							if($user && $prjs) { // Verifica se algum usuário foi selecionado no combo e se pelo menos um projeto foi marcado
								$userPrjs = $accessService->getPrjByUser($user);
								$userService->begin();
								/*if(!$userPrjs){
									$userService->rollback();
									return $this->showMessage('Não foi possível associar o usuário aos subprojetos', 'admin-error', $url);
								}*/
								if (! $accessService->removeAllByUser ( $user )) { //Remove a associação do usuário aos projetos para depois inserir novamente
									$userService->rollback();
									return $this->showMessage('Não foi possível associar o usuário aos subprojetos', 'admin-error', $url);
								}
								
								$list = array();
								foreach($userPrjs as $prj){
									array_push($list, $prj->prjId);
								}
								
								foreach ($list as $prjSelected) {
									if(array_search($prjSelected,$prjs)===false){
										$tableName = 'table_' . $user->useId;
										$project = $projectService->getById ($prjSelected);
										$commitService = $serviceLocator->get ('Storage\Service\CommitService');
										$isTrue = $commitService->removeByUserAndPrj($this->session->user, $project);
										$dir = $this->getParentDir(__DIR__, 5);
										$dir = $dir . "/geogig-repositories/" . $project->prjId . "/" . $user->useId;;
										if(!$isTrue && !$this->deleteTable($tableName, $project->projectName && !$this->removeDir($dir))){
											return $this->showMessage('Não foi possível associar o usuário aos subprojetos', 'admin-error', $url);
										}
										$commitService->removeByUserAndPrj($user, $project);
									}
								}
								
								$accessList = array();
								foreach ($prjs as $prj) {
									$project = $projectService->getById ($prj);
									$access = new Access ();
									$access->prj = $project;
									$access->use = $user;
									if(!$this->createPostGISTable($project, $user)){
										return $this->showMessage('Não foi possível associar o usuário ao projeto: ' . $project->projectName, 'admin-error', $url);
									}
									array_push ($accessList, $access);
								}
								if ($accessList) {
									if ($accessService->addAll($accessList)){
										$userService->commit();
										return $this->showMessage('Associação concluída com sucesso!', 'admin-success', '/user');
									}
									else {
										$userService->rollback();
										return $this->showMessage('Não foi possível associar o usuário aos subprojetos', 'admin-error', $url);
									}
								}
							}
							else{
								return $this->showMessage('Selecione um usuário e pelo menos um subprojeto', 'admin-error', $url);
							}
						} catch ( \Exception $e ) {
							LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
							return $this->showMessage('Não foi possível associar o usuário aos subprojetos.', 'admin-error', $url);
						}
					}
					return array (
						'users' => $users,
						'user' => $user,
						'projects' => $projects 
					);
				}
				return $this->showMessage('Você não possui permissões para realizar essa operação.', 'home-error', '/');
			}
			return $this->showMessage('Você precisa fazer o login para realizar essa operação', 'home-error', '/');
		} catch ( \Exception $e ) {
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
			return $this->showMessage('Não foi possível associar o usuário aos subprojetos.', 'home-error', '/');
		}
	}
	
	private function createPostGISTable($project, $user){
		$tableName = 'table_' . $user->useId;
		$serviceLocator = $this->getServiceLocator();
		$datasourceService = $serviceLocator->get ( 'Storage\Service\DataSourceService' );
		$config = $this->getConfiguration();
		$dbConn = pg_connect('host='.$config["datasource"]["host"].' dbname='.strtolower($project->projectName).' user='.$config["datasource"]["login"].' password='.$config["datasource"]["password"].' connect_timeout=5');
		if($dbConn!==false){
			$selectTableSQL = 'SELECT * FROM public.' . $tableName;
			$tableExists = pg_query($dbConn, $selectTableSQL);
			if($tableExists !== false){
				return true;
			}
			$createTableSQL = 'CREATE TABLE public.' . $tableName . ' (id serial PRIMARY KEY)';
			$query = pg_query($dbConn, $createTableSQL);
			if($query!==false){
				/*if(pg_connection_status() === 0) {
					pg_close($dbConn);
				}*/
				$geomColumn = "SELECT AddGeometryColumn ('public','". $tableName ."','geom',3857,'POINT',2)";
				$query = pg_query($dbConn, $geomColumn);
				if ($query!==false){
					pg_close($dbConn);
					$layerService = $this->serviceLocator->get ( 'Storage\Service\LayerService' );
					$layer = new Layer();
					$layer->prj = $project;
					$layer->use = $user;
					$layer->projection = "3857";
					$resultLayer = $layerService->addLayer($layer);
					if(!$resultLayer){
						$this->deleteTable($tableName, $project->projectName);
					}else{
						$this->publishLayer($tableName, $project);
						if($this->createGeoGigRepo($user, $project)){
							return true;
						}else{
							$this->deleteTable($tableName, $project->projectName);
							return false;
						}
					}
				}else{
					$this->deleteTable($tableName, $project->projectName);
					pg_close($dbConn);
					return false;
				}
			}else{
				//$this->deleteDatabase($project->projectName);
				pg_close($dbConn);
				return false;
			}
		}else{
			return false;
		}
	}
	
	private function publishLayer($tableName, $prj){
		try{
			$prjName = $prj->projectName;
			$geoRestService = $this->serviceLocator->get ( 'Storage\Service\GeoServerRESTService' );
			$geoServerService = $this->serviceLocator->get ( 'Storage\Service\GeoServerService' );
			$shape = strtolower(pathinfo($tableName, PATHINFO_FILENAME));
			$geoserver = $geoServerService->getByPrj($prj->prjId);
			$geoServerLogin = $geoserver->login.':'.$geoserver->pass;
			$responseGeoServer =$geoRestService->createLayer($geoServerLogin, $prjName, $shape, $geoserver->host);
			if ($responseGeoServer){
				return true;
			}else{
				return false;
			}
		} catch (\Exception $e) {
			return false;
		}
	}
	
	private function createGeoGigRepo($user, $project){
		$dir = $this->getParentDir(__DIR__, 5);
		$dir = $dir . "/geogig-repositories/" . $project->prjId;
		if(!is_dir($dir)){
			if(mkdir ( $dir, 0777, true ) === false){
				return false;
			}
		}
		$dir = $dir . "/" . $user->useId;
		if(!is_dir($dir)){
			if(mkdir ( $dir, 0777, true ) === false){
				return false;
			}
			if(chdir($dir)){
				$commands = array(
						"sudo geogig init", 
						'sudo geogig config --local user.name "' . $user->name .'"',
						'sudo geogig config --local user.email "' . $user->email . '"'
				);
				foreach($commands as $command){
					//$output = shell_exec(escapeshellcmd($command));
					exec(escapeshellcmd($command), $output, $return_var);
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$output."- Linha: " . __LINE__);
					if($return_var !== 0){
						$this->removeDir($dir);
						return false;
					}
				}
			}else{
				$this->removeDir($dir);
				return false;
			}
		}
		return true;
	}
	public function disableAction() {
		try {
			$response = $this->getResponse();
			if ($this->verifyUserSession ()) {
				$acl = $this->getServiceLocator ()->get ( 'Admin\Permissions\Acl' );
				if ($acl->isAllowed ( $this->session->user->rol->name, "Administração", "Administrar usuários e permissões" )) {
					$formData = $this->getFormData ();
					$user_id = $formData ['id'];
					
					$serviceLocator = $this->getServiceLocator ();
					$userService = $serviceLocator->get ( 'Storage\Service\UserService' );
					$accessService = $serviceLocator->get ( 'Storage\Service\AccessService' );
					$user = $userService->getById ( $user_id );
					if ($userService->setActive ( $user, 0 )) {
						$response->setContent ( \Zend\Json\Json::encode ( array (
								'status' => true,
								'msg' => 'O usuário foi desativado com sucesso!',
								'userId' => $user_id
						) ) );
						return $response;
					} else {
						$response->setContent ( \Zend\Json\Json::encode ( array (
								'status' => false,
								'msg' => 'Não foi possível desativar o usuário'
						) ) );
						return $response;
					}
				}
				$response->setContent ( \Zend\Json\Json::encode ( array (
						'status' => false,
						'msg' => 'Você não possui permissões para realizar essa operação'
				)));
				return $response;
			}
			$response->setContent ( \Zend\Json\Json::encode ( array (
					'status' => false,
					'msg' => 'Você precisa fazer o login para realizar essa operação'
			) ) );
			return $response;
		} catch ( \Exception $e ) {
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
			$response->setContent ( \Zend\Json\Json::encode ( array ('status' => false, 'msg' => 'Não foi possível desativar o usuário') ) );
			return $response;
		}
	}
	public function enableAction() {
		try {
			$response = $this->getResponse();
			if ($this->verifyUserSession ()) {
				$acl = $this->getServiceLocator ()->get ( 'Admin\Permissions\Acl' );
				if ($acl->isAllowed ( $this->session->user->rol->name, "Administração", "Administrar usuários e permissões" )) {
					
					$formData = $this->getFormData ();
					$user_id = $formData ['id'];
						
					$serviceLocator = $this->getServiceLocator ();
					$userService = $serviceLocator->get ( 'Storage\Service\UserService' );
					$accessService = $serviceLocator->get ( 'Storage\Service\AccessService' );
					$user = $userService->getById ( $user_id );
					if ($user && $userService->setActive( $user, 1 )) {
						$response->setContent ( \Zend\Json\Json::encode ( array (
							'status' => true,
							'msg' => 'O usuário foi ativado com sucesso!',
							'userId' => $user_id
						) ) );
						return $response;
					} else {
						$response->setContent ( \Zend\Json\Json::encode ( array (
							'status' => false,
							'msg' => 'Não foi possível ativar o usuário'
						) ) );
						return $response;
					}
				}
				$response->setContent ( \Zend\Json\Json::encode ( array (
						'status' => false,
						'msg' => 'Você não possui permissões para realizar essa operação'
				)));
				return $response;
			}
			$response->setContent ( \Zend\Json\Json::encode ( array (
					'status' => false,
					'msg' => 'Você precisa fazer o login para realizar essa operação'
			) ) );
			return $response;
		} catch ( \Exception $e ) {
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
			$this->showMessage('Não foi possível ativar o usuário', 'admin-error');
			$response->setContent ( \Zend\Json\Json::encode ( array ('status' => false, 'msg' => 'Não foi possível ativar o usuário') ) );
			return $response;
		}
	}
	public function checkIfEmailExistsAction() {
		try {
			$response = $this->getResponse();
			if ($this->verifyUserSession ()) {
				$acl = $this->getServiceLocator ()->get ( 'Admin\Permissions\Acl' );
				if ($acl->isAllowed ( $this->session->user->rol->name, "Administração", "Administrar usuários e permissões" )) {
					$formData = $this->getFormData ();
					$email = $formData ['email'];
					$userId = null;
					if ($formData ['id'])
						$userId = $formData ['id'];
					
					$serviceLocator = $this->getServiceLocator ();
					$userService = $serviceLocator->get ( 'Storage\Service\UserService' );
					if ($userService->checkIfEmailExists ( $email, $userId )) {
						$response->setContent ( \Zend\Json\Json::encode ( array (
								'status' => false,
								'isLogged' => true
						) ) );
						return $response;
					} else {
						$response->setContent ( \Zend\Json\Json::encode ( array (
								'status' => true,
								'isLogged' => true
						) ) );
						return $response;
					}
				}
				$this->showMessage('Você não possui permissões para realizar essa operação.', 'home-error');
				$response->setContent ( \Zend\Json\Json::encode ( array ('status' => false, 'isLogged' => true) ) );
				return $response;
			}
			$this->showMessage('Você precisa fazer o login para realizar essa operação', 'home-error');
			$response->setContent ( \Zend\Json\Json::encode ( array ('status' => false, 'isLogged' => false) ) );
			return $response;
		} catch ( \Exception $e ) {
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
			$response->setContent ( \Zend\Json\Json::encode ( array (
					'status' => false,
					'isLogged' => true
			) ) );
			return $response;
		}
	}
	public function getUserPrjsAction() {
		try {
			$response = $this->getResponse();
			if ($this->verifyUserSession ()) {
				$acl = $this->getServiceLocator ()->get ( 'Admin\Permissions\Acl' );
				if ($acl->isAllowed ( $this->session->user->rol->name, "Administração", "Administrar usuários e permissões" )) {
					$formData = $this->getFormData ();
					
					$assocPrjs = null;
					$assocAccess = null;
					$user = null;
					$prjs = null;
					$prjsToDisable = null;
					
					$serviceLocator = $this->getServiceLocator ();
					$userService = $serviceLocator->get ( 'Storage\Service\UserService' );
					$accessService = $serviceLocator->get ( 'Storage\Service\AccessService' );
					
					if($formData ['id']){
						$userId = $formData ['id'];
						$user = $userService->getById ( $userId );
					
						if($user){
							$assocAccess = $accessService->getPrjByUser($user);
							if(is_array($assocAccess)){
								$assocPrjs = array ();
								if(count($assocAccess) > 0){
									foreach ( $assocAccess as $access ) {
										array_push ( $assocPrjs, $access->prjId );
									}
								}
// 								$prjs = $accessService->getPrjs($user);
// 								if(is_array($prjs)){
// 									$prjsToDisable = array();
// 									if(count($prjs) > 0){
// 										foreach ($prjs as $prj){
// 											array_push($prjsToDisable, $prj->prjId);
// 										}
// 									}
// 								}
// 								else{
// 									$response->setContent ( \Zend\Json\Json::encode ( array (
// 											'status' => false,
// 											'msg' => 'Não foi possível recuperar os projetos associados a este usuário.'
// 									) ) );
// 									return $response;
// 								}
							}
							else{
								$response->setContent ( \Zend\Json\Json::encode ( array (
											'status' => false,
											'msg' => 'Não foi possível recuperar os projetos associados a este usuário.'
									) ) );
								return $response;
							}
						}
						else{
							$response->setContent ( \Zend\Json\Json::encode ( array (
											'status' => false,
											'msg' => 'Não foi possível recuperar os projetos associados a este usuário.'
									) ) );
							return $response;
						}
					}
					$response->setContent ( \Zend\Json\Json::encode ( array (
							'status' => true,
							'assocPrjs' => $assocPrjs,
							'prjsToDisable' => $prjsToDisable,
							'isLogged' => true
					) ) );
					return $response;
				}
				$response->setContent ( \Zend\Json\Json::encode ( array (
							'status' => false,
							'msg' => 'Você não possui permissões para realizar essa operação.'
					) ) );
				return $response;
			}
			$response->setContent ( \Zend\Json\Json::encode ( array (
					'status' => false,
					'msg' => 'Você precisa fazer o login para realizar essa operação'
			) ) );
			return $response;
		} catch ( \Exception $e ) {
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
			$response->setContent ( \Zend\Json\Json::encode ( array (
				'status' => false,
				'msg' => 'Não foi possível recuperar os projetos'
			) ) );
			return $response;
		}
	}
}