<?php declare(strict_types=1);

namespace Del;

final class SessionManager
{
    const IP_REGEX = '/(\d{1,3}\.\d{1,3}\.\d{1,3}\.)(\d{1,3})/';

    /**
     *  As this is a singleton, construction and clone are disabled
     *  use SessionManager::getInstance() if you need the instance
     */
    private function __construct(){}

    private function __clone(){}

    /**
     * @return SessionManager
     */
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
     *
     * @param string $name
     * @param int $lifetime
     * @param string $path
     * @param string $domain
     * @param bool|null $secure
     */
    public static function sessionStart(string $name, int $lifetime = 0, string $path = '/', string $domain = '', ?bool $secure = null): void
    {
        // get the instance of the session manager
        $inst = self::getInstance();

        // Set the domain to default to the current domain.
        $domain = isset($domain) ? $domain : $_SERVER['SERVER_NAME'];

        // Set the default secure value to whether the site is being accessed with SSL
        $secure = isset($secure) ? $secure : isset($_SERVER['HTTPS']);

        $id = session_id();
        if(empty($id)) {
            session_name($name . '_Session');
            session_set_cookie_params($lifetime, $path, $domain, $secure, true);
            session_start();
        }

        // Make sure the session hasn't expired, and destroy it if it has
        if ($inst->validateSession()) {

            // Check to see if the session is new or a hijacking attempt
            if (!$inst->preventHijacking()) {

                // Reset session data and regenerate id
                $_SESSION = [];
                $_SESSION['ipAddress'] = $inst->getIpAddress();
                $_SESSION['userAgent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $inst->regenerateSession();

                // Give a 5% chance of the session id changing on any request
            } elseif ($inst->shouldRandomlyRegenerate()) {
                $inst->regenerateSession();
            }
        } else {
            $inst->destroySession();
        }
    }

    /**
     * @return bool
     */
    private function shouldRandomlyRegenerate(): bool
    {
        return rand(1, 100) <= 5;
    }


    /**
     * Checks session IP and user agent are still the same
     * @return bool
     */
    private function preventHijacking(): bool
    {
        $ipAddress = $this->getIpAddress();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (!isset($_SESSION['ipAddress']) || !isset($_SESSION['userAgent'])) {
            return false;
        }

        if ($_SESSION['ipAddress'] != $ipAddress) {
            return false;
        }

        if ($_SESSION['userAgent'] !== $userAgent) {
            return false;
        }

        return true;
    }

    /**
     * If a site goes through the likes of Cloudflare, the last part of the IP might change
     * So we replace it with an x.
     *
     * @return string
     */
    private function getIpAddress(): string
    {
        $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';

        return preg_replace(SessionManager::IP_REGEX, '$1x', $remoteAddress);
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
        if (isset($_SESSION['OBSOLETE']) && $_SESSION['OBSOLETE'] == true) {
            return;
        }

        // Set current session to expire in 10 seconds
        $_SESSION['OBSOLETE'] = true;
        $_SESSION['EXPIRES'] = time() + 10;

        // Create new session without destroying the old one
        session_regenerate_id(false);

        // Grab current session ID and close both sessions to allow other scripts to use them
        $newSession = session_id();
        session_write_close();

        // Set session ID to the new one, and start it back up again
        session_id($newSession);
        session_start();

        // Now we unset the obsolete and expiration values for the session we want to keep
        unset($_SESSION['OBSOLETE']);
        unset($_SESSION['EXPIRES']);
    }

    /**
     * Checks whether the session has expired or not
     * @return bool
     */
    private function validateSession()
    {
        if (isset($_SESSION['OBSOLETE']) && !isset($_SESSION['EXPIRES'])) {
            return false;
        }

        if (isset($_SESSION['EXPIRES']) && $_SESSION['EXPIRES'] < time()) {
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

        if(!empty($id)) {
            $_SESSION = array();
            session_destroy();
            session_start();
        }
    }

    /**
     * @param string $key
     * @param mixed $val
     */
    public function set(string $key, $val):  void
    {
        $_SESSION[$key] = $val;
    }

    /**
     * @param $key
     * @return null
     */
    public function get(string $key)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * @param $key
     * @param $val
     */
    public function unset(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * @param $key
     * @param $val
     * @deprecated use unset
     */
    public function destroy(string $key): void
    {
        unset($_SESSION[$key]);
    }
}
