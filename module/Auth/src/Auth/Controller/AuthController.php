<?php

namespace Auth\Controller;

use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\Session as SessionStorage;
use Storage\Entity\User;
use Zend\Session\Container;
use Storage\Service\UserService;
use Email\Controller\EmailController;
use Auth\Form\ChangePasswordForm;
use Auth\Form\ChangePasswordFilter;
use Zend\Validator\EmailAddress;
use Zend\View\HelperPluginManager;
use Zend\Http\Client;
use Zend\Http\Request;
use Main\Controller\MainController;

class AuthController extends MainController {
	public function indexAction() {
		return array();
	}
	public function loginAction() {
	    try {
    		$request = $this->getRequest();
    		$basePath=$request->getBasePath();
    		if ($request->isPost ()) {
    			$data = $request->getPost ();
    			$validatorEmail = new EmailAddress();
    			$validatorEmail->setOptions(array('domain' => FALSE));
    			if (!($validatorEmail->isValid($data ['email']))) {
    				foreach ($validatorEmail->getMessages() as $messageId => $message) {
    					$this->showMessage($message, 'home-error');
    				}
    				$url = $request->getHeader ( 'Referer' )->getUri ();
    				return $this->redirect ()->toUrl ( '/' );
    			}
    			$userService = $this->getServiceLocator ()->get ( 'Storage\Service\UserService' );
    			$accessService = $this->getServiceLocator ()->get ( 'Storage\Service\AccessService' );
    			$authAdapter = $this->getServiceLocator ()->get ( 'Auth\Auth\Adapter' );
    			
    			$authenticationService = new AuthenticationService ();
    			$authenticationService->setStorage (new SessionStorage ());
    			
    			$authAdapter->setUsername ( $data ['email'] )->setPassword ( $data ['password'] );
    			
    			$result = $authenticationService->authenticate ( $authAdapter );
    			$user = $result->getIdentity ()['user'];
    			
    			if ($result->isValid ()) {
    				$this->session = new Container ( 'App_Auth' );
    				$this->session->user = $result->getIdentity ()['user'];
    				
    				$userService->updateLastAccess($this->session->user->useId);
    				$userService->clearResetToken($this->session->user->useId);
    				
    				$projects=null;
    				$coordinatorResponsible=0;
    				
    				$projects = $accessService->getPrjByUser ( $this->session->user);
    				if(count($projects)>0) {
    					$this->session->projects = $projects;
    				}else{
    					unset($this->session->projects);
    					$this->session->projects=null;
    				}
    				$acl = $this->getServiceLocator()->get('Admin\Permissions\Acl');
    				if ($acl->isAllowed($this->session->user->rol->name, "Administração", "Administrar usuários e permissões")) {
    				    $url = $basePath . "/user";
    				    return $this->redirect ()->toUrl ( $url );
    				}
    				else {
    					$url = $basePath . "/workspace";
    				    return $this->redirect ()->toUrl ( $url );
    				}
    			} else{
    				if($result->getCode() == 0)
    					return $this->showMessage('Esse usuário está desativado', 'home-error', '/');
    				else if($result->getCode() == -3)
    					return $this->showMessage('Login ou senha inválidos', 'home-error', '/');
    			}
    		}
		}catch (\Exception $e){
			return $this->showMessage('Ocorreu um erro ao realizar o login', 'home-error', '/');
		}
	}
	public function logoutAction() {
	    try {
    		$this->session->getManager ()->getStorage ()->clear ();
    		return $this->showMessage('Logout realizado com sucesso!', 'home-success', '/');
		}catch (\Exception $e){
			return $this->showMessage('Ocorreu um erro ao realizar o logout', 'home-error', '/');
		}
	}
	//TESTE http:IP/auth/externalLogin?project=terterter&user=admin@admin.com&password=admin
	public function externalLoginAction(){	
		$response = $this->getResponse();	
		$clientIp = substr($_SERVER['REMOTE_ADDR'], 0, strrpos($_SERVER['REMOTE_ADDR'], '.'));	
		$serverIp = substr($_SERVER['SERVER_ADDR'], 0, strrpos($_SERVER['SERVER_ADDR'], '.'));		
		if ($clientIp == $serverIp){		
			if (isset($_REQUEST['project']) && isset($_REQUEST['user']) && isset($_REQUEST['password'])){
				$project = $_REQUEST['project'];
				$user = $_REQUEST['user'];
				$password = $_REQUEST['password'];
				
				$authAdapter = $this->getServiceLocator ()->get ( 'Auth\Auth\Adapter' );
				$authenticationService = new AuthenticationService ();
				$authenticationService->setStorage (new SessionStorage ());		
				$authAdapter->setUsername ($user)->setPassword ($password);			
				$result = $authenticationService->authenticate ( $authAdapter );	
				
				
				if ($result->isValid()){
					$prjService = $this->getServiceLocator ()->get ( 'Storage\Service\ProjectService' );
					$geoService = $this->getServiceLocator ()->get ( 'Storage\Service\GeoServerService' );
					$prj = $prjService->getByName($project);
					if ($prj){
					$geoserverLogin = $geoService->getByPrj($prj->prjId);
					if ($geoserverLogin){
						$user = $result->getIdentity ()['user'];	
						$isAdmin = $user->rol->isAdmin;					
						$response->setContent(\Zend\Json\Json::encode(array('login' => true, 'isAdmin' => $isAdmin, 'geoserver' => array('user' => $geoserverLogin->login, 'pass' => $geoserverLogin->pass))));
						return $response;

						}else{				
							$response->setContent(\Zend\Json\Json::encode(array('login' => false)));
							return $response;
						}
					}else{				
						$response->setContent(\Zend\Json\Json::encode(array('login' => false)));
						return $response;
					}
				}else{				
					$response->setContent(\Zend\Json\Json::encode(array('login' => false)));
					return $response;
				}
			
			}else{
				$response->setContent(\Zend\Json\Json::encode(array('login' => false)));
				return $response;
			}
		
		}else{
			$response->setContent(\Zend\Json\Json::encode(array('login' => false)));
			return $response;			
		}
	}
}
