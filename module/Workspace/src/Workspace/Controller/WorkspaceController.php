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