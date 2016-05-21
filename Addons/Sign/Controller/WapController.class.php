<?php

namespace Addons\Sign\Controller;

use Home\Controller\AddonsController;
include_once("phpqrcode.php");

class WapController extends AddonsController {
    var $model;
    var $sign_id;
    function index() {
        //session_start();
        $isWeixinBrowser = isWeixinBrowser ();
        if (! $isWeixinBrowser) {
          $this->error ( '请在微信里打开' );
        }
        $appinfo = get_token_appinfo();//获取公众号信息
        $this->model = $this->getModel ( 'sign_value' );
        $this->sign_id = I ( 'sign_id', 0 );
        $id = I ( 'id', 0 );
        //活动信息
        $sign = M ( 'sign' )->find ( $this->sign_id );
        $sign ['cover'] = ! empty ( $sign ['cover'] ) ? get_cover_url ( $sign ['cover'] ) : ADDON_PUBLIC_PATH . '/background.png';

        $sign ['guize'] = nl2br($sign['guize']);
        $involer_numbers = 0;//默认邀请人数

        $get_tj_uid    = I('get.tj_uid');
        if($get_tj_uid)
        {
            $tj_uid     =    $get_tj_uid;
        }else
        {
            $tj_uid =    0;
        }
        // 防止自己推荐自己
        if($this->mid == $tj_uid){
          $tj_uid =    '0';
        }
        // 判断该用户是否已经报名该活动
        $map ['uid'] = $this->mid;
        $map ['token'] = get_token ();
        $map ['sign_id'] = $this->sign_id;
        $map ['openid'] = get_openid ();
        $data = M ( 'sign_value' )->where ( $map )->find ();
        logs("[sign_value] wheremap=" . json_encode($map));

        //获取用户是否关注
        $map_follow    =    array();
        $map_follow['token']    =    $map ['token'];
        $map_follow['openid']    =    $map ['openid'];
        $followinfo    =    M ( 'public_follow' )->where($map_follow)->find();
        $qrcodeimg = '';//初始化
        
        //没有报名时才出二维码，要加判断条件
        if(empty($data)){
          $qrcodeimg = D('Home/QrCode')->add_qr_code('QR_LIMIT_STR_SCENE', 'Sign', $sign['id'], '1', $this->sign_id.'_'.$tj_uid."_".$this->mid);
        }
        $user2 = getWeixinUserInfo ( $map ['openid'] );
        if ($user2['subscribe'] == 1) {
         $newdata ['has_subscribe'] = 1;
          
          // $newdata ['headimgurl'] = empty ( $user2 ['headimgurl'] ) ? SITE_URL . '/Public/static/face/default_head_50.png' : $user2 ['headimgurl'];
         $newdata ['headimgurl'] = empty ( $user2 ['headimgurl'] ) ? SITE_URL . '/Public/static/face/default_head_90.png' : $user2 ['headimgurl'];

          $newdata['nickname']  = $user2['nickname'];
          M ( 'public_follow' )->where($map_follow)->save ( $newdata );
          $needinfo = array();
          $needinfo['nickname'] = $user2['nickname'];
          $needinfo['headimgurl'] = $newdata ['headimgurl'];
          

           D ( 'Common/User' )->updateInfo ($this->mid, $needinfo );
        }
        //已邀请人数
        $map_yq    =    array();
        $map_yq['tj_uid']    =    $this->mid;
        $involer_numbers    =    M ( 'sign_value' )->where ( $map_yq )->count ();

        $id = $data ['id'];
        if (! empty ( $id )) {
            $value = unserialize ( htmlspecialchars_decode ( $data ['value'] ) );
            unset ( $data ['value'] );
            $data = array_merge ( $data, $value );
        } 
        
        $map2 ['sign_id'] = $this->sign_id;
        $fields = M ( 'sign_attribute' )->where ( $map2 )->order ( 'sort asc, id asc' )->select ();
        foreach ( $fields as &$fd ) {
            $fd ['name'] = 'field_' . $fd ['id'];
        }
    
        $fields [] = array (
                'is_show' => 4,
                'name' => 'sign_id',
                'value' => $this->sign_id 
        );

        //获取报名价格
        $price = $this->get_price_money($data, $sign, $involer_numbers);
 
        // 状态
        $status = array (
            // 是否开始
            'isBegin' => $sign['start_time'] && $sign['start_time'] > time() ? false : true,
            // 是否结束
            'isEnd' => $sign['end_time'] && $sign['end_time'] < time() ? true : false,
            // 是否满额
            'isFull' => $sign['max_limit'] > 0 && $sign['join_count'] == $sign['max_limit'],
            // 是否已报名
            'isSigned' => empty($data) ? false : true,
            // 是否可以展示邀请入口：已报名
            'isCanInvite' => empty($data) == false ? true : false,
            'is_pay' => $data['is_pay']
        );
        //根据openid获取用户信息
       $info = M ( 'public_follow' )->where($map_follow)->find ();

        //没有关注时加默认头像
        // $info ['headimgurl'] = empty ( $info ['headimgurl'] ) ? SITE_URL . '/Public/static/face/default_head_50.png' : $info ['headimgurl'];
       $info ['headimgurl'] = empty ( $info ['headimgurl'] ) ? SITE_URL . '/Public/static/face/default_head_90.png' : $info ['headimgurl'];

        $this->assign('userInfo', array(
            'face' => $info['headimgurl'],
            'name' => $info['nickname']
        ));

        $this->assign('data', $data); // 当前价
        $this->assign('qrcodeimg',$qrcodeimg); // 
        $this->assign('uid', $this->mid); // 
        $this->assign('involer_numbers', $involer_numbers); // 已邀请人数
        $this->assign('appinfo', $appinfo ); // 公共号信息
        $this->assign('sign', $sign); // 活动表单项
        $this->assign('price', $price); // 当前价
        $this->assign('fields', $fields); // 附加字段
        $this->assign('status', $status); // 状态，是否开始、结束、满额、已报名、可展示邀请
        $this->display (SITE_PATH . '/Addons/Sign/View/sign.html');
    }

    // 报名提交
    function post() {
      //session_start();
      $appinfo = get_token_appinfo();//获取公众号信息
      if (!IS_POST) {$this->error ( '非法请求' );eixt;}

      $openid = get_openid ();
      $token = get_token ();
      $this->model = $this->getModel ( 'sign_value' );
      $this->sign_id = I ( 'sign_id', 0 );

      // 判断该用户是否已经报名该活动
      $map ['uid'] = $this->mid;
      $map ['token'] = $token;
      $map ['sign_id'] = $this->sign_id;
      $map ['openid'] = $openid;
      if (M ( 'sign_value' )->where ( $map )->count() > 0) {
        $this->error ( '您已报名过' );
        exit;
      }

      //活动信息
      $sign = M ( 'sign' )->find ( $this->sign_id );

      // 计算出推荐ID
      $get_tj_uid = I('get.tj_uid');
      if($get_tj_uid){
        //session('tj_uid', $get_tj_uid);
        $tj_uid = $get_tj_uid;
      }else {
        $tj_uid = 0;
      }

      $map2 ['sign_id'] = $this->sign_id;
      $fields = M ( 'sign_attribute' )->where ( $map2 )->order ( 'sort asc, id asc' )->select ();

      foreach ( $fields as $vo ) {
        $post [$vo ['name']] = $_POST [$vo ['name']];
        if (is_array ( $_POST [$vo ['name']] )) {
          $post [$vo ['name']] = implode ( ',', $_POST [$vo ['name']] );
        }
        unset ( $_POST [$vo ['name']] );
      }
      
      $_POST ['value'] = serialize ( $post );
      $_POST ['uid'] = $this->mid;
      $_POST ['tj_uid'] = $tj_uid;

      $Model = D ( parse_name ( get_table_name ( $this->model ['id'] ), 1 ) );
      
      // 获取模型的字段信息
      $Model = $this->checkAttr ( $Model, $this->model ['id'], $fields );
      
      if ($Model->create () && $res = $Model->add ()) {
        //组装token和openid信息
        $otherparam = array();
        $otherparam['token'] = $token; 
        $otherparam['openid']  = $openid;
        //增加报名后的处理
        D('Sign')->add_sign_value($sign,$this->mid,$res,$tj_uid,$appinfo,$otherparam);

        $param =  array();
        $param ['sign_id'] = $this->sign_id;
        $param ['id'] = $res;
        $param ['publicid'] = $appinfo['id'];
        $param ['model'] = $this->model ['id'];

        $url = U ( 'sign_success', $param ) ;

        $this->success ( $tip, $url, 5 );
      } else {
        $this->error ( $Model->getError () );
      }
    }

    // 申请表
    function userinfo() {
      session_start();
      $appinfo = get_token_appinfo();//获取公众号信息
      $this->model = $this->getModel ( 'sign_value' );
      $this->sign_id = I ( 'sign_id', 0 );
      $id = I ( 'id', 0 );
      //活动信息
      $sign = M ( 'sign' )->find ( $this->sign_id );
      $sign ['cover'] = ! empty ( $sign ['cover'] ) ? get_cover_url ( $sign ['cover'] ) : ADDON_PUBLIC_PATH . '/background.png';

      $sign ['guize'] = nl2br($sign['guize']);

      // 判断该用户是否已经报名该活动
      $map ['uid'] = $this->mid;
      $map ['token'] = get_token ();
      $map ['sign_id'] = $this->sign_id;
      $map ['openid'] = get_openid ();
      $data = M ( 'sign_value' )->where ( $map )->find ();

      $id = $data ['id'];
      if (! empty ( $id )) {
        $value = unserialize ( htmlspecialchars_decode ( $data ['value'] ) );
        unset ( $data ['value'] );
        $data = array_merge ( $data, $value );
      } 
      
      $map2 ['sign_id'] = $this->sign_id;
      $fields = M ( 'sign_attribute' )->where ( $map2 )->order ( 'sort asc, id asc' )->select ();
      foreach ( $fields as &$fd ) {
        $fd ['name'] = 'field_' . $fd ['id'];
      }

      $fields [] = array (
          'is_show' => 4,
          'name' => 'sign_id',
          'value' => $this->sign_id 
      );

      $this->assign('data', $data); // 当前价
      $this->assign('sign', $sign); // 活动表单项
      $this->assign('fields', $fields); // 附加字段
      $this->display(SITE_PATH . '/Addons/Sign/View/form_shenqing.html');
    }

    // 申请表提交
    function userpost() {
      session_start();
      $appinfo = get_token_appinfo();//获取公众号信息
      if (!IS_POST) {$this->error ( '非法请求' );eixt;}

      $openid = get_openid ();
      $token = get_token ();
      $this->model = $this->getModel ( 'sign_value' );
      $this->sign_id = I ( 'sign_id', 0 );

      // 判断该用户是否已经报名该活动并完成pay
      $map ['uid'] = $this->mid;
      $map ['token'] = $token;
      $map ['sign_id'] = $this->sign_id;
      $map ['openid'] = $openid;
      $map ['is_pay'] = 1;
      if (M ( get_table_name ( $this->model ['id'] ) )->where ( $map )->count() == 0) {
        $this->error ( '您暂未报名或不满足申请资格' );
        exit;
      }

      // 获取申请表字段
      $map2 ['sign_id'] = $this->sign_id;
      $fields = M ( 'sign_attribute' )->where ( $map2 )->order ( 'sort asc, id asc' )->select ();
      foreach ( $fields as &$fd ) {
        $fd ['name'] = 'field_' . $fd ['id'];
      }

      // 检验申请表字段
      foreach ( $fields as $vo ) {
        $value = $_POST [$vo ['name']];
        if ($vo ['is_must'] &&  $value == '') {
          $this->error ( '请输入' . $vo ['title'] );
          exit ();
        }

        $post [$vo ['name']] = $_POST [$vo ['name']];
        if (is_array ( $_POST [$vo ['name']] )) {
          $post [$vo ['name']] = implode ( ',', $_POST [$vo ['name']] );
        }
        unset ( $_POST [$vo ['name']] );
      }

      $save['sign_id'] = $this->sign_id;
      $save['uid'] = $this->mid;
      $save['openid'] = $openid;
      $save['token'] = $token;

      M ( get_table_name ( $this->model ['id'] ) )->where ( $save )->save ( array('value' => serialize ( $post )));

      $param ['sign_id'] = $this->sign_id;
      $param ['publicid'] = $appinfo['id'];

      //发送申请表成功消息通知
      $sign = M ( 'sign' )->find ( $this->sign_id );
      $userinfoUrl = U ( 'userinfo', $param );
      $tempMsgstatus = D ( 'Common/TemplateMessage' )->signSuccess($this->mid, '恭喜您，您已正式被录取，请耐心等待我们的联系。', $sign['title'] ,'已录取', $appinfo['template_id'], ''); 

      $this->success ( '提交成功，请耐心等待通知', addons_url('Sign://Wap/index', $param), 5 );
    }

    // 报名成功
    function sign_success() {
        $appinfo = get_token_appinfo();//获取公众号信息
        $token = get_token();
        $map3 ['sign_id'] = $map ['sign_id'] = $map2 ['sign_id'] = $this->sign_id = I ( 'sign_id', 0, intval );
        $sign = M ( 'sign' )->find ( $this->sign_id );
        $sign ['cover'] = ! empty ( $sign ['cover'] ) ? get_cover_url ( $sign ['cover'] ) : ADDON_PUBLIC_PATH . '/background.png';
        $sign ['intro'] = str_replace ( chr ( 10 ), '<br/>', $sign ['intro'] );
        
        $map2 ['token'] = $map3 ['token'] = $token;

        // 获取参与人数
        $attend = $sign['join_count'];
        
        $map3 ['uid'] = $this->mid;
        $valueArr = M ( 'sign_value' )->where ( $map3 )->find ();
  
        $price = 0;

        // 计算已推荐人数
        $tjmap    =    array();
        $tjmap['tj_uid'] = $this->mid;
        $hasInvolerNumber = M ( 'sign_value' )->where ( $tjmap )->count ();

        //获取报名价格
        $price = $this->get_price_money($valueArr, $sign, $hasInvolerNumber);

        $promotion_minute_leve = $sign['promotion_minute'] * 60 - (time() - $valueArr['cTime']); 

        // 生成支付连接
        $param = array (
          'aim_id'=>$sign['id'],
          'orderName' => $sign['title'],
          'single_orderid' => date ( 'YmdHis' ) . substr ( uniqid (), 4 ),
          'price' => $price,
          'token' => $token,
          'wecha_id' => get_openid(),
          'umid' => md5($token.$price),
          'paytype' =>0
        );
        $payUrl=addons_url('Signpay://Weixin/pay', $param);
        $this->assign ( 'noSigned', empty($valueArr) );
        $this->assign ( 'payUrl', $payUrl ); // 支付连接
        $this->assign ( 'is_pay', $valueArr['is_pay'] ); // 支付金额
        $this->assign ( 'price', $price ); // 支付金额
        $this->assign ( 'hasInvolerNumber', $hasInvolerNumber ); // 已邀请人数
        $this->assign ( 'needInvolerNumber', $sign['tj_count'] ); // 需邀请人数
        $this->assign ( 'promotion_minute_leve', $promotion_minute_leve ); // 是否在优惠时间段内
        $this->assign ( 'uid', $this->mid );
        $this->assign ( 'sign', $sign );
        $this->assign ( 'attend_num', $attend );
        $this->display (SITE_PATH . '/Addons/Sign/View/sign_result.html');
    }

    // 支付成功
    function pay_success() {
        $this->display (SITE_PATH . '/Addons/Sign/View/pay_success.html');
    }

    /**
     * 获取报名后的价格
     * @param  [type] $valuedata       报名后的信息array
     * @param  [type] $sign 报名条件信息
     * @param  [type] $hasInvolerNumber 已邀请人数
     * @return [type]   price               返回价格
     */
    public function get_price_money($valuedata, $sign, $hasInvolerNumber){
        $price = $sign['money'];
        if(empty($valuedata)){
            // 未报名
            return $price = $sign ['promotion_new'];
        } 
        // 计算需支付金额
        if($valuedata['is_pay'] == 0){
            $involer_date =    time()-$valuedata['cTime'];
            // if($hasInvolerNumber >= $sign['tj_count']){// 满足推荐
            //     $price = $sign ['promotion_involer'];
            // }else 
            if($involer_date < ($sign['promotion_minute']*60)){ // 满足刚报名时间
                $price = $sign ['promotion_new'];
            }
        }
        return $price;
    }

}
