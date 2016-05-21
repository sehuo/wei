<?php
namespace Addons\Signpay\Controller;

use Home\Controller\AddonsController;

class WeixinController extends AddonsController
{

    public $token;

    public $wecha_id;

    public $payConfig;

    public function __construct()
    {
        parent::__construct();
        
        $this->token = get_token();
        $this->wecha_id = get_openid();
        // 读取配置
        $pay_config_db = M('signpay_set');
        $signpaySet = $pay_config_db->where(array(
            'token' => $this->token
        ))->find();
        if ($signpaySet['wx_cert_pem'] && $signpaySet['wx_key_pem']){
            $ids[]=$signpaySet['wx_cert_pem'];
            $ids[]=$signpaySet['wx_key_pem'];
            $map['id']=array('in',$ids);
            $fileData=M('file')->where($map)->select();
            $downloadConfig=C(DOWNLOAD_UPLOAD);
            foreach ($fileData as $f){
                if ($signpaySet['wx_cert_pem']==$f['id']){
                    
                    $certpath=SITE_PATH.str_replace('/', '\\', substr($downloadConfig['rootPath'],1).$f['savepath'].$f['savename']);
                }else{
                    $keypath=SITE_PATH.str_replace('/', '\\', substr($downloadConfig['rootPath'],1).$f['savepath'].$f['savename']);
                }
                
            }
            $signpaySet['cert_path']=$certpath;
            $signpaySet['key_path']=$keypath;
        }
        $this->payConfig=$signpaySet;
       
        session('signpayinfo', $this->payConfig);
    }
    
    // 处理from URL字符串
    function getSignpayOpenid(){
        $callback = GetCurUrl();
        if ((defined('IN_WEIXIN') && IN_WEIXIN) || isset($_GET['is_stree']))
            return false;
        
        $callback = urldecode($callback);
        $isWeixinBrowser = isWeixinBrowser(); 
        
        if (strpos($callback, '?') === false) {
            $callback .= '?';
        } else {
            $callback .= '&';
        }
        
        $param['appid'] = $this->payConfig['wxappid'];
        
        if (! isset($_GET['getOpenId'])) {
            $param['redirect_uri'] = $callback . 'getOpenId=1';
            $param['response_type'] = 'code';
            $param['scope'] = 'snsapi_base';
            $param['state'] = 123;
            
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?' . http_build_query($param) . '#wechat_redirect';
            redirect($url);
        } else if ($_GET['state']) {
            $param['secret'] = $this->payConfig['wxappsecret'];
            $param['code'] = I('code');
            $param['grant_type'] = 'authorization_code';
            
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?' . http_build_query($param);
            $content = file_get_contents($url);
            $content = json_decode($content, true);
            return $content['openid'];
        }
    }

    public function pay(){
        header ( "Content-type: text/html; charset=utf-8" );
        require_once ('Weixinpay/WxPayData.class.php');
        require_once ('Weixinpay/WxPayApi.class.php');
        require_once ('Weixinpay/WxPayJsApiPay.php');

        // 参数数据
        // 订单名称,没有取当前 Unix 时间戳和微秒数
        $token = $_GET['token'];
        $price = $_GET ['price'];

        if (! $_GET ['price'] || $this->token != $token || $_GET['umid'] != md5($token.$price)) {
          exit ( '参数不合法' );
        }

        $single_orderid = $_GET ['single_orderid'];

        // 写入 signpay_order
        $map['single_orderid'] = $single_orderid;
        $res=M('signpay_order')->where($map)->getField('id');
        if ($res){
            $signpayId = $res;
        }else {
            $signpayId = M ( 'signpay_order' )->add ( array(
                'from' => 'sign',
                'orderName' => $_GET ['orderName'],
                'single_orderid' => $single_orderid,
                'price' => $_GET ['price'],
                'token' => $_GET['token'],
                'wecha_id' => $_GET['wecha_id'],
                'paytype' => 'Weixin',
                'showwxpaytitle' => 1,
                'aim_id' => $_GET['aim_id'],
                'uid' => $this->mid
            ) );
        }

        
        $body = $_GET['orderName'];
        $totalFee = $_GET['price'] * 100; // 单位为分

        $tools = new \JsApiPay();
        $openId = $this->getSignpayOpenid();
        // 统一下单
        import('Weixinpay.WxPayData');
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($body);
        $input->SetOut_trade_no($single_orderid);
        $input->SetTotal_fee($totalFee);
        $input->SetNotify_url("Weixinpay/notify.php");
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);

        $order = \WxPayApi::unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($order);

        $returnUrl = addons_url('Signpay://Weixin/payOK', array(
            'umid' => $_GET['umid']
        ));

        header('Location:' . SITE_URL . '/WxpayAPI/unifiedorder.php?jsApiParameters=' . $jsApiParameters . '&returnurl=' . urlencode($returnUrl) . '&totalfee=' . $_GET['price'] . '&paymentId=' . $signpayId);
    }

    public function payOK() {
        $isPay = I('get.ispay', 0, 'intval');
        $signpayId = I('get.paymentId');
        $appinfo = get_token_appinfo();

        if ($isPay) {
            $signpayDao = D('Addons://Signpay/SignpayOrder');
            $res = $signpayDao->where(array(
                'id' => $signpayId
            ))->setField('status', $isPay);

            $signpay_orderInfo = $signpayDao->where(array(
                'id' => $signpayId))->find();

            if ($res) {
                $info = $signpayDao->getInfo($signpayId, true);
                $map['order_number'] = $info['single_orderid'];
                
                $sign_data =    array();
                $sign_data['is_pay'] =  1;
                $sign_data['price'] = $signpay_orderInfo['price'];
                $singvalue = M ( 'sign_value' )->where(array('sign_id'=>$signpay_orderInfo['aim_id'],'openid'=>$signpay_orderInfo['wecha_id']))->save($sign_data);

                // 交费成功，推送报名表
                $param = array(
                    'sign_id' => $signpay_orderInfo['aim_id'],
                    'publicid' => $appinfo['id']
                );

                $userinfoUrl = addons_url('Sign://Wap/userinfo', $param);
                $tempMsgstatus = D ( 'Common/TemplateMessage' )->signSuccess($this->mid, '您已完付款，获得参与资格，请点详情填写学员资料申请表，完成报名', $signpay_orderInfo['orderName'], '已支付，请填写申请表', $appinfo['template_id'], $userinfoUrl);

                $url = addons_url('Sign://Wap/pay_success', $param);
                header('Location: ' . $url);
                // $this->success('支付成功,即将跳转', $url);    
            } else {
                $this->error('failed!');
            }
        }
    }
    // 同步数据处理
    public function return_url()
    {
        S('pay', $_GET);
        $out_trade_no = $this->_get('out_trade_no');
        if (intval($_GET['total_fee']) && ! intval($_GET['trade_state'])) {
            $okurl = addons_url($_GET['from'], array(
                "token" => $_GET['token'],
                "wecha_id" => $_GET['wecha_id'],
                "orderid" => $out_trade_no
            ));
            redirect($okurl);
        } else {
            exit('付款失败');
        }
    }

    public function notify_url()
    {
        echo "success";
        exit();
    }

    function api_notice_increment($url, $data)
    {
        $ch = curl_init();
        $header = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        $errorno = curl_errno($ch);
        if ($errorno) {
            return array(
                'rt' => false,
                'errorno' => $errorno
            );
        } else {
            $js = json_decode($tmpInfo, 1);
            if ($js['errcode'] == '0') {
                return array(
                    'rt' => true,
                    'errorno' => 0
                );
            } else {
                $this->error ( error_msg ( $js ) );                
            }
        }
    }
}
?>