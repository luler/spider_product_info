<?php

namespace SpiderProductInfo;

use Curl\Curl;

class SpiderProductInfoHelper
{
    private $agent_config = [//http请求代理服务配置
        'proxy_server' => '',
        'proxy_user' => '',
        'proxy_password' => '',
    ];

    /**
     * @param array $agent_config //http请求代理服务配置
     */
    public function __construct(array $agent_config = [])
    {
        $agent_config = array_filter($agent_config);
        if (!empty($agent_config) && isset($agent_config['proxy_server'])) {
            $this->agent_config = $agent_config;
        }
    }

    /**
     * 截取字符
     * @param $str
     * @param $start
     * @param $end
     * @return int|mixed
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    private function getValue($str, $start, $end)
    {
        preg_match('/(?<=(' . $start . '))[.\\s\\S]*?(?=(' . $end . '))/', $str, $match);
        return $match[0] ?? 0;
    }

    /**
     * 请求工具
     * @param $url
     * @param array $param
     * @param false $is_post
     * @param false $is_json
     * @param false $is_use_agent
     * @return false|string|null
     * @throws \Exception
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    private function spiderRequest($url, $param = [], $is_post = false, $is_json = false, $is_use_agent = false)
    {
        $curl = new Curl();
        //出现问题，自动启用代理
        if ($is_use_agent) {
            if (empty($this->agent_config['proxy_server'])) {
                throw new \Exception('代理配置不能为空');
            }
            $curl->setOpt(CURLOPT_HTTPPROXYTUNNEL, false);
            $curl->setOpt(CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            $curl->setOpt(CURLOPT_PROXY, $this->agent_config['proxy_server']);
            if (!empty($this->agent_config['proxy_user']) && !empty($this->agent_config['proxy_password'])) {
                $curl->setOpt(CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
                $curl->setOpt(CURLOPT_PROXYUSERPWD, "{$this->agent_config['proxy_user']}:{$this->agent_config['proxy_password']}");
            }
        }
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
        $curl->setOpt(CURLOPT_AUTOREFERER, true);
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36');
        if ($is_post) {
            if ($is_json) {
                $curl->setHeader('Content-Type', 'application/json;charset=UTF-8');
            }
            $curl->post($url, $param, $is_json);
        } else {
            $curl->get($url, $param);
        }
        $response = $curl->response;
        $curl->close();
        return $response;
    }

    /**
     * 爬取商品信息
     * @param $url //商品链接
     * @param false $is_use_agent //是否启用http代理
     * @return array
     * @throws \Exception
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public function info($url, $is_use_agent = false)
    {
        $host = parse_url($url)['host'] ?? '';
        $product_price = 0;
        $product_name = '';
        switch ($host) {
            case 'i-item.jd.com': //京东
            case 'item.jd.com': //京东
                //识别商品价格
                preg_match('/\/(\d+).html/', $url, $matchs);
                $sku = $matchs[1] ?? '';
                $data = $this->spiderRequest("https://item-soa.jd.com/getWareBusiness?callback=jQuery4724608&skuId=" . $sku, [], false, false, $is_use_agent);
                $product_price = $this->getValue($data, '"p":"', '"');
                //识别商品名称
                $data = $this->spiderRequest($url, [], false, false, $is_use_agent);
                preg_match('/<div class="sku-name">(.*?)<\/div>/s', $data, $matches);
                $product_name = $matches[1] ?? '';
                $product_name = strip_tags($product_name);
                preg_match('/[\"\'\s]*(.*)[\"\'\s]*/s', $product_name, $matches);
                $product_name = $matches[1] ?? '';
                $product_name = trim($product_name);
                break;
            case 'detail.tmall.com'://天猫
                //识别商品价格
                preg_match('/skuId=(\d+)/', $url, $match);
                $sku_id = $match[1] ?? '';
                $data = $this->spiderRequest($url, [], false, false, $is_use_agent);
                $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');
                if (empty($sku_id)) {
                    $product_price = $this->getValue($data, '"defaultItemPrice":"', '"');
                    $product_price = explode(' ', $product_price)[0];
                } else {
                    preg_match('/\{[^\{]*"price":"([\d\.]+)"[^\{]*"skuId":"' . $sku_id . '"[^\}]*}/', $data, $match);
                    $product_price = $match[1] ?? 0;
                }
                //识别商品名称
                $product_name = $this->getValue($data, 'title":"', '"');
                break;
            case 'product.suning.com': //苏宁
                //识别商品价格
                $data = $this->spiderRequest($url, [], false, false, $is_use_agent);
                //要拼接这个地址进行查询。
                $partNumber = $this->getValue($data, '"partNumber":"', '",');
                $vendorCode = $this->getValue($data, '"vendorCode":"', '",');
                $catenIds = $this->getValue($data, '"catenIds":"', '",');
                $category1 = $this->getValue($data, '"category1":"', '",');
                $brandId = $this->getValue($data, '"brandId":"', '",');
                $price_url = "https://pas.suning.com/nspcsale_0_{$partNumber}_{$partNumber}_{$vendorCode}_100_512_5120501_{$category1}_1000176_9176_11397_Z001___{$catenIds}_0.24_3___{$brandId}_01_.html";
                $price_data = $this->spiderRequest($price_url, [], false, false, $is_use_agent);
                $product_price = $this->getValue($price_data, 'promotionPrice":"', '"');
                //识别商品名称
                $product_name = $this->getValue($data, 'itemDisplayName":"', '"');
                break;
            case 'item.gome.com.cn': //国美
                //识别商品价格
                preg_match('/(\d+)-(\d+)/', $url, $matchs);
                $product_id = $matchs[1] ?? '';
                $sku_id = $matchs[2] ?? '';
                $data = $this->spiderRequest("http://ss.gome.com.cn/search/v1/price/single/1001/G001/{$product_id}/{$sku_id}/null/flag/item", [], false, false, $is_use_agent);
                $product_price = $this->getValue($data, 'price":"', '"');
                //识别商品名称
                $data = $this->spiderRequest($url, [], false, false, $is_use_agent);
                $product_name = $this->getValue($data, '商品名称：', '<');
                if (empty($product_name)) {
                    $product_name = $this->getValue($data, '<h1>', '<');
                }
                break;
            case 'b2b.nbdeli.com': //得力
                //识别商品价格
                $data = $this->spiderRequest($url, [], false, false, $is_use_agent);
                $product_price = $this->getValue($data, '"SalePrice":', ',');
                //识别商品名称
                $product_name = $this->getValue($data, '"StyleName":"', '"');
                break;
            case 'www.mg.cn': //晨光
                //识别商品价格
                $data = $this->spiderRequest($url, [], false, false, $is_use_agent);
                $product_price = $this->getValue($data, "'price':'", "'");
                $product_price = str_replace('￥', '', $product_price);
                //识别商品名称
                $product_name = $this->getValue($data, "'goodsName':'", "'");
                break;
            case 'www.comix.com.cn': //齐心
                //识别商品价格
                $data = $this->spiderRequest($url, [], false, false, $is_use_agent);
                preg_match('/\¥\<\/span\>([\d\.]+)\</', $data, $matchs);
                $product_price = $matchs[1] ?? 0;
                //识别商品名称
                $product_name = $this->getValue($data, '商品名称：', '<');
                break;
            case 'www.stbchina.cn': //斯泰博价格
                //识别商品价格
                preg_match('/itemId=(\d+)/', $url, $matches);
                $itemId = $matches[1] ?? '';
                $data = $this->spiderRequest("http://www.stbchina.cn/api/realTime/getPriceStockList?areaCode=78&mpIds={$itemId}", [], false, false, $is_use_agent);
                $product_price = $this->getValue($data, 'price":', ',');
                //识别商品名称
                $data = $this->spiderRequest('http://www.stbchina.cn/back-product-web2/extra/merchantProduct/getMerchantProductBaseInfoById.do', [
                    'mpId' => $itemId
                ], true, true, $is_use_agent);
                $product_name = $this->getValue($data, 'chineseName":"', '"');
                break;
        }

        $product_price = is_numeric($product_price) ? $product_price : 0;
        $product_price = $product_price > 0 ? $product_price : 0;

        $res = [
            'name' => $product_name,
            'price' => (float)$product_price,
        ];
        return $res;
    }

}
