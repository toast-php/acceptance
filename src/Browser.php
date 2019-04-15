<?php

namespace Toast\Acceptance;

use HeadlessChromium\{ BrowserFactory, Page };

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

    public function get(string $url) : Page
    {
        return $this->initializeRequest($url);
    }

    public function post($url, string $form, array $data, string $submit = 'button[type=submit]') : Page
    {
        $page = $this->initializeRequest($url);
        $populate = [];
        array_walk($data, function ($value, $key) use (&$populate) {
            $value = addslashes($value);
            $populate[] = "form.querySelector('[name=$key]').value = '$value';";
        });
        $populate = implode("\n", $populate);
        $evaluation = $page->evaluate(
            <<<EOT
(() => {
    const form = document.querySelector('$form');
    $populate
    form.querySelector('$submit').click();
})();
EOT
        );
        $evaluation->waitForPageReload();
        return $page;
    }

    private function initializeRequest(string $url) : Page
    {
        $url .= (strpos($url, '?') !== false ? '&' : '?')."TOAST=".getenv("TOAST")."&TOAST_CLIENT=".$this->sessionid;
        $browserFactory = new BrowserFactory($this->command);
        $cookies = sys_get_temp_dir().'/'.getenv("TOAST_CLIENT");
        $browser = $browserFactory->createBrowser([
            'ignoreCertificateErrors' => true,
            'customFlags' => ['--ssl-protocol=any', '--web-security=false', "--cookies-file=$cookies"],
        ]);
        $page = $browser->createPage();
        $page->navigate($url)->waitForNavigation();
        $page->setUserAgent('Toast/Chrome headless');
        return $page;
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

