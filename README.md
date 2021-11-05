# session
[![Latest Stable Version](https://poser.pugx.org/delboy1978uk/session/v/stable)](https://packagist.org/packages/delboy1978uk/session) [![Build Status](https://travis-ci.com/delboy1978uk/session.png?branch=master)](https://travis-ci.com/delboy1978uk/session) [![Code Coverage](https://scrutinizer-ci.com/g/delboy1978uk/session/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/delboy1978uk/session/?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/delboy1978uk/session/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/delboy1978uk/session/?branch=master) [![License](https://poser.pugx.org/delboy1978uk/session/license)](https://packagist.org/packages/delboy1978uk/session)


A reasonably secure session manager for PHP
## Installation
Use composer:
```
composer require delboy1978uk/session
```
## Usage
```php
use Del\SessionManager;

// Starting the session - also takes a name argument
SessionManager::sessionStart();

// Setting a variable
SessionManager::set('name', 'DelBoy');

// Check a session variable exists
$hasName = SessionManger::has('name');

// Getting a variable
$name = SessionManger::get('name');

// Unsetting a variable
SessionManager::destroy('name');

// Ending the session
SessionManager::destroySession();

```
