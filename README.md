# session
[![Latest Stable Version](https://poser.pugx.org/delboy1978uk/session/v/stable)](https://packagist.org/packages/delboy1978uk/session) [![Total Downloads](https://poser.pugx.org/delboy1978uk/session/downloads)](https://packagist.org/packages/delboy1978uk/session) [![License](https://poser.pugx.org/delboy1978uk/session/license)](https://packagist.org/packages/delboy1978uk/session)
![build status](https://github.com/delboy1978uk/session/actions/workflows/master.yml/badge.svg) [![Code Coverage](https://scrutinizer-ci.com/g/delboy1978uk/session/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/delboy1978uk/session/?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/delboy1978uk/session/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/delboy1978uk/session/?branch=master) 


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
