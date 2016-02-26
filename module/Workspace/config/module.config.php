<?php
namespace Workspace;

return array(
    'controllers' => array(
        'invokables' => array(
            'Workspace\Controller\Workspace' => 'Workspace\Controller\WorkspaceController',
            'Workspace\Controller\RevertDoc' => 'Workspace\Controller\RevertDocController',
        	'Workspace\Controller\Report' => 'Workspace\Controller\ReportController',
        		
        ),
    ),
    'router' => array(
        'routes' => array(
            'workspace' => array(
                'type'    => 'Segment',
                'options' => array(
                    // Change this to something specific to your module
                    'route'    => '/workspace[/:action]',
                    'defaults' => array(
                        // Change this value to reflect the namespace in which
                        // the controllers for your module are found
                        '__NAMESPACE__' => 'Workspace\Controller',
                        'controller'    => 'Workspace',
                        'action'        => 'index',
                    ),
                ),
            ),
    		'revert' => array(
				'type'    => 'Segment',
				'options' => array(
						// Change this to something specific to your module
						'route'    => '/revert[/:action]',
						'defaults' => array(
								// Change this value to reflect the namespace in which
								// the controllers for your module are found
								'__NAMESPACE__' => 'Workspace\Controller',
								'controller'    => 'RevertDoc',
								'action'        => 'index',
						),
				),
    		),
        	'report' => array(
        			'type'    => 'Segment',
        			'options' => array(
        					// Change this to something specific to your module
        					'route'    => '/report[/:action]',
        					'defaults' => array(
        							// Change this value to reflect the namespace in which
        							// the controllers for your module are found
        							'__NAMESPACE__' => 'Workspace\Controller',
        							'controller'    => 'Report',
        							'action'        => 'index',
        					),
        			),
        	),
        ),
    ),
    'view_manager' => array(
        'template_map' => array(
            'Workspace/carosel'  => __DIR__ . '/../view/partials/carosel.phtml',
        	'Workspace/manageReports'  => __DIR__ . '/../view/partials/manageReports.phtml',	
        	'Workspace/reports'  => __DIR__ . '/../view/partials/reports.phtml',
        	'Workspace/documents'  => __DIR__ . '/../view/partials/documents.phtml',
        	'Workspace/requisitions'  => __DIR__ . '/../view/partials/requisitions.phtml',	
            'layout/revertDoc' => __DIR__ . '/../view/layout/layout.phtml',
        		
        		
        		
        ),
        'template_path_stack' => array(
            'Workspace'          => __DIR__ . '/../view'
        ),
    )
);
