<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
    'router' => array(
        'routes' => array(
            'projeto' => array(
        		'type'    => 'Segment',
        		'options' => array(
        			'route'    => '/projeto[/:action]',
        			'defaults' => array(
        				'__NAMESPACE__' => 'Project\Controller',
        				'controller'    => 'Project',
       					'action'        => 'index',
        			),
        		),
        	),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Project\Controller\Project' => 'Project\Controller\ProjectController'
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'project' => __DIR__ . '/../view'
        ),
    ),
    'strategies' => array(
        'ViewJsonStrategy'
    )
);
