<?php

use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Pimple\Container;
use Phroute\Phroute\RouteCollector;
use NShiell\MastermindNotes\Dav\Go as DavGo;
use NShiell\MastermindNotes\CalDAV\Backend\MastermindNotes as CalDAVBackendMastermindNotes;

require_once (__DIR__ . '/vendor/autoload.php');

date_default_timezone_set('Europe/London');

function go($hasRun): bool {
    if ($hasRun) {
        return true;
    }
    session_start();

    // Load config
    $dotenv = new \Dotenv\Dotenv(__DIR__ . '/');
    $dotenv->load();

    $container = new Container();

    $container[DavGo::class] = function ($c) {
        return new DavGo(
            new CalDAVBackendMastermindNotes(
                $c[DocumentManager::class]
                    ->getRepository(\NShiell\MastermindNotes\Entity\Note::class),
                getenv('USERNAME')
            ),
            $c['Authentication']
        );
    };

    $container[DocumentManager::class] = function ($c) {
        // Stuff for entitites
        AnnotationDriver::registerAnnotationClasses();
        $dir = __DIR__;
        $config = new Configuration();
        $config->setProxyDir($dir . '/Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setHydratorDir($dir . '/docker/Hydrators');
        $config->setHydratorNamespace('Hydrators');
        $config->setDefaultDB('mastermindNotes');
        $config->setMetadataDriverImpl(AnnotationDriver::create($dir . '/Entity'));

        $options = [];
        $username = getenv('DATABASE_USERNAME');
        if ($username) {
            $options['username'] = $username;
        }

        $password = getenv('DATABASE_PASSWORD');
        if ($password) {
            $options['password'] = $password;
        }

        $host = getenv('DATABASE_HOST');
        if (!$host) {
            $host = 'localhost';
        }

        $port = (int) getenv('DATABASE_PORT');
        if (!$port) {
            $port = 27017;
        }
    
        $connection = new Connection('mongodb://' . $host . ':' . $port, $options);
    
        return DocumentManager::create($connection, $config);
    };
        
    $container['Authentication'] = function () {
        return new class {
            public function getUsername(): ?string
            {
                return getenv('USERNAME');
            }

            public function authenticate(array $post = null) {
                if (isset ($_SESSION['authorized']) && $_SESSION['authorized']) {
                    return true;
                }

                if ($post === null) {
                    $post = $_POST;
                }

                if ($post['username'] !== getenv('USERNAME')) {
                  return false;
                }
    
                if ($post['password'] !== getenv('PASSWORD')) {
                    return false;
                }

                $_SESSION['authorized'] = true;
                return true;
            }

            public function isAuthenticated() {
                if (!empty ($_SESSION['authorized'])) {
                    return true;
                }
    
                if (empty ($_SERVER['PHP_AUTH_USER'])) {
                    return false;
                }
    
                if ($_SERVER['PHP_AUTH_USER'] !== getenv('USERNAME')) {
                    return false;
                }
    
                if ($_SERVER['PHP_AUTH_PW'] !== getenv('PASSWORD')) {
                    return false;
                }
    
                return true;
            }
        };
    };

    $container['Redirector'] = function ($c) {
        return new class {
            public function redirect($uri) {
                $protocol = strtolower(
                    substr($_SERVER["SERVER_PROTOCOL"], 0, strpos(
                        $_SERVER["SERVER_PROTOCOL"],'/'
                    ))
                );
                $url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $uri;
                header('Location: ' . $url);
            }
        };
    };

    $container[\Phroute\Phroute\Dispatcher::class] = function ($c) {
        $router = new RouteCollector();
        
        $router->filter('auth', function () use ($c) {
            $isAuthenticated = $c['Authentication']->isAuthenticated();
    
            if (!$isAuthenticated) {
                //$c['Redirector']->redirect('/login');
                die('not logged in');
                
                return false;
            }
        });

        $router->filter('notAuth', function () use ($c) {
            $isAuthenticated = $c['Authentication']->isAuthenticated();
        
            if ($isAuthenticated) {
                //$c['Redirector']->redirect('/');
                die('already auth');
                
                return false;
            }
        });

        // cheater
        $router->any('/calendars/', function () use ($c) {
            return $c[DavGo::class]->go();
        });
        $router->any('/principals/', function () use ($c) {
            return $c[DavGo::class]->go();
        });

        $router->get('/', function () {
            $hasRun = true;
            require (__DIR__ . '/www/index.php');
        });

        $router->group(['before' => 'auth'], function ($router) use ($c) {
            $router->get('/note', function () use ($c) {
                $notes = $c[DocumentManager::class]->createQueryBuilder(\NShiell\MastermindNotes\Entity\Note::class)
                    ->getQuery()
                    ->execute();
                return json_encode(array_values($notes->toArray()));
            });
        });

        $router->group(['before' => 'notAuth'], function ($router) use ($c) {
            $router->post('/authentication', function () use ($c) {
                //header('application/json; charset=utf8');
                return json_encode($c['Authentication']->authenticate());
            });
        });

        return new \Phroute\Phroute\Dispatcher($router->getData());
    };

    $calendarPrefix = '/calendars/';
    $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (substr($url, 0, strlen($calendarPrefix)) == $calendarPrefix) {
        $url = $calendarPrefix;
    }
    $principalsPrefix = '/principals/';
    if (substr($url, 0, strlen($principalsPrefix)) == $principalsPrefix) {
        $url = $principalsPrefix;
    }
    echo $container[\Phroute\Phroute\Dispatcher::class]->dispatch(
        $_SERVER['REQUEST_METHOD'],
        $url
    );
    die;
}
