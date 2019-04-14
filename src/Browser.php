<?php

namespace Toast\Acceptance;

use HeadlessChromium\BrowserFactory;

class Browser
{
    /** @var string */
    public static $sessionname = 'PHPSESSID';
    /** @var string */
    private $sessionid;
    /** @var string */
    private $command;

    public function __construct(string $command = 'chrome', string $sessionid = null)
    {
        $this->command = $command;
        if (isset($sessionid)) {
            $this->sessionid = $sessionid;
        }
    }

    public function get($url)
    {
        list($browser, $request, $response) = $this->initializeRequest();
        $request->setMethod('GET');
        $request->setUrl($url);
        $browser->send($request, $response);
        return $response;
    }

    public function post($url, array $data)
    {
        list($browser, $request, $response) = $this->initializeRequest();
        $request->setMethod('POST');
        $request->setUrl($url);
        $request->setRequestData($data);
        $browser->send($request, $response);
        return $response;
    }

    private function initializeRequest()
    {
        $browserFactory = new BrowserFactory($this->command);
        $browser = $browserFactory->createBrowser();
        $browser->getEngine()->addOption('--ssl-protocol=any');
        $browser->getEngine()->addOption('--ignore-ssl-errors=true');
        $browser->getEngine()->addOption('--web-security=false');
        $browser->getEngine()->setPath(getcwd().'/vendor/bin/phantomjs');
        $cookies = sys_get_temp_dir().'/'.getenv("TOAST_CLIENT");
        $browser->getEngine()->addOption("--cookies-file=$cookies");
        $request = $browser->getMessageFactory()->createRequest();
        $response = $browser->getMessageFactory()->createResponse();
        $browser->getProcedureCompiler()->disableCache();
        $request->addHeader('Cookie', self::$sessionname.'='.$this->sessionid);
        $request->addHeader('Toast', getenv("TOAST"));
        $request->addHeader('Toast-Client', getenv("TOAST_CLIENT"));
        $request->addHeader('User-Agent', 'Toast/PhantomJs headless');
        $request->setTimeout(5000);
        return [$browser, $request, $response];
    }
}

