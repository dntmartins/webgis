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
				
				$auth_user = $this->session->user;
				$acl = $this->getServiceLocator ()->get ( 'Admin\Permissions\Acl' );
				$tableName = "table_" . $auth_user->useId;
				$shapes = null;
				$commits = null;
				if($current_prj){
					$commits = null;
					$dir = $this->getParentDir(__DIR__, 5);
					$dir = $dir . "/geogig-repositories/" . $this->session->current_prj->prjId . "/" .$this->session->user->useId;
					if(chdir($dir)){
						$commands = array(
								"sudo geogig log",
						);
						foreach($commands as $command){
							exec(escapeshellcmd($command), $output, $return_var);
							if($return_var !== 0){
								//$this->removeDir($dir);
								return $this->showMessage('Ocorreu um erro ao realizar commit: ' . end($output), 'workspace-error', '/workspace');
							}
						}
						$commits = $output;
					}
				}
				return array (
					'commits' => $commits,
					'user' => $auth_user,
					'user_session' => $this->session,
					'prjs' => $projects,
					'current_prj' => $current_prj,
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
				return false;
			}
		} catch (\Exception $e) {
			return false;
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
						if($return_var !== 0){
							//$this->removeDir($dir);
							shell_exec(escapeshellcmd("sudo geogig reset"));
							return $this->showMessage('Ocorreu um erro ao realizar commit: ' . end($output), 'workspace-error', '/workspace');
						}
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
	
	public function pushAction(){
		try {
			if ($this->verifyUserSession ()) {
				$formData = $this->getFormData ();
				$dir = $this->getParentDir(__DIR__, 5);
				$dir = $dir . "/geogig-repositories/" . $this->session->current_prj->prjId . "/" .$this->session->user->useId;
				if(chdir($dir)){
					$commands = array(
							"sudo geogig push origin",
					);
					foreach($commands as $command){
						exec(escapeshellcmd($command), $output, $return_var);
						if($return_var !== 0){
							//$this->removeDir($dir);
							return $this->showMessage('Ocorreu um erro ao realizar push para servidor remoto', 'workspace-error', '/workspace');
						}
					}
				}else{
					return $this->showMessage('Ocorreu um erro ao realizar push para servidor remoto', 'workspace-error', '/workspace');
				}
				return $this->showMessage('Push realizado com sucesso', 'workspace-success', '/workspace');
			}else{
				return $this->showMessage('Sua sessão expirou, favor relogar', 'workspace-error', '/workspace');
			}
		} catch (\Exception $e) {
			return $this->showMessage('Ocorreu um erro ao realizar push', 'workspace-error', '/workspace');
		}
	}
	
	public function pullAction(){
		try {
			if ($this->verifyUserSession ()) {
				$config = $this->getConfiguration();
				$dir = $this->getParentDir(__DIR__, 5);
				$dir = $dir . "/geogig-repositories/" . $this->session->current_prj->prjId . "/" . $this->session->user->useId;
				$database = strtolower($this->session->current_prj->projectName);
				$tableName = "table_" . $this->session->user->useId;
				if(chdir($dir)){
					$commands = array(
							"sudo geogig pull origin master",
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
							return $this->showMessage('Ocorreu um erro ao realizar pull para o repositorio remoto: ' . end($output), 'workspace-error', '/workspace');
						}
					}
				}else{
					return $this->showMessage('Ocorreu um erro ao realizar pull para o repositorio remoto', 'workspace-error', '/workspace');
				}
				return $this->showMessage('Pull realizado com sucesso', 'workspace-success', '/workspace');
			}else{
				return $this->showMessage('Sua sessão expirou, favor relogar', 'workspace-error', '/workspace');
			}
		} catch (\Exception $e) {
			return $this->showMessage('Ocorreu um erro ao realizar pull para o repositorio remoto', 'workspace-error', '/workspace');
		}
	}
}