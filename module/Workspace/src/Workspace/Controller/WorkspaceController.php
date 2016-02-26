<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/Workspace for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Workspace\Controller;

use Zend\Session\Container;
use Storage\Entity\Shapefile;
use Storage\Entity\Layer;
use Main\Controller\MainController;

class WorkspaceController extends MainController {
	
	public function __construct() {
		parent::__construct();
		$this->session = new Container ( 'App_Auth' );
	}

	public function indexAction() {
		try {
			$request = $this->getRequest ();
			if ($this->verifyUserSession ()) {
				$basePath = $request->getBasePath();
				$serviceLocator = $this->getServiceLocator ();
				$accessService = $serviceLocator->get ( 'Storage\Service\AccessService' );
				$projectService = $serviceLocator->get ( 'Storage\Service\ProjectService' );
				$shapefileService = $serviceLocator->get ( 'Storage\Service\ShapefileService' );
				$layerService = $serviceLocator->get ( 'Storage\Service\LayerService' );
				$sldService = $serviceLocator->get ( 'Storage\Service\SldService' );
				$layerService = $serviceLocator->get ( 'Storage\Service\LayerService' );
				
				$current_prj = $this->session->current_prj;
				
				$formData = $this->getFormData();
				$projects = $accessService->getPrjByUser ($this->session->user);
				if (isset ( $formData ['current_prj'] ) && $formData ['current_prj'] != "" && is_numeric ( $formData ['current_prj'] )) {
					$prjId = $formData ['current_prj'];
					$prj = null;
					if ( ! empty ( $projects )) {
						foreach ( $projects as $prj ) {
							if ($prj->prjId == $prjId) {
								$current_prj = $prj;
								$this->session->current_prj = $current_prj;
								break;
							}
						}
					}
				}

				if (! isset ( $current_prj )) {
					if(count($projects) > 0)
						$current_prj = $projects [0]; // não encontrou um projeto associado ao usuário, usar o primeiro projeto da lista
					$this->session->current_prj = $current_prj;
				}
				
				if (isset ( $formData ['current_sld'] ) && $formData ['current_sld'] != "" && is_numeric ( $formData ['current_sld'] )) {
					$sldId = $formData ['current_sld'];
					$sld = $sldService->getById($sldId);
					$current_sld = $sld;
					$this->session->current_sld = $sld;
				}else{
					if (isset($current_prj)){
						$sld = $layerService->getSldByPrj($current_prj->prjId);
						if ($sld != null){
							$this->session->current_sld = $sld;
						}else{
							$this->session->current_sld = null;
						}
					}else{
						$this->session->current_sld = null;
					}
				}
				$current_sld = $this->session->current_sld;
				$auth_user = $this->session->user;
				$acl = $this->getServiceLocator ()->get ( 'Admin\Permissions\Acl' );
				$uploadShape = $acl->isAllowed ( $auth_user->rol->name, "Área de trabalho", "Upload de shapefile" );
				$uploadSld = $acl->isAllowed ( $auth_user->rol->name, "Área de trabalho", "Upload de sld" );
				
				$shapes = null;
				if($current_prj){
					$shapes = $shapefileService->listByProjectId ( $current_prj->prjId );
				}
				$slds = $sldService->listAll();
				return array (
					'shapes' => $shapes,
					'user' => $auth_user,
					'user_session' => $this->session,
					'prjs' => $projects,
					'current_prj' => $current_prj,
					'current_sld' => $current_sld,
					'slds' => $slds,
					'uploadShape' => $uploadShape,
					'uploadSld' => $uploadSld
				);
			} else {
				return $this->showMessage('Sua sessão expirou, favor relogar', 'home-error', '/');
			}
		} catch ( \Exception $e ) {
			return $this->showMessage('Não foi possível abrir o workspace', 'home-error', '/');
		}
	}
	
	public function getDbfTemplate(){
		$templateContent = file_get_contents (dirname ( __DIR__ )."/dbfTemplate.json" );
		$template = json_decode ($templateContent, true);
		return $template;
	}
	
	public function getDbfJSONAction(){
		$response = $this->getResponse();
		$response->setContent(\Zend\Json\Json::encode($this->getDbfTemplate()));
		return $response;
	}
	
	private function verifySldDuplicateName($name, $ignore=NULL){
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
			return false;
		}
	}
	
	public function uploadShapeFileAction() {
		$response = $this->getResponse();
		if (!$this->verifyUserSession ()) {
			$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Sua sessão expirou, favor relogar", 'isLogged' => false)));
			return $response;
		}
		if(!$this->session->current_prj){
			$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Selecione um projeto", 'isLogged' => true)));
			return $response;
		}
		if(!$this->session->current_prj->active){
			$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Esse projeto está desativado", 'isLogged' => true)));
			return $response;
		}
		try{
			if(isset($_FILES['shapeUpload']))
			{
				$shapeFile = $_FILES['shapeUpload']; //Arquivo
				$this->session->shapename = $_FILES['shapeUpload']['name'];
				$shapeFileSize = $shapeFile["size"]; //Pegando tamanho do arquivo
				$ext = strtolower(substr($shapeFile['name'],-4)); //Pegando extensão do arquivo
				if($shapeFileSize < 51200000){ // 50 mb é o limite
					if(!($ext == ".zip")){
						$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Extensão inválida", 'isLogged' => true)));
						return $response;
					}
				}else{
					$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Tamanho do Shapefile excede o limite", 'isLogged' => true)));
					return $response;
				}
				date_default_timezone_set("Brazil/East"); //Definindo timezone padrão
				$this->session->now = new \DateTime("now");
				$new_name = $this->session->now->format("Y-m-d.H-i-s") . $ext; //Definindo um novo nome para o arquivo
				$this->session->dir = dirname ( __DIR__ ) . '/file-uploads/shape-files/' . $this->session->current_prj->prjId . "/"; //Diretório para uploads
	
				$this->session->dir .= $this->session->now->format("Y-m-d.H-i-s");
				mkdir ( $this->session->dir );
				chmod ( $this->session->dir , 0777 );
	
				if(!move_uploaded_file($shapeFile['tmp_name'], $this->session->dir . "/".$_FILES['shapeUpload']['name'])){//Fazer upload do arquivo
					$this->delTree($this->session->dir );
					$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Falhou ao realizar upload do Shapefile", 'isLogged' => true)));
					return $response;
				}else{
					$response->setContent(\Zend\Json\Json::encode(array('status' => true,'msg' => "Shapefile enviado com sucesso!", 'isLogged' => true)));
					return $response;
				}
			}
		}catch(\Exception $e){
			$this->delTree($this->session->dir);
			$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Ocorreu um erro ao salvar o Shapefile", 'isLogged' => true)));
			return $response;
		}
	}
	
	public function extractShapeFileAction() {
		$response = $this->getResponse();
		try{
			$zip = new \ZipArchive();
			//Validação dos arquivos dentro do Zip
			$shapeFileExtensions = array("shp", "prj", "shx", "dbf");
			$this->session->archivesNames = array();
			$prjName = null;
			$shpName = null;
			$dbfName = null;
			if( $zip->open( $this->session->dir . "/" . $this->session->shapename )  === true){
				$countExt = 0;
				for ($i = 0; $i < $zip->numFiles; $i++) {
					if(preg_match("/.*[\/]$/", $zip->getNameIndex($i))){ //Verifica se o zip contém uma pasta
						$this->delTree($this->session->dir);
						$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Arquivo zip não deve conter pastas", 'isLogged' => true)));
						$zip->close();
						return $response;
					}
					foreach ($shapeFileExtensions as $shpExt) {
						$zipFileExt = pathinfo($zip->getNameIndex($i), PATHINFO_EXTENSION);
						if($shpExt == $zipFileExt){
							$countExt++;
							array_push($this->session->archivesNames, $zip->getNameIndex($i));
							if($shpExt == "prj"){
								$this->session->prjName = $zip->getNameIndex($i);
							}else if($shpExt == "shp"){
								$this->session->shpName = $zip->getNameIndex($i);
							}else if($shpExt == "dbf"){
								$this->session->dbfName = $zip->getNameIndex($i);
							}
						}
					}
				}
				if($countExt == 4){ //Possui ao menos os 4 arquivos?
					if($zip->extractTo($this->session->dir . "/", $this->session->archivesNames)){//Extrai apenas arquivos shp, prj, shx, dbf. Dessa maneira pastas dentro do arquivo não são extraidas.
						$response->setContent(\Zend\Json\Json::encode(array('status' => true,'msg' => "Extração concluída com sucesso", 'isLogged' => true)));
					}else{
						$this->delTree($this->session->dir);
						$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Ocorreu um erro ao extrair o Shapefile", 'isLogged' => true)));
					}
				}else{
					$this->delTree($this->session->dir);
					$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Arquivo zip deve conter 4 arquivos nas extensões .shp .dbf .prj .shx", 'isLogged' => true)));
				}
				$zip->close();
				return $response;
			}
		}catch(\Exception $e){
			$zip->close();
			$this->delTree($this->session->dir);
			$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Ocorreu um erro ao salvar o Shapefile", 'isLogged' => true)));
			return $response;
		}
	}
	
	public function validateShapeDbfAction() {
		$response = $this->getResponse();
		try{
			$response->setContent(\Zend\Json\Json::encode(array('status' => true,'msg' => "Arquivo válido.", 'isLogged' => true)));
			if($this->session->dbfName){
				$path = $this->session->dir."/".$this->session->dbfName;
				chmod (dirname($path) , 0777 );
				chmod ($path , 0777 );
				foreach (glob($path) as $file) {
					$dbf = dbase_open($file,0);
					if ($dbf){
						$template = $this->getDbfTemplate();
						$dbfHeaders = dbase_get_header_info($dbf);
						if(count($dbfHeaders) >= count($template)){
							foreach ($template as $count => $column){
								$name = $column["name"];
								$type = $column["type"];
								$nameExists = array_search($name, array_column($dbfHeaders, 'name'));
								$typeExists = array_search($type, array_column($dbfHeaders, 'type'));
								if(!is_numeric($nameExists)){
									$this->delTree($this->session->dir);
									$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Arquivo .dbf inválido. Coluna ".$name." não foi encontrada", 'isLogged' => true)));
								}
								if(!is_numeric($typeExists)){
									$this->delTree($this->session->dir);
									$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Arquivo .dbf inválido. O tipo da coluna ".$name."(".$type.") está incorreto", 'isLogged' => true)));
								}
							}
						}else{
							$this->delTree($this->session->dir);
							$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Arquivo .dbf com número de colunas inválido.", 'isLogged' => true)));
						}
					}else{
						$this->delTree($this->session->dir);
						$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Não foi possível abrir o arquivo .dbf", 'isLogged' => true)));
					}
				}
			}else{
				$this->delTree($this->session->dir);
				$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Não há nome do arquivo .dbf.", 'isLogged' => true)));
			}
			return $response;
		}catch(\Exception $e){
			$this->delTree($this->session->dir);
			$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Ocorreu um erro ao salvar o Shapefile", 'isLogged' => true)));
			return $response;
		}
	}
	
	public function importShapeToDBAction() {
		$response = $this->getResponse();
		try{
			$shapeFileService = $this->serviceLocator->get ( 'Storage\Service\ShapefileService' );
			$dataSourceService = $this->serviceLocator->get ( 'Storage\Service\DataSourceService' );
			$layerService = $this->serviceLocator->get ( 'Storage\Service\LayerService' );
			$shapeReturn = $shapeFileService->getByPrjID($this->session->current_prj->prjId);
			$shapeFileService->begin();
			$shpEntity = new Shapefile();
			$shpEntity->fileName = pathinfo($this->session->shpName, PATHINFO_FILENAME);
			$shpEntity->fileExtension = "shp";
			$shpEntity->diskLocation = $this->session->dir;
			$shpEntity->uploadDate = $this->session->now;
			$shpEntity->info = "";
			$shpEntity->prj = $this->session->current_prj;
			$result = $shapeFileService->add($shpEntity);
			if($result){
				//Realiza a criação do shape file em tabelas para o o PostGIS
				$shapenameSession = pathinfo($this->session->shpName, PATHINFO_FILENAME);
				if($shapeReturn){
					$shapenameNoExt = pathinfo($shapeReturn->fileName, PATHINFO_FILENAME);
					$command = escapeshellcmd(dirname ( __DIR__ ) .'/shp2pgsql.py ' . $this->session->dir . '/'. $this->session->prjName . ' ' . $this->session->dir . '/'. $shapenameSession . ' ' .$this->session->dir . ' ' . $shapenameNoExt . ' True');
				}else{
					$command = escapeshellcmd(dirname ( __DIR__ ) .'/shp2pgsql.py ' . $this->session->dir . '/'. $this->session->prjName . ' ' . $this->session->dir . '/'. $shapenameSession . ' ' . $this->session->dir . ' ' . $shapenameSession . ' False');
				}
				$projection = shell_exec($command);
				$layer = $layerService->getByPrjID($this->session->current_prj->prjId);
				if($layer && !($layer->projection == $projection)){
					$shapeFileService->rollback();
					$this->delTree($this->session->dir);
					$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Projeção do Shapefile não é a mesma do atual", 'isLogged' => true)));
					return $response;
				}
				chmod ( $this->session->dir . "/file.sql", 0777 );
				$dataSource = $dataSourceService->getByDbName($this->session->current_prj->projectName);
				$command = escapeshellcmd('psql -d '. strtolower($dataSource->dbName) . ' -U '. $dataSource->login .' -h '.$dataSource->host.' --single-transaction -f ' . $this->session->dir . '/file.sql');
				$output = shell_exec($command);
				//Deleta arquivos
				unlink($this->session->dir . "/file.sql");
				foreach ($this->session->archivesNames as $archive) {
					unlink ($this->session->dir. "/" . $archive);
				}
				//Fim deleta arquivos
				$prj = $this->session->current_prj->projectName;
				$layerEntity = new Layer();
				$layerEntity->official = '0';
				$layerEntity->prj = $this->session->current_prj;
				$layerEntity->datasource = $dataSource;
				$layerEntity->projection = $projection;
				$resultLayer = $layerService->addLayer($layerEntity);
				if($shapeReturn){
					$publishLayer = $this->createLayer($shapeReturn->fileName);
				}else{
					$publishLayer = $this->createLayer($this->session->shpName);
				}
				if ($resultLayer && $publishLayer){
					$shapeFileService->commit();
					$response->setContent(\Zend\Json\Json::encode(array('status' => true,'msg' => "Shapefile enviado com sucesso!", 'shapeId'=> $result, 'fileName'=> $shpEntity->fileName, "date"=>$this->session->now->format("d-m-Y h:i:s"), "info"=>$shpEntity->info, 'isLogged' => true)));
				}
				else{
					$shapeFileService->rollback();
					$this->delTree($this->session->dir);
					$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Não foi possível criar o layer", 'shapeId'=> $result, 'fileName'=> $shpEntity->fileName, "date"=>$this->session->now->format("d-m-Y h:i:s"), "info"=>$shpEntity->info, 'isLogged' => true)));
				}
			}
			else{
				$shapeFileService->rollback();
				$this->delTree($this->session->dir);
				$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Não foi possível enviar o Shapefile", 'isLogged' => true)));
			}
			return $response;
		}catch(\Exception $e){
			$shapeFileService->rollback();
			$this->delTree($this->session->dir);
			$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Ocorreu um erro ao salvar o Shapefile", 'isLogged' => true)));
			return $response;
		}
	}
	
	public function uploadSldAction() {
		$response = $this->getResponse();
		if(!$this->session->current_prj){
			$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Selecione um projeto")));
			return $response;
		}
		if(!$this->session->current_prj->active){
			$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Esse projeto está desativado")));
			return $response;
		}
		if(isset($_FILES['sldUpload']))
		{
			$sldFile = $_FILES['sldUpload']; //Arquivo			
			$serviceLocator = $this->getServiceLocator ();
			$sldService = $serviceLocator->get ( 'Storage\Service\SldService' );
			$geoService = $serviceLocator->get ( 'Storage\Service\GeoServerRESTService' );
			$geoServerService = $serviceLocator->get ( 'Storage\Service\GeoServerService' );

			if(!$this->verifySldDuplicateName(strtolower($sldFile['name']))){
				$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Já existe um estilo com esse nome")));
				return $response;
			}
			
			$sldService->begin();
							
			$sld = $sldService->saveSld($sldFile, $serviceLocator, '0');
			$geoServer = $geoServerService->getByPrj($this->session->current_prj->prjId);
			$geoServerLogin = $geoServer->login.':'.$geoServer->pass;
				
			$responseGeoServerSld = $geoService->createStyle($geoServerLogin, $sld, $geoServer->host);
				
			if ($sld && $responseGeoServerSld){
				$sldService->commit();
				$response->setContent(\Zend\Json\Json::encode(array('status' => true,'msg' => "Arquivo de estilo salvo com sucesso!", "sldId" => $sld->sldId, "sldName" => $sld->sldName)));
				return $response;
			}else{
				$sldService->rollback();
				$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Ocorreu um erro ao salvar o arquivo de estilo")));
				return $response;
			}
		}else{
			$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Ocorreu um erro ao salvar o arquivo de estilo")));
			return $response;
		}
	}
	
	public function setDefaultSldAction() {
		$response = $this->getResponse();
		$serviceLocator = $this->getServiceLocator ();
		$geoRestService = $serviceLocator->get ( 'Storage\Service\GeoServerRESTService' );
		$shapeFileService = $serviceLocator->get ( 'Storage\Service\ShapefileService' );
		$sldService = $serviceLocator->get ( 'Storage\Service\SldService' );
		$projectService = $serviceLocator->get ( 'Storage\Service\ProjectService' );
		$layerService = $serviceLocator->get ( 'Storage\Service\LayerService' );
		$geoServerService = $serviceLocator->get ( 'Storage\Service\GeoServerService' );
		
		$formData = $this->getFormData();	
		$sldId = $formData['sldId'];
		$prjId = $formData['prjId'];
		$sld = $sldService->getById($sldId);
		$prj = $projectService->getById($prjId);
		$tableName = $shapeFileService->getLayerTableName($prj->prjId);
		$shapeTable = strtolower(pathinfo($tableName, PATHINFO_FILENAME));
		$geoserver = $geoServerService->getByPrj($this->session->current_prj->prjId);
		$geoServerLogin = $geoserver->login.':'.$geoserver->pass;
		if(!$prj){
			$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Selecione um projeto")));
			return $response;
		}
		
		if ($tableName == null){
			$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Nenhum shapefile foi enviado")));
			return $response;
		}
					
		if ($sld != null){
			$responseGeoServer = $geoRestService->setDefaultStyle($geoServerLogin, $prj->projectName, $shapeTable, pathinfo(strtolower($sld->sldName), PATHINFO_FILENAME), $geoserver->host);
			if ($responseGeoServer){
				$layerUpdate = $layerService->setSld($prjId, $sldId);
			}else{
				$layerUpdate = false;	
			}
		}else{
			$layerUpdate = false;
		}
		if ($layerUpdate){
			$response->setContent(\Zend\Json\Json::encode(array('status' => true,'msg' => "Estilo padrão definido com sucesso!")));
			return $response;
		}else{
			$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Não foi possível definir o estilo padrão")));
			return $response;
		}
	}
	
	private function createLayer($shpName){		
		$current_prj = $this->session->current_prj->projectName;
		$serviceLocator = $this->getServiceLocator ();
		$geoRestService = $serviceLocator->get ( 'Storage\Service\GeoServerRESTService' );
		$geoServerService = $serviceLocator->get ( 'Storage\Service\GeoServerService' );
		$shape = strtolower(pathinfo($shpName, PATHINFO_FILENAME));
		$geoserver = $geoServerService->getByPrj($this->session->current_prj->prjId);
		$geoServerLogin = $geoserver->login.':'.$geoserver->pass;
		$responseGeoServer =$geoRestService->createLayer($geoServerLogin, $current_prj, $shape, $geoserver->host);
		if ($responseGeoServer){
			return true;
		}else{
			return false;
		}
	}
}	
