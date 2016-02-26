<?php

namespace Storage\Service;

class GeoServerRESTService extends AbstractService {
	
	public function createWorkspace($geoServerLogin, $workspace, $host){
		try{
			if (isset($geoServerLogin) && isset($workspace) && isset($host)){
				$passwordStr =  $geoServerLogin; // replace with your username:password			
				// Initiate cURL session
				$service = "http://".$host."/geoserver/"; // replace with your URL		
				$request = "rest/workspaces"; // to add a new workspace
				$url = $service . $request;
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
				// Check for errors and process results
				$info = curl_getinfo($ch);
				if ($info['http_code'] != $successCode) {
					return false;		
				} else {
					return true;
				}
			}else{
				return false;
			}
			curl_close($ch); // free resources if curl handle will not be reused
		}catch (\Exception $e){
			return false;
		}
	}

	public function createStyle($geoServerLogin, $sld, $host){
		try{
			if (isset($geoServerLogin) && isset($sld) && isset($host)){
				$passwordStr =  $geoServerLogin; // replace with your username:password
				// Initiate cURL session
				$service = "http://".$host."/geoserver/"; // replace with your URL
				$request = "rest/styles"; // to add a new workspace
				$url = $service . $request;
				$ch = curl_init($url);
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
					return false;
				} else {
					$cmd = "curl -v -u admin:geoserver -XPUT -H 'Content-type: application/vnd.ogc.sld+xml' -d @".$sldPath." http://localhost:8080/geoserver/rest/styles/".$sldName. " 2>&1";
					$output = shell_exec($cmd);
					$successCode = "HTTP/1.1 200 OK";
					if($output){
						if(strpos($output, $successCode)){
							return true;
						}
					}
					return false;
				}
			}else{
				return false;
			}
		}catch (\Exception $e){
			return false;
		}
	}

	public function createDatasource($geoServerLogin, $workspace, $datasource, $host){
		try{
			if (isset($geoServerLogin) && isset($workspace) && isset($datasource) && isset($host)){	
				// Initiate cURL session
				$service = "http://".$host."/geoserver/"; // replace with your URL
				$request = "rest/workspaces/".$workspace."/datastores"; // to add a new workspace
				$url = $service . $request;
				$ch = curl_init($url);
				// Optional settings for debugging
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //option to return string
				curl_setopt($ch, CURLOPT_VERBOSE, true);	
				//Required POST request settings
				curl_setopt($ch, CURLOPT_POST, True);
				$passwordStr = $geoServerLogin; // replace with your username:password
				curl_setopt($ch, CURLOPT_USERPWD, $passwordStr);
				//POST data
				curl_setopt($ch, CURLOPT_HTTPHEADER,
						array("Content-type: application/xml"));
				$xmlStr = "<dataStore>
				  <name>".$datasource->dbName."</name>
				  <connectionParameters>
				    <host>".$datasource->host."</host>
				    <port>".$datasource->port."</port>
				    <database>".$datasource->dbName."</database>
				    <user>".$datasource->login."</user>
				    <passwd>".$datasource->password."</passwd>
				    <schema>public</schema>
				    <dbtype>postgis</dbtype>
				  </connectionParameters>
				</dataStore>";
			
				curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlStr);
				//POST return code
				$successCode = 201;
				$buffer = curl_exec($ch); // Execute the curl request
				// Check for errors and process results
				$info = curl_getinfo($ch);
			
				if ($info['http_code'] != $successCode) {
					return false;
				} else {
					return true;
				}
			}else{
				return false;
			}
			curl_close($ch); // free resources if curl handle will not be reused
		}catch (\Exception $e){
			return false;
		}
	}
	
	public function createLayer($geoServerLogin, $workspace, $tableName, $host){
		try{
			if (isset($geoServerLogin) && isset($workspace) && isset($tableName)){
				$verifyLayer = $this->verifyLayer($geoServerLogin, $workspace, $tableName, $host);
				if ($verifyLayer == true){
					return true;
				}else{
					$passwordStr =  $geoServerLogin; // replace with your username:password
					// Initiate cURL session
					$service = "http://".$host."/geoserver/rest/workspaces/"; // replace with your URL
					$request = $workspace."/datastores/".$workspace."/featuretypes";
					$url = $service . $request;
					$ch = curl_init($url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //option to return string
					curl_setopt($ch, CURLOPT_VERBOSE, true);
					//Required POST request settings
					curl_setopt($ch, CURLOPT_POST, True);
					curl_setopt($ch, CURLOPT_USERPWD, $passwordStr);
					//POST data
					curl_setopt($ch, CURLOPT_HTTPHEADER,
							array("Content-type: application/xml"));
					$xmlStr = '<featureType><name>'.$tableName.'</name></featureType>';
					curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlStr);
					//POST return code
					$successCode = 201;
					$buffer = curl_exec($ch); // Execute the curl request
					$info = curl_getinfo($ch);
					curl_close($ch);
					if ($info['http_code'] != $successCode) {
						return false;
					} else {
						return true;
					}
				}
			}else{
				return false;
			}
		}catch (\Exception $e){
			return false;
		}
	}
	
	public function setDefaultStyle($geoServerLogin, $workspace, $tableName, $sldName, $host){
		try{
			if (isset($geoServerLogin) && isset($workspace) && isset($tableName) && isset($sldName) && isset($host)){
				$cmd = "curl -v -u ".$geoServerLogin." -XPUT -H 'Content-type: text/xml' -d '<layer><defaultStyle><name>".$sldName."</name></defaultStyle></layer>' http://localhost:8080/geoserver/rest/layers/".$workspace.":".strtolower(pathinfo($tableName, PATHINFO_FILENAME))." 2>&1";
				$output = shell_exec($cmd);
				$successCode = "HTTP/1.1 200 OK";
				if($output){
					if(strpos($output, $successCode)){
						return true;
					}
					return false;
				}
			}else{
				return false;
			}
		}catch (\Exception $e){
			return false;
		}
	}
	
	public function deleteWorkspace($geoServerLogin, $workspace, $host){
		try{
			if (isset($geoServerLogin) && isset($workspace)){
				$passwordStr =  $geoServerLogin; // replace with your username:password
				// Initiate cURL session
				$service = "http://".$host."/geoserver/"; // replace with your URL
				//Parametro true para deleção recursiva
		    	$request = "rest/workspaces/".$workspace."?recurse=true"; // to delete this workspace		
		    	$url = $service . $request;
				$ch = curl_init($url);
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
					return false;
				} else {
					return true;
				}
			}else{
				return false;
			}
			curl_close($ch); // free resources if curl handle will not be reused
		}catch (\Exception $e){
			return false;
		}	
	}
	
	public function verifyLayer($geoServerLogin, $workspace, $tableName, $host){
		try{
			if (isset($geoServerLogin) && isset($workspace) && isset($tableName) && isset($host)){
				$passwordStr =  $geoServerLogin; // replace with your username:password
				// Initiate cURL session
				$service = "http://".$host."/geoserver/rest/workspaces/".$workspace."/datastores/".$workspace. "/featuretypes/";
				$request = $tableName; // replace with your URL
				$url = $service.$request;
				$ch = curl_init($url);
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
				if ($info['http_code'] != $successCode) {
					$msgStr = "# Unsuccessful cURL request to ";
					$msgStr .= $url." [". $info['http_code']. "]\n";
					return false;
	
				} else {
					
					$layer = $buffer;
					if ($layer != false){
						return true;
					}else{
						return false;
					}
				}
			}else{
				return false;
			}
			curl_close($ch); // free resources if curl handle will not be reused
		}catch (\Exception $e){
			return false;
		}
	}
	
}