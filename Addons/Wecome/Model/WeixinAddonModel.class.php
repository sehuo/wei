<?php

namespace Addons\Wecome\Model;

use Home\Model\WeixinModel;

/**
 * Vote模型
 */
class WeixinAddonModel extends WeixinModel {
	function reply($dataArr, $keywordArr = array()) {
		$config = getAddonConfig ( 'Wecome' ); // 获取后台插件的配置参数
		if ($dataArr ['Content'] == 'subscribe') {
			$uid = D ( 'Common/Follow' )->init_follow ( $dataArr ['FromUserName'] );
			D ( 'Common/Follow' )->set_subscribe ( $dataArr ['FromUserName'], 1 );
			// 增加积分
			session ( 'mid', $uid );
			
			$has_return = false;
			if (! empty ( $dataArr ['EventKey'] )) {
				$has_return = $this->scan ( $dataArr, $keywordArr, $config );
			}
			if ($has_return)
				return true;
				
				// 其中token和openid这两个参数一定要传，否则程序不知道是哪个微信用户进入了系统
			$param ['token'] = get_token ();
			$param ['openid'] = get_openid ();
			
			$sreach = array (
					'[follow]',
					'[token]',
					'[openid]' 
			);
			$replace = array (
					addons_url ( 'UserCenter://Wap/bind', $param ),
					$param ['token'],
					$param ['openid'] 
			);
			$config ['description'] = str_replace ( $sreach, $replace, $config ['description'] );
			
			switch ($config ['type']) {
				case '3' :
				    $res = D ( 'Common/Custom' )->replyNews ( $uid, $config ['appmsg_id'] );
						// $res = $this->replyNews ( $articles );
					break;
				case '2' :
					return false;
					break;
				default :
					$res = $this->replyText ( $config ['description'] );
			}
		} elseif ($dataArr ['Content'] == 'scan') {
			logs('wecome model reply scan');
			$this->scan ( $dataArr, $keywordArr, $config );
		} elseif ($dataArr ['Content'] == 'unsubscribe') {
			// 直接删除用户
			$map1 ['openid'] = $dataArr ['FromUserName'];
			$map1 ['token'] = get_token ();
			$map2 ['uid'] = D ( 'public_follow' )->where ( $map1 )->getField ( 'uid' );
			M ( 'public_follow' )->where ( $map1 )->delete ();
			M ( 'user' )->where ( $map2 )->delete ();
			M ( 'credit_data' )->where ( $map2 )->delete ();
			session ( 'mid', null );
		}
	}
	function scan($dataArr, $keywordArr = array(), $config = array()) {
		$map ['scene_id'] = ltrim ( $dataArr ['EventKey'], 'qrscene_' );
		$map ['token'] = get_token ();
		$qr = M ( 'qr_code' )->where ( $map )->find ();
		if ($qr ['addon'] == 'UserCenter') { // 设置用户分组
			$group = D ( 'Home/AuthGroup' )->move_group ( $GLOBALS ['mid'], $qr ['aim_id'] );
			
			$this->replyText ( '您已加入' . $group ['title'] );
			return true; // 告诉上面的关注方法，不需要再回复欢迎语了
		} else if ($qr ['addon'] == 'Shop') {
			$savedata ['openid'] = $map1 ['openid'] = $dataArr ['FromUserName'];
			$map1 ['token'] = get_token ();
			$followId = M ( 'public_follow' )->where ( $map1 )->getField ( 'uid' );
			
			$savedata ['duid'] = $qr ['aim_id'];
			$savedata ['uid'] = $followId;
			$res1 = M ( 'shop_statistics_follow' )->where ( $map1 )->getField ( 'id' );
			if (! $res1) {
				$savedata ['ctime'] = time ();
				$savedata ['token'] = get_token ();
				M ( 'shop_statistics_follow' )->add ( $savedata );
			}
		} elseif ($qr ['addon'] == 'HelpOpen') {
			$user = getUserInfo ( $qr ['extra_int'] );
			$url = addons_url ( 'HelpOpen://Wap/index', array (
					'invite_uid' => $qr ['extra_int'],
					'id' => $qr ['aim_id'] 
			) );
			$this->replyText ( "关注成功，<a href='{$url}'>请点击这里继续帮{$user[nickname]}领取奖品</a>" );
			return true; // 告诉上面的关注方法，不需要再回复欢迎语了
		} elseif ($qr ['addon'] == 'CouponShop') {
			// 门店二维码
			// 触发会员卡图文
			$config = getAddonConfig ( 'Card' ); // 获取后台插件的配置参数
			$articles [0] = array (
					'Title' => '点击进入免费领取微会员哦~',
					'Description' => $config ['title'],
					'PicUrl' => SITE_URL . "/Addons/Card/View/default/Public/cover_pic.png",
					'Url' => addons_url ( 'Card://Wap/index', array (
							'token' => get_token () 
					) ) 
			);
			$res = $this->replyNews ( $articles );
			return true; // 告诉上面的关注方法，不需要再回复欢迎语了
		}
	}
}
