<?php

use jblond\session\SessionEncryption;

require '../vendor/autoload.php';

$key = 'random2376289uwq8239872deadAnimal2398rz3BeefBurger';

session_set_save_handler(new SessionEncryption($key));
