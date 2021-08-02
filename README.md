# luler/spider_product_info

通过商品链接，简单爬取商品的基本信息，兼容主流电商平台

# 助手类列表如下

- SpiderProductInfoHelper

# 使用示例

```injectablephp
        $spider = new \Luler\Helpers\SpiderProductInfoHelper([
            'proxy_server' => '121.127.241.235:32081',
        ]);

        $info = $spider->info('https://detail.tmall.com/item.htm?id=631833014942&skuId=4676531095838', true);
        
        dump($info);
```

输出

```injectablephp
array(2) {
  ["name"] => string(70) "姿彩a4打印纸整箱70g 80g复印纸80克a3 5包/箱(2500张) 70gA4"  //商品名称
  ["price"] => float(119)   //商品价格
}
```
