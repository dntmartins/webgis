<?php
namespace Admin\Controller;

use Storage\Service\ShapefileService;
use Zend\Session\Container;
use Storage\Entity\Access;
use Zend\I18n\Validator\Alnum;
use Zend\I18n\Validator\Alpha;
use Zend\I18n\Validator\Int;
use Zend\Validator\StringLength;
use Zend\I18n\Validator\PhoneNumber;
use Zend\I18n\Validator\Zend\I18n\Validator;
use Main\Controller\MainController;
use Main\Helper\LogHelper;

class ShapeController extends MainController {
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
				return $this->showMessage('Você não possui permissões para realizar essa operação', 'home-error', '/');
			}
			return $this->showMessage('Você precisa fazer o login para realizar essa operação', 'home-error', '/');
		} catch ( \Exception $e ) {
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
			return $this->showMessage('Não foi possível recuperar os usuários cadastrados', 'home-error', '/');
		}
	}
	public function removeZipFilesAction(){
		try {
			$response = $this->getResponse ();
			if ($this->verifyUserSession ()) {
				$acl = $this->getServiceLocator ()->get ( 'Admin\Permissions\Acl' );
				if ($acl->isAllowed ( $this->session->user->rol->name, "Administração", "Administrar usuários e permissões" )) {
					$formData = $this->getFormData ();
					$from = $formData ['from'];
					$to = $formData ['to'];
					$prjId = $formData ['prjId'];
					
					$dTFrom = new \DateTime($from);
					$dTTo = new \DateTime($to);
					if($dTTo->getTimestamp()<$dTFrom->getTimestamp()){
						$response->setContent ( \Zend\Json\Json::encode ( array (
								'status' => false,
								'isLogged' => true,
								'msg' => 'A data inicial deve ser anterior a data final'
						) ) );
						return $response;
					}else{
						LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Erro ao pegar Timestamp. - Linha: " . __LINE__);		
					}
					
					$serviceLocator = $this->getServiceLocator ();
					$shapeFileService = $serviceLocator->get ( 'Storage\Service\ShapefileService' );
						
					$result = $shapeFileService->removeZips($from, $to, $prjId);
					if ($result) {
						$dir = getcwd() . '/module/Workspace/src/Workspace/file-uploads/shape-files/' . $prjId . '/';
						$files = array_diff(scandir($dir), array('.','..'));
    					foreach ($files as $fileName) {
							$file = explode('.', $fileName);
							$dTFile = new \DateTime($file[0]);
							$dTFrom = new \DateTime($from);
							$dTTo = new \DateTime($to);
							
							$fileDate = $dTFile->format("Y-m-d");
							$fromDate = $dTFrom->format("Y-m-d");
							$toDate = $dTTo->format("Y-m-d");
							if($fileDate >= $fromDate && $fileDate <= $toDate)
								$this->delTree($dir.$fileName);
						}	
						$response->setContent ( \Zend\Json\Json::encode ( array (
								'status' => true,
								'isLogged' => true,
								'msg' => 'Shapefiles removidos com sucesso'
						) ) );
						return $response;
					} else {
						$response->setContent ( \Zend\Json\Json::encode ( array (
								'status' => false,
								'isLogged' => true,
								'msg' => 'Não foi possível remover os arquivos'
						) ) );
						return $response;
					}
				}
				$response->setContent ( \Zend\Json\Json::encode ( array (
						'status' => false,
						'isLogged' => true,
						'msg' => 'Você não possui permissões para realizar essa operação'
				) ) );
				return $response;
			}
			$response->setContent ( \Zend\Json\Json::encode ( array (
					'status' => false,
					'isLogged' => false,
					'msg' => 'Você precisa fazer login para realizar essa operação'
			) ) );
			return $response;
		} catch ( \Exception $e ) {
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
			$response->setContent ( \Zend\Json\Json::encode ( array (
					'status' => false,
					'isLogged' => true,
					'msg' => 'Não foi possível realizar essa operação'
			) ) );
			return $response;
		}
	}
	public function getOlderAndNewerDatesAction(){
		try{
			$response = $this->getResponse();
			$formdata = $this->getFormData();
			$prjId = $formdata["prjId"];
			$serviceLocator = $this->getServiceLocator();
			$shapeFileService = $serviceLocator->get ( 'Storage\Service\ShapefileService' );
				
			$dates = $shapeFileService->getOlderAndNewerDates($prjId);
			if($dates){
				$older = explode(' ', $dates[1])[0];
				$newer = explode(' ', $dates[2])[0];
				$response->setContent ( \Zend\Json\Json::encode ( array (
						'status' => true,
						'older' => $older,
						'newer' => $newer
				) ) );
			}else{
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Datas não foram recuperadas - Linha: " . __LINE__);
				$response->setContent ( \Zend\Json\Json::encode ( array (
						'status' => false
				) ) );
			}	
			return $response;
		} catch ( \Exception $e ) {
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage() ."- Linha: " . __LINE__);
			$response->setContent ( \Zend\Json\Json::encode ( array (
					'status' => false,
					'msg' => 'Não foi possível realizar essa operação'
			) ) );
			return $response;
		}
	}
}