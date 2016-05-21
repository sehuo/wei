<?php 
// ini_set('date.timezone','Asia/Shanghai');
// error_reporting(E_ERROR);
// require_once "lib/WxPay.Data.php";
// require_once "lib/WxPay.Api.php";
// require_once "unit/WxPay.JsApiPay.php";
// require_once 'unit/log.php';

// 初始化日志
// $logHandler= new CLogFileHandler("./logs/".date('Y-m-d').'.log');
// $log = Log::Init($logHandler, 15);
// function printf_info($data)
// {
//     foreach($data as $key=>$value){
//         echo "<font color='#00ff55;'>$key</font> : $value <br/>";
//     }
// }

if(!empty($_GET)){
// 	$body=$_GET['body'];
// 	$out_trade_no=$_GET['out_trade_no'];
	$totalfee=$_GET['totalfee'];
// 	$openId=$_GET['openid'];
	$returnUrl=$_GET['returnurl'];
	$jsApiParameters=$_GET['jsApiParameters'];
	$paymentId=$_GET['paymentId'];
}
//echo $_GET['code'].'<br/>';
// // //获取用户openid
// $tools = new JsApiPay();
// // $openId = $tools->GetOpenid();
// // echo '<br/>openid:'.$openId.'<br/>';
// $input = new WxPayUnifiedOrder();
// $input->SetBody($body);
// $input->SetOut_trade_no($out_trade_no);
// $input->SetTotal_fee($totalfee*100);
// $input->SetNotify_url("http://project.weiphp.cn/weishi/WxpayAPI/notify.php");
// $input->SetTrade_type("JSAPI");
// $input->SetOpenid($openId);
// $order = WxPayApi::unifiedOrder($input);
// //echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
// //printf_info($order);
// $jsApiParameters = $tools->GetJsApiParameters($order);
//echo $jsApiParameters;

// // //统一下单

// $tools = new JsApiPay();
// $openId = $tools->GetOpenid();
// echo '<br/>openid:'.$openId.'<br/>';
// $input = new WxPayUnifiedOrder();
// $input->SetBody("test");
// $input->SetAttach("test");
// $input->SetOut_trade_no(WxPayConfig::MCHID.date("YmdHis"));
// $input->SetTotal_fee("1");
// $input->SetTime_start(date("YmdHis"));
// $input->SetTime_expire(date("YmdHis", time() + 600));
// $input->SetGoods_tag("test");
// $input->SetNotify_url("http://project.weiphp.cn/weishi/WxpayAPI/notify.php");
// $input->SetTrade_type("JSAPI");
// $input->SetOpenid($openId);
// $order = WxPayApi::unifiedOrder($input);
// echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
// printf_info($order);
// $jsApiParameters = $tools->GetJsApiParameters($order);
// echo $jsApiParameters;
?>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport"content="width=device-width,height=device-height,inital-scale=1.0,maximum-scale=1.0,user-scalable=no;">
	   <meta name="apple-mobile-web-app-capable"content="yes">
    <meta name="apple-mobile-web-app-status-bar-style"content="black">
    <meta name="format-detection"content="telephone=no">
    <title>微信支付</title>
    <style type="text/css">
		*{ padding:0; margin:0;}
    </style>
<script type="text/javascript">

function jsApiCall(){
	WeixinJSBridge.invoke(
		'getBrandWCPayRequest',
		<?php echo $jsApiParameters; ?>,
		function(res){
			WeixinJSBridge.log(res.err_msg);
			if(res.err_msg=='get_brand_wcpay_request:ok'){
						window.location.href = '<?php echo $returnUrl.'&ispay=1&paymentId='.$paymentId; ?>';	
				}
		}
	);
}

		
if (typeof WeixinJSBridge == "undefined"){
    if( document.addEventListener ){
        document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
    }else if (document.attachEvent){
        document.attachEvent('WeixinJSBridgeReady', jsApiCall); 
        document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
    }
}else{
    jsApiCall();
}
</script>
</head>
<body>
    <div id="successDom" style="display:none">
    	<span>支付成功</span>
      <span>您已支付成功，页面正在跳转...</span>
    </div>
</body>
</html>