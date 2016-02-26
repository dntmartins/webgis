<?php
namespace Main\Helper;

use \Email\Exception\MissingConfigException;

class ConfigHelper {
	
	public function getConfig()
	{
		$filename = __DIR__ . '/../../../config/system.config.php';
		$dir = __DIR__ . '/../../../config/';
		if(!file_exists($filename)) {
			$this->createConfigFile($filename);
		}
	    return include $filename;
	}
	
	private function createConfigFile($filename) {
		$configTemplate =
						"<?php
						namespace Main;
						return array(
							'geoserver' => array(
								'login' => 'admin',
								'password' => 'geoserver',
								'host' => 'localhost:8080'
							),
							'datasource' => array(
								'dbName' => 'postgres',
								'login' => 'postgres',
								'password' => 'postgres',
								'host' => 'localhost',
								'port' => 5432
							)
						);";
		file_put_contents($filename, $configTemplate);
		@chmod($filename, 0666);
	}
}