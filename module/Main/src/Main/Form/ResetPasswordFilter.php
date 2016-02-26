<?php

namespace Main\Form;

use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class ResetPasswordFilter implements InputFilterAwareInterface {
	protected $inputFilter;
	public $passwordNew1;
	public $passwordNew2;
	
	public function exchangeArray($data) {
		$this->passwordNew1 = (isset ( $data ['passwordNew1'] )) ? $data ['passwordNew1'] : null;
		$this->passwordNew2 = (isset ( $data ['passwordNew2'] )) ? $data ['passwordNew2'] : null;
	}
	
	public function setInputFilter(InputFilterInterface $inputFilter) {
		throw new \Exception ( "Not used" );
	}
	public function getInputFilter() {
		if (! $this->inputFilter) {
			$inputFilter = new InputFilter ();
			$factory = new InputFactory ();
			
			$inputFilter->add ( $factory->createInput ( array (
					'name' => 'passwordNew1',
					'required' => true,
					'filters' => array (
							array (
									'name' => 'StripTags'
							),
							array (
									'name' => 'StringTrim'
							)
					),
					'validators' => array (
							array (
									'name' => 'StringLength',
									'options' => array (
											'encoding' => 'UTF-8',
											'min' => 6,
											'max' => 100
									)
							)
					)
			) ) );
			
			$inputFilter->add ( $factory->createInput ( array (
					'name' => 'passwordNew2',
					'required' => true,
					'filters' => array (
							array (
									'name' => 'StripTags'
							),
							array (
									'name' => 'StringTrim'
							)
					),
					'validators' => array (
							array (
									'name' => 'StringLength',
									'options' => array (
											'encoding' => 'UTF-8',
											'min' => 6,
											'max' => 100
									)
							)
					)
			) ) );
			
			
			$this->inputFilter = $inputFilter;
		}
		
		return $this->inputFilter;
	}
}
