<?php
namespace Main\Helper;

use Zend\Log\Writer\Stream;
use Zend\Log\Logger;

class LogHelper {
	public static function writeOnLog($string) {
		if(getenv("APPLICATION_ENV") === "development"){
			$writer = new Stream('/tmp/updateData.log');// /path/to/logfile
			$logger = new Logger();
			$logger->addWriter($writer);
			$logger->info($string);
		}
	}
}