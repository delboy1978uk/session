<?php declare(strict_types=1);

namespace Del;

use function getenv;
use function preg_replace;
use function random_int;
use function session_destroy;
use function session_id;
use function session_name;
use function session_regenerate_id;
use function session_set_cookie_params;
use function session_start;
use function session_write_close;
use function time;

final class SessionManager
{
    private const IP_REGEX = '/(\d{1,3}\.\d{1,3}\.\d{1,3}\.)(\d{1,3})/';
    private array $session = [];

    /**
     *  As this is a singleton, construction and clone are disabled
     *  use SessionManager::getInstance() if you need the instance
     */
    private function __construct(){}

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
        $domain = $domain ?? $_SERVER['SERVER_NAME'];

        // Set the default secure value to whether the site is being accessed with SSL
        $secure = $secure ?? isset($_SERVER['HTTPS']);
        $id = session_id();

        if (empty($id)) {
            session_name($name . '_Session');
            session_set_cookie_params($lifetime, $path, $domain, $secure, true);
            session_start();
            $inst->session = & $_SESSION;
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
        return $this->hasSessionRotation() && random_int(1, 100) <= 5;
    }

    /**
     * Checks session IP and user agent are still the same
     */
    private function isHijackAttempt(): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if ($this->hasIPCheck()) {
            $ipAddress = $this->getIpAddress();

            if (!isset($this->session['ipAddress'], $this->session['userAgent'])) {
                return true;
            }

            if ($this->session['ipAddress'] !== $ipAddress) {
                return true;
            }
        }

        return $this->session['userAgent'] !== $userAgent;
    }

    /**
     * If a site goes through the likes of Cloudflare, the last part of the IP might change
     * So we replace it with an x.
     */
    private function getIpAddress(): string
    {
        $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';

        return preg_replace(self::IP_REGEX, '$1x', $remoteAddress);
    }

    /**
     *  Creates a fresh session ID to make it harder to hack
     *  If the site is very slow in parts increase the expiry time
     *  10 seconds is a good default which allows ajax calls to work
     *  without losing the session
     */
    private function regenerateSession(): void
    {
        // If this session is obsolete it means there already is a new id
        if (isset($this->session['OBSOLETE']) && $this->session['OBSOLETE'] === true) {
            return;
        }

        // Set current session to expire in 10 seconds
        $this->session['OBSOLETE'] = true;
        $this->session['EXPIRES'] = time() + 10;

        // Create new session without destroying the old one
        session_regenerate_id();

        // Grab current session ID and close both sessions to allow other scripts to use them
        $newSession = \session_id();
        session_write_close();

        // Set session ID to the new one, and start it back up again
        session_id($newSession);
        session_start();
        $this->session = & $_SESSION;

        // Now we unset the obsolete and expiration values for the session we want to keep
        unset($this->session['OBSOLETE'], $this->session['EXPIRES']);
    }

    /**
     * Checks whether the session has expired or not
     * @return bool
     */
    private function isValid(): bool
    {
        if (!$this->hasSessionRotation()) {
            return true;
        }

        if (isset($this->session['OBSOLETE']) && !isset($this->session['EXPIRES'])) {
            return false;
        }

        if (isset($this->session['EXPIRES']) && $this->session['EXPIRES'] < time()) {
            return false;
        }

        return true;
    }

    private function hasSessionRotation(): bool
    {
        $rotation = getenv('SESSION_ROTATION');
        return $rotation ? (bool) $rotation : true;
    }

    private function hasIPCheck(): bool
    {
        $ipCheck = getenv('SESSION_IP_CHECK');

        return $ipCheck ? (bool) $ipCheck: true;
    }

    /**
     *  Resets the session
     */
    public static function destroySession(): void
    {
        $id = session_id();

        if (!empty($id)) {
            $_SESSION = [];
            session_destroy();
            session_start();
        }
    }

    public function set(string $key, mixed $val): void
    {
        $this->session[$key] = $val;
    }

    public function get(string $key): mixed
    {
        return $this->session[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->session[$key]);
    }

    public function unset(string $key): void
    {
        unset($this->session[$key]);
    }

    /**
     * @deprecated use unset
     */
    public function destroy(string $key): void
    {
        $this->unset($key);
    }
}
