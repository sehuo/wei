<?php

namespace Addons\Sign\Controller;

use Addons\Sign\Controller\BaseController;

class SignValueController extends BaseController {
	var $model;
	var $sign_id;
	function _initialize() {
		parent::_initialize ();
		
		$this->model = $this->getModel ( 'sign_value' );
		
		// $param ['sign_id'] = $this->sign_id = intval ( $_REQUEST ['sign_id'] );
		$param ['sign_id'] = $this->sign_id = intval ( I ( 'sign_id' ) );
		$res ['title'] = '活动列表	';
		$res ['url'] = addons_url ( 'Sign://Sign/lists' );
		$res ['class'] = '';
		$nav [] = $res;
		
		$res ['title'] = '报名用户';
		$res ['url'] = addons_url ( 'Sign://SignValue/lists', $param );
		$res ['class'] = 'current';
		$nav [] = $res;
		
		$this->assign ( 'nav', $nav );
	}
	
	// 通用插件的列表模型
	public function lists() {
		// 解析列表规则
		$fields [] = 'openid';
		$fields [] = 'cTime';
		$fields [] = 'sign_id';
		
		$girds ['field'] = 'uid';
		$girds ['title'] = '用户';
		
		$list_data ['list_grids'] [] = $girds;
		
		$girds ['field'] = 'is_pay|get_paystatus';
		$girds ['title'] = '状态';
		$list_data ['list_grids'] [] = $girds;

		$girds ['field'] = 'price';
		$girds ['title'] = '付款';
		$list_data ['list_grids'] [] = $girds;

		$girds ['field'] = 'cTime|time_format';
		$girds ['title'] = '增加时间';
		$list_data ['list_grids'] [] = $girds;
		
		$map ['sign_id'] = $this->sign_id;

		$attribute = M ( 'sign_attribute' )->where ( $map )->order ( 'sort asc, id asc' )->select ();
		foreach ( $attribute as &$fd ) {
			$fd ['name'] = 'field_' . $fd ['id'];
		}
		foreach ( $attribute as $vo ) {
			$girds ['field'] = $fields [] = $vo ['name'];
			$girds ['title'] = $vo ['title'];
			$list_data ['list_grids'] [] = $girds;
			
			$attr [$vo ['name']] ['type'] = $vo ['type'];
			
			if ($vo ['type'] == 'radio' || $vo ['type'] == 'checkbox' || $vo ['type'] == 'select') {
				$extra = parse_config_attr ( $vo ['extra'] );
				if (is_array ( $extra ) && ! empty ( $extra )) {
					$attr [$vo ['name']] ['extra'] = $extra;
				}
			} elseif ($vo ['type'] == 'cascade' || $vo ['type'] == 'dynamic_select') {
				$attr [$vo ['name']] ['extra'] = $vo ['extra'];
			}
		}
		
		$fields [] = 'id';
		
		$list_data ['fields'] = $fields;

		$param ['sign_id'] = $this->sign_id;
		$param ['model'] = $this->model ['id'];
		$add_url = U ( 'add', $param );
		$this->assign ( 'add_url', $add_url );
		
		// 搜索条件
		$map = $this->_search_map ( $this->model, $fields );
		
		$page = I ( 'p', 1, 'intval' );
		$row = 20;
		
		$name = parse_name ( get_table_name ( $this->model ['id'] ), true );

		$list = M ( $name )->where ( $map )->order ( 'id DESC' )->selectPage ();
		
		$list_data = array_merge ( $list_data, $list );

		foreach ( $list_data ['list_data'] as &$vo ) {
			$value = unserialize ( $vo ['value'] );
			foreach ( $value as $n => &$d ) {
				$type = $attr [$n] ['type'];
				$extra = $attr [$n] ['extra'];
				
				if ($type == 'radio') {
					// echo "$n";
					if ($extra) {
						$d = $extra[$d];
					}
				} elseif ($type == 'checkbox') {
					$extArr = explode ( ' ', $extra [0] );
					foreach ( $d as &$v ) {
						if (isset ( $extArr [$v] )) {
							$v = $extArr [$v];
						}
					}
					$d = implode ( ', ', $d );
				} elseif ($type == 'cascade') {
					$d = getCascadeTitle ( $d, $extra );
				}
			}
			
			unset ( $vo ['value'] );
			$vo = array_merge ( $vo, $value );
			$vo ['uid'] = get_nickname ( $vo ['uid'] );
		}
		
		$this->assign ( $list_data );
		$this->assign ( 'del_button', false );
		$this->assign('search_button', false);
		$this->assign('add_button', false);
		
		
		$this->display (SITE_PATH . '/Addons/Sign/View/user_list.html');
	}
	
	// 通用插件的编辑模型
	public function edit() {
		$this->add ();
	}
	function detail() {
		$id = I ( 'id' );
		// $sign = M ( 'sign' )->find ( $id );
		$sign = D ( 'Sign' )->getInfo ( $id );
		$sign ['cover'] = ! empty ( $sign ['cover'] ) ? get_cover_url ( $sign ['cover'] ) : ADDON_PUBLIC_PATH . '/background.png';
		$this->assign ( 'sign', $sign );
		
		$this->display ();
	}
	
	// 通用插件的删除模型
	public function del() {
		// $ids = $_POST['ids'];
		// $sign_id = intval ( I ( 'sign_id' ) );
		// foreach($ids as $id){
		// 	$data = M ( 'sign_value' )->where ( array('id' => $id))->find();
		// 	print_r(array(
		// 		'tj_uid' => $data['uid'], 
		// 		'sign_id' => $this->sign_id
		// 	)); 
		// 	M ( 'sign_value' )->where ( array(
		// 		'tj_uid' => $data['uid'], 
		// 		'sign_id' => $this->sign_id
		// 	) )->save ( array('tj_uid' => '0') );
		// };
		// 移去推荐关系
		// M ( 'sign_value' )->where ( array('tj_uid' => 1, ) )->save ( array('tj_uid' => '0') );

		parent::common_del ( $this->model );
	}
}
