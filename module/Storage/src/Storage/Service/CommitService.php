<?php

namespace Storage\Service;

use Doctrine\ORM\EntityManager;

class CommitService extends AbstractService {
	public function __construct(EntityManager $em) {
		parent::__construct ( $em );
		$this->entity = "Storage\Entity\Commit";
	}
	public function addCommit($commit) {
		try {
			$sql = "INSERT INTO commit (hash,use_id, prj_id, msg, date) values('" . $commit->hash . "'," . $commit->use->useId . "," . $commit->prj->prjId . ",'" . $commit->msg . "','" . $commit->date . "')";
			$conn = $this->em->getConnection ();
			$stmt = $conn->prepare ( $sql );
			$stmt->execute ();
			$id = $conn->lastInsertId ();
			return $id;
		} catch ( \Doctrine\DBAL\ConnectionException $e ) {
			return false;
		} catch ( \Doctrine\DBAL\DBALException $dbalExc ) {
			return false;
		} catch ( \Exception $e ) {
			return false;
		}
	}
	
	public function getByUserAndPrj($user, $prj) {
		try {
			$repository=$this->em->getRepository($this->entity);
			$criteria=array("use"=>$user, "prj"=> $prj);
			$commits=$repository->findBy($criteria);
			return $commits;
		}catch (\Exception $e){
			return null;
		}
	}
	
	public function removeByUserAndPrj($user, $prj){
		$sql="DELETE FROM commit WHERE use_id = " . $user->useId. " and prj_id = ".$prj->prjId;
		try {
			$conn=$this->em->getConnection();
			$conn->exec($sql);
			return true;
		}catch (\Doctrine\DBAL\DBALException $dbalExc){
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$dbalExc->getMessage()." Linha: " . __LINE__);
			return false;
		}catch (\Exception $e){
			LogHelper::writeOnLog(__CLASS__ . ":" . __FUNCTION__ . " - Mensagem: ".$e->getMessage()." Linha: " . __LINE__);
			return false;
		}
	}
	
}