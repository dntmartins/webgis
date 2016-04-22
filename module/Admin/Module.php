<?php
namespace Admin;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ModuleManager\ModuleManager;
use Zend\Mvc\Controller\Plugin\Layout;

class Module
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
    public function getServiceConfig() {
    	return array (
    			'factories' => array (
    					'Auth\Service\Role' => function ($sm) {
    						return new Service\Role ( $sm->get ( 'Doctrine\ORM\Entitymanager' ) );
    					},
    					'Auth\Service\Resource' => function ($sm) {
    						return new Service\Resource ( $sm->get ( 'Doctrine\ORM\Entitymanager' ) );
    					},
    					'Auth\Service\Privilege' => function ($sm) {
    						return new Service\Privilege ( $sm->get ( 'Doctrine\ORM\Entitymanager' ) );
    					},
    					'Admin\Permissions\Acl' => function ($sm) {
    						$em = $sm->get ( 'Doctrine\ORM\EntityManager' );
    							
    						$repoRole = $em->getRepository ( "Storage\Entity\Role" );
    						$roles = $repoRole->findAll ();
    							
    						$repoResource = $em->getRepository ( "Storage\Entity\Resource" );
    						$resources = $repoResource->findAll ();
    							
    						$repoPrivilege = $em->getRepository ( "Storage\Entity\Privilege" );
    						$privileges = $repoPrivilege->findAll ();
    							
    						$repoRolePrivilege = $em->getRepository ( "Storage\Entity\RolePrivilege" );
    						$rolePrivileges = $repoRolePrivilege->findAll ();
    							
    						return new Permissions\Acl ( $roles, $resources, $privileges, $rolePrivileges);
    					}
    			)
    	);
    }
    public function init(ModuleManager $manager) {
    	$events = $manager->getEventManager ();
    	$sharedEvents = $events->getSharedManager ();
    	$sharedEvents->attach ( __NAMESPACE__, 'dispatch', function ($e) {
    		$controller = $e->getTarget ();
    		if (get_class ( $controller ) == 'Admin\Controller\UserController') {
    			$controller->layout ( 'Admin/layout' );
    		}
    		if (get_class ( $controller ) == 'Admin\Controller\RoleController') {
    			$controller->layout ( 'Admin/layout' );
    		}
    		if (get_class ( $controller ) == 'Admin\Controller\ProjectController') {
    			$controller->layout ( 'Admin/layout' );
    		}
    	}, 100 );
    }
}