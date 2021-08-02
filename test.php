<?php

require 'vendor/autoload.php';

$spider = new \SpiderProductInfo\SpiderProductInfoHelper([
    'proxy_server' => '121.127.241.235:32081',
]);

var_dump($spider->info('https://detail.tmall.com/item.htm?id=631833014942&skuId=4676531095838', true));