<?php

namespace Main\Form;

use Zend\Form\Form;

class ResetPasswordForm extends Form {
	public function __construct($name = null) {
		parent::__construct ( 'Main' );
		$this->setAttribute ( 'method', 'post' );
		//$this->setAttribute ( 'enctype', 'multipart/form-data' );
		
		$this->add ( array (
				'name' => 'token',
				'attributes' => array (
						'type' => 'hidden',
						'value' => 0,
				)
		));
		
		$this->add ( array (
				'name' => 'email',
				'attributes' => array (
						'type' => 'hidden',
						'value' => 0,
				)
		));
		
		$this->add(array(
				'name' => 'passwordNew1',
				'attributes' => array(
						'type'  => 'password',
						'id' => 'passwordNew1',
						'class' => 'form-control',
						'placeholder' => "Nova senha"
				),
		));
		
		$this->add(array(
				'name' => 'passwordNew2',
				'attributes' => array(
						'type'  => 'password',
						'id' => 'passwordNew2',
						'class' => 'form-control',
						'placeholder' => "Confirme a senha"
				),
		));
		
		$this->add ( array (
				'name' => 'submit',
				'attributes' => array (
						'type' => 'submit',
						'value' => 'Alterar',
						'title' => 'Criar nova senha.',
						'class' => 'btn btn-default',
						'onclick' => 'showLoadingGif();'
				) 
		) );
	}
}