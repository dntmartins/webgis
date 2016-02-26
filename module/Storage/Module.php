<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/Storage for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Storage;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Storage\Service\ShapefileService;
use Storage\Service\PhotoService;
use Storage\Service\UserService;
use Storage\Service\ProjectService;
use Storage\Service\PhotoAlbumService;
use Storage\Service\AccessService;
use Storage\Service\RoleService;
use Storage\Service\ResourcesService;
use Storage\Service\PrivilegeService;
use Storage\Service\RolePrivilegeService;
use Storage\Service\RequisitionService;
use Storage\Service\AttachmentService;
use Storage\Service\ReportService;
use Storage\Service\RequisitionUserService;
use Storage\Service\EmailService;
use Storage\Service\SldService;
use Storage\Service\LayerService;
use Storage\Service\DataSourceService;
use Storage\Service\GeoServerService;
use Storage\Service\GeoServerRESTService;

class Module implements AutoloaderProviderInterface
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
		    // if we're in a namespace deeper than one level we need to fix the \ in the path
                    __NAMESPACE__ => __DIR__ . '/src/' . str_replace('\\', '/' , __NAMESPACE__),
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $e)
    {
        // You may not need to do this if you're doing it elsewhere in your
        // application
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }
    
    public function getServiceConfig() {
    
    	return array(
    			'factories' => array(
    					'Storage\Service\ShapefileService' => function($service) {
    						return new ShapefileService($service->get('Doctrine\ORM\EntityManager'));
    					},
    					'Storage\Service\UserService' => function($service) {
    						return new UserService($service->get('Doctrine\ORM\EntityManager'));
    					},
    					'Storage\Service\ProjectService' => function($service) {
    						return new ProjectService($service->get('Doctrine\ORM\EntityManager'));
    					},
    					'Storage\Service\AccessService' => function($service) {
    						return new AccessService($service->get('Doctrine\ORM\EntityManager'));
    					},
    					'Storage\Service\RoleService' => function($service) {
    						return new RoleService($service->get('Doctrine\ORM\EntityManager'));
    					},
    					'Storage\Service\ResourcesService' => function($service) {
    					   return new ResourcesService($service->get('Doctrine\ORM\EntityManager'));
    					},
    					'Storage\Service\PrivilegeService' => function($service) {
    					   return new PrivilegeService($service->get('Doctrine\ORM\EntityManager'));
    					},
    					'Storage\Service\RolePrivilegeService' => function($service) {
    					   return new RolePrivilegeService($service->get('Doctrine\ORM\EntityManager'));
    					},
    					'Storage\Service\EmailService' => function($service) {
    						return new EmailService();
    					},
    					'Storage\Service\SldService' => function($service) {
    						return new SldService($service->get('Doctrine\ORM\EntityManager'));
    					},
    					'Storage\Service\LayerService' => function($service) {
    						return new LayerService($service->get('Doctrine\ORM\EntityManager'));
    					},
    					'Storage\Service\DataSourceService' => function($service) {
    						return new DataSourceService($service->get('Doctrine\ORM\EntityManager'));
    					},
    					'Storage\Service\GeoServerRESTService' => function($service) {
    						return new GeoServerRESTService($service->get('Doctrine\ORM\EntityManager'));
    					},
    					'Storage\Service\GeoServerService' => function($service) {
    						return new GeoServerService($service->get('Doctrine\ORM\EntityManager'));
    					},
    			),
    	);
    }
}