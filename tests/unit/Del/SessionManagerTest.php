<?php
namespace DelTesting;

use Codeception\TestCase\Test;
use Del\SessionManager;

class SessionManagerTest extends Test
{
    public function _before()
    {
        $_SERVER = array();
        $_SERVER['SERVER_NAME'] = 'random.com';
        $_SERVER['HTTPS'] = false;
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.5; rv:10.0.1) Gecko/20100101 Firefox/10.0.1 SeaMonkey/2.7.1';
        if(session_id()) {
          session_destroy();
        }
    }

    public function _after()
    {
        SessionManager::destroySession();
    }

    public function testGetAndSetVariable()
    {
        SessionManager::sessionStart('testGetAndSet');
        SessionManager::set('test',1234);
        $this->assertEquals(1234, SessionManager::get('test'));
    }

    public function testPreventHijacking()
    {
        SessionManager::sessionStart('testGetAndSet');
        SessionManager::set('test',1234);
        $_SERVER['REMOTE_ADDR'] = '4.3.2.1';
        $this->assertEquals(1234, SessionManager::get('test'));
    }

    public function testValidateSessionWhenObsoleteKillsSession()
    {
        SessionManager::sessionStart('testObsoleteSession');
        SessionManager::set('testObsolete',1234);
        SessionManager::set('OBSOLETE', true);
        SessionManager::sessionStart('testObsoleteSession');
        $this->assertNull(SessionManager::get('testObsolete'));
    }

    public function testValidateSessionWhenExpiredKillsSession()
    {
        SessionManager::sessionStart('testExpiredSession');
        SessionManager::set('testExpired',1234);
        SessionManager::set('EXPIRES', time() - 10);
        SessionManager::sessionStart('testExpired');
        $this->assertNull(SessionManager::get('testUserAgent'));
    }

    public function testPreventHijackingWithDifferentIp()
    {
        SessionManager::sessionStart('testIp');
        SessionManager::set('ipAddress', '6.5.4.3');
        SessionManager::sessionStart('testIp');
        $_SERVER['REMOTE_ADDR'] = '10.20.30.40';
        SessionManager::sessionStart('testIp');
        $this->assertNull(SessionManager::get('testIp'));
    }

    public function testPreventHijackingWithDifferentUserAgent()
    {
        SessionManager::sessionStart('testUserAgent');
        SessionManager::set('UserAgent', 'GoogleBot');
        SessionManager::sessionStart('testUserAgent');
        $_SERVER['HTTP_USER_AGENT'] = 'A completely different user agent string';
        SessionManager::sessionStart('testUserAgent');
        $this->assertNull(SessionManager::get('testUserAgent'));
    }

    public function testValidateSession()
    {
        SessionManager::sessionStart('testValidateSession');
        SessionManager::set('variable', 'random');
        SessionManager::sessionStart('testValidateSession');
        SessionManager::sessionStart('testValidateSession');
        $this->assertEquals('random', SessionManager::get('variable'));
    }

    public function testRegenerateSessionWhenObsolete()
    {
        SessionManager::sessionStart('testRegenerateWhenObsolete');
        SessionManager::set('variable', 'random');
        SessionManager::set('OBSOLETE', true);
        SessionManager::set('EXPIRES', time() + 20);
        SessionManager::sessionStart('testRegenerateWhenObsolete');
        // deliberately mess with the expiry, so that it should destroy the session
        SessionManager::destroy('EXPIRES');
        SessionManager::sessionStart('testRegenerateWhenObsolete');
        $this->assertNull(SessionManager::get('variable'));
    }

    public function testRandomRegenerateSession()
    {
        // There's a 1 in 20 chance of it randomly regenerating an ID.
        // So we'll run it 40 times and hopefully the test will cover it!
        // (We can't use aspect mock, we need PHP 5.4 :-( )
        SessionManager::sessionStart('testRandomRegenerateSession');
        SessionManager::set('variable', 'random');
        SessionManager::set('OBSOLETE', true);
        SessionManager::set('EXPIRES', true);

        for($x = 0; $x < 40; $x ++ ) {
            SessionManager::sessionStart('testRandomRegenerateSession');
            SessionManager::set('OBSOLETE', true);
            SessionManager::set('EXPIRES', true);
        }

        $this->assertEquals('random', SessionManager::get('variable'));
    }
}
