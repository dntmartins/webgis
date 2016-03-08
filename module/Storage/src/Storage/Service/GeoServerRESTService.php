<?php

namespace Storage\Service;

use Main\Helper\LogHelper;

class GeoServerRESTService extends AbstractService {
	
	public function createWorkspace($geoServerLogin, $workspace, $host){

		try{
			if (isset($geoServerLogin) && isset($workspace) && isset($host)){
				LogHelper::writeOnLog("createWorkspace inicio");
				$passwordStr =  $geoServerLogin; // replace with your username:password			
				// Initiate cURL session
				$service = "http://".$host; // replace with your URL
				$request = "rest/workspaces"; // to add a new workspace
				$url = $service . $request;
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." URL:".$url);
				$ch = curl_init($url);

				// Optional settings for debugging
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //option to return string
				curl_setopt($ch, CURLOPT_VERBOSE, true);	
				//Required POST request settings
				curl_setopt($ch, CURLOPT_POST, True);
				curl_setopt($ch, CURLOPT_USERPWD, $passwordStr);
				//POST data
				curl_setopt($ch, CURLOPT_HTTPHEADER,
						array("Content-type: application/xml"));
				$xmlStr = '<workspace><name>'.$workspace.'</name></workspace>';
				curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlStr);
				//POST return code
				$successCode = 201;
				$buffer = curl_exec($ch); // Execute the curl request
				LogHelper::writeOnLog("createWorkspace execRequest:".$buffer);
				// Check for errors and process results
				$info = curl_getinfo($ch);
				LogHelper::writeOnLog("createWorkspace INFO:".print_r($info,true));
				if ($info['http_code'] != $successCode) {
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." FALHOU ".$info["http_code"]);
					return false;		
				} else {
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ .": OK");
					return true;
				}
			}else{
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." FALHOU em teste de parametros");
				return false;
			}
			curl_close($ch); // free resources if curl handle will not be reused
		}catch (\Exception $e){
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." FALHOU ".$e->getMessage());
			return false;
		}
	}

	public function createStyle($geoServerLogin, $sld, $host){
		try{
			if (isset($geoServerLogin) && isset($sld) && isset($host)){
				$passwordStr =  $geoServerLogin; // replace with your username:password
				// Initiate cURL session
				$service = "http://".$host; // replace with your URL
				$request = "rest/styles"; // to add a new workspace
				$url = $service . $request;
				$ch = curl_init($url);
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." URL:".$url);
				// Optional settings for debugging
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //option to return string
				curl_setopt($ch, CURLOPT_VERBOSE, true);
				//Required POST request settings
				curl_setopt($ch, CURLOPT_POST, True);
				curl_setopt($ch, CURLOPT_USERPWD, $passwordStr);
				//POST data
				curl_setopt($ch, CURLOPT_HTTPHEADER,array("Content-type: text/xml"));
				$sldName = strtolower(pathinfo($sld->sldName, PATHINFO_FILENAME));
				$sldId = $sld->sldId;
				$sldPath = $sld->diskLocation.$sldId.'.sld';
				$xmlStr = "<style><name>$sldName</name><filename>$sld->sldId.sld</filename></style>";
				curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlStr);
	
				//POST return code
				$successCode = 201;
				$buffer = curl_exec($ch); // Execute the curl request
				// Check for errors and process results
				$info = curl_getinfo($ch);
				curl_close($ch);
				if ($info['http_code'] != $successCode) {
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." FALHOU ao registrar o estilo:".print_r($info,true));
					return false;
				} else {
					$cmd = "curl -v -u ".$passwordStr." -XPUT -H 'Content-type: application/vnd.ogc.sld+xml' -d @".$sldPath." ".$url."/".$sldName. " 2>&1";
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." Envio de arquivo SLD, comando:".$cmd);
					$output = shell_exec($cmd);
					$successCode = "HTTP/1.1 200 OK";
					if($output){
						if(strpos($output, $successCode)){
							return true;
						}
					}
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." FALHOU ao executar comando:".$cmd);
					return false;
				}
			}else{
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." FALHOU em teste de parametros");
				return false;
			}
		}catch (\Exception $e){
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." FALHOU ".$e->getMessage());
			return false;
		}
	}

	public function createDatasource($geoServerLogin, $prjName, $datasource, $host){
		try{
			
			if (isset($geoServerLogin) && isset($prjName) && isset($datasource) && isset($host)){
				$workspace=$prjName;
				LogHelper::writeOnLog("Teste de parametros OK em createDatasource, continue");
				// Initiate cURL session
				$service = "http://".$host; // replace with your URL
				$request = "rest/workspaces/".$workspace."/datastores"; // to add a new workspace
				$url = $service . $request;
				$ch = curl_init($url);
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." URL:".$url);
				// Optional settings for debugging
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //option to return string
				curl_setopt($ch, CURLOPT_VERBOSE, true);	
				//Required POST request settings
				curl_setopt($ch, CURLOPT_POST, True);
				$passwordStr = $geoServerLogin; // replace with your username:password
				curl_setopt($ch, CURLOPT_USERPWD, $passwordStr);
				//POST data
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/xml"));
				$xmlStr = "<dataStore>
				  <name>".$datasource->dbName."</name>
				  <connectionParameters>
				    <host>".$datasource->host."</host>
				    <port>".$datasource->port."</port>
				    <database>".$datasource->dbName."</database>
				    <user>".$datasource->login."</user>
				    <passwd>".htmlspecialchars($datasource->password, ENT_COMPAT)."</passwd>
				    <schema>public</schema>
				    <dbtype>postgis</dbtype>
				  </connectionParameters>
				</dataStore>";
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ .":INFO:".$xmlStr);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlStr);
				//POST return code
				$successCode = 201;
				$buffer = curl_exec($ch); // Execute the curl request
				// Check for errors and process results
				$info = curl_getinfo($ch);
			
				if ($info['http_code'] != $successCode) {
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." FALHOU info:".print_r($info,true));
					return false;
				} else {
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." OK");
					return true;
				}
			}else{
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." FALHOU em teste de parametros");
				return false;
			}
			curl_close($ch); // free resources if curl handle will not be reused
		}catch (\Exception $e){
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." FALHOU ".$e->getMessage());
			return false;
		}
	}
	
	public function createLayer($geoServerLogin, $workspace, $tableName, $host, $sldName=null){
		try{
			if (isset($geoServerLogin) && isset($workspace) && isset($tableName)){
				$verifyLayer = $this->verifyDuplicateLayer($geoServerLogin, $workspace, $tableName, $host);
				
				if ($verifyLayer === false){
					$passwordStr =  $geoServerLogin; // replace with your username:password
					// Initiate cURL session
					$service = "http://".$host."rest/workspaces/"; // replace with your URL
					$request = $workspace."/datastores/".$workspace."/featuretypes";
					$url = $service . $request;
					$ch = curl_init($url);
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." URL:".$url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //option to return string
					curl_setopt($ch, CURLOPT_VERBOSE, true);
					//Required POST request settings
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_USERPWD, $passwordStr);
					//POST data
					curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/xml"));
					
					$xmlStr = '<featureType><name>'.$tableName.'</name></featureType>';
					curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlStr);
					//POST return code
					$successCode = 201;
					$buffer = curl_exec($ch); // Execute the curl request
					$info = curl_getinfo($ch);
					curl_close($ch);
					if($sldName){
						if ($info['http_code'] == $successCode) {
							$resultStyle = $this->setDefaultStyle($geoServerLogin, $workspace, $tableName, $sldName, $host);
							if($resultStyle){
								return true;
							} else {
								LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ .":".__LINE__." FALHOU: info:\n".print_r($info, true));
								return false;
							}
						}
					}
					if ($info['http_code'] != $successCode) {
						LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ .":".__LINE__." FALHOU: info:\n".print_r($info, true));
						return false;
					} else {
						return true;
					}
				}else{
					// retorna true pois o teste indicou que o layer já existe no geoserver
					return true;
				}
			}else{
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." FALHOU em teste de parametros");
				return false;
			}
		}catch (\Exception $e){
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." FALHOU ".$e->getMessage());
			return false;
		}
	}
	
	public function setDefaultStyle($geoServerLogin, $workspace, $tableName, $sldName, $host){
		try{
			if (isset($geoServerLogin) && isset($workspace) && isset($tableName) && isset($sldName) && isset($host)){
				LogHelper::writeOnLog("Teste de parametros OK em setDefaultStyle, continue.");
				$host = "http://".$host."rest/layers/".$workspace;
				$cmd = "curl -v -u ".$geoServerLogin." -XPUT -H 'Content-type: text/xml' -d '<layer><defaultStyle><name>".$sldName."</name></defaultStyle></layer>' ".$host.":".strtolower(pathinfo($tableName, PATHINFO_FILENAME))." 2>&1";
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." URL:".$cmd);
				$output = shell_exec($cmd);
				$successCode = "HTTP/1.1 200 OK";
				if($output){
					if(strpos($output, $successCode)){
						LogHelper::writeOnLog("Comando OK em setDefaultStyle, continue.");
						return true;
					}
					LogHelper::writeOnLog("Comando FALHOU em setDefaultStyle, pare.");
					return false;
				}
			}else{
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." FALHOU em teste de parametros");
				return false;
			}
		}catch (\Exception $e){
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." FALHOU ".$e->getMessage());
			return false;
		}
	}
	
	public function deleteWorkspace($geoServerLogin, $workspace, $host){
		try{
			if (isset($geoServerLogin) && isset($workspace)){
				$passwordStr =  $geoServerLogin; // replace with your username:password
				// Initiate cURL session
				$service = "http://".$host; // replace with your URL
				//Parametro true para deleção recursiva
		    	$request = "rest/workspaces/".$workspace."?recurse=true"; // to delete this workspace		
		    	$url = $service . $request;
				$ch = curl_init($url);
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." URL:".$url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //option to return string
			
				//Required DELETE request settings
		    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
				curl_setopt($ch, CURLOPT_USERPWD, $passwordStr);
				curl_setopt($ch, CURLOPT_HTTPHEADER,
		              array("Content-type: application/atom+xml"));
				$successCode = 200;
				$buffer = curl_exec($ch); // Execute the curl request
				$info = curl_getinfo($ch);
				if ($info['http_code'] != $successCode) {
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." Requisicao CURL FALHOU".print_r($info,true));
					return false;
				} else {
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." Requisicao CURL OK");
					return true;
				}
			}else{
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." FALHOU em teste de parametros");
				return false;
			}
			curl_close($ch); // free resources if curl handle will not be reused
		}catch (\Exception $e){
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." FALHOU ".$e->getMessage());
			return false;
		}	
	}
	
	public function verifyDuplicateLayer($geoServerLogin, $workspace, $tableName, $host){
		try{
			if (isset($geoServerLogin) && isset($workspace) && isset($tableName) && isset($host)){
				$passwordStr =  $geoServerLogin; // replace with your username:password
				// Initiate cURL session
				$service = "http://".$host."rest/workspaces/".$workspace."/datastores/".$workspace. "/featuretypes/";
				$request = $tableName; // replace with your URL
				$url = $service.$request;
				$ch = curl_init($url);
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." URL:".$url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //option to return string
				curl_setopt($ch, CURLOPT_VERBOSE, true);
				//Required POST request settings
				curl_setopt($ch, CURLOPT_USERPWD, $passwordStr);
				//POST data
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/xml"));
				//POST return code
				$successCode = 200;
				$buffer = curl_exec($ch); // Execute the curl request
				$info = curl_getinfo($ch);
				LogHelper::writeOnLog("Comando:".$url);
				
				if ($info['http_code'] != $successCode) {
					$msgStr = "# Unsuccessful cURL request to ";
					$msgStr .= $url." [". $info['http_code']. "]\n";
					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ );
					return false;
				} else {
					$layer = $buffer;
					if ($layer != false){
						return true;
					}else{
						LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ );
						return false;
					}
				}
			}else{
				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." FALHOU em teste de parametros");
				return false;
			}
			curl_close($ch); // free resources if curl handle will not be reused
		}catch (\Exception $e){
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ ." FALHOU ".$e->getMessage());
			return false;
		}
	}
}