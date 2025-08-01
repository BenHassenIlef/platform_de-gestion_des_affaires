<?php

use App\Kernel;

#require_once dirname(__DIR__).'../App/public/vendor/autoload_runtime.php';#
require_once '.././vendor/autoload_runtime.php';
return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
