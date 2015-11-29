<?php

/**
 * Base controller class for all derived controllers
 */
class BaseController
{
    /**
     * Arguments to be passed to template later
     * @var stdClass
     */
    protected $args;

    /**
     * Users model
     * @var UsersModel
     */
    private $usersModel = null;
    /**
     * Contributions model
     * @var ContributionsModel
     */
    private $contributionsModel = null;

    /**
     * Logged in user record
     * @var array
     */
    protected $loggedUser = null;

    /**
     * Flag for template subsystem to enable template rendering
     * @var boolean
     */
    protected $enableRender = true;

    public function __construct($templateArgs = null)
    {
        if ($templateArgs)
            $this->args = $templateArgs;
        else
            $this->args = new stdClass();

        SessionHolder::start();

        $userId = SessionHolder::getLoggedUserId();
        if ($userId)
        {
            $this->loggedUser = $this->users()->getUserById($userId);
            $this->args->user = $this->loggedUser;
        }

        $this->args->userRoles = UserRoles::getRoleTranslations();
    }

    /**
     * Startup method - may be overwritten by child controllers
     * @return boolean
     */
    public function startup()
    {
        return true;
    }

    /**
     * Actions before proceeding with render
     */
    public function beforeRender()
    {
        // widthdraw flash messages before rendering
        $this->args->flashmessage = $this->withdrawFlashMessage();
    }

    /**
     * Retrieves template arguments
     * @return stdClass
     */
    public function getTemplateArgs()
    {
        return $this->args;
    }

    /**
     * Hooks our extensions to Twig environment
     * @param Twig_Environment $twigEnvironment
     */
    public function hookTemplateExtensions($twigEnvironment)
    {
        // hook link-building function
        $function = new Twig_SimpleFunction('link', function($uri) {
            return $this->buildLink($uri);
        });
        $twigEnvironment->addFunction($function);
    }

    /**
     * Enables / disables rendering of template
     * @param type $state
     */
    public function setRenderEnabled($state = true)
    {
        $this->enableRender = $state;
    }

    /**
     * Returns render enable flag
     * @return boolean
     */
    public function isRenderingEnabled()
    {
        return $this->enableRender;
    }

    /**************************
     * Generic functions
     **************************/

    /**
     * Echoes JSON-encoded message
     * @param array $data
     * @param string $status
     * @param string $redirect
     */
    public function sendResponseJSON($data = array(), $status = AjaxResponseCodes::OK, $redirect = null)
    {
        header('Content-Type: application/json');
        echo json_encode(array(
            'status' => $status,
            'redirect' => $redirect ? $this->buildLink($redirect) : null,
            'data' => $data
        ));
    }

    /**
     * Redirects user to another page within this portal
     * @param string $uri
     */
    public function sendRedirect($uri)
    {
        // disable rendering when redirecting away
        $this->setRenderEnabled(false);

        header('Location: '.$this->buildLink($uri));
    }

    /**
     * Builds link based on base_url and input path
     * @param string $uri
     * @return string
     */
    public function buildLink($uri)
    {
        return $this->args->base_url.(($uri[0] == '/') ? $uri : '/'.$uri);
    }

    /**
     * Creates flash message to be displayed at next page load
     * @param string $title
     * @param string $type
     */
    public function flashMessage($title, $type)
    {
        SessionHolder::setVariable('flashmessage', 'msg', $title.';;;'.$type);
    }

    /**
     * Withdraws flash message from session storage, if any
     * @return array
     */
    public function withdrawFlashMessage()
    {
        $val = SessionHolder::getVariable('flashmessage', 'msg');
        if (!$val)
            return null;
        $expl = explode(';;;', $val);
        if (count($expl) !== 2)
            return null;

        SessionHolder::setVariable('flashmessage', 'msg', null);

        return $expl;
    }

    /**************************
     * Model factories part
     **************************/

    /**
     * Factory method for UsersModel
     * @return UsersModel
     */
    protected function users()
    {
        if ($this->usersModel === null)
            $this->usersModel = new UsersModel();
        return $this->usersModel;
    }

    /**
     * Factory method for ContributionsModel
     * @return ContributionsModel
     */
    protected function contributions()
    {
        if ($this->contributionsModel === null)
            $this->contributionsModel = new ContributionsModel();
        return $this->contributionsModel;
    }

    /**************************
     * Generic handlers
     **************************/

    /**
     * Logout action - logs user out and redirects to sign in page
     */
    public function actionLogout()
    {
        SessionHolder::destroy();

        $this->setRenderEnabled(false);
        $this->sendRedirect('/sign/in');
    }
}
