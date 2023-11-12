<?php declare(strict_types=1);

namespace Del;

final class SessionManager
{
    private const IP_REGEX = '/(\d{1,3}\.\d{1,3}\.\d{1,3}\.)(\d{1,3})/';
    private array $session;

    /**
     *  As this is a singleton, construction and clone are disabled
     *  use SessionManager::getInstance() if you need the instance
     */
    private function __construct()
    {
        $this->session = & $_SESSION;
    }

    private function __clone(){}

    public static function getInstance(): SessionManager
    {
        static $inst = null;

        if ($inst === null) {
            $inst = new SessionManager();
        }

        return $inst;
    }

    /**
     * Creates a secure session
     */
    public static function sessionStart(string $name, int $lifetime = 0, string $path = '/', string $domain = '', ?bool $secure = null): void
    {
        // get the instance of the session manager
        $inst = self::getInstance();

        // Set the domain to default to the current domain.
        $domain = isset($domain) ? $domain : $_SERVER['SERVER_NAME'];

        // Set the default secure value to whether the site is being accessed with SSL
        $secure = isset($secure) ? $secure : isset($_SERVER['HTTPS']);
        $id = \session_id();

        if (empty($id)) {
            \session_name($name . '_Session');
            \session_set_cookie_params($lifetime, $path, $domain, $secure, true);
            \session_start();
        }

        // Make sure the session hasn't expired, and destroy it if it has
        $inst->isValid() ? $inst->initialise() :  self::destroySession();

    }

    private function initialise(): void
    {
        // Check to see if the session is a hijacking attempt
        if ($this->isHijackAttempt()) {

            // Reset session data and regenerate id
            $this->session = [];
            $this->session['ipAddress'] = $this->getIpAddress();
            $this->session['userAgent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $this->regenerateSession();

            return;
        }

        // Give a 5% chance of the session id changing on any request
        if ($this->shouldRandomlyRegenerate()) {
            $this->regenerateSession();
        }
    }

    private function shouldRandomlyRegenerate(): bool
    {
        return \random_int(1, 100) <= 5;
    }


    /**
     * Checks session IP and user agent are still the same
     */
    private function isHijackAttempt(): bool
    {
        $ipAddress = $this->getIpAddress();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (!isset($this->session['ipAddress']) || !isset($this->session['userAgent'])) {
            return true;
        }

        if ($this->session['ipAddress'] != $ipAddress) {
            return true;
        }

        if ($this->session['userAgent'] !== $userAgent) {
            return true;
        }

        return false;
    }

    /**
     * If a site goes through the likes of Cloudflare, the last part of the IP might change
     * So we replace it with an x.
     */
    private function getIpAddress(): string
    {
        $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';

        return \preg_replace(self::IP_REGEX, '$1x', $remoteAddress);
    }

    /**
     *  Creates a fresh session Id to make it harder to hack
     *  If the site is very slow in parts increase the expiry time
     *  10 seconds is a good default which allows ajax calls to work
     *  without losing the session
     */
    private function regenerateSession()
    {
        // If this session is obsolete it means there already is a new id
        if (isset($this->session['OBSOLETE']) && $this->session['OBSOLETE'] == true) {
            return;
        }

        // Set current session to expire in 10 seconds
        $this->session['OBSOLETE'] = true;
        $this->session['EXPIRES'] = \time() + 10;

        // Create new session without destroying the old one
        \session_regenerate_id(false);

        // Grab current session ID and close both sessions to allow other scripts to use them
        $newSession = \session_id();
        \session_write_close();

        // Set session ID to the new one, and start it back up again
        \session_id($newSession);
        \session_start();

        // Now we unset the obsolete and expiration values for the session we want to keep
        unset($this->session['OBSOLETE']);
        unset($this->session['EXPIRES']);
    }

    /**
     * Checks whether the session has expired or not
     * @return bool
     */
    private function isValid(): bool
    {
        if (isset($this->session['OBSOLETE']) && !isset($this->session['EXPIRES'])) {
            return false;
        }

        if (isset($this->session['EXPIRES']) && $this->session['EXPIRES'] < time()) {
            return false;
        }

        return true;
    }

    /**
     *  Resets the session
     */
    public static function destroySession()
    {
        $id = session_id();

        if (!empty($id)) {
            $_SESSION = [];
            \session_destroy();
            \session_start();
        }
    }

    /**
     * @param string $key
     * @param mixed $val
     */
    public function set(string $key, $val): void
    {
        $this->session[$key] = $val;
    }

    /**
     * @param $key
     * @return null|mixed
     */
    public function get(string $key)
    {
        return isset($this->session[$key]) ? $this->session[$key] : null;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->session[$key]);
    }

    /**
     * @deprecated  use destroy($key) instead
     */
    public function unset(string $key): void
    {
        unset($this->session[$key]);
    }

    /**
     * @param $key
     * @param $val
     * @deprecated use unset
     */
    public function destroy(string $key): void
    {
        unset($this->session[$key]);
    }
}
