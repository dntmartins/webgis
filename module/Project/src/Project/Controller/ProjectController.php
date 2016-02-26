<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Project\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Storage\Entity\User as AuthUser;
use Zend\Session\Container;
use Main\Controller\MainController;

class ProjectController extends MainController
{
    public function indexAction()
    {
        try {
        	$serviceLocator=$this->getServiceLocator();
        	$prjService=$serviceLocator->get('Storage\Service\ProjectService');
        	$accessService = $serviceLocator->get ( 'Storage\Service\AccessService' );
        	
        	$formData = $this->getFormData();
        	$id = $formData['id'];
        	$project = $prjService->getById($id);
            if(!isset($project))
                return $this->showMessage('Projeto não encontrado', 'main-error', '/');
            return new ViewModel(
                array(
                    'project' => $project
                )
            );
        }catch (\Exception $e){
            return null;
        }
    }
}