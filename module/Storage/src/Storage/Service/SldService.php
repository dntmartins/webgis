<?php

namespace Storage\Service;

use Doctrine\ORM\EntityManager;
use Storage\Entity\Configurator;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query;
use Storage\Entity\Sld;
use Main\Helper\LogHelper;

class SldService extends AbstractService {
    public function __construct(EntityManager $em) {
        parent::__construct($em);
        $this->entity = "Storage\Entity\Sld";
    }
    
    public function addSld($sld) {
    	try {
    		$this->em->persist($sld);
    		$this->em->flush();
    		return $sld;
    	}catch (\Exception $e){
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return null;
    	}
    }
    
    public function getById($id) {
        try {
        	$repository=$this->em->getRepository($this->entity);
        	$criteria=array("sldId"=>$id);
        	$orderBy=null;
        	$sld=$repository->findOneBy($criteria);
        	return $sld;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    	    return null;
    	}
    }
    public function listByPrj($prjId) {
    	try{
    		$sql="SELECT s.* FROM sld s, layer l WHERE s.layer_id = l.layer_id AND l.prj_id =".$prjId.";";
    
    		$rsm=new ResultSetMapping();
    		$rsm->addEntityResult('Storage\Entity\Sld', 's');
    		 
    		$query = $this->em->createNativeQuery($sql, $rsm);
    		$layers=$query->getResult();
    		$this->em->clear();
    		return $access;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return null;
    	}
    }
    
    public function saveSld($sldFile, $serviceLocator, $isAdmin) {
    
    	try {   	
    		if (isset($sldFile) && isset($serviceLocator)){
		    	$sldFileSize = $sldFile["size"]; //Pegando tamanho do arquivo
		    	$ext = strtolower(substr($sldFile['name'],-4)); //Pegando extensão do arquivo
		    	if($sldFileSize < 51200000){ // 50 mb é o limite
		    		if(!($ext == ".sld")){
		    			throw new \Exception("Extensão inválida.");
		    		}
		    	}else{
		    		throw new \Exception("Tamanho do arquivo de estilo excede o limite."); 
		    	}
		    	$dirArquivos = dirname ( __DIR__ );
		    	$dir = dirname(dirname(dirname($dirArquivos))) . '/Workspace/src/Workspace/file-uploads/sld/'; //Diretório para uploads	    	
		    	if (! is_dir ( $dir )) { //Criando diretório caso não exista
		    		mkdir ( $dir );
		    		chmod ( $dir, 0777 );
		    	}
		    	$sld = $this->sendSldToStorage($sldFile['name'], $dir, $serviceLocator, $isAdmin);
		    	$nameSld = $sld->sldId.$ext;
		    	if ($sld){
			    	$moveSld = move_uploaded_file($sldFile['tmp_name'], $dir.$nameSld); //Fazer upload do arquivo
			    	chmod($dir.$nameSld, 0777);
			    	if ($moveSld) {
			    		$this->replaceTagNameInFile($dir.$nameSld);
			    		return $sld;
			    	}
		    	}else{
		    		throw new \Exception("Erro ao salvar arquivo de estilo."); 
		    	}
    		}else{
		    		throw new \Exception("Erro ao salvar arquivo de estilo."); 
		    	}
    	} catch (Exception $e) {
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    	}
    }
    
    /**
     * Troca a tag SvgParameter pela CssParameter. O geoserver só aceita esta tag para configuração dos estilos.
     * @param string $fileName, path mais nome do arquivo SLD
     */
    private function replaceTagNameInFile($fileName) {
    	
    	$search = array("SvgParameter");
    	$put = array("CssParameter");
    	
    	if(file_exists($fileName)===TRUE)
    	{
    		if(is_writeable($fileName))
    		{
    			try
    			{
    				$FileContent = file_get_contents($fileName);
    				$FileContent = str_replace($search, $put, $FileContent);
    				if(file_put_contents($fileName, $FileContent)===false)
    				{
    					LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " Error while writing file. Linha: " . __LINE__);
    				}
    			}
    			catch(Exception $e)
    			{
    				LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    				return false;
    			}
    		}
    		else
    		{
    			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " File ".$fileName." is not writable. Linha: " . __LINE__);
    			return false;
    		}
    	}
    	else
    	{
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " File ".$fileName." does not exist. Linha: " . __LINE__);
    		return false;
    	}
    	return true;
    }
    
    private function sendSldToStorage($fileName, $diskLocation, $serviceLocator, $isAdmin) {
    	try {
    		
    		if (isset($fileName) && isset($diskLocation) && isset($serviceLocator)){
	    		// gravar metadados do arquivo recebido no banco
	    		$sldService = $serviceLocator->get ( 'Storage\Service\SldService' );
	    		$date = new \DateTime ( "now" );
	    		$newSld = new Sld();
	    		$newSld->sldName = $sldName = str_replace(" ", "", $fileName);;
	    		$newSld->diskLocation = $diskLocation;
	    		$newSld->sldDate = $date;
	    		$newSld->layerId = null;
	    		$newSld->registered = 0;
	    		$newSld->adminUploaded = $isAdmin;
	    
	    		$addedSld = $sldService->addSld( $newSld );
	    
	    		if(!$addedSld){
	    			throw new \Exception("Fail on check integrity of the new document.");
	    		}
	    		return $addedSld;
	    		
    		}else{
    			throw new \Exception("Erro ao salvar arquivo de estilo.");
    			 
    		}
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		throw new \Exception($e->getMessage(),$e->getCode(),$e->getPrevious());
    	}
    }
    
    public function getByAdmin(){
    	try {
    		$repository=$this->em->getRepository($this->entity);
    		$criteria=array("adminUploaded"=>'1');
    		$role=$repository->findOneBy($criteria);
    		return $role;
    	}catch (\Exception $e){
    		LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
    		return null;
    	}
    }
}