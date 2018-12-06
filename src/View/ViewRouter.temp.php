<?php
namespace __CLASS_NAMESPACE__;

class ViewRouter {

    public static function process($uri, $httpMethod)
    {
        $dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
__ROUTES__
        });

        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        $uri = rawurldecode($uri);
        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                // 404 Not Found
                header('HTTP/1.1 404 Not Found');
            break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // 405 Method Not Allowed
                header('HTTP/1.1 405 Method Not Allowed');
            break;

            case \FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars    = $routeInfo[2];

                if ($handler[0] === 'self::render') {
                    \PerspectiveSimulator\View\View::render($handler[1]);
                } else {
                    call_user_func_array($handler, $vars);
                }
            break;
        }//end switch

    }//end process


}//end class
