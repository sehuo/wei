<?php

namespace Addons\Sign\Model;
use Think\Model;
include_once(SITE_PATH . '/Addons/Sign/Controller/phpqrcode.php');
/**
 * Sign模型
 */
class SignModel extends Model{
  function getInfo($id, $update = false, $data = array()) {
    $key = 'Sign_getInfo_' . $id;
    $info = S ( $key );
    if ($info === false || $update) {
      $info = ( array ) (empty ( $data ) ? $this->find ( $id ) : $data);
      S ( $key, $info, 86400 );
    }
    
    return $info;
  }
  
  // 素材相关
  function getSucaiList($search = '') {
    $map ['token'] = get_token ();
    $map ['uid'] = session ( 'mid' );
    empty ( $search ) || $map ['title'] = array (
        'like',
        "%$search%" 
    );
    
    $data_list = $this->where ( $map )->field ( 'id' )->order ( 'id desc' )->selectPage ();
    foreach ( $data_list ['list_data'] as &$v ) {
      $data = $this->getInfo ( $v ['id'] );
    }
    
    return $data_list;
  }
  function getPackageData($id) {
    $info = get_token_appinfo ();
    $param ['publicid'] = $info ['id'];
    $param ['id'] = $id;
    
    $data ['sign'] = $this->getInfo ( $id );
    
    return $data;
  }
  /**
   * [add_sign_value 增加报名信息]
   * @param [type] $sign       [活动信息]
   * @param [type] $uid           [用户id]
   * @param [type] $sign_value_id [报名信息id]
   * @param [type] $tj_uid [推荐人uid]
   * @param [type] $appinfo       [公众号信息]
   * @param [type] $otherparam       [其他信息] 微信openid token等
   */
  public function add_sign_value($sign,$uid,$sign_value_id,$tj_uid,$appinfo,$otherparam)
  {
        //增加报名信息
        $map3 = array();

        $sign_id =   $sign['id'];
        $map3 ['id'] = $sign_id;
        M ( 'sign' )->where ( $map3 )->setInc ( 'join_count' );

        $param =  array();
        $param ['sign_id'] = $sign_id;
        $param ['id'] = $sign_value_id;
        $param ['publicid'] = $appinfo['id'];
        
        //发送报名成功消息通知
        $url_index  = addons_url('Sign://Wap/index', $param);
        $tempMsgstatus = D ( 'Common/TemplateMessage' )->signSuccess($uid, '已报名活动，邀请'. $sign['tj_count'] .'个好友一起可免费获得参与资格，', $sign['title'], '暂未获得参与资格，请选择以下任一种方式获得参与资格:', $appinfo['template_id'], $url_index);
        $this->sendMsg($uid, $sign,$appinfo);//发送三条消息
       
        // 给tj_uid发送邀请成功消息通知
        $tj_uid_signid = M ( 'sign_value' )->where ( array('uid' => $tj_uid) )->getFields ( 'id' );
        if($tj_uid && !empty($tj_uid_signid)){
          //获取用户是否关注
          $token = $otherparam['token'];
          $openid = $otherparam['openid'];
          $map_follow = array();
          $map_follow['token']  = $token;
          $map_follow['openid'] = $openid;
          $followinfo = M ( 'public_follow' )->where($map_follow)->find();
          logs('[singn/model/signmoel/add_sign_value] followinfo:'. json_encode($followinfo));
          //有推荐人给推荐人发送消息通知
          $yaoqing_name = $followinfo['nickname'] ? $followinfo['nickname'] : '朋友';
          $msg_text = '您的好友'.$yaoqing_name.'已接受您的邀请，报名了活动，点击详情继续邀请';
          $tempMsgstatus = D ( 'Common/TemplateMessage' )->signSuccess($tj_uid,'',$sign['title'], $msg_text, $appinfo['template_id'], $url_index);

          /* 计算推荐人是否可获得参与资格
          * 1. 获取tj_uid推荐的数量
          * 2. 比较邀请门槛，正好相等时
          *     2.1 设置is_pay
          *     2.2 推送一条消息
          */
          $tj_uid_hasInvolerNumber = M ( 'sign_value' )->where ( array('tj_uid' => $tj_uid) )->count (); 
          // 只在相等时发送，新支付状态为已支付
          if($tj_uid_hasInvolerNumber == $sign['tj_count']){
            $userinfoUrl = $userinfoUrl = addons_url('Sign://Wap/userinfo', $param );
            $changestatus = M ( 'sign_value' )->where ( array(
              'sign_id' => $sign_id,
              'uid' => $tj_uid
            ) )->setField('is_pay',1);
            $tempMsgstatus = D ( 'Common/TemplateMessage' )->signSuccess($tj_uid, '您已完成邀请，获得免费参与资格，请点详情，填写学员资料申请表，完成报名', $sign['title'], '点击详情填写报名表', $appinfo['template_id'],$userinfoUrl);
          }
        } 
  }

 /**
   * [sendMsg ]报名成功后消息发送
   * @return [type] [description]
   */
  public function sendMsg($uid,$sign,$appinfo=array())
  {
      if(empty($appinfo))
      {
        $appinfo = get_token_appinfo();//获取公众号信息
      }
      
      $custmsgModel =  D('Common/Custom');

      // 第一条消息
      $firstmsg = '方式一：
回复【购买】,直接付费购买参与资格';
      $custmsgModel->replyText($uid,$firstmsg);

      // 第二条 二维码消息
      $secondmsg  = '方式二：
请将你的邀请卡分享到朋友圈或好友,邀请'. $sign['tj_count'] .'个好友成功报名,免费获得参与资格。↓';
      $custmsgModel->replyText($uid,$secondmsg);

      $sign ['qrcodebg'] = ! empty ( $sign ['qrcodebg'] ) ? get_cover_url ( $sign ['qrcodebg'] ) : ADDON_PUBLIC_PATH . '/qrcodebg.jpg';

      $media_id = $this->get_qrcode_medit_id($sign['id'], $uid, $sign['qrcodebg'],$appinfo);//获取场景二维码的素材id

      // 二维码消息
      $custmsgModel->replyImage_simple($uid, $media_id);
  }

  //获取合并后的图片素材id
  public function get_qrcode_medit_id($sign_id, $mid ,$cover_url,$appinfo) 
  {
         
      $involer_info = addons_url ( 'Sign://Wap/index', array(
          'sign_id' => $sign_id,
          'publicid' => $appinfo['id'],
          'tj_uid' => $mid
      ) );
      //关注后对头像和昵称为空的进行清空处理，重新获取资料
      $followinfo    =    M ( 'public_follow' )->where(array('uid'=>$mid))->find();
      
      $face_url = $followinfo['headimgurl'];
      $involer_path='./Uploads/Download/involer_'.$sign_id;
      $involer_filename = md5($face_url) .'_'. $mid .'.jpg';
      $outerFrame = 2; 
      $pixelPerPoint = 4; 
      $jpegQuality = 50;

      // 尺寸定义 -----------------------
      // 卡片宽高
      $size_involer_w = 480; 
      $size_involer_h = 800;
      // 头像尺寸
      $size_face = 90;
      // 头像起始坐标
      $x_face = 34;
      $y_face = 255;
      // qr
      $size_qr = 122;
      $x_qr = 179;
      $y_qr = 615;

      if(empty($cover_url) || empty($sign_id) || empty($mid)) {
        logs('素材图片生成失败msg1', 'error');
        return 0;
      }
      if(!file_exists($involer_path.'/'.$involer_filename)){
        if(!file_exists($involer_path)){
          mkdir($involer_path); 
          chmod($involer_path, 0777);
        }
        // 生成二维码 bengin
        $frame = \QRcode::text($involer_info, false, 0, 4); //写入推广链接地址
        logs('[singn/model/signmoel/] qrcode info=' . $involer_info);
        $qr_length = count($frame); 
        $qr_lay_size = $qr_length + 2*$outerFrame; 
        $qr_size = $qr_lay_size * $pixelPerPoint;
        $base_image = imagecreate($qr_lay_size, $qr_lay_size);
        $qrcode_image = imagecreate($qr_size, $qr_size);
        $col[0] = imagecolorallocate($base_image, 255, 255, 255); // BG, white  
        $col[1] = imagecolorallocate($base_image, 0, 0, 0);     // FG, blue 

        imagefill($base_image, 0, 0, $col[0]); 
        for($y=0; $y<$qr_length; $y++) { 
            for($x=0; $x<$qr_length; $x++) { 
                if ($frame[$y][$x] == '1') { 
                    imagesetpixel($base_image, $x + $outerFrame, $y + $outerFrame, $col[1]);  
                } 
            } 
        } 
        imagecopyresized( 
            $qrcode_image,  
            $base_image,  
            0, 0, 0, 0,  
            $qr_size, $qr_size, $qr_lay_size, $qr_lay_size 
        );
        $qrcode_image_srcsize=imagesx($qrcode_image_srcsize);
        // 生成二维码 end
        $cover_image = imagecreatefromstring($this->curl_get($cover_url));
        $face_image = imagecreatefromstring($this->curl_get($face_url));
        $face_image_srcsize=imagesx($face_image);

        // 1. 声明场景图片
        $image=imagecreatetruecolor($size_involer_w, $size_involer_h);
        // 2. 画上像头
        imagecopyresampled($image, $face_image, $x_face, $y_face, 0, 0, $size_face, $size_face, $face_image_srcsize, $face_image_srcsize);
        // 3. 画上邀请卡背景
        imagecopymerge($image, $cover_image, 0, 0, 0, 0, $size_involer_w, $size_involer_h, 100);
        // 4. 画上二维码
        imagecopyresampled($image, $qrcode_image, $x_qr, $y_qr, 0, 0, $size_qr, $size_qr, $qr_size, $qr_size);
        // 5. 写入磁盘
        imagejpeg($image, $involer_path.'/'.$involer_filename, $jpegQuality); 
        // 6. 销毁
        imagedestroy($qrcode_image);
        imagedestroy($base_image); 
        imagedestroy($cover_image); 
        imagedestroy($face_image); 
        imagedestroy($image);
      }else{
        logs('使用邀请卡缓存：'.$involer_path.'/'.$involer_filename);
      }
      //开始上传微信图片临时素材
      $param =  array();
      $param['type'] = 'image';
      $param['media'] = '@' . realpath ($involer_path.'/'.$involer_filename );
      $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token=' . get_access_token ();

      $res = post_data ( $url, $param, true );
      if (isset ( $res ['errcode'] ) && $res ['errcode'] != 0) {
        logs('微信素材上传失败'.$res ['errmsg'], 'error');
        return 0;
      }else{
        return $res ['media_id'];
      }
  }


/**
* 发起HTTP GET请求
*/
public function curl_get($url)
{
    $oCurl = curl_init();
    if(stripos($url,"https://")!==FALSE){
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
    }
    curl_setopt($oCurl, CURLOPT_TIMEOUT, 3);
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
    $sContent = curl_exec($oCurl);
    $aStatus = curl_getinfo($oCurl);
    $error = curl_error($oCurl);
    curl_close($oCurl);
    if($error)
    {
         $sContent   =   file_get_contents($url);
         return $sContent;
    }

    if(intval($aStatus["http_code"])==200){
        return $sContent;
    }else{
        return false;
    }
}

}