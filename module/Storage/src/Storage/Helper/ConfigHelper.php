<?php
namespace Storage\Helper;

use \Email\Exception\MissingConfigException;

class ConfigHelper {
	
	public function getConfig()
	{
		$filename = dirname(dirname(dirname(__DIR__))) . '/config/email.config.php';
		$dir = dirname(dirname(dirname(__DIR__))) . '/config/';
		if(!file_exists($filename)) {
			if (is_dir($dir)) {
				if (!is_writable($dir) && !@chmod($dir, 0666)) {
					throw new MissingConfigException("Error on create email.config.php. Please, verify write permissions on directory (".$dir.").");
				}
			}else{
				throw new MissingConfigException("Error on create email.config.php. Your directory (".$dir."), not found.");
			}
			$this->createConfigFile($filename);
		}
	    return include $filename;
	}
	
	private function createConfigFile($filename) {
		
		$configTemplate =
"<?php

namespace Storage;

return array (
		'send_account' => array(
				'host' => 'smtp.funcate.com.br',
				'mail_user' => 'no-reply@funcate.org.br',
				'mail_password' => 'f4nc@t3!',
				'port' => 587
		),
		'webmaster_account' => array(
			'mail_user' => ''
		),
);";
		file_put_contents($filename, $configTemplate);
		@chmod($filename, 0666);
	}
}