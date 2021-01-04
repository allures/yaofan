<?php
error_reporting(0);
session_start();
define('BASE_PATH',str_replace('\\','/',dirname(__FILE__))."/");
define('ROOT_PATH',str_replace('app/','',BASE_PATH));
define('DB_TYPE', 'sqlite'); //数据库类型
define('DB_NAME', ROOT_PATH.'app/db/e99dba9e1c.db'); //上线请更改数据库地址
define('TOKEN', '0e6d15df8781e');
/*** 请填写以下配置信息 ***/
$appid = 'xxxx';  //https://open.alipay.com 账户中心->密钥管理->开放平台密钥，填写添加了电脑网站支付的应用的APPID
$notifyUrl = 'https://xxxxx/app/pay.php';     //付款成功后的异步回调地址
$signType = 'RSA2';			//签名算法类型，支持RSA2和RSA，推荐使用RSA2
$rsaPrivateKey='xxxx';	
//支付宝公钥，账户中心->密钥管理->开放平台密钥，找到添加了支付功能的应用，根据你的加密类型，查看支付宝公钥
$alipayPublicKey='xxxxx';