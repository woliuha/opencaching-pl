<?php

namespace Controllers;

use lib\Objects\ApplicationContainer;
use lib\Objects\User\User;
use lib\Objects\OcConfig\OcConfig;
use Utils\View\View;
use Utils\Uri\Uri;

require_once(__DIR__.'/../lib/common.inc.php');

abstract class BaseController
{
    /** @var View $view */
    protected $view = null;

    /** @var ApplicationContainer $applicationContainer */
    private $applicationContainer = null;

    /** @var User */
    protected $loggedUser = null;

    /** @var OcConfig $ocConfig */
    protected $ocConfig = null;

    protected function __construct()
    {
        $this->view = tpl_getView();

        $this->applicationContainer = ApplicationContainer::Instance();
        $this->loggedUser = $this->applicationContainer->getLoggedUser();
        $this->ocConfig = $this->applicationContainer->getOcConfig();

        // there is no DB access init - DB operations should be performed in models/objects
    }

    abstract public function index(); //every Controller should have index method whoch should be call to handle requests

    protected function redirectToLoginPage()
    {
        $this->view->redirect(
            Uri::setOrReplaceParamValue('target', Uri::getCurrentUri(), '/login.php'));
        exit;
    }

    protected function isUserLogged(){
        return !is_null($this->loggedUser);
    }

    protected function ajaxSuccessResponse($message=null){
        $response = [
            'status' => 'OK'
        ];

        if(!is_null($message)){
            $response['message'] = $message;
        }

        header('Content-Type: application/json');
        print (json_encode($response));

        exit;
    }

    protected function ajaxErrorResponse($message=null, $statusCode=null){
        $response = [
            'status' => 'ERROR'
        ];

        if(!is_null($message)){
            $response['message'] = $message;
        }

        if(is_null($statusCode)){
            $statusCode = 500;
        }

        http_response_code($statusCode);

        header('Content-Type: application/json; charset=UTF-8');
        print json_encode(
                array(
                'message' => $message,
                'status' => 'ERROR')
        );

        exit;
    }

}
