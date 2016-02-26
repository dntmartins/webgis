<?php

namespace Storage\Service;

use Zend\Mail;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\Session as SessionStorage;
use Storage\Entity\User;
use Zend\Session\Container;
use Storage\Service\UserService;
use Zend\Mail\Storage\Imap as ReceiveMail;
use Storage\Entity\Email;
use Zend\Stdlib\ArrayObject;
use \RecursiveIteratorIterator;
use Email\Helper\ConfigHelper;
use \Email\Exception\MissingConfigException;
use \Email\Exception\ServiceReceiveException;
use Zend\Mail\Storage as Storage;
use \Zend\Mail\Transport\Exception\RuntimeException;

class EmailService {
	
	private $IMAP=null;
	
	public function send($subject, $emailsTo, $content) {
		
		// Configurações de envio de email, ver o arquivo email.config.php
		$emailConfig = $this->getEmailConfigurations ();
		$USER_MAIL = $emailConfig ['send_account'] ['mail_user'];
		$PASS_MAIL = $emailConfig ['send_account'] ['mail_password'];
		$HOST = $emailConfig ['send_account'] ['host'];
		$PORT = $emailConfig ['send_account'] ['port'];
		
		if (! isset ( $emailsTo ) || empty ( $emailsTo )) {
			throw new \Email\Exception\MissingConfigException ( "Missing address to \"To recipients\"." );
		}
		
		if (
				! isset ( $USER_MAIL ) || empty ( $USER_MAIL ) ||
				! isset ( $PASS_MAIL ) || empty ( $PASS_MAIL ) ||
				! isset ( $HOST ) || empty ( $HOST ) ||
				! isset ( $PORT ) || empty ( $PORT )
			) {
			throw new \Email\Exception\MissingConfigException ( "Missing account configuration. Please, verify your email.config.php file." );
		}
		
		$options = new Mail\Transport\SmtpOptions ( array (
				'name' => 'localhost',
				'host' => $HOST,
				'port' => $PORT,
				'connection_class' => 'login',
				'connection_config' => array (
						'username' => $USER_MAIL,
						'password' => $PASS_MAIL,
						'ssl' => 'tls' 
				) 
		) );
		
		$mail = new Mail\Message ();
		
		$html = new MimePart ( $content );
		$html->type = "text/html";
		$html->charset = "utf-8";
		$body = new MimeMessage ();
		$body->setParts ( array (
				$html 
		) );
		
		$env = getenv('APPLICATION_ENV');
		$emailsTo = ( ($env == 'development') ? ($emailConfig['webmaster_account']['mail_user']) : ($emailsTo) );
		
		$mail->setBody ( $body ); // will generate our code html from template.phtml
		$mail->setFrom ( $USER_MAIL, 'FUNCATE' );
		$mail->addTo ( $emailsTo );
		$mail->setSubject ( $subject );
		$transport = new Mail\Transport\Smtp ( $options );
		$returnSend=false;
		try{
			$returnSend = $transport->send ( $mail );
		}catch (\Zend\Mail\Transport\Exception\RuntimeException $e) {
			$returnSend = $e->getMessage();
			return false;
		}
		
		return $returnSend;
	}
	
	public function getEmailConfigurations() {
		$config = new ConfigHelper ();
		$emailConfig = $config->getConfig ();
		return $emailConfig;
	}	
	
}
