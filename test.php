<?php

require 'vendor/autoload.php';

$spider = new \Luler\Helpers\SpiderProductInfoHelper([
    'proxy_server' => '121.127.241.235:32081',
]);

var_dump($spider->info('https://item.jd.com/10026184700599.html#crumb-wrap', true));