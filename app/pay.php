<?php
header('Content-type:text/html; Charset=utf-8');
require_once 'config.php';
require_once 'db.class.php';
require_once 'alipay.class.php';
$c = isset($_GET['c'])?$_GET['c']:''; 
$n = isset($_GET['n'])?$_GET['n']:'0.01';
$m = isset($_GET['m'])?$_GET['m']:'';
$p = isset($_GET['p']) ? intval($_GET['p']) : 1;
$db = new Db(); 
if($c==='polling'){
  $oid = isset($_GET['oid'])?$_GET['oid']:'';
  if($oid == $_SESSION[TOKEN.'_OID']){  
  $rs = $db->getline("select * from dmf_order where oid=:oid",array('oid'=>$oid));
  if(empty($rs)){
      exit ('{"status":"400","message":"失败了！"}');
   }else{	
	   if($rs['ispay'] == 1){
	      $_SESSION[TOKEN.'_OID'] = '';
          exit ('{"status":"200","message":"施舍成功，施主果然宅心仁厚！"}');         
	   }else{
	      exit ('{"status":"400","message":"失败了！"}');
	   }  
   }

   }else{
      exit ('{"status":"400","message":"失败了！"}');
   }
}elseif($c==='list'){
$per_page = 20;
$start = $per_page * ($p - 1);
$rs = $db->getdata("select * from `dmf_order` order by id desc limit $start,$per_page");
$count = $db->total('dmf_order');
//$rs = $db->getdata("select * from `dmf_order` order by id desc");
//print_r($rs);

if(empty($rs)){	
$arr['total'] = 0;     
$_str = '<li>什么都没有~~</li>';
}else{
      $arr['total'] = $count; 
            	// $_str = '<div class="weui-cells">';
      $_str = '';
      foreach ($rs as $v){
		      
            	  	$_str .='<li><h2><a class="layui-badge">'.$v['user'].'</a> <a href="">施舍'.$v['oje'].'元</a></h2><div class="fly-list-info"><a href="" link><cite>'.$v['otime'].'</cite> </a><span>'.($v['ispay']=="1"?'[已支付]':'[待支付]').'</span> <span class="fly-list-nums"><span class="fly-list-kiss layui-hide-xs" title="施舍单号">'.$v['oid'].'</span></span></div><div class="fly-list-badge"><span class="layui-badge '.($v['ispay']=="1"?'layui-bg-red':'layui-bg-blue').'">'.($v['ispay']=="1"?'真慷慨':'假大方').'</span></div></li>';
         }
            	 
            	  //$_str .= '</div>';
            	  //echo $_str;
 }   
$arr['html'] = $_str;
echo json_encode($arr);
}elseif($c==='order'){
$outTradeNo = date('Ymd').uniqid();     //你自己的商品订单号，不能重复
$payAmount = $n;          //付款金额，单位:元
$orderName = '在线施舍';    //订单标题 
if($m==='new'){unset($_SESSION[TOKEN.'_OID']);}
if(empty($_SESSION[TOKEN.'_OID'])){
$aliPay = new AlipayService();
$aliPay->setAppid($appid);
$aliPay->setNotifyUrl($notifyUrl);
$aliPay->setRsaPrivateKey($rsaPrivateKey);
$aliPay->setTotalFee($payAmount);
$aliPay->setOutTradeNo($outTradeNo);
$aliPay->setOrderName($orderName);
$result = $aliPay->doPay();
$result = $result['alipay_trade_precreate_response'];
if($result['code'] && $result['code']=='10000'){ 
	$arr['oid'] = $outTradeNo;
	$arr['oje'] = $payAmount;
	$arr['pay_url'] = $result['qr_code'];	
	$arr['user'] = '匿名施主';
	$sql = arr_sql('dmf_order','insert',$arr); 
	$db->runsql($sql,$arr);
	$_SESSION[TOKEN.'_OID'] = $outTradeNo;
    exit ('{"status":"200","new":"1","url":"'.$result['qr_code'].'","qr":"'.url_qr($result['qr_code']).'","oid":"'.$outTradeNo.'"}');

}else{
    exit ('{"status":"500","message":"'.$result['sub_msg'].'"}');
   // echo $result['msg'].' : '.$result['sub_msg'];
}
}else{
   $arr['oid'] = $_SESSION[TOKEN.'_OID'];
   $rs = $db->getline("select * from dmf_order where oid=:oid",$arr);
   if(empty($rs)){
      exit ('{"status":"500","message":"支付订单生成失败"}');
   }else{
	 //$result = $rs[0];
	 $otime = strtotime($rs['otime']);
	 $now = time();
	 if($now-$otime>1180){
	 	unset($_SESSION[TOKEN.'_OID']);
        exit ('{"status":"400","message":"订单已过期"}');        
	 }else{
        exit ('{"status":"200","new":"0","url":"'.$result['qr_code'].'","qr":"'.url_qr($rs['pay_url']).'","oid":"'.$arr['oid'].'"}');
	 }
   }
}
} else{
//异步通知   
    
$aliPay = new AlipayService($alipayPublicKey);
//验证签名
$result = $aliPay->rsaCheck($_POST);
if($result===true){
    //处理你的逻辑，例如获取订单号$_POST['out_trade_no']，订单金额$_POST['total_amount']等
    //程序执行完后必须打印输出“success”（不包含引号）。如果商户反馈给支付宝的字符不是success这7个字符，支付宝服务器会不断重发通知，直到超过24小时22分钟。一般情况下，25小时以内完成8次通知（通知的间隔频率一般是：4m,10m,10m,1h,2h,6h,15h）；
    $oid = $_POST['out_trade_no'];
    $oje = $_POST['total_amount'];
    $ptime = $_POST['gmt_payment'];
    $trade_no = $_POST['trade_no'];
	$user =  $_POST['buyer_logon_id'];
	$db = new Db();
    $rs = $db->getline("select * from dmf_order where oid=:oid",array('oid'=>$oid));
	if(!empty($rs) && $rs['oje'] = $oje  ){
	   $db->runsql("update dmf_order set user=:user,ispay=1,ptime=:ptime,trade_no=:trade_no where oid=:oid",array('user'=>$user,'oid'=>$oid,'ptime'=>$ptime,'trade_no'=>$trade_no));
	}    
   exit('success');
}else{
   exit('error');
}
}

function url_qr($m){
   include_once 'qrcode.class.php'; 
   $level = 'L'; 
   $size = 4;
   ob_start();//开启缓冲区
   QRcode::png($m, false, $level, $size);//生成二维码
   $img = ob_get_contents();//获取缓冲区内容
   ob_end_clean();//清除缓冲区内容
   return 'data:png;base64,'.urlencode(chunk_split(base64_encode($img)));//转base64
   ob_flush();
}