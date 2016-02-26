<?php

namespace Auth;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Storage\Service\UserService as UserService;
use Auth\Auth\Adapter as AuthAdapter;
use Auth\Form;
use Auth\Controller;


class Module implements AutoloaderProviderInterface {
	public function getAutoloaderConfig() {
		return array (
				'Zend\Loader\ClassMapAutoloader' => array (
						__DIR__ . '/autoload_classmap.php' 
				),
				'Zend\Loader\StandardAutoloader' => array (
						'namespaces' => array (
								__NAMESPACE__ => __DIR__ . '/src/' . str_replace ( '\\', '/', __NAMESPACE__ ) 
						) 
				) 
		);
	}
	public function getConfig() {
		return include __DIR__ . '/config/module.config.php';
	}
	public function onBootstrap(MvcEvent $e) {
		// You may not need to do this if you're doing it elsewhere in your
		// Auth
		$eventManager = $e->getApplication()->getEventManager ();
		$moduleRouteListener = new ModuleRouteListener ();
		$moduleRouteListener->attach ( $eventManager );
	}
	public function getServiceConfig() {
		return array (
			'factories' => array (
				'Auth\Auth\Adapter' => function ($service) {
					return new Auth\Adapter ( $service->get ( 'Doctrine\ORM\EntityManager' ) );
				},
				'Auth\Form\ChangeForm' => function ($sm) {
					$em = $sm->get ( 'Doctrine\ORM\EntityManager' );
					$repo = $em->getRepository ( 'Storage\Entity\User' );
					$parent = $repo->fetchParent ();
						
					return new Form\ChangeForm ( 'user', $parent );
				},
			) 
		);
	}
}
