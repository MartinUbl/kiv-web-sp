<?php

define('BASE_DIR', __DIR__);
define('UPLOADS_DIR', BASE_DIR.'/uploads/');

$scrname = str_replace('index.php', '', $_SERVER["SCRIPT_FILENAME"]);
$requesturi = $_SERVER["REQUEST_URI"];
$baseurl = null;

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

if ($reqbase === '/')
{
    $controller = 'homepage';
    $view = 'homepage';
}
else
{
    $explbase = explode('/', $reqbase);
    if (count($explbase) > 2)
    {
        $controller = $explbase[1];
        $view = $explbase[2];
    }
    else if (count($explbase) === 2)
    {
        $controller = $explbase[1];
        $view = $explbase[1];
    }

    if (!$controller || !$view || strlen($controller) === 0 || strlen($view) === 0)
    {
        $controller = 'error';
        $view = 'error404';
    }

    // here handle exceptions for routing system

    if ($controller === 'system')
        $controller = 'base';
}

require __DIR__.'/app/config/config.inc.php';
require __DIR__.'/app/vendor/autoload.php';

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
spl_autoload_register('class_loader');

$isAjax = false;
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtoupper($_SERVER['HTTP_X_REQUESTED_WITH']) == 'XMLHTTPREQUEST')
{
    $isAjax = true;
}

$controllerClassName = ucfirst($controller).'Controller';

if (!file_exists(__DIR__.'/app/controllers/'.$controllerClassName.'.php'))
{
    $controllerClassName = 'ErrorController';
    $view = 'error404';
}

$templateArgs = new stdClass();

$templateArgs->base_url = $baseurl;
$templateArgs->request_uri = $reqbase;
$templateArgs->controller_name = $controller;
$templateArgs->view_name = $view;
$templateArgs->is_ajax = $isAjax;
$templateArgs->page_title = PAGE_TITLE;

$controllerObject = new $controllerClassName($templateArgs);

$actionMethodName = 'action'.ucfirst($view);

// do not render when content is being requested using AJAX call
if ($isAjax)
{
    $controllerObject->setRenderEnabled(false);
    if ($_SERVER['REQUEST_METHOD'] !== 'GET')
    {
        $view .= strtoupper($_SERVER['REQUEST_METHOD']);
        $actionMethodName = 'action'.ucfirst($view);
    }
}

if (!method_exists($controllerObject, $actionMethodName) && !file_exists(__DIR__.'/app/views/'.ucfirst($controller).'/'.$view.'.twig'))
{
    $controllerClassName = 'ErrorController';
    $controllerObject = new ErrorController($templateArgs);
    $templateArgs->controller_name = 'error';
    $templateArgs->view_name = 'error404';
    $view = 'error404';
}

$startupResult = $controllerObject->startup();

if ($startupResult && method_exists($controllerObject, $actionMethodName))
    $controllerObject->{$actionMethodName}();

if ($startupResult && $controllerObject->isRenderingEnabled())
{
    $loader = new Twig_Loader_Filesystem(__DIR__.'/app/views/');
    $twig = new Twig_Environment($loader);

    $controllerObject->hookTemplateExtensions($twig);

    echo $twig->render(ucfirst($controller).'/'.$view.'.twig', (array)$controllerObject->getTemplateArgs());
}
