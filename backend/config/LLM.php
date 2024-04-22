<?php

return [
    'apiKey' => $_ENV['API_KEY'],
    'proxy' => $_ENV['PROXY'],
    'usingFrontendProxy' => !!$_ENV['FRONTEND_PROXY'],
    'uri' => 'https://generativelanguage.googleapis.com/v1beta/',
];