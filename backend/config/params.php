<?php

return [
    'usingFrontendProxy' => isset($_ENV['FRONTEND_PROXY']) ? ($_ENV['FRONTEND_PROXY'] == 1) : false,
];
