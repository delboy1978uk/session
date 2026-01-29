<?php

namespace DelTesting;

use Codeception\Test\Unit;
use Del\SessionManager;

class SessionManagerTest extends Unit
{
    private SessionManager $sessionManager;

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

        $this->sessionManager = SessionManager::getInstance();
    }

    public function _after()
    {
        SessionManager::destroySession();
    }

    public function testGetAndSetVariable()
    {
        SessionManager::sessionStart('testGetAndSet');
        $this->sessionManager->set('test',1234);
        $this->assertEquals(1234, $this->sessionManager->get('test'));
    }

    public function testPreventHijacking()
    {
        SessionManager::sessionStart('testGetAndSet');
        $this->sessionManager->set('test',1234);
        $_SERVER['REMOTE_ADDR'] = '4.3.2.1';
        $this->assertEquals(1234, $this->sessionManager->get('test'));
    }

    public function testValidateSessionWhenObsoleteKillsSession()
    {
        SessionManager::sessionStart('testObsoleteSession');
        $this->sessionManager->set('testObsolete',1234);
        $this->sessionManager->set('OBSOLETE', true);
        SessionManager::sessionStart('testObsoleteSession');
        $this->assertNull($this->sessionManager->get('testObsolete'));
    }

    public function testValidateSessionWhenExpiredKillsSession()
    {
        SessionManager::sessionStart('testExpiredSession');
        $this->sessionManager->set('testExpired',1234);
        $this->sessionManager->set('EXPIRES', time() - 10);
        SessionManager::sessionStart('testExpired');
        $this->assertNull($this->sessionManager->get('testUserAgent'));
    }

    public function testPreventHijackingWithDifferentIp()
    {
        SessionManager::sessionStart('testIp');
        $this->sessionManager->set('ipAddress', '6.5.4.3');
        SessionManager::sessionStart('testIp');
        $_SERVER['REMOTE_ADDR'] = '10.20.30.40';
        SessionManager::sessionStart('testIp');
        $this->assertNull($this->sessionManager->get('testIp'));
    }

    public function testPreventHijackingWithDifferentUserAgent()
    {
        SessionManager::sessionStart('testUserAgent');
        $this->sessionManager->set('UserAgent', 'GoogleBot');
        SessionManager::sessionStart('testUserAgent');
        $_SERVER['HTTP_USER_AGENT'] = 'A completely different user agent string';
        SessionManager::sessionStart('testUserAgent');
        $this->assertNull($this->sessionManager->get('testUserAgent'));
    }

    public function testValidateSession()
    {
        SessionManager::sessionStart('testValidateSession');
        $this->sessionManager->set('variable', 'random');
        SessionManager::sessionStart('testValidateSession');
        SessionManager::sessionStart('testValidateSession');
        $this->assertEquals('random', $this->sessionManager->get('variable'));
    }

    public function testRegenerateSessionWhenObsolete()
    {
        SessionManager::sessionStart('testRegenerateWhenObsolete');
        $this->sessionManager->set('variable', 'random');
        $this->sessionManager->set('OBSOLETE', true);
        $this->sessionManager->set('EXPIRES', time() + 20);
        SessionManager::sessionStart('testRegenerateWhenObsolete');
        // deliberately mess with the expiry, so that it should destroy the session
        $this->sessionManager->unset('EXPIRES');
        SessionManager::sessionStart('testRegenerateWhenObsolete');
        $this->assertNull($this->sessionManager->get('variable'));
    }

    public function testRotateSessionEnvVar()
    {
        \putenv('SESSION_ROTATION=false');
        SessionManager::sessionStart('testRotateSessionEnvVar');
        $this->sessionManager->set('variable', 'random');
        SessionManager::sessionStart('testRotateSessionEnvVar');
        $this->assertTrue($this->sessionManager->has('variable'));
        $this->assertEquals('random',  $this->sessionManager->get('variable'));
        $this->sessionManager->destroy('variable');
        $this->assertFalse($this->sessionManager->has('variable'));
    }

    public function testRandomRegenerateSession()
    {
        // There's a 1 in 20 chance of it randomly regenerating an ID.
        // So we'll run it 10000 times and hopefully the test will cover it!
        SessionManager::sessionStart('testRandomRegenerateSession');
        $this->sessionManager->set('variable', 'random');
        $this->sessionManager->set('OBSOLETE', true);
        $this->sessionManager->set('EXPIRES', true);

        for($x = 0; $x < 10000; $x ++ ) {
            SessionManager::sessionStart('testRandomRegenerateSession');
            $this->sessionManager->set('OBSOLETE', true);
            $this->sessionManager->set('EXPIRES', true);
        }

        $this->assertEquals('random', $this->sessionManager->get('variable'));
    }
}
