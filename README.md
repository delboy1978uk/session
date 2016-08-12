# session
A secure session manager for PHP
## Installation
Use composer:
```
composer require delboy1978uk/session
```
## Usage
```php
use Del\SessionManager;

// Starting the seeion - also takes a name argument
SessionManager::sessionStart();

// Setting a variable
SessionManager::set('name', 'DelBoy');

// Getting a variable
$name = SessionManger::get('name');

// Unsetting a variable
SessionManager::destroy('name');

// Ending the session
SessionManager::destroySession();

```
