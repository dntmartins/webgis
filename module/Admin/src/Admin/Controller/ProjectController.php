<?php

namespace Admin\Controller;

use Storage\Entity\User;
use Storage\Service\UserService;
use Zend\Validator\StringLength;
use Main\Controller\MainController;
use Storage\Entity\Project;
use Storage\Entity\Datasource;
use Storage\Entity\Geoserver;
use Main\Helper\LogHelper;

class ProjectController extends MainController {
	
	public function indexAction() {
		try {
			if ($this->verifyUserSession ()) {
				$errorMessage = '';
				$acl = $this->getServiceLocator ()->get ( 'Admin\Permissions\Acl' );
				if ($acl->isAllowed ( $this->session->user->rol->name, "Administração", "Administrar usuários e permissões" )) {
					$serviceLocator = $this->getServiceLocator ();
					$projectService = $serviceLocator->get ( 'Storage\Service\ProjectService' );
					$projects = $projectService->listAll ();
					return array (
						'projects' => $projects 
					);
				}
				return $this->showMessage ( 'Você não possui permissões para realizar essa operação', 'home-error', '/' );
			}
			return $this->showMessage ( 'Você precisa fazer o login para realizar essa operação', 'home-error', '/' );
		} catch ( \Exception $e ) {
			return $this->showMessage ( 'Não foi possível recuperar os projetos cadastrados', 'home-error', '/' );
		}
	}
	public function formAction() {
		try {
			$response = $this->getResponse();
			if ($this->verifyUserSession ()) {
				$request = $this->getRequest ();
				$serviceLocator = $this->getServiceLocator ();
				$acl = $this->getServiceLocator ()->get ( 'Admin\Permissions\Acl' );
				if ($acl->isAllowed ( $this->session->user->rol->name, "Administração", "Administrar usuários e permissões" )) {
					$userService = $serviceLocator->get ( 'Storage\Service\UserService' );
					$projectService = $serviceLocator->get ( 'Storage\Service\ProjectService' );
					$accessService = $serviceLocator->get ( 'Storage\Service\AccessService' );
					$geoserverRESTService = $serviceLocator->get ( 'Storage\Service\GeoServerRESTService' );
					$geoserverService = $serviceLocator->get ( 'Storage\Service\GeoServerService' );
					$datasourceService = $serviceLocator->get ( 'Storage\Service\DataSourceService' );
					$formData = $this->getFormData ();
					$id = null;
					$prj = null;
					$ignoreId = null;
					if ($formData ['id']) {
						$id = $formData ['id'];
						$ignoreId = $id;
						$prj = $projectService->getById ( $id );
					}
					if ($request->isPost ()) {
						$url = '/project/form';
						if ($id)
							$url .= '?id=' . $prj->prjId;
						
						if (! $prj) {
							$prj = new Project ();
						}
						$nameLengthValidator = new StringLength (array(
							'min' => 1,
							'max' => 10
						));
						
						$projectService->begin();
						$name = strtolower(trim($formData['name']));
						if ($name){
							if($this->verifyDuplicateProject($name, $ignoreId)){
								if ($nameLengthValidator->isValid($name)){
									$prj->projectName = $name;
								}else{
									$response->setContent ( \Zend\Json\Json::encode ( array (
											'status' => false,
											'msg' => "Nome do projeto deve conter no máximo 10 caracteres"
									) ) );
									return $response;
								}
							}else{
								$response->setContent ( \Zend\Json\Json::encode ( array (
										'status' => false,
										'msg' => "Já existe um projeto com esse nome"
								) ) );
								return $response;
							}
						}else {
							$response->setContent ( \Zend\Json\Json::encode ( array (
									'status' => false,
									'msg' => "O campo nome é obrigatório"
							) ) );
							return $response;
						}
						$description = trim ( $formData ['description'] );
						if ($description) {
							$prj->description = $description;
						}else {
							$response->setContent ( \Zend\Json\Json::encode ( array (
									'status' => false,
									'msg' => "O campo descrição é obrigatório"
							) ) );
							return $response;
						}
						$link = trim ( $formData ['link'] );
						if ($link) {
							$prj->link = $link;
						}else {
							$response->setContent ( \Zend\Json\Json::encode ( array (
									'status' => false,
									'msg' => "O campo link é obrigatório"
							) ) );
							return $response;
						}
						$saveLogo = null;
						if(array_key_exists('image', $_FILES)){
							if ($_FILES ['image']['tmp_name'] != null){
								$imagePath = $_FILES ['image'] ['tmp_name'];
								$logoName = $_FILES ['image'] ['name'];
								if($imagePath && $logoName)
									$saveLogo = $this->sendLogoToStorage ( $imagePath, $logoName );
								else{
									$response->setContent ( \Zend\Json\Json::encode ( array (
											'status' => false,
											'msg' => "Não foi possível inserir o logo!"
									) ) );
									return $response;
								}
								if ($saveLogo != null) {
									$prj->logo = $saveLogo;
								}else{
									$response->setContent ( \Zend\Json\Json::encode ( array (
											'status' => false,
											'msg' => "Não foi possível inserir o logo!"
									) ) );
								}
							}else {
								$response->setContent ( \Zend\Json\Json::encode ( array (
										'status' => false,
										'msg' => "O campo logo é obrigatório"
								) ) );
								return $response;
							}
						}
						if ($id) {
							$result = $projectService->updateProject ( $prj );
							if ($result) {
								$projectService->commit ();
								$this->showMessage ( 'Projeto alterado com sucesso!', "admin-success" );
								$response->setContent ( \Zend\Json\Json::encode ( array (
										'status' => true,
										'msg' => "Projeto alterado com sucesso!" 
								) ) );
								return $response;
							} else {
								$projectService->rollback ();
								$response->setContent ( \Zend\Json\Json::encode ( array (
										'status' => false,
										'msg' => 'Não foi possível alterar as informações do projeto' 
								) ) );
								return $response;
							}
						} else {
							$result = false;
							if ($projectService->add ( $prj )) {
								if($this->renameLogo ( $prj )){
									if ($this->createDatabase ( $prj->projectName )) {
										$config = $this->getConfiguration();
										
										$datasourceService->begin();
										if($config){
											$datasource = new Datasource();
											$datasource->dbName = $prj->projectName;
											$datasource->host = $config["datasource"]["host"];
											$datasource->port = $config["datasource"]["port"];
											$datasource->login = $config["datasource"]["login"];
											$datasource->password = $config["datasource"]["password"];
											
											$geoserver = new Geoserver();
											$geoserver->prj = $prj;
											$geoserver->login = $config["geoserver"]["login"];
											$geoserver->pass = $config["geoserver"]["password"];
											$geoserver->host = $config["geoserver"]["host"]."/".$config["geoserver"]["path"]."/";
											
											$login = $geoserver->login.":".$geoserver->pass;
										}
										if($datasourceService->add($datasource)){
											if($geoserverService->addGeoserver($geoserver)){
												if ($geoserverRESTService->createWorkspace ( $login, $prj->projectName, $geoserver->host)) {
													if ($geoserverRESTService->createDatasource ($login, $prj->projectName, $datasource, $geoserver->host)) {
														$result = true;
													}else{
														$result = false;
														$this->deleteDatabase($prj->projectName);
														$geoserverRESTService->deleteWorkspace($login ,$prj->projectName, $geoserver->host);
														$datasourceService->rollback();
													}
												}
											}else{
												$result = false;
												$this->deleteDatabase($prj->projectName);
												$datasourceService->rollback();
											}
										}
										else{
											$datasourceService->rollback();
											$this->deleteDatabase($prj->projectName);
										}
									}else{
										$projectService->rollback ();
										$response->setContent ( \Zend\Json\Json::encode ( array (
												'status' => true,
												'msg' => 'Ocorreu um erro ao criar o projeto!'
										) ) );
									}
								}
								if ($result) {
									$datasourceService->commit();
									$projectService->commit ();
									$this->showMessage ( 'Projeto criado com sucesso!', "admin-success" );
									$response->setContent ( \Zend\Json\Json::encode ( array (
											'status' => true,
											'msg' => 'Projeto criado com sucesso!' 
									) ) );
									return $response;
								} else {
									if($saveLogo != null){
										$ext = pathinfo($saveLogo, PATHINFO_EXTENSION);
										$pathLogo = getCwd() . '/module/Workspace/src/Workspace/file-uploads/logos/'.$prj->prjId.".".$ext;
										if ($pathLogo){
											unlink ($pathLogo);
										}
									}
									$projectService->rollback ();
									$response->setContent ( \Zend\Json\Json::encode ( array (
											'status' => false,
											'msg' => 'Não foi possível inserir o projeto' 
									) ) );
									return $response;
								}
							}
						}
					} else {
						$errorMessage = '';
						return array (
								'prj' => $prj 
						);
					}
				}
				$response->setContent ( \Zend\Json\Json::encode ( array (
						'status' => false,
						'msg' => 'Você não possui permissões para realizar essa operação'
				) ) );
				return $response;
			}
			$response->setContent ( \Zend\Json\Json::encode ( array (
					'status' => false,
					'msg' => 'Você precisa fazer o login para realizar essa operação'
			) ) );
			return $response;
		} catch ( \Exception $e ) {
			$response->setContent ( \Zend\Json\Json::encode ( array (
					'status' => false,
					'msg' => 'Não foi possível realizar essa operação'
			) ) );
			return $response;
		}
	}
	
	public function createDatabase($prjName){
		$serviceLocator = $this->getServiceLocator();
		$datasourceService = $serviceLocator->get ( 'Storage\Service\DataSourceService' );
		$config = $this->getConfiguration();
		$dbConn = pg_connect('host='.$config["datasource"]["host"].' user='.$config["datasource"]["login"].' password='.$config["datasource"]["password"].' connect_timeout=5');
		if($dbConn!==false){
			$sql = 'CREATE DATABASE "'. $prjName.'"';
			$query = pg_query($dbConn, $sql);
			if($query!==false){
				if(pg_connection_status() === 0) {// conexao ok, então fecha
					pg_close($dbConn);
				}
				$dbConn = pg_connect('host='.$config["datasource"]["host"].' dbname='.strtolower($prjName).' user='.$config["datasource"]["login"].' password='.$config["datasource"]["password"].' connect_timeout=5');
				$sqlPostgis = "CREATE EXTENSION postgis";
				$queryPostgis = pg_query($dbConn, $sqlPostgis);
				if ($queryPostgis!==false){
					$sqlTopology = "CREATE EXTENSION postgis_topology";
					$queryTopology = pg_query($dbConn, $sqlTopology);
					if ($queryTopology!==false){
						pg_close($dbConn);
						return true;					
					}else{
						$this->deleteDatabase($prjName);
						pg_close($dbConn);
						return false;
					}
				}else{
					$this->deleteDatabase($prjName);
					pg_close($dbConn);
					return false;
				}
			}else{
				//$this->deleteDatabase($prjName);
				pg_close($dbConn);
				return false;
			}
		}else{
			return false;
		}
	}
	public function disableAction() {
		try {
			$response = $this->getResponse ();
			if ($this->verifyUserSession ()) {
				$acl = $this->getServiceLocator ()->get ( 'Admin\Permissions\Acl' );
				if ($acl->isAllowed ( $this->session->user->rol->name, "Administração", "Administrar usuários e permissões" )) {
					$formData = $this->getFormData ();
					$prj_id = $formData ['id'];
					$serviceLocator = $this->getServiceLocator ();
					$projectService = $serviceLocator->get ( 'Storage\Service\ProjectService' );
					$accessService = $serviceLocator->get ( 'Storage\Service\AccessService' );
					$prj = $projectService->getById ( $prj_id );
					if ($projectService->setActive ( $prj, 0 )) {
						$response->setContent ( \Zend\Json\Json::encode ( array (
								'status' => true,
								'msg' => 'O projeto foi desativado com sucesso!',
								'prjId' => $prj_id
						) ) );
						return $response;
					} else {
						$response->setContent ( \Zend\Json\Json::encode ( array (
								'status' => false,
								'msg' => 'Não foi possível desativar o projeto'
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
			$this->showMessage ( 'Não foi possível desativar o projeto', 'admin-error' );
			$response->setContent ( \Zend\Json\Json::encode ( array (
					'status' => false,
					'isLogged' => true 
			) ) );
			return $response;
		}
	}
	public function enableAction() {
		try {
			$response = $this->getResponse ();
			if ($this->verifyUserSession ()) {
				$acl = $this->getServiceLocator ()->get ( 'Admin\Permissions\Acl' );
				if ($acl->isAllowed ( $this->session->user->rol->name, "Administração", "Administrar usuários e permissões" )) {
					
					$formData = $this->getFormData ();
					$prj_id = $formData ['id'];
					
					$serviceLocator = $this->getServiceLocator ();
					$projectService = $serviceLocator->get ( 'Storage\Service\ProjectService' );
					$accessService = $serviceLocator->get ( 'Storage\Service\AccessService' );
					$prj = $projectService->getById ( $prj_id );
					if ($prj && $projectService->setActive ( $prj, 1 )) {
						$response->setContent ( \Zend\Json\Json::encode ( array (
								'status' => true,
								'msg' => 'O projeto foi ativado com sucesso!',
								'prjId' => $prj_id
						) ) );
						return $response;
					} else {
						$response->setContent ( \Zend\Json\Json::encode ( array (
								'status' => false,
								'msg' => 'Não foi possível ativar o projeto'
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
			$this->showMessage ( 'Não foi possível ativar o projeto', 'admin-error' );
			$response->setContent ( \Zend\Json\Json::encode ( array (
					'status' => false,
					'isLogged' => true 
			) ) );
			return $response;
		}
	}
	private function sendLogoToStorage($imagePath, $fileName) {			
		try{
			$dirArquivos = dirname ( __DIR__ );
			$pathUpload = getCwd() . '/module/Workspace/src/Workspace/file-uploads';
				
			if (! is_dir ( $pathUpload ))
				mkdir ( $pathUpload, 0777 );

			$pathLogo = $pathUpload . '/logos';
			if (! is_dir ( $pathLogo ))
				mkdir ( $pathLogo, 0777 );
			$logo = $pathLogo . '/' . $fileName;
			if ($logo != null){
				$copy = copy ( $imagePath , $logo);
				chmod($logo, 0777);
				if ($copy){
					return $logo;
				}
			}
		}catch (\Exception $e){
			return false;
		}
	}
	private function renameLogo($prj) {
		$serviceLocator = $this->getServiceLocator ();
		$projectService = $serviceLocator->get ( 'Storage\Service\ProjectService' );
		
		if ($prj) {
			$extensao = pathinfo ( $prj->logo );
			$extensao = $extensao ['extension'];
			
			$path = dirname ( $prj->logo );
			$path = $path . '/' . $prj->prjId . '.' . $extensao;
			
			$rename = rename ( $prj->logo, $path );
			
			if ($rename) {
				if ($projectService->updateLogo ( $prj->prjId, $path )) {
					return true;
				}
			}
		}
	}
	public function getLogoAction()
	{
		try {
			$formData=$this->getFormData();
			$logoId = $formData['id'];
	
			$imageContent=null;
			
			if(!isset($logoId)){
				$imageContent=$this->getImage();
			}else{
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: LogoId vazio. - Linha: " . __LINE__);		
			}
			
			$serviceLocator=$this->getServiceLocator();
			$projectService=$serviceLocator->get('Storage\Service\ProjectService');
			$prj=$projectService->getById($logoId);
				
			if(!$prj){
				$imageContent=$this->getImage();
			}
					
			$extensao = pathinfo($prj->logo);
			$extensao = $extensao['extension'];
				
			$path=$prj->logo;
			$imageContent = $this->getImage($path);
	
			$response = $this->getResponse();
			$response->setContent($imageContent);
			$response->getHeaders()
			->addHeaderLine('Content-Transfer-Encoding', 'binary')
			->addHeaderLine('Content-Type', 'image/jpeg')
			->addHeaderLine('Content-Length', strlen($imageContent));
			// TODO: aqui eh aconselhado usar a funcao mb_strlen no lugar de strlen, mas um suporte adicional deve ser instalado no php, e portanto adotamos strlen
			return $response;
		}catch (\Exception $e){
			$response->setContent(\Zend\Json\Json::encode(array('status' => false, 'isLogged' => true, 'permitted' => true, 'msg' => 'Falhou ao recuperar a foto.')));
			return $response;
		}
	}
		
	private function getImage($path=NULL){
        try {
        	if(!$path || !is_file($path)) $path=PUBLIC_PATH."/img/100x100.jpg";
        	return file_get_contents($path);
    	}catch (\Exception $e){
    	    return null;
    	}
	}
	
	private function verifyDuplicateProject($name, $ignore=NULL){
		try {
			$serviceLocator = $this->getServiceLocator();
			$projectService = $serviceLocator->get ('Storage\Service\ProjectService');
			$projects = $projectService->listAll();
			if($projects){
				foreach ($projects as $project){
					if($ignore && $ignore == $project->prjId)continue;
					if (strtolower($project->projectName) == strtolower($name))
						return false;
				}
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}
}
