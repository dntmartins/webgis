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
			$response->setContent ( \Zend\Json\Json::encode ( array (
					'status' => false,
					'isLogged' => true,
					'msg' => 'Não foi possível realizar essa operação'
			) ) );
			return $response;
		}
	}
}