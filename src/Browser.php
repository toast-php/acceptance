<?php

namespace Toast\Acceptance;

use JonnyW\PhantomJs\Client;

class Browser
{
    public static $sessionname = 'PHPSESSID';
    private $sessionid = null;

    public function __construct($sessionid = null)
    {
        if (isset($sessionid)) {
            if ($sessionid === true) {
                $sessionid = session_id();
            }
            $this->sessionid = $sessionid;
        }
    }

    public function get($url)
    {
        list($client, $request, $response) = $this->initializeRequest();
        $request->setMethod('GET');
        $request->setUrl($url);
        $client->send($request, $response);
        return $response;
    }

    public function post($url, array $data)
    {
        list($client, $request, $response) = $this->initializeRequest();
        $request->setMethod('POST');
        $request->setUrl($url);
        $request->setRequestData($data);
        $client->send($request, $response);
        return $response;
    }

    private function initializeRequest()
    {
        $client = Client::getInstance();
        $client->getEngine()->addOption('--ssl-protocol=any');
        $client->getEngine()->addOption('--ignore-ssl-errors=true');
        $client->getEngine()->addOption('--web-security=false');
        $client->getEngine()->setPath(getcwd().'/vendor/bin/phantomjs');
        $cookies = sys_get_temp_dir().'/'.getenv("TOAST_CLIENT");
        $client->getEngine()->addOption("--cookies-file=$cookies");
        $request = $client->getMessageFactory()->createRequest();
        $response = $client->getMessageFactory()->createResponse();
        $client->getProcedureCompiler()->disableCache();
        $request->addHeader('Cookie', self::$sessionname.'='.$this->sessionid);
        $request->addHeader('Toast', getenv("TOAST"));
        $request->addHeader('Toast-Client', getenv("TOAST_CLIENT"));
        $request->addHeader('User-Agent', 'Toast/PhantomJs headless');
        $request->setTimeout(5000);
        return [$client, $request, $response];
    }
}

