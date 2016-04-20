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
use Storage\Entity\Commit;
use Main\Controller\MainController;
use Main\Helper\LogHelper;

class WorkspaceController extends MainController {
	
	public function __construct() {
		parent::__construct();
		$this->session = new Container ( 'App_Auth' );
	}

	public function indexAction() {
		try {
			$request = $this->getRequest ();
			if ($this->verifyUserSession ()) {
				$serviceLocator = $this->getServiceLocator ();
				$accessService = $serviceLocator->get ( 'Storage\Service\AccessService' );
				$projectService = $serviceLocator->get ( 'Storage\Service\ProjectService' );
				$shapefileService = $serviceLocator->get ( 'Storage\Service\ShapefileService' );
				$layerService = $serviceLocator->get ( 'Storage\Service\LayerService' );
				$sldService = $serviceLocator->get ( 'Storage\Service\SldService' );
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
				$tableName = "table_" . $auth_user->useId;
				$shapes = null;
				$commits = null;
				if($current_prj){
					$commitService = $serviceLocator->get ('Storage\Service\CommitService');
					$commits = $commitService->getByUserAndPrj($this->session->user,$current_prj);
					//$shapes = $shapefileService->listByProjectId ( $current_prj->prjId );
				}
				$slds = $sldService->listAll();
				$fileSizeiBytes = $this->returnBytes(ini_get('post_max_size'));
				$fileSizeString = ini_get('post_max_size');
				return array (
					'commits' => $commits,
					'shapes' => $shapes,
					'user' => $auth_user,
					'user_session' => $this->session,
					'prjs' => $projects,
					'current_prj' => $current_prj,
					'current_sld' => $current_sld,
					'slds' => $slds,
					'uploadShape' => $uploadShape,
					'uploadSld' => $uploadSld,
					'fileSizeInBytes' => $fileSizeiBytes,
					'fileSizeString' => $fileSizeString,
					'tableName' => $tableName
				);
			} else {
				return $this->showMessage('Sua sessão expirou, favor relogar', 'home-error', '/');
			}
		} catch ( \Exception $e ) {
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: " . $e->getMessage() . " - Linha: " . $e->getLine());
			return $this->showMessage('Não foi possível abrir o workspace', 'home-error', '/');
		}
	}
	
	public function getDbfJSONAction(){
		$response = $this->getResponse();
		try {
			$template = $this->getDbfTemplate();
			if($template){
				LogHelper::writeOnLog("Template criado com sucesso");
				$response->setContent(\Zend\Json\Json::encode($template));
			}else{
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Falhou ao ler template do arquivo dbf" . " - Linha: " . __LINE__);
			}
			return $response;
		} catch (\Exception $e) {
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: " . $e->getMessage() . " - Linha: " . $e->getLine());
			return false;
		}
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
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: " . $e->getMessage() . " - Linha: " . $e->getLine());
			return false;
		}
	}
	
	private function returnBytes($val) {
		$val = trim($val);
		$last = strtolower($val[strlen($val)-1]);
		switch($last) {
			// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
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
				$sizeInBytes = $this->returnBytes(ini_get('post_max_size'));
				if($shapeFileSize < $sizeInBytes){ // 100 mb é o limite
					if(!($ext == ".zip")){
						$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Extensão inválida", 'isLogged' => true)));
						return $response;
					}
				}else{
					$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Falhou ao carregar seu arquivo pois o tamanho máximo permitido foi excedido. Máximo permitido de " . ini_get('post_max_size'), 'isLogged' => true)));
					return $response;
				}
				date_default_timezone_set("Brazil/East"); //Definindo timezone padrão
				$this->session->now = new \DateTime("now");
				$new_name = $this->session->now->format("Y-m-d.H-i-s") . $ext; //Definindo um novo nome para o arquivo
				$this->session->dir = dirname ( __DIR__ ) . '/file-uploads/shape-files/' . $this->session->current_prj->prjId . "/"; //Diretório para uploads
	
				$this->session->dir .= $this->session->now->format("Y-m-d.H-i-s");
				if(mkdir ( $this->session->dir, 0777, true )===false){
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: FALHOU criar diretorio ".$this->session->dir." - Linha: " . __LINE__);
					$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Falhou ao realizar upload do Shapefile", 'isLogged' => true)));
					return $response;
				}
				
				/* if(chmod ( $this->session->dir , 0777 )===false) {
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: FALHOU aplicar permissoes ao dir ".$this->session->dir." - Linha: " . __LINE__);
					$this->delTree($this->session->dir );
					$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Falhou ao realizar upload do Shapefile", 'isLogged' => true)));
					return $response;
				} */
	
				if(move_uploaded_file($shapeFile['tmp_name'], $this->session->dir . "/".$_FILES['shapeUpload']['name'])===false) {//Fazer upload do arquivo
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: FALHOU ao mover o arquivo ".$shapeFile['tmp_name']." - Linha: " . __LINE__);
					if($this->delTree($this->session->dir )===false){
						LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: FALHOU ao remover diretorios criados anteriormente - Linha: " . __LINE__);
					}
					$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Falhou ao realizar upload do Shapefile", 'isLogged' => true)));
					return $response;
				}else{
					$response->setContent(\Zend\Json\Json::encode(array('status' => true,'msg' => "Shapefile enviado com sucesso!", 'isLogged' => true)));
					return $response;
				}
			}else{
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: shapeUpload is not setted - Linha: " . __LINE__);
				$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Falhou ao realizar upload do Shapefile", 'isLogged' => true)));
				return $response;
			}
		}catch(\Exception $e){
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: " . $e->getMessage() . " - Linha: " . $e->getLine());
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
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: " . $e->getMessage() . " - Linha: " . $e->getLine());
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
				if(chmod (dirname($path) , 0777 ) === false) {
					$shapeFileService->rollback();
					$this->delTree($this->session->dir );
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: FALHOU ao aplicar permissoes ao dir ".$path."/file.sql - Linha: " . __LINE__);
					$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Ocorreu um erro ao importar shapefile para o PostGIS", 'isLogged' => true)));
					return $response;
				}
				if(chmod ($path , 0777 ) === false) {
					$shapeFileService->rollback();
					$this->delTree($this->session->dir );
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: FALHOU ao aplicar permissoes ao dir ".$path."/file.sql - Linha: " . __LINE__);
					$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Ocorreu um erro ao importar shapefile para o PostGIS", 'isLogged' => true)));
					return $response;
				}
				foreach (glob($path) as $file) {
					$dbf = dbase_open($file,0);
					if ($dbf){
						$template = $this->getDbfTemplate();
						if(!$template){
							$this->delTree($this->session->dir);
							$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Ocorreu um erro ao salvar o Shapefile", 'isLogged' => true)));
							return $response;
						}
						$dbfHeaders = dbase_get_header_info($dbf);
						LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . ":". __LINE__. " Mensagem: DBF data type ".print_r($dbfHeaders,true)." ");
						if(count($dbfHeaders) >= count($template)){
							foreach ($template as $count => $column){
								$name = $column["name"];
								$type = $column["type"];
								$nameExists = array_search($name, array_column($dbfHeaders, 'name'));
								$typeExists = array_search($type, array_column($dbfHeaders, 'type'));
								if(!is_numeric($nameExists)){
									$this->delTree($this->session->dir);
									$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Arquivo .dbf inválido. Coluna ".$name." não foi encontrada", 'isLogged' => true)));
									return $response;
								}
								if(!is_numeric($typeExists)){
									$this->delTree($this->session->dir);
									$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Arquivo .dbf inválido. O tipo da coluna ".$name."(".$type.") está incorreto", 'isLogged' => true)));
									return $response;
								}
							}
						}else{
							$this->delTree($this->session->dir);
							$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Arquivo .dbf com número de colunas inválido.", 'isLogged' => true)));
							return $response;
						}
					}else{
						$this->delTree($this->session->dir);
						$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Não foi possível abrir o arquivo .dbf", 'isLogged' => true)));
						return $response;
					}
				}
			}else{
				$this->delTree($this->session->dir);
				$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Não há nome do arquivo .dbf.", 'isLogged' => true)));
				return $response;
			}
			return $response;
		}catch(\Exception $e){
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: " . $e->getMessage() . " - Linha: " . $e->getLine());
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
			$command = null;
			if($result){
				//Realiza a criação do shape file em tabelas para o o PostGIS
				$shapenameSession = pathinfo($this->session->shpName, PATHINFO_FILENAME);
				if($shapeReturn){// não é a primeira importação para o layer padrao do projeto
					$shapenameNoExt = pathinfo($shapeReturn->fileName, PATHINFO_FILENAME);
					$command = escapeshellcmd(dirname ( __DIR__ ) .'/shp2pgsql.py ' . $this->session->dir . '/'. $this->session->prjName . ' ' . $this->session->dir . '/'. $shapenameSession . ' ' .$this->session->dir . ' ' . $shapenameNoExt . ' True');
				}else{// é a primeira importação de shape para este projeto
					$command = escapeshellcmd(dirname ( __DIR__ ) .'/shp2pgsql.py ' . $this->session->dir . '/'. $this->session->prjName . ' ' . $this->session->dir . '/'. $shapenameSession . ' ' . $this->session->dir . ' ' . $shapenameSession . ' False');
				}
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . ":".__LINE__." Projection command:".$command);
				$projection = shell_exec($command);
				$fileSqlExists = file_exists($this->session->dir . "/file.sql");
				if($projection == "false"){
					$shapeFileService->rollback();
					$this->delTree($this->session->dir);
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: FALHOU ao ler projeção do shapefile - Linha: " . __LINE__);
					$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Falhou ao ler projeção do arquivo Shapefile", 'isLogged' => true)));
					return $response;
				}
				if($fileSqlExists === true){
					if(chmod ( $this->session->dir . "/file.sql", 0777 ) === false) {
						$shapeFileService->rollback();
						$this->delTree($this->session->dir );
						LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: FALHOU ao aplicar permissoes ao dir ".$this->session->dir."/file.sql - Linha: " . __LINE__);
						$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Ocorreu um erro ao importar shapefile para o PostGIS", 'isLogged' => true)));
						return $response;
					}
				}else{
					$shapeFileService->rollback();
					$this->delTree($this->session->dir );
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Arquivo file.sql não criado - Linha: " . __LINE__);
					$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Ocorreu um erro ao importar shapefile para o PostGIS", 'isLogged' => true)));
					return $response;
				}
				$layer = $layerService->getByPrjID($this->session->current_prj->prjId);
				if($layer && !($layer->projection == $projection)){
					$shapeFileService->rollback();
					$this->delTree($this->session->dir);
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: FALHOU ao buscar layer no banco - Linha: " . __LINE__);
					$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Projeção do Shapefile não é a mesma do atual", 'isLogged' => true)));
					return $response;
				}
				
				$dataSource = $dataSourceService->getByDbName($this->session->current_prj->projectName);
				if($dataSource === null){
					$shapeFileService->rollback();
					$this->delTree($this->session->dir );
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: FALHOU ao buscar datasource no banco - Linha: " . __LINE__);
					$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Ocorreu um erro ao importar shapefile para o PostGIS", 'isLogged' => true)));
					return $response;
				}
				// para que o psql use a senha correta sem solicitá-la após o comando usamos uma variável de ambiente
				putenv("PGPASSWORD=".$dataSource->password); //TODO: Tratar retorno?
				$command = escapeshellcmd('psql -d '. strtolower($dataSource->dbName) . ' -U '. $dataSource->login .' -h '.$dataSource->host.' --single-transaction -f ' . $this->session->dir . '/file.sql');
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . ":".__LINE__." Comando PSQL:".$command);
				$output = shell_exec($command);
				if($output === null){
					$shapeFileService->rollback();
					$this->delTree($this->session->dir);
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: FALHOU ao importar arquivo file.sql - Linha: " . __LINE__);
					$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Ocorreu um erro ao importar shapefile para o PostGIS", 'isLogged' => true)));
					return $response;
				}
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . ":".__LINE__." Saida do comando PSQL:".$output);
				
				//Deleta arquivos
				if(unlink($this->session->dir . "/file.sql") === false){
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: FALHOU ao remover arquivo file.sql - Linha: " . __LINE__);
				}
				foreach ($this->session->archivesNames as $archive) {
					if(unlink ($this->session->dir. "/" . $archive) === false){
						LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: FALHOU ao remover arquivo " . $this->session->dir. "/" . $archive . " - Linha: " . __LINE__);
					}
				}
				//Fim deleta arquivos
				
				$prj = $this->session->current_prj->projectName;
				if(!$layer){ // Se não existe um layer associado a este projeto, cria um novo
					$layer = new Layer();
					$layer->official = '0';
					$layer->prj = $this->session->current_prj;
					$layer->sld = null;
					$layer->datasource = $dataSource;
					$layer->projection = $projection;
					$layer->publicacaoOficial = null;
					$resultLayer = $layerService->addLayer($layer);
				}else{
					$resultLayer = true;
				}
				if($shapeReturn){
					$publishLayer = $this->createLayer($shapeReturn->fileName);
				}else{// primeira importação de shape
					$publishLayer = $this->createLayer($this->session->shpName);
					if($publishLayer===true) {// registrou o layer no geoserver? então escreve permissoes no arquivo do geoserver
						// escreve permissoes direto no arquivo, pois na versão 2.5 utilizada nao existe servico REST para isso.
						$accessControl = $this->setAccessControlToLayer($this->session->shpName, $prj);
						$accessControl = true;
						if(!$accessControl)
							$publishLayer = false;
					}
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
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: " . $e->getMessage() . " - Linha: " . $e->getLine());
			$shapeFileService->rollback();
			if(is_dir($this->session->dir))
				$this->delTree($this->session->dir);
			$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Ocorreu um erro ao salvar o Shapefile", 'isLogged' => true)));
			return $response;
		}
	}
	
	public function uploadSldAction() {
		$response = $this->getResponse();
		try{
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
				$sldPostSize = $_FILES['sldUpload']["size"];
				$sldSizeInBytes = $this->returnBytes(ini_get('post_max_size'));
				if($sldPostSize > $sldSizeInBytes){
					$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Tamanho do sld excede: " . ini_get('post_max_size') , 'isLogged' => true)));
					return $response;
				}
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
		} catch (\Exception $e) {
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: " . $e->getMessage() . " - Linha: " . $e->getLine());
			$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Ocorreu um erro ao salvar o arquivo de estilo")));
			return $response;
		}
	}
	
	public function setDefaultSldAction() {
		$response = $this->getResponse();
		try{
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
				$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Selecione um projeto", 'warning' => true)));
				return $response;
			}
			
			if ($tableName == null){
				$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Nenhum shapefile foi enviado", 'warning' => true)));
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
		} catch (\Exception $e) {
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: " . $e->getMessage() . " - Linha: " . $e->getLine());
			$response->setContent(\Zend\Json\Json::encode(array('status' => false,'msg' => "Não foi possível definir o estilo padrão")));
			return $response;
		}
	}
	
	private function createLayer($shpName){	
		try{
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
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Não foi possível publicar layer no geoserver" . " - Linha: " . __LINE__);
				return false;
			}
		} catch (\Exception $e) {
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: " . $e->getMessage() . " - Linha: " . $e->getLine());
			return false;
		}
	}
	
	/**
	 * 
	 * Só funciona se configurar o arquivo para permitir escrita por outros usuários.
	 * sudo chmod 666 /opt/tomcat/webapps/deter/data/security/layers.properties
	 * 
	 * @param string $layerName
	 * @param string $projectName
	 * @return boolean
	 * 
	 */
	private function setAccessControlToLayer($layerName, $projectName) {
		$tomcatBaseDir="/opt/tomcat7/webapps/";
		$geoserverProjectDir=$tomcatBaseDir.$projectName."/";
		$securityDir=$geoserverProjectDir."data/security/";
		$securityLayerFile=$securityDir."layers.properties";
		if(is_file($securityLayerFile)){
			$fileSecurityContent=file_get_contents($securityLayerFile);
		}
		else{
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . ":" . __LINE__." Arquivo de de seguranca não encontrado: ".$securityLayerFile);
			return false;
		}
		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . ":" . __LINE__." Conteudo do arquivo de de seguranca: ".$fileSecurityContent);
		if(strpos($fileSecurityContent, $layerName)===false) {// não encontrou o registro no arquivo, então grava
			$data="mode=HIDE";
			$data+=$projectName.".".$layerName.".r=ROLE_AUTHENTICATED,ADMIN";
			if(file_put_contents($fileSecurityContent, $data)===false) {
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . ":" . __LINE__." FALHOU ao gravar dados no arquivo: ".$securityLayerFile);
				return false;
			}
		}else{
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . ":" . __LINE__." Já existe registro de seguranca no arquivo: ".$securityLayerFile);
		}
		return true;
	}
	
	public function commitAction(){
		try {
			if ($this->verifyUserSession ()) {
				$formData = $this->getFormData ();
				$msg = trim($formData["commitMsg"]);
				if(strlen($msg) === 0){
					return $this->showMessage('Por favor, insira uma mensagem para realizar o commit', 'workspace-error', '/workspace');
				}
				$serviceLocator = $this->getServiceLocator ();
				$commitService = $serviceLocator->get ('Storage\Service\CommitService');
				$config = $this->getConfiguration();
				$dir = $this->getParentDir(__DIR__, 5);
				$dir = $dir . "/geogig-repositories/" . $this->session->current_prj->prjId . "/" .$this->session->user->useId;
				$database = strtolower($this->session->current_prj->projectName);
				$tableName = 'table_' . $this->session->user->useId;
				if(chdir($dir)){
					
					$commands = array(
							"sudo geogig pg import --database " . $database . " --port 5432 --user " . $config["datasource"]["login"] . " --password " . $config["datasource"]["password"] . " --table " . $tableName,
							"sudo geogig add",
							'sudo geogig commit -m "' .$msg .'"',
					);
					foreach($commands as $command){
						exec(escapeshellcmd($command), $output, $return_var);
						LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$output."- Linha: " . __LINE__);
						if($return_var !== 0){
							//$this->removeDir($dir);
							return $this->showMessage('Ocorreu um erro ao realizar commit: ' . end($output), 'workspace-error', '/workspace');
						}
					}
					$commitEntity = new Commit();
					$commitEntity->hash =  substr($output["17"],1,40);
					$commitEntity->msg = $msg;
					$commitEntity->use = $this->session->user;
					$commitEntity->name = $this->session->user->name; //mudar
					$date = new \DateTime("now", new \DateTimeZone("America/Sao_Paulo"));
					$commitEntity->date = date_format($date,"d/m/Y H:i:s");
					$commitEntity->prj = $this->session->current_prj;
					$commitOk = $commitService->addCommit($commitEntity);
					if(!$commitOk){
						shell_exec(escapeshellcmd("sudo geogig reset"));
						return $this->showMessage('Ocorreu um erro ao realizar commit', 'workspace-error', '/workspace');
					}
				}else{
					return $this->showMessage('Ocorreu um erro ao realizar commit', 'workspace-error', '/workspace');
				}
				return $this->showMessage('Commit realizado com sucesso: ' . $output["17"], 'workspace-success', '/workspace');
			}else{
				return $this->showMessage('Sua sessão expirou, favor relogar', 'workspace-error', '/workspace');
			}
		} catch (\Exception $e) {
			return $this->showMessage('Ocorreu um erro ao realizar commit', 'workspace-error', '/workspace');
		}
	}
	
	
	public function revertCommitAction(){
		try {
			if ($this->verifyUserSession ()) {
				$formData = $this->getFormData ();
				if(!$formData["commitId"]){
					return $this->showMessage('Ocorreu um erro ao realizar o revert', 'workspace-error', '/workspace');
				}
				$config = $this->getConfiguration();
				$dir = $this->getParentDir(__DIR__, 5);
				$dir = $dir . "/geogig-repositories/" . $this->session->current_prj->prjId . "/" . $this->session->user->useId;
				$database = strtolower($this->session->current_prj->projectName);
				$tableName = "table_" . $this->session->user->useId;
				if(chdir($dir)){
					$msg = $formData["commitMsg"];
					$commands = array(
							"sudo geogig revert " . $formData["commitId"],
							"sudo geogig pg export --database " .
							$database . " --user " .
							$config["datasource"]["login"] .
							" --password " . 
							$config["datasource"]["password"] .
							" -o " . $tableName . 
							" " .
							$tableName,
					);
					
					foreach($commands as $command){
						exec(escapeshellcmd($command), $output, $return_var);
						if($return_var !== 0){
							shell_exec(escapeshellcmd("sudo geogig revert --abort"));
							return $this->showMessage('Ocorreu um erro ao realizar o revert: ' . end($output), 'workspace-error', '/workspace');
						}
					}
				}else{
					return $this->showMessage('Ocorreu um erro ao realizar o revert', 'workspace-error', '/workspace');
				}
				return $this->showMessage('Revert realizado com sucesso', 'workspace-success', '/workspace');
			}else{
				return $this->showMessage('Sua sessão expirou, favor relogar', 'workspace-error', '/workspace');
			}
		} catch (\Exception $e) {
			return $this->showMessage('Ocorreu um erro ao realizar o revert', 'workspace-error', '/workspace');
		}
	}
	
}