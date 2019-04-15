<?php

namespace Toast\Acceptance;

use HeadlessChromium\{ BrowserFactory, Page, Cookies\Cookie };

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
        $browserFactory = new BrowserFactory($this->command);
        $cookies = sys_get_temp_dir().'/'.getenv("TOAST_CLIENT");
        $browser = $browserFactory->createBrowser([
            'ignoreCertificateErrors' => true,
            'customFlags' => ['--ssl-protocol=any', '--web-security=false', "--cookies-file=$cookies"],
            'headless' => false,
        ]);
        $page = $browser->createPage();
        $domain = preg_replace('@^https?://(.*?)/.*?$@', '$1', $url);
        $expires = time() + 3600;
        $page->setCookies([
            Cookie::create('TOAST', "1", compact('domain', 'expires')),
            Cookie::create('TOAST_CLIENT', ''.getenv('TOAST_CLIENT'), compact('domain', 'expires')),
            Cookie::create(self::$sessionname, $this->sessionid, compact('domain', 'expires')),
        ])->await();
        $page->navigate($url)->waitForNavigation();
        $page->setUserAgent('Toast/Chrome headless');
        return $page;
    }
}

