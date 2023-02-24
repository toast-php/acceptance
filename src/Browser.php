<?php

namespace Toast\Acceptance;

use HeadlessChromium\{ BrowserFactory, Page, Cookies\Cookie };

/**
 * The Toast browser wrapper.
 */
class Browser
{
    /** Change this if needed for your project. */
    public static string $sessionname = 'PHPSESSID';

    private string $sessionid;

    private string $command;

    /**
     * @param string $command The command to use when launching Chrome. Defaults
     *  to `chrome`.
     * @param string $sessionid Optional fake session ID to use. Supply this
     *  whenever testing something that relies on a session (e.g. a login).
     * @return void
     */
    public function __construct(string $command = 'chrome', string $sessionid = null)
    {
        $this->command = $command;
        if (isset($sessionid)) {
            $this->sessionid = $sessionid;
        } else {
            $this->sessionid = md5(time());
        }
    }

    /**
     * Perform a GET on the supplied URL.
     *
     * @param string $url
     * @return HeadlessChromium\Page
     */
    public function get(string $url) : Page
    {
        return $this->initializeRequest($url);
    }

    /**
     * Perform a POST to the supplied URL.
     *
     * @param string $url
     * @param string $form Selector for the form to post to.
     * @param array $data Array of key/value pairs of data to post. Should match
     *  the `name` attributes of the form elements. Note that if nothing is
     *  supplied, the default values are posted which may or may not be empty.
     * @param string $submit Selector for the submit button. Defaults to
     *  `button[type=submit]`.
     * @param bool $waitForReload Whether or not to wait for the page to reload
     *  after submission. Defaults to `true`. `false` may be required if e.g.
     *  the page does stuff using AJAX.
     * @return HeadlessChromium\Page
     */
    public function post($url, string $form, array $data, string $submit = 'button[type=submit]', bool $waitForReload = true) : Page
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
        if ($waitForReload) {
            $evaluation->waitForPageReload();
        }
        return $page;
    }

    /**
     * Private helper to setup a request.
     *
     * @param string $url
     * @return HeadlessChromium\Page
     */
    private function initializeRequest(string $url) : Page
    {
        $browserFactory = new BrowserFactory($this->command);
        $cookies = sys_get_temp_dir().'/'.getenv("TOAST_CLIENT");
        $browser = $browserFactory->createBrowser([
            'ignoreCertificateErrors' => true,
            'customFlags' => ['--ssl-protocol=any', '--web-security=false', "--cookies-file=$cookies"],
        ]);
        $page = $browser->createPage();
        $domain = preg_replace('@^https?://(.*?)/.*?$@', '$1', $url);
        $expires = time() + 3600;
        $page->setCookies([
            Cookie::create('TOAST', "1", compact('domain', 'expires')),
            Cookie::create('TOAST_CLIENT', ''.getenv('TOAST_CLIENT'), compact('domain', 'expires')),
            Cookie::create(self::$sessionname, "{$this->sessionid}", compact('domain', 'expires')),
        ])->await();
        $page->navigate($url)->waitForNavigation();
        $page->setUserAgent('Toast/Chrome headless');
        return $page;
    }
}

