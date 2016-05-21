<?php
          
namespace Addons\Sign\Model;
use Home\Model\WeixinModel;
          
/**
 * Sign的微信模型
 */
class WeixinAddonModel extends WeixinModel{
  function reply($dataArr, $keywordArr = array()) {
    logs('[singn/model/winxin/reply] dataArr=['. json_encode($dataArr) .'] keywordArr='. json_encode($keywordArr));
    $map['token'] = get_token();
    if (! empty ( $keywordArr ['aim_id'] )) {
      $map ['id'] = $keywordArr ['aim_id'];
    }

    //组装用户在微信里点击图文的时跳转URL
    //其中token和openid这两个参数一定要传，否则程序不知道是哪个微信用户进入了系统
    $param =  array();    
    $param ['token'] = get_token ();
    $param ['openid'] = get_openid ();
    $url = addons_url ( 'Sign://SignValue/add', $param );
    
    //增加场景ID接收
    if ($dataArr ['MsgType']=='event'){
      if(($dataArr ['Event']=='subscribe'&&$dataArr['Ticket'])||$dataArr ['Event']=='SCAN'){
          $uid = $GLOBALS ['mid'];
          logs('[singn/model/winxin/subscribe] subscribe mid='. $uid);
          if($uid) {
            $user2 = getWeixinUserInfo ( $param ['openid'] );
            if ($user2['subscribe'] == 1) {
              $newdata =  array();
              $newdata ['has_subscribe'] = 1;
              $newdata ['headimgurl'] = empty ( $user2 ['headimgurl'] ) ? SITE_URL . '/Public/static/face/default_head_90.png' : $user2 ['headimgurl'];

              $newdata['nickname']  =  $user2['nickname'];
              M ( 'public_follow' )->where($map_follow)->save ( $newdata );
              $needinfo =  array();
              $needinfo['nickname']  =  $user2['nickname'];
              $needinfo['headimgurl']  =  $newdata ['headimgurl'];
              D ( 'Common/User' )->updateInfo ($uid, $needinfo );
            }
            if(isset($dataArr['Ticket'])&&!empty($dataArr['Ticket'])){
              //带二维码场景扫描的加入
              $this->add_sign($param, $uid, $dataArr['Ticket']);
            }
          }
          return true;
      }else if($dataArr ['Event']=='unsubscribe'){
          //取消关注时回调信息
          logs('[singn/model/winxin/unsubscribe] event:unsubscribe');
          // 直接删除用户
          $map2 ['uid'] = $GLOBALS ['mid'];
          // M ( 'public_follow' )->where ( $map2 )->delete ();
          // logs('delsql1:'.M()->_sql());
          // M ( 'user' )->where ( $map2 )->delete ();
          // logs('delsql2:'.M()->_sql());
          // M ( 'sign_value' )->where ( $map2 )->delete ();//删除报名信息
          //去掉用户先关信息
          session ( 'mid', null );
          session ( 'manager_menu_default', null );      
          session ( 'user_auth', null );
          session ( 'user_auth_sign', null );
          session ( 'token', null );
          session ( 'openid_' . $map1 ['token'], null );
          session ( 'manager_id', null );
          return true;
      }
    }
  }

  //增加报名信息，发送消息
  /**
   * [add_sign description]
   * @param [type] $param     [description]
   * @param [type] $uid       [description]
   * @param [type] $ticket [场景二维码地址]
   */
  function add_sign($param,$uid,$ticket){
    $data =  array();
    $data['is_check']  =  0;
    
    $data['value']    =  'a:1:{s:0:"";N;}';
    $data['cTime']    = time();
    $data['openid']  =  $param['openid'];
    $data['uid']  =  $uid;
    $data['token']  =  $param['token'];
    $data['is_pay']  =  0;
    $qrcode ='https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket;
    $qrcodeinfo = M('qr_code')->where(array('qr_code'=>$qrcode))->find();
    $extra_text = $qrcodeinfo['extra_text'];

    if(strpos($extra_text, '_')){
      $strarray =  explode('_',$extra_text);

      $sign_id = $strarray[0];
      $tj_uid = $strarray[1]?$strarray[1]:0;
      logs('[add_sign] tj_uid:'.$tj_uid);

      $data['sign_id']  =  $sign_id;

      $data['tj_uid']  = $tj_uid;

      $checkresult =   M('sign_value')->where(array('sign_id'=>$sign_id,'uid'=>$uid))->find();
      if($checkresult){
        return false;//去充处理
      }
      $result = M('sign_value')->add($data);
      if(!$result)
      {
        logs('signadderror:'.M()->_sql, 'error');//记录报名失败sql
      }
      $sign_info =  M('sign')->where(array('id'=>$sign_id))->find();

      $appinfo = get_token_appinfo();
      $params =  array();
      $params['sign_id'] = $sign_id;
      $params ['id'] = $result;
      $params ['publicid'] = $appinfo['id'];
      $url_index = addons_url('Sign://Wap/index',$params);

      //顺利获取Sign Model 参加插件model 调用方法
      defined ( '_ADDONS' ) or define ( '_ADDONS', 'Sign' );
      defined ( 'ADDON_PUBLIC_PATH' ) or define ( 'ADDON_PUBLIC_PATH', ONETHINK_ADDON_PATH . 'Sign/View/default/Public' );
      
      //增加报名后的处理
      D('Sign')->add_sign_value($sign_info,$uid,$result,$tj_uid,$appinfo,$param);
    }
  }
}
          