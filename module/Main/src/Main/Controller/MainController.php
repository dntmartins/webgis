<?php
namespace Main\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Storage\Entity\User as AuthUser;
use Zend\Session\Container;
use Zend\Validator\EmailAddress;
use Main\Form\ResetPasswordForm;
use Main\Form\ResetPasswordFilter;
use Zend\Mail;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;
use Zend\Validator\StringLength;
use Zend\I18n\Validator\Alnum;
use Main\Helper\ConfigHelper;
use Main\Helper\LogHelper;

class MainController extends AbstractActionController
{
	private $session;
	private $logger;
	
	public function __construct() {
		$this->session = new Container ( 'App_Auth' );
	}
	
	public function getConfiguration() {
        $config = new ConfigHelper();
        $systemConfig = $config->getConfig ();
		return $systemConfig;
	}
	
    public function indexAction()
    {
    	try{
    		$prjService = $this->getServiceLocator()->get ('Storage\Service\ProjectService');
    		$projects = $prjService->listAll();
    		return array('projects'=>$projects);
    	}catch(\Exception $e){
    		LogHelper::writeOnLog("Ocorreu um erro: ". $e->getMessage());
    		$this->flashMessenger ()->setNamespace ( "home-error" )->addMessage ( 'Ocorreu um problema, contate o administrador do sistema.' );
	    	return null;
    	}
    }

    public function resetPasswordAction()
    {
    	$request = $this->getRequest ();

    	if ($request->isPost ()) {
    		$url = $request->getHeader ( 'Referer' )->getUri ();
	    	$data = $request->getPost ();
	    	$email = $data ['email'];
	    	$validatorEmail = new EmailAddress();
	    	$validatorEmail->setOptions(array('domain' => FALSE));
	    	if (!($validatorEmail->isValid($email))) {
	    		// Email não é válido
	    		foreach ($validatorEmail->getMessages() as $messageId => $message) {
	    			$this->showMessage($message, 'error-email');
	    		}
	    		// Redirecionando usuário para mesma rota que estava
	    		return $this->redirect ()->toUrl ( '/reset/resetPassword' );
	    	}else{
	    		$serviceLocator = $this->getServiceLocator();
	    		$userService = $serviceLocator->get ('Storage\Service\UserService');
	    		$emailService = $serviceLocator->get ('Storage\Service\EmailService');
	    		
	    		$user = $userService->identifyUserByEmail($email);
	    		if($user){
	    			$timeStamp = time();
	    			$user->resetToken = $timeStamp;
	    			try {
	    				if($userService->updateUser ( $user )){
		    				$url_token_validation = str_replace("resetPassword", "newResetedPassword" ,$url) . "?email=" . $user->email ."&token=" . $timeStamp;
		    				try {
		    					$this->renderer = $this->getServiceLocator ()->get ( 'ViewRenderer' );
		    					$content = $this->renderer->render ('main/tpl/template',  array( "token" => $url_token_validation));
		    					$emailService->send('Portal projeto MSA - Definir nova senha', $user->email, $content);
		    				} catch (\Exception $e) {
		    					$erro = $e->getMessage();
		    					return $this->showMessage('Falhou ao enviar e-mail', 'error-email', '/reset/resetPassword');
		    				}
		    				return $this->showMessage("Um link de confirmação para reset de senha foi enviado para o e-mail: " . $user->email, 'success-email', '/reset/resetPassword');
	    				}
	    				return $this->showMessage('Ocorreu um problema, contate o administrador do sistema.', 'error-email', '/reset/resetPassword');
	    			}catch(\Exception $e){
	    				LogHelper::writeOnLog("Ocorreu um erro: ". $e->getMessage());
	    				return $this->showMessage('Ocorreu um problema, contate o administrador do sistema.', 'error-email', '/reset/resetPassword');
	    			}

	    		}else{
	    			return $this->showMessage('E-mail não cadastrado no sistema.', 'error-email', '/reset/resetPassword');
	    		}
	    	}
    	}
    }

    public function newResetedPasswordAction()
    {
    	$request = $this->getRequest ();
    	$form = new ResetPasswordForm();
    	$filter = new ResetPasswordFilter();
    	if($request->isGet ()){
    		$requestGET = $request->getQuery();
    		$token = $requestGET['token'];
    		$form->get ( 'token' )->setAttribute ( 'value', $token );
    		$email = $requestGET['email'];;
    		$form->get ( 'email' )->setAttribute ( 'value', $email );
    		return array ("form" => $form);
    	}else if($request->isPost ()){
    		$url = $request->getHeader ( 'Referer' )->getUri ();
    		$serviceLocator = $this->getServiceLocator();
    		$userService = $serviceLocator->get ('Storage\Service\UserService');
    		$requestPOST = $request->getPost();
    		$data = $request->getPost ();
    		$form->setData ( $requestPOST );
    		
    		if($data['token'] && $data['email'])
    			$user = $userService->getByEmailAndToken($data['email'], $data['token']);
    		
    		if($user){
    			$diferenca =  $data['token'] - time();
    			$dias = (int)floor($diferenca / (60 * 60 * 24));
    			if($dias <= 3){
    				$form->setInputFilter ( $filter->getInputFilter() );
    				if ($form->isValid ()) {
    					$filter->exchangeArray ( $form->getData () ); // Pega valores do input do changePasswordForm filtra e popula objeto
    					$passNew1 = sha1($filter->passwordNew1);
    					$passNew2 = sha1($filter->passwordNew2);
    					if($passNew1 == $passNew2){
    						$user->password = $passNew1;
    						try {
    							if($userService->updateUser ( $user ))
    								return $this->showMessage('Nova senha criada com sucesso.', 'home-success', '/');
    							else
    								return $this->showMessage('Não foi possível criar a nova senha.', 'error-email', '/reset/resetPassword');
    						}catch(\Exception $e){
    							LogHelper::writeOnLog("Ocorreu um erro: ". $e->getMessage());
    							return $this->showMessage('Ocorreu um problema, contate o administrador do sistema.', 'error-email', '/reset/resetPassword');
    						}
    					}else
    						return $this->showMessage('As senhas digitadas são diferentes, favor, tente novamente.', 'error-email', '/reset/resetPassword');
    				}
    			}else
    				return $this->showMessage('Prazo para alteração expirou, favor, solicitar um novo reset de senha.', 'error-email', '/reset/resetPassword');
    		}else
    			return $this->showMessage('Não foi possível alterar sua senha, contate o administrador do sistema.', 'error-email', '/reset/resetPassword');
    	}
    }
	public function verifyUserSession()
    {
        $this->session = new Container('App_Auth');
        $user = $this->session->user;
        if ($user && get_class($user) == 'Storage\Entity\User')
            return true;
        return false;
    }
    public function showMessage($message, $namespace, $redirectTo = null) {
    	$request = $this->getRequest ();
    	$this->flashMessenger ()->setNamespace ( $namespace )->addMessage ( $message );
    	if($redirectTo){
    		$basePath = $request->getBasePath ();
    		if($redirectTo[0] != '/')
    			$redirectTo = '/'.$redirectTo;
    		$url = $basePath . $redirectTo;
    		return $this->redirect ()->toUrl ( $url );
    	}
    }
    public function getFormData()
    {
    	$request = $this->getRequest();
    	$formData = null;
    
    	// get data from browser form whith GET method
    	if ($request->isGet()) {
    		$formData = $request->getQuery();
    	}
    
    	// get data from browser form whith POST method
    	if ($request->isPost()) {
    		$formData = $request->getPost();
    	}
    	return $formData;
    }
    
    public function regexValidate($name, $regex){
    	$matches = array();
    	$validatedName = preg_match($regex, $name, $matches);
    	if($validatedName && $validatedName != 0){
    		if($matches[0] == $name)
    			return true;
    	}
    	return false;
    }
    
    public function userConfigurationsAction(){
    	$request = $this->getRequest();
    	$email = null;
    	$name = null;
    	if ($this->verifyUserSession ()) {
    		$acl = $this->getServiceLocator ()->get ( 'Admin\Permissions\Acl' );
    		$userService = $this->getServiceLocator ()->get ( 'Storage\Service\UserService' );
    		$user = $userService->getById ( $this->session->user );
    		$id = $user->useId;
    		$email = $user->email;
    		$name = $user->name;
    		
    		$isAdmin = $this->session->user->rol->isAdmin;
    		//if($isAdmin)
    			//$this->session->active_menu = 0;
    		
    		/**
    		 * Permite informar o usuário que esta ferramenta é de uso exclusivo dos coordenadores que avaliam as requisições
    		 * 
    		 * requisitionTool==1 indica que é um coordenador geral responsável por requisições
    		 * rolId==4 indica que é um coordenador de subprojeto responsável por avaliar uma requisição do subprojeto
    		 */
    		$acceptRejectRequisitions = $acl->isAllowed($this->session->user->rol->name, "Área de trabalho", "Aceitar/recusar requisições");
    		
    		if ($request->isGet()){
    			$data = $request->getQuery();
	    		if(isset($data["id"])){
	    			$this->session->active_menu = $data["id"]; //Ativa qual aba fica salva na sessão
	    		}
    		}
    		return array(
    				'active_menu' => $this->session->active_menu,
    				'id' => $id,
    				'email' => $email,
    				'name' => $name,
    				'isAdmin' => $isAdmin
    		);
    	}
    	return $this->showMessage('Sua sessão expirou, favor relogar', 'home-error', '/');
    }
    
    public function editUserAction(){
    	try {
    		$request = $this->getRequest();
    		$response = $this->getResponse();
    		$userService = $this->getServiceLocator ()->get ( 'Storage\Service\UserService' );
    		$user = $userService->getById ( $this->session->user );
    		if ($this->verifyUserSession ()) {
    			if ($request->isPost()){
    				$data = $request->getPost();
    				$acl = $this->getServiceLocator()->get('Admin\Permissions\Acl');
    				if ($acl->isAllowed($this->session->user->rol->name, "Área de trabalho", "Trocar senha")) { //Alterar nome do ACL, porém esse também funciona.
    					$name = trim ( $data['name'] );
    					$newEmail = trim ( $data['email'] );
    					$newEmailConfirm = trim ( $data['email2'] );
    					$lengthValidator = new StringLength(array('min'=>1, 'max'=>45));
    					$validator = new Alnum(true);
    					$validatorEmail = new EmailAddress();
    					$validatorEmail->setOptions(array('domain' => FALSE));
    					 
    					if(!$lengthValidator->isValid($name)){
    						return $this->showMessage('Campo de nome não pode ser vazio', 'error-user-config', '/configurations');
    					}
    					if($lengthValidator->isValid($newEmail) && $lengthValidator->isValid($newEmailConfirm)){
    						if(!($newEmail === $newEmailConfirm)){
    							return $this->showMessage('Os campos de Email devem ser iguais', 'error-user-config', '/configurations');
    						}
    						if (!($validatorEmail->isValid($newEmail))) {
    							foreach ($validatorEmail->getMessages() as $messageId => $message) {
    								$this->showMessage($message, 'error-user-config');
    							}
    							// Redirecionando usuário para mesma rota que estava
    							$url = $request->getHeader ( 'Referer' )->getUri ();
    							return $this->redirect ()->toUrl ( $url );
    						}
    					}else{
    						return $this->showMessage('Campo de Email não pode ser vazio', 'error-user-config', '/configurations');
    					}
    				}
    				else {
    					return $this->showMessage('Você não possui permissões para realizar essa operação', 'home-error', '/');
    				}
    			}
    		} else {
    			return $this->showMessage('Sua sessão expirou, favor relogar', 'home-error', '/');
    		}
    		//Se chegou até aqui é porque tudo ocorreu bem, logo, deve-se alterar os dados do usuário na sessão e persistir no banco.
    		
    		$oldName = $this->session->user->name;
    		$oldEmail = $this->session->user->email;
    		
    		$this->session->user->name = $name;
    		$this->session->user->email = $newEmail;
    		
    		$userOk = $userService->updateUser($this->session->user);
    		if($userOk){
    			return $this->showMessage('Usuário alterado com sucesso!', 'success-user-config', '/configurations');
    		}else{
    			//retornando dados anteriores nos campos do usuário
    			$this->session->user->name = $oldName;
    			$this->session->user->email = $oldEmail;
    			return $this->showMessage('Ocorreu um erro ao alterar o usuário', 'error-user-config', '/configurations');
    		}
    	}catch (\Exception $e){
    		LogHelper::writeOnLog("Ocorreu um erro: ". $e->getMessage());
    		return $this->showMessage('Não foi possível alterar a senha, contate o administrador do sistema', 'home-error', '/configurations');
    	}
    }
    
    public function checkIfEmailExistsAction() {
    	try {
    		$response = $this->getResponse();
    		if ($this->verifyUserSession ()) {
    			$acl = $this->getServiceLocator()->get('Admin\Permissions\Acl');
    			if ($acl->isAllowed($this->session->user->rol->name, "Área de trabalho", "Trocar senha")) {
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
    		$response->setContent ( \Zend\Json\Json::encode ( array ('status' => false, 'isLogged' => false, 'permitted' => true,) ) );
    		return $response;
    	} catch ( \Exception $e ) {
    		LogHelper::writeOnLog("Ocorreu um erro: ". $e->getMessage());
    		$response->setContent ( \Zend\Json\Json::encode ( array (
    				'status' => false,
    				'isLogged' => true
    		) ) );
    		return $response;
    	}
    }
    
    public function changePasswordAction() {
    	try {
    		$request = $this->getRequest();
    		$response = $this->getResponse();
    		$userService = $this->getServiceLocator ()->get ( 'Storage\Service\UserService' );
    		if ($this->verifyUserSession ()) {
    			$user_pass = $userService->getById ( $this->session->user->useId );
    			if ($request->isPost()){
    				$data = $request->getPost();
	    			$acl = $this->getServiceLocator()->get('Admin\Permissions\Acl');
	    			if ($acl->isAllowed($this->session->user->rol->name, "Área de trabalho", "Trocar senha")) {
    					$passOld = $data['password'];
    					$passNew1 = sha1 ( $data['passwordNew1'] );
    					$passNew2 = sha1 ( $data['passwordNew2'] );
    					$lengthValidator = new StringLength(array('min' => 6, 'max' => 45));
    					if (sha1 ( $passOld ) == $user_pass->password) {
    						if($lengthValidator->isValid($data['passwordNew1']) && $lengthValidator->isValid($data['passwordNew2'])){
    							if ($passNew1 == $passNew2) {
    								$user_pass->password = $passNew1;
    								$user_change = $userService->updateUser ( $user_pass );
    								$this->session->user->password = $passNew1;
    								$this->msgSucesso = "<div class='alert alert-success' role='alert'> Senha alterada com sucesso! </div>";;
    							} else {
    								return $this->showMessage('Os campos de senha devem ser iguais!', 'error-user-config', '/configurations');
    							}
    						} else {
    							return $this->showMessage('Nova senha deve conter no mínimo 6 caracteres!', 'error-user-config', '/configurations');
    						}
    					}else{
    						return $this->showMessage('Senha atual incorreta!', 'error-user-config', '/configurations');
    					}
	    			}
	    			else {
	    				return $this->showMessage('Você não possui permissões para realizar essa operação', 'home-error', '/');
	    			}
    			}
    			
    		} else {
    			return $this->showMessage('Sua sessão expirou, favor relogar', 'home-error', '/');
    		}
    		return $this->showMessage('Senha alterada com sucesso', 'success-user-config', '/configurations');
    		
    	}catch (\Exception $e){
    		LogHelper::writeOnLog("Ocorreu um erro: ". $e->getMessage());
    		return $this->showMessage('Não foi possível alterar a senha, contate o administrador do sistema', 'home-error', '/configurations');
    	}
    }
    
    public function __set($name, $value) {
    	$this->$name = $value;
    }
    public function __get($name) {
    	return $this->$name;
    }
    
    public function checkCurrentPasswordAction() {
    	try {
    		$response = $this->getResponse();
    		if ($this->verifyUserSession ()) {
    			$acl = $this->getServiceLocator()->get('Admin\Permissions\Acl');
    			if ($acl->isAllowed($this->session->user->rol->name, "Área de trabalho", "Trocar senha")) {
    				$auth_user = $this->session->user;
    				$formData = $this->getFormData ();
    				$password = sha1($formData ['password']);
    				
    				if ($password == $auth_user->password) {
    					$response->setContent ( \Zend\Json\Json::encode ( array (
    							'status' => true,
    							'isLogged' => true
    					) ) );
    					return $response;
    				} else {
    					$response->setContent ( \Zend\Json\Json::encode ( array (
    							'status' => false,
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
    		$response->setContent ( \Zend\Json\Json::encode ( array ('status' => false, 'isLogged' => false, 'permitted' => true,) ) );
    		return $response;
    	} catch ( \Exception $e ) {
    		LogHelper::writeOnLog("Ocorreu um erro: ". $e->getMessage());
    		$response->setContent ( \Zend\Json\Json::encode ( array (
    				'status' => false,
    				'isLogged' => true
    		) ) );
    		return $response;
    	}
    }
    public function delTree($dir) {
    	try {
    		$files = array_diff(scandir($dir), array('.','..'));
    		foreach ($files as $file) {
    			(is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
    		}
    		return rmdir($dir);
    	} catch (\Exception $e) {
    		LogHelper::writeOnLog("Ocorreu um erro: ". $e->getMessage());
    		throw new \Exception("Ocorreu um erro ao remover o diretório");
    	}
    }
    
    public function removeDir($dir){
    	exec(escapeshellcmd("sudo rm -R " . $dir), $output, $return_var);
    	if($return_var !== 0){
    		return false;
    	}
    	return true;
    }
    
    public function getDbfTemplate(){
    	$templateContent = file_get_contents (getcwd()."/module/Workspace/src/Workspace/dbfTemplate.json" );
    	if($templateContent){
    		$template = json_decode ($templateContent, true);
    	}else{
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Falhou ao ler template do arquivo dbf" . " - Linha: " . __LINE__);
    		return false;
    	}
    	return $template;
    }
    
    public function deleteDatabase($prjName){
    	$serviceLocator = $this->getServiceLocator();
    	$datasourceService = $serviceLocator->get ( 'Storage\Service\DataSourceService' );
    	$config = $this->getConfiguration();
    	LogHelper::writeOnLog("Em deleteDatabase, continue.");
    
    	if ($prjName != null){
    		$db =  pg_connect('host='.$config["datasource"]["host"].' dbname='.$config["datasource"]["dbName"].' user='.$config["datasource"]["login"].' password='.$config["datasource"]["password"].' connect_timeout=5');
    		if ($db != null){
    			$sql = "DROP DATABASE ". strtolower($prjName);
    			$query = pg_query($db, $sql);
    			if ($query!==false){
    				LogHelper::writeOnLog("Removeu o banco, continue.");
    				pg_close($db);
    				return true;
    			}else{
    				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Falhou ao remover o banco - Linha: " . __LINE__);
    				pg_close($db);
    				return false;
    			}
    		}else{
    			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Falhou ao conectar o postgres - Linha: " . __LINE__);
    			pg_close($db);
    			return false;
    		}
    	}else{
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: Variável prjName não inicializada - Linha: " . __LINE__);
    		return false;
    	}
    }
    
    public function deleteTable($tableName, $dbName){
    	$serviceLocator = $this->getServiceLocator();
    	$datasourceService = $serviceLocator->get ( 'Storage\Service\DataSourceService' );
    	$config = $this->getConfiguration();
    	if ($tableName != null){
    		$db =  pg_connect('host='.$config["datasource"]["host"].' dbname='.strtolower($dbName).' user='.$config["datasource"]["login"].' password='.$config["datasource"]["password"].' connect_timeout=5');
    		if ($db != null){
    			$sql = "DROP TABLE public.". $tableName;
    			$query = pg_query($db, $sql);
    			pg_close($db);
    			if ($query!==false){
    				return true;
    			}else{
    				return false;
    			}
    		}else{
    			return false;
    		}
    	}else{
    		return false;
    	}
    }
    
    public function getParentDir($dir, $levels){
    	for ($i = 0; $i<$levels; $i++){
    		$dir = dirname($dir);
    	}
    	return $dir;
    }
    
}
