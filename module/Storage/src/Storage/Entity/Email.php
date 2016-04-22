<?php

namespace Storage\Entity;


class Email
{
	
	private $emailId;

	private $from;

	private $date;
	
	private $subject;

	private $bodyMessage;

	
	public function setFrom($from) {
	         $this->from = $from;
	}
	
	public function getFrom() {
		 return $this->from;
	}

	
	public function setSubject($subject) {
	         $this->subject = $subject;
	}
	
	public function getSubject() {
		return $this->subject;
	}
	
	public function setBodyMessage($bodyMessage) {
	         $this->bodyMessage = $bodyMessage;
	}
	
	public function getBodyMessage() {
		return $this->bodyMessage;
	}
	
	public function setDate($date) {
	         $this->date = $date;
	}
	
	public function getDate() {
		return $this->date;
	}
	
}
