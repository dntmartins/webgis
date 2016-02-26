<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/Storage for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Storage\Controller;

use Zend\Validator\File\Upload;
use Storage\Module;
use Storage\Service\DocumentService;
use Storage\Service\UserService;
use Storage\Service\CleanJsonSerializerService;
use Zend\Form\Element\DateTimeLocal;
use Doctrine\DBAL\Types\DateType;
use Zend\Config\Config;
use Zend\Session\Container;
use Storage\Entity\Document;
use Storage\Entity\User;
use Storage\Entity\Project;
use Zend\View\Helper\ViewModel;
use Storage\Entity\Photo;
use Main\Controller\MainController;

class StorageController extends MainController{}