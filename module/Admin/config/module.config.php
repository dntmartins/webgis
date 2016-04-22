<?php

namespace Admin;

return array(
    'controllers' => array(
        'invokables' => array(
        	'Admin\Controller\Role' => 'Admin\Controller\RoleController',
        	'Admin\Controller\User' => 'Admin\Controller\UserController',
        	'Admin\Controller\Project ' => 'Admin\Controller\ProjectController',
        ),
    ),
    'router' => array(
        'routes' => array(
        	'user' => array(
        		'type'    => 'Segment',
        		'options' => array(
        			'route'    => '/user[/:action]',
        			'defaults' => array(
        				'__NAMESPACE__' => 'Admin\Controller',
        				'controller'    => 'User',
        				'action'        => 'index',
        			),
        		),
        	),
        	'role' => array(
        		'type'    => 'Segment',
        		'options' => array(
        			'route'    => '/role[/:action]',
        			'defaults' => array(
        				'__NAMESPACE__' => 'Admin\Controller',
        				'controller'    => 'Role',
       					'action'        => 'index',
        			),
        		),
        	),
        	'project' => array(
        		'type'    => 'Segment',
        		'options' => array(
        			'route'    => '/project[/:action]',
        			'defaults' => array(
        				'__NAMESPACE__' => 'Admin\Controller',
        				'controller'    => 'Project',
        				'action'        => 'index',
        			),
        		),
        	),
        ),
    ),
    'view_manager' => array(
    	'template_map' => array(
    			'Admin/layout' => __DIR__ . '/../view/admin/layout/layout.phtml',
    	),
        'template_path_stack' => array(
            'Admin' => __DIR__ . '/../view',
        ),
    )
);