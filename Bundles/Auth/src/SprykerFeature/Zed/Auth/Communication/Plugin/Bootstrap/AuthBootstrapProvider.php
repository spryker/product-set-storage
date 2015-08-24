<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace SprykerFeature\Zed\Auth\Communication\Plugin\Bootstrap;

use SprykerEngine\Zed\Kernel\Communication\AbstractPlugin;
use Silex\Application;
use Silex\ServiceProviderInterface;
use SprykerFeature\Zed\Auth\Business\AuthFacade;
use SprykerFeature\Zed\Auth\Communication\AuthDependencyContainer;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method AuthDependencyContainer getDependencyContainer()
 * @method AuthFacade getFacade()
 */
class AuthBootstrapProvider extends AbstractPlugin implements ServiceProviderInterface
{

    /**
     * @param Application $app
     */
    public function register(Application $app)
    {

    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
        $bundleSettings = $this->getDependencyContainer()->getConfig();
        $facadeAuth = $this->getFacade();

        $app->before(function (Request $request) use ($app, $facadeAuth, $bundleSettings) {
            $bundle = $request->attributes->get('module');
            $controller = $request->attributes->get('controller');
            $action = $request->attributes->get('action');

            if ($facadeAuth->isIgnorable($bundle, $controller, $action)) {
                return true;
            }

            $token = null;

            if ($facadeAuth->hasCurrentUser()) {
                $token = $facadeAuth->getCurrentUserToken();
            }

            if ($request->headers->get('Auth-Token')) {
                $token = $request->headers->get('Auth-Token');
            }

            if (!$facadeAuth->isAuthorized($token)) {
                return $app->redirect($bundleSettings->getLoginPageUrl());
            }
        });
    }

}
