<?php

// define some path constants
define('BASE_DIR', __DIR__);
define('UPLOADS_DIR', BASE_DIR.'/uploads/');

// get script name, request URI, so we can determine, which controller+view to use
$scrname = str_replace('index.php', '', $_SERVER["SCRIPT_FILENAME"]);
$requesturi = $_SERVER["REQUEST_URI"];
$baseurl = null;

// match controller+view strings
for ($i = strlen($requesturi)-1; $i > 0; $i--)
{
    if ($requesturi[$i] == '/')
    {
        $baseurl = substr($requesturi, 0, $i);
        $pp = strpos($scrname, $baseurl);
        if ($pp > 0)
        {
            $reqbase = substr($requesturi, $i);
            break;
        }
    }
}

$controller = null;
$view = null;

// if no stuff at all, go to homepage
if ($reqbase === '/')
{
    $controller = 'homepage';
    $view = 'homepage';
}
else
{
    $explbase = explode('/', $reqbase);
    // both specified
    if (count($explbase) > 2)
    {
        $controller = $explbase[1];
        $view = $explbase[2];
    }
    // one specified - view has same name as controller
    else if (count($explbase) === 2)
    {
        $controller = $explbase[1];
        $view = $explbase[1];
    }

    // no stuff at all, raise error
    if (!$controller || !$view || strlen($controller) === 0 || strlen($view) === 0)
    {
        $controller = 'error';
        $view = 'error404';
    }

    // here handle exceptions for routing system

    if ($controller === 'system')
        $controller = 'base';
}

// require config and vendor libs

if (!file_exists(__DIR__.'/app/config/config.inc.php'))
{
    die('System jeste nebyl nainstalovan. Pro instalaci prejdete na <a href="'.$baseurl.'/install.php" title="Instalace systemu">instalacni skript</a>');
}

if (!file_exists(__DIR__.'/app/vendor/autoload.php'))
{
    die('Nebylo mozne naleznout vendor knihovny. Presunte se do slozky app/ a spustte prikaz "php composer.phar install --no-dev"');
}

require __DIR__.'/app/config/config.inc.php';
require __DIR__.'/app/vendor/autoload.php';

// use custom class loader for lazy class loading
function class_loader($class_name)
{
    $subdirs = array('/app/controllers/', '/app/models/', '/app/models/enums/', '/app/helpers/');

    foreach ($subdirs as $subdir)
    {
        if (file_exists(__DIR__.$subdir.$class_name.'.php'))
        {
            include __DIR__.$subdir.$class_name.'.php';
            break;
        }
    }
}
// register class loader
spl_autoload_register('class_loader');

// determine, if request comes from AJAX call
$isAjax = false;
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtoupper($_SERVER['HTTP_X_REQUESTED_WITH']) == 'XMLHTTPREQUEST')
    $isAjax = true;

// build controller class name
$controllerClassName = ucfirst($controller).'Controller';

// if controller does not exist, use error controller
if (!file_exists(__DIR__.'/app/controllers/'.$controllerClassName.'.php'))
{
    $controller = 'error';
    $controllerClassName = 'ErrorController';
    $view = 'error404';
}

$templateArgs = new stdClass();

// build implicit template arguments
$templateArgs->base_url = $baseurl;
$templateArgs->request_uri = $reqbase;
$templateArgs->controller_name = $controller;
$templateArgs->view_name = $view;
$templateArgs->is_ajax = $isAjax;
$templateArgs->page_title = PAGE_TITLE;

// create controller object
$controllerObject = new $controllerClassName($templateArgs);

// build method name
$actionMethodName = 'action'.ucfirst($view);

// do not render when content is being requested using AJAX call
if ($isAjax)
{
    $controllerObject->setRenderEnabled(false);
    // append method after action, when using AJAX
    if ($_SERVER['REQUEST_METHOD'] !== 'GET')
    {
        $view .= strtoupper($_SERVER['REQUEST_METHOD']);
        $actionMethodName = 'action'.ucfirst($view);
    }
}

// if method does not exist, and view as well, raise error
if (!method_exists($controllerObject, $actionMethodName) && !file_exists(__DIR__.'/app/views/'.ucfirst($controller).'/'.$view.'.twig'))
{
    $controller = 'error';
    $controllerClassName = 'ErrorController';
    $controllerObject = new ErrorController($templateArgs);
    $templateArgs->controller_name = 'error';
    $templateArgs->view_name = 'error404';
    $view = 'error404';
}

// startup controller
$startupResult = $controllerObject->startup();

// call action method, if exists
if ($startupResult && method_exists($controllerObject, $actionMethodName))
    $controllerObject->{$actionMethodName}();

// render view, if rendering is enabled
if ($startupResult && $controllerObject->isRenderingEnabled())
{
    $loader = new Twig_Loader_Filesystem(__DIR__.'/app/views/');
    $twig = new Twig_Environment($loader);

    $controllerObject->hookTemplateExtensions($twig);

    $controllerObject->beforeRender();

    echo $twig->render(ucfirst($controller).'/'.$view.'.twig', (array)$controllerObject->getTemplateArgs());
}
