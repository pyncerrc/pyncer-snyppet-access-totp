<?php
namespace Pyncer\Snyppet\Access;

defined('Pyncer\Snyppet\Access\TOTP_SCHEME') or define('Pyncer\Snyppet\Access\TOTP_SCHEME', 'totp');
defined('Pyncer\Snyppet\Access\TOTP_ISSUER') or define('Pyncer\Snyppet\Access\TOTP_ISSUER', null);

defined('Pyncer\Snyppet\Access\TOTP_METHOD_APP_ENABLED') or define('Pyncer\Snyppet\Access\TOTP_METHOD_APP_ENABLED', true);
defined('Pyncer\Snyppet\Access\TOTP_METHOD_EMAIL_ENABLED') or define('Pyncer\Snyppet\Access\TOTP_METHOD_EMAIL_ENABLED', false);
defined('Pyncer\Snyppet\Access\TOTP_METHOD_PHONE_ENABLED') or define('Pyncer\Snyppet\Access\TOTP_METHOD_PHONE_ENABLED', false);

defined('Pyncer\Snyppet\Access\TOTP_METHOD_APP_PERIOD') or define('Pyncer\Snyppet\Access\TOTP_METHOD_APP_PERIOD', 30);
defined('Pyncer\Snyppet\Access\TOTP_METHOD_EMAIL_PERIOD') or define('Pyncer\Snyppet\Access\TOTP_METHOD_EMAIL_PERIOD', 300);
defined('Pyncer\Snyppet\Access\TOTP_METHOD_PHONE_PERIOD') or define('Pyncer\Snyppet\Access\TOTP_METHOD_PHONE_PERIOD', 300);
