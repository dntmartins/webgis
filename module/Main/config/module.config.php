<?php
return array (
		'router' => array (
				'routes' => array (
						'home' => array (
								'type' => 'Zend\Mvc\Router\Http\Literal',
								'options' => array (
										'route' => '/',
										'defaults' => array (
												'controller' => 'Main\Controller\Main',
												'action' => 'index' 
										) 
								) 
						),
						'about' => array (
								'type' => 'Literal',
								'options' => array (
										'route' => '/about',
										'defaults' => array (
												'__NAMESPACE__' => 'Main\Controller',
												'controller' => 'Main',
												'action' => 'about' 
										) 
								) 
						),
						'faq' => array (
								'type' => 'Literal',
								'options' => array (
										'route' => '/faq',
										'defaults' => array (
												'__NAMESPACE__' => 'Main\Controller',
												'controller' => 'Main',
												'action' => 'faq' 
										) 
								) 
						),		
						'resetPassword' => array(
								'type'    => 'Segment',
								'options' => array(
										// Change this to something specific to your module
										'route'    => '/reset[/:action]',
										'defaults' => array(
												// Change this value to reflect the namespace in which
												// the controllers for your module are found
												'__NAMESPACE__' => 'Main\Controller',
												'controller'    => 'Main',
												//'action'        => 'resetPassword',
										),
								),
						),		
						'userConfigurations' => array(
								'type'    => 'Segment',
								'options' => array(
										// Change this to something specific to your module
										'route'    => '/configurations[/:action]',
										'defaults' => array(
												// Change this value to reflect the namespace in which
												// the controllers for your module are found
												'__NAMESPACE__' => 'Main\Controller',
												'controller'    => 'Main',
												'action' => 'userConfigurations' 
										),
								),
						)
				) 
		),
		'service_manager' => array (
				'abstract_factories' => array (
						'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
						'Zend\Log\LoggerAbstractServiceFactory' 
				),
				'aliases' => array (
						'translator' => 'MvcTranslator' 
				) 
		),
		'translator' => array (
				'locale' => 'en_US',
				'translation_file_patterns' => array (
						array (
								'type' => 'gettext',
								'base_dir' => __DIR__ . '/../language',
								'pattern' => '%s.mo' 
						) 
				) 
		),
		'controllers' => array (
				'invokables' => array (
						'Main\Controller\Main' => 'Main\Controller\MainController' 
				) 
		),
		
		'view_manager' => array (
				'display_not_found_reason' => true,
				'display_exceptions' => true,
				'doctype' => 'HTML5',
				'not_found_template' => 'error/404',
				'exception_template' => 'error/index',
				'template_map' => array (
						'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
						'error/404' => __DIR__ . '/../view/error/404.phtml',
						'error/index' => __DIR__ . '/../view/error/index.phtml' 
				),
				
				'template_path_stack' => array (
						__DIR__ . '/../view' 
				) 
		) 
);
