<?php

namespace Drupal\Core;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader as DICLoader;


class DrupalKernal implements HttpKernelInterface
{
  /**
   * The class loader object.
   *
   * @var \Composer\Autoload\ClassLoader
   */
  protected $classLoader;
  protected $moduleList;
  protected $container;

  public function __construct($classLoader)
  {
    $this->classLoader = $classLoader;
    $this->root = static::guessApplicationRoot();
  }

  protected static function guessApplicationRoot() {
    // Determine the application root by:
    // - Removing the namespace directories from the path.
    // - Getting the path to the directory two levels up from the path
    //   determined in the previous step.
    return dirname(dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__))));
  }

  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
  {
    try {
      $response = $this->boot();
    }
    catch (\Exception $e) {
      if ($catch === FALSE) {
        throw $e;
      }
    }

    return $response;
  }

  public function boot() {
    $this->container = new DependencyInjection\ContainerBuilder();
    $request = Request::createFromGlobals();

    $this->initializeContainer();
    $configDirectories = array(__DIR__);
    $fileLocator = new FileLocator($configDirectories);
    $dic = new DICLoader($this->container, $fileLocator);
    $dic->load($this->root . '/core/services.yml');
    $dic->load($this->root . '/core/core.services.yml');
    $response = $this->container->get('http_kernel')->handle($request);
    return $response;
  }

  public function initializeContainer() {
    $finder1 = new Finder();
    $modules = ['modules/'];
    foreach ($modules as $module) {
      $finder1->files()->name('*.info.yml')->in($module);
      $collection = new RouteCollection();
      $yamlParser = new Parser();
      foreach ($finder1 as $file) {
        $path = $module . $file->getRelativePath();
        $module_name = str_replace('.info.yml', '', $file->getFilename());
        $modules[$module_name]['path'] = $path;
        $servicefilename = $path . "/$module_name.services.yml";
        if (file_exists($servicefilename)) {
          $this->moduleList[$module_name]['service'] = $servicefilename;
        }
        $routefilename = $path . "/$module_name.routing.yml";
        if (file_exists($routefilename)) {
          $this->moduleList[$module_name]['route'] = $routefilename;
          $parsedConfig = $yamlParser->parseFile($routefilename, Yaml::PARSE_CONSTANT);
          if ($parsedConfig) {
            $this->classLoader->addPsr4('Drupal\\' . $module_name . '\\', $path . '/src');

            foreach ($parsedConfig as $name => $config) {
              $route = new Route($config['path'], $config['defaults']);
              $collection->add($name, $route);
            }
          }
        }
      }
      $this->container->setParameter('routes', $collection);
    }
  }
}