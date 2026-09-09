<?php
namespace Pyncer\Snyppet\Access;

defined('Pyncer\Snyppet\Access\Totp\SCHEME') or define('Pyncer\Snyppet\Access\Totp\SCHEME', 'TOTP');
defined('Pyncer\Snyppet\Access\Totp\ISSUER') or define('Pyncer\Snyppet\Access\Totp\ISSUER', null);

defined('Pyncer\Snyppet\Access\Totp\METHOD_APP_ENABLED') or define('Pyncer\Snyppet\Access\Totp\METHOD_APP_ENABLED', true);
defined('Pyncer\Snyppet\Access\Totp\METHOD_EMAIL_ENABLED') or define('Pyncer\Snyppet\Access\Totp\METHOD_EMAIL_ENABLED', false);
defined('Pyncer\Snyppet\Access\Totp\METHOD_PHONE_ENABLED') or define('Pyncer\Snyppet\Access\Totp\METHOD_PHONE_ENABLED', false);

defined('Pyncer\Snyppet\Access\Totp\METHOD_APP_PERIOD') or define('Pyncer\Snyppet\Access\Totp\METHOD_APP_PERIOD', 30);
defined('Pyncer\Snyppet\Access\Totp\METHOD_EMAIL_PERIOD') or define('Pyncer\Snyppet\Access\Totp\METHOD_EMAIL_PERIOD', 300);
defined('Pyncer\Snyppet\Access\Totp\METHOD_PHONE_PERIOD') or define('Pyncer\Snyppet\Access\Totp\METHOD_PHONE_PERIOD', 300);
