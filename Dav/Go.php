<?php

namespace NShiell\MastermindNotes\Dav;

use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Sabre\VObject;

use NShiell\MastermindNotes\DAVACL\PrincipalBackend\MastermindNotes as MastermindNotesPrincipalBackend;

use Sabre\CalDAV\Backend\BackendInterface as CalDAVBackendInterface;

class Go
{
    /** @var CalDAVBackendInterface */
    private $calendarBackend;

    /**
     * @var Some object that can authenticate
     * @todo type-hint an interface
     */
    private $authenticator;

    public function __construct(CalDAVBackendInterface $calendarBackend, $authenticator)
    {
        $this->calendarBackend = $calendarBackend;
        $this->authenticator = $authenticator;
    }

    public function go()
    {
        // settings
        //date_default_timezone_set('Europe/London');
        
        // If you want to run the SabreDAV server in a custom location (using mod_rewrite for instance)
        // You can override the baseUri here.
        // $baseUri = '/';
        
        /* Database */
        //$pdo = new PDO('sqlite:data/db.sqlite');
        //$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        //Mapping PHP errors to exceptions
        /*function exception_error_handler($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }
        set_error_handler("exception_error_handler");
        */
        // Files we need
        //require_once 'vendor/autoload.php';
        
        // Backends
        //$authBackend = new Sabre\DAV\Auth\Backend\PDO($pdo);
        $authBackend = new \Sabre\DAV\Auth\Backend\BasicCallBack(function ($username, $password) {
            //return ($username == 'admin' && $password == 'admin');
            return $this->authenticator->authenticate([
                'username' => $username,
                'password' => $password
            ]);
        });
        
        
        //$calendarBackend = new Sabre\CalDAV\Backend\PDO($pdo);

        // HERE!
        //$calendarBackend = new MastermindNotes;

        //$principalBackend = new Sabre\DAVACL\PrincipalBackend\PDO($pdo);
        $principalBackend = new MastermindNotesPrincipalBackend($this->authenticator);
        
        // Directory structure
        $tree = [
            new \Sabre\CalDAV\Principal\Collection($principalBackend),
            new \Sabre\CalDAV\CalendarRoot($principalBackend, $this->calendarBackend),
        ];
        
        $server = new \Sabre\DAV\Server($tree);
        
        /*if (isset($baseUri)) {
            $server->setBaseUri($baseUri);
        }*/

        /* Server Plugins */
        $authPlugin = new \Sabre\DAV\Auth\Plugin($authBackend);
        $server->addPlugin($authPlugin);
        
        $aclPlugin = new \Sabre\DAVACL\Plugin();
        $server->addPlugin($aclPlugin);
        
        /* CalDAV support */
        $caldavPlugin = new \Sabre\CalDAV\Plugin();
        $server->addPlugin($caldavPlugin);
        
        /* Calendar subscription support */
        $server->addPlugin(
            new \Sabre\CalDAV\Subscriptions\Plugin()
        );
        
        /* Calendar scheduling support */
        $server->addPlugin(
            new \Sabre\CalDAV\Schedule\Plugin()
        );
        
        /* WebDAV-Sync plugin */
        $server->addPlugin(new \Sabre\DAV\Sync\Plugin());
        
        /* CalDAV Sharing support */
        $server->addPlugin(new \Sabre\DAV\Sharing\Plugin());
        $server->addPlugin(new \Sabre\CalDAV\SharingPlugin());

        // Support for html frontend
        $browser = new \Sabre\DAV\Browser\Plugin();
        $server->addPlugin($browser);
        
        // And off we go!
        $server->exec();
    }
}