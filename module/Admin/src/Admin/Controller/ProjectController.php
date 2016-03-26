<?php

namespace Admin\Controller;

use Storage\Entity\User;
use Storage\Service\UserService;
use Zend\Session\Container;
use Storage\Entity\Access;
use Zend\I18n\Validator\Alnum;
use Zend\I18n\Validator\Alpha;
use Zend\I18n\Validator\Int;
use Zend\Validator\StringLength;
use Zend\I18n\Validator\PhoneNumber;
use Zend\I18n\Validator\Zend\I18n\Validator;
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
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
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
							LogHelper::writeOnLog("O campo descricao estah ok.");
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
							LogHelper::writeOnLog("O campo link estah ok.");
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
									LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Logo (_FILES ['image']) está vazio - Linha: " . __LINE__);
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
								LogHelper::writeOnLog("Imagem de logo nao enviada mas eh obrigatoria.");
								$response->setContent ( \Zend\Json\Json::encode ( array (
										'status' => false,
										'msg' => "O campo logo é obrigatório"
								) ) );
								return $response;
							}
						}
						$sldPath = null;
						if( array_key_exists('sldUpload', $_FILES)){
							$sldFile = $_FILES['sldUpload'];
							$sldService = $serviceLocator->get ( 'Storage\Service\SldService' );
							//verificando nome de estilo duplicado
							if($this->verifyDuplicateSld($sldFile['name'], $ignoreId)){
								$sld = $sldService->saveSld($sldFile, $serviceLocator, '1');
								if (!$sld){
									$response->setContent ( \Zend\Json\Json::encode ( array (
											'status' => false,
											'msg' => "Não foi possível inserir o arquivo de estilo"
									) ) );
									return $response;
								}else{
									$sldPath = $sld->diskLocation.$sld->sldId;
									LogHelper::writeOnLog("Arquivo SLD salvo no sistema em (".$sldPath."), continue.");
								}
							}else{
								LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Sld com o nome ".$sldFile['name']." já existe Linha: " . __LINE__);
								$response->setContent ( \Zend\Json\Json::encode ( array (
										'status' => false,
										'msg' => "Já existe um arquivo de estilo com esse nome!"
								) ) );
								return $response;
							}
						}
						
						if ($id) {
							$result = $projectService->updateProject ( $prj );
							if ($result) {
								$projectService->commit ();
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
										LogHelper::writeOnLog("Criado banco de dados no PostgreSQL, continue.");
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
														if (isset ( $sld )){
															$result = $geoserverRESTService->createStyle ( $login, $sld, $geoserver->host);
														}else{
															$result = true;
														}
													}else{
														$this->deleteDatabase($prj->projectName);
														$geoserverRESTService->deleteWorkspace($login ,$prj->projectName, $geoserver->host);
														$datasourceService->rollback();
													}
												}else{
													LogHelper::writeOnLog("Create workspace geoserver FALHOU, pare.");												
												}
											}else{
												LogHelper::writeOnLog("Falhou ao adicionar dados do geoserver no mysql, pare.");
												$this->deleteDatabase($prj->projectName);
												$datasourceService->rollback();
											}
										}
										else{
											$datasourceService->rollback();
											$this->deleteDatabase($prj->projectName);
										}
									}else{
										LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ .":". __LINE__.": FALHOU ao criar o banco de dados.");
									}
								}
								if ($result) {
									$datasourceService->commit();
									$dirShapeFiles = getcwd () . "/module/Workspace/src/Workspace/file-uploads/shape-files/" . $prj->prjId.'/';
									if (mkdir ( $dirShapeFiles) && chmod($dirShapeFiles, 0777)) {
										$projectService->commit ();
										$this->showMessage ( 'Projeto criado com sucesso!', "admin-success" );
										$response->setContent ( \Zend\Json\Json::encode ( array (
												'status' => true,
												'msg' => 'Projeto criado com sucesso!' 
										) ) );
									} else {
										$projectService->rollback ();
										$response->setContent ( \Zend\Json\Json::encode ( array (
												'status' => true,
												'msg' => 'Ocorreu um erro ao criar o projeto!' 
										) ) );
									}
									return $response;
								} else {
									if($saveLogo != null){
										$ext = pathinfo($saveLogo, PATHINFO_EXTENSION);
										$pathLogo = getCwd() . '/module/Workspace/src/Workspace/file-uploads/logos/'.$prj->prjId.".".$ext;
										if ($pathLogo){
											unlink ($pathLogo);
										}else{
											LogHelper::writeOnLog("Ocorreu um erro ao apagar logo, caminho nao existe.");
										}
									}
									if($sldPath)
										unlink ( $sldPath );
									$projectService->rollback ();
									$response->setContent ( \Zend\Json\Json::encode ( array (
											'status' => false,
											'msg' => 'Não foi possível inserir o projeto' 
									) ) );
									return $response;
								}
							}else{
								LogHelper::writeOnLog("Projeto NAO inserido, parando.");
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
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
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
			LogHelper::writeOnLog("Conectado ao PostgreSQL, continue.");
			$sql = 'CREATE DATABASE "'. $prjName.'"';
			$query = pg_query($dbConn, $sql);
			if($query!==false){
				LogHelper::writeOnLog("Database ".$prjName." criada, continue.");
				if(pg_connection_status() === 0) {// conexao ok, então fecha
					pg_close($dbConn);
				}
				$dbConn = pg_connect('host='.$config["datasource"]["host"].' dbname='.strtolower($prjName).' user='.$config["datasource"]["login"].' password='.$config["datasource"]["password"].' connect_timeout=5');
				$sqlPostgis = "CREATE EXTENSION postgis";
				$queryPostgis = pg_query($dbConn, $sqlPostgis);
				if ($queryPostgis!==false){
					LogHelper::writeOnLog("Extensao Postgis criada, continue.");
					$sqlTopology = "CREATE EXTENSION postgis_topology";
					$queryTopology = pg_query($dbConn, $sqlTopology);
					if ($queryTopology!==false){
						LogHelper::writeOnLog("Extensao postgis_topology criada, continue.");
						pg_close($dbConn);
						return true;					
					}else{
						LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Extensao postgis_topology falhou - Linha: " . __LINE__);
						$this->deleteDatabase($prjName);
						pg_close($dbConn);
						return false;
					}
				}else{
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Extensao postgis falhou - Linha: " . __LINE__);
					$this->deleteDatabase($prjName);
					pg_close($dbConn);
					return false;
				}
			}else{
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Criar database ".$prjName." falhou - Linha: " . __LINE__);
				//$this->deleteDatabase($prjName);
				pg_close($dbConn);
				return false;
			}
		}else{
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . ":" . __LINE__." FALHOU ao conectar ao banco de dados: ".print_r($config, true));
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
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
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
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
			$this->showMessage ( 'Não foi possível ativar o projeto', 'admin-error' );
			$response->setContent ( \Zend\Json\Json::encode ( array (
					'status' => false,
					'isLogged' => true 
			) ) );
			return $response;
		}
	}
	public function publishAction() {
		try {
			$response = $this->getResponse ();
			if ($this->verifyUserSession ()) {
				$acl = $this->getServiceLocator ()->get ( 'Admin\Permissions\Acl' );
				if ($acl->isAllowed ( $this->session->user->rol->name, "Administração", "Administrar usuários e permissões" )) {
					$formData = $this->getFormData ();
					$date = $formData ['date'];
					$prjId = $formData ['id'];
					
					$serviceLocator = $this->getServiceLocator ();
					$projectService = $serviceLocator->get ( 'Storage\Service\ProjectService' );
					$accessService = $serviceLocator->get ( 'Storage\Service\AccessService' );
					$shapefileService = $serviceLocator->get ( 'Storage\Service\ShapefileService' );
					$datasourceService = $serviceLocator->get ( 'Storage\Service\DataSourceService' );
					
					$prj = $projectService->getById ( $prjId);
					$shapes = $shapefileService->listByProjectId ( $prjId);
					if($shapes){
						$datasource = $datasourceService->getByDbName($prj->projectName);
						$tableName = $shapefileService->getLayerTableName($prjId);
						LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . ":". __LINE__." Layer table name: ".print_r($tableName, true));
						$template = $this->getDbfTemplate();
						if ($prj && $projectService->publish($prj,$date,$datasource,$tableName, $serviceLocator, $template)) {
							$response->setContent ( \Zend\Json\Json::encode ( array (
									'status' => true,
									'isLogged' => true,
									'msg' => 'Publicação realizada com sucesso' 
							) ) );
							return $response;
						} else {
							$response->setContent ( \Zend\Json\Json::encode ( array (
									'status' => false,
									'isLogged' => true,
									'msg' => 'Não foi possível realizar a publicação' 
							) ) );
							return $response;
						}
					}
					else{
						$response->setContent ( \Zend\Json\Json::encode ( array (
								'status' => false,
								'isLogged' => true,
								'msg' => 'Não existem layers para esse projeto'
						) ) );
						return $response;
					}
				}
				$this->showMessage ( 'Você não possui permissões para realizar essa operação.', 'home-error' );
				$response->setContent ( \Zend\Json\Json::encode ( array (
						'status' => false,
						'isLogged' => true 
				) ) );
				return $response;
			}
			$this->showMessage ( 'Você precisa fazer o login para realizar essa operação', 'home-error' );
			$response->setContent ( \Zend\Json\Json::encode ( array (
					'status' => false,
					'isLogged' => false 
			) ) );
			return $response;
		} catch ( \Exception $e ) {
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
			$this->showMessage ( '', 'admin-error' );
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
			}else{
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Variável logo vazia. - Linha: " . __LINE__);		
			}
		}catch (\Exception $e){
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
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
				}else{
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Erro no update de logo. - Linha: " . __LINE__);		
				}
			}else{
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Erro ao renomear diretório. - Linha: " . __LINE__);		
				}
		}else{
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Variável prj vazia. - Linha: " . __LINE__);		
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
			}else{
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Variável projeto vazia. - Linha: " . __LINE__);		
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
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
			$response->setContent(\Zend\Json\Json::encode(array('status' => false, 'isLogged' => true, 'permitted' => true, 'msg' => 'Falhou ao recuperar a foto.')));
			return $response;
		}
	}
		
	private function getImage($path=NULL){
        try {
        	if(!$path || !is_file($path)) $path=PUBLIC_PATH."/img/100x100.jpg";
        	return file_get_contents($path);
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
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
			}else{
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Erro ao listar projetos. - Linha: " . __LINE__);
			}
			return true;
		} catch (\Exception $e) {
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
			return false;
		}
	}
	
	private function verifyDuplicateSld($name, $ignore=NULL){
		try {
			$serviceLocator = $this->getServiceLocator();
			$sldService = $serviceLocator->get ('Storage\Service\SldService');
			$slds = $sldService->listAll();
			if($slds){
				foreach ($slds as $sld){
					if($ignore && $ignore == $sld->sldId)continue;
					if (strtolower($sld->sldName) == strtolower($name))
						return false;
				}
			}
			return true;
		} catch (\Exception $e) {
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
			return false;
		}
	}
}
