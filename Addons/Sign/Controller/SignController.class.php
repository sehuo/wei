<?php

namespace Addons\Sign\Controller;

use Home\Controller\AddonsController;

class SignController extends AddonsController {
	var $model;
	var $sign_id;
	function lists() {
		$isAjax = I ( 'isAjax' );
		$isRadio = I ( 'isRadio' );
		$model = $this->getModel ( 'sign' );
		$page = I ( 'p', 1, 'intval' ); // 默认显示第一页数据
		                                
		// 解析列表规则
		$list_data = $this->_list_grid ( $model );
		
		// 搜索条件
		$map = $this->_search_map ( $model, $fields );
		
		$row = empty ( $model ['list_row'] ) ? 20 : $model ['list_row'];
		$order = 'id desc';
		
		// 读取模型数据列表
		$name = parse_name ( get_table_name ( $model ['id'] ), true );
		$data = M ( $name )->field ( true )->where ( $map )->order ( $order )->page ( $page, $row )->select ();
		
		foreach ( $data as &$vo ) {
			$vo ['cTime'] = time_format ( $vo ['cTime'] );
		}
		
		/* 查询记录总数 */
		$count = M ( $name )->where ( $map )->count ();
		
		$list_data ['list_data'] = $data;
		
		// 分页
		if ($count > $row) {
			$page = new \Think\Page ( $count, $row );
			$page->setConfig ( 'theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%' );
			$list_data ['_page'] = $page->show ();
		}
		if ($isAjax) {
			$this->assign ( 'isRadio', $isRadio );
			$this->assign ( $list_data );
			$this->display ( 'ajax_lists_data' );
		} else {
			$this->assign ( $list_data );
			// dump($list_data);
			
			$this->display ();
		}
	}
	function add() {
		$this->display ( 'edit' );
	}
	function edit() {
		$id = I ( 'id' );
		$model = $this->getModel ( 'sign' );
		if (IS_POST) {
			$this->checkDate();
			$act = empty ( $id ) ? 'add' : 'save';
			$Model = D ( parse_name ( get_table_name ( $model ['id'] ), 1 ) );
			// 获取模型的字段信息
			$Model = $this->checkAttr ( $Model, $model ['id'] );
			$res = false;
			$Model->create () && $res = $Model->$act ();
			if ($res !== false) {
				$act == 'add' && $id = $res;
				
				$this->_setAttr ( $id, $_POST );
				
				$this->success ( '保存成功！', U ( 'lists?model=' . $model ['name'], $this->get_param ) );
			} else {
				$this->error ( $Model->getError () );
			}
		} else {
			// 获取数据
			$data = M ( get_table_name ( $model ['id'] ) )->find ( $id );
			$data || $this->error ( '数据不存在！' );
			
			$token = get_token ();
			if (isset ( $data ['token'] ) && $token != $data ['token'] && defined ( 'ADDON_PUBLIC_PATH' )) {
				$this->error ( '非法访问！' );
			}
			$this->assign ( 'data', $data );
			
			$map ['sign_id'] = $id;

			// 字段信息
			$list = M ( 'sign_attribute' )->where ( $map )->order ( 'sort asc' )->select ();
			$this->assign ( 'attr_list', $list );
			$this->assign ( 'involer_demo', array(
				'face' => ADDON_PUBLIC_PATH.'/face.png',
				'qr' => ADDON_PUBLIC_PATH.'/qr.png'
			));
			
			$this->display ( 'edit' );
		}
	}
	// 保存字段信息
	function _setAttr($sign_id, $data) {
		$dao = M ( 'sign_attribute' );
		$save ['sign_id'] = $sign_id;
		
		$old_ids = $dao->where ( $save )->getFields ( 'id' );
		
		$sort = 0;
		foreach ( $data ['attr_title'] as $key => $val ) {
			$save ['title'] = safe ( $val );
			if (empty ( $save ['title'] ))
				continue;
			
			$save ['extra'] = safe ( $data ['extra'] [$key] );
			$save ['type'] = safe ( $data ['type'] [$key] );
			$save ['is_must'] = intval ( $data ['is_must'] [$key] );
			$save ['value'] = safe ( $data ['value'] [$key] );
			$save ['remark'] = safe ( $data ['remark'] [$key] );
			$save ['validate_rule'] = safe ( $data ['validate_rule'] [$key] );
			$save ['error_info'] = safe ( $data ['error_info'] [$key] );
			$save ['sort'] = $sort;
			
			$id = intval ( $data ['attr_id'] [$key] );
			if (! empty ( $id )) {
				$ids [] = $map ['id'] = $id;
				$dao->where ( $map )->save ( $save );
			} else {
				$save ['token'] = get_token ();
				$ids [] = $dao->add ( $save );
			}
			
			$sort += 1;
		}
		
		$diff = array_diff ( $old_ids, $ids );
		if (! empty ( $diff )) {
			$map2 ['id'] = array (
					'in',
					$diff 
			);
			$dao->where ( $map2 )->delete ();
		}
	}

	function setStatus() {
		$map ['id'] = I ( 'id', 0, 'intval' );
		$save ['status'] = I ( 'status', 0, 'intval' );
		
		$res = M ( 'sign' )->where ( $map )->save ( $save );
		echo $res === false ? 0 : 1;
	}

	function index() {
		$this->model = $this->getModel ( 'sign_value' );
		$this->sign_id = I ( 'id', 0 );
		
		$sign = M ( 'sign' )->find ( $this->sign_id );
		$sign ['cover'] = ! empty ( $sign ['cover'] ) ? get_cover_url ( $sign ['cover'] ) : ADDON_PUBLIC_PATH . '/background.png';

		$sign ['qrcodebg'] = ! empty ( $sign ['qrcodebg'] ) ? get_cover_url ( $sign ['qrcodebg'] ) : ADDON_PUBLIC_PATH . '/qrcodebg.jpg';

		$sign ['intro'] = str_replace ( chr ( 10 ), '<br/>', $sign ['intro'] );
		$this->assign ( 'sign', $sign );
		
		if (! empty ( $id )) {
			$act = 'save';
			
			$data = M ( get_table_name ( $this->model ['id'] ) )->find ( $id );
			$data || $this->error ( '数据不存在！' );
			
			// dump($data);
			$value = unserialize ( htmlspecialchars_decode ( $data ['value'] ) );
			// dump($value);
			unset ( $data ['value'] );
			$data = array_merge ( $data, $value );
			
			$this->assign ( 'data', $data );
			// dump($data);
		} else {
			$act = 'add';
			if ($this->mid != 0 && $this->mid != '-1') {
				$map ['uid'] = $this->mid;
				$map ['sign_id'] = $this->sign_id;
				
				$data = M ( get_table_name ( $this->model ['id'] ) )->where ( $map )->find ();
				if ($data && $sign ['jump_url']) {
					// redirect ( $sign ['jump_url'] );
				}
			}
		}
		
		// dump ( $sign );
		
		$map ['sign_id'] = $this->sign_id;
		$map ['token'] = get_token ();
		$fields = M ( 'sign_attribute' )->where ( $map )->order ( 'sort asc, id asc' )->select ();
		
		if (IS_POST) {
			foreach ( $fields as $vo ) {
				$error_tip = ! empty ( $vo ['error_info'] ) ? $vo ['error_info'] : '请正确输入' . $vo ['title'] . '的值';
				$value = $_POST [$vo ['name']];
				if (($vo ['is_must'] && empty ( $value )) || (! empty ( $vo ['validate_rule'] ) && ! M ()->regex ( $value, $vo ['validate_rule'] ))) {
					$this->error ( $error_tip );
					exit ();
				}
				
				$post [$vo ['name']] = $vo ['type'] == 'datetime' ? strtotime ( $_POST [$vo ['name']] ) : $_POST [$vo ['name']];
				unset ( $_POST [$vo ['name']] );
			}
			
			$_POST ['value'] = serialize ( $post );
			$act == 'add' && $_POST ['uid'] = $this->mid;
			// dump($_POST);exit;
			$Model = D ( parse_name ( get_table_name ( $this->model ['id'] ), 1 ) );
			
			// 获取模型的字段信息
			$Model = $this->checkAttr ( $Model, $this->model ['id'], $fields );
			
			if ($Model->create () && $res = $Model->$act ()) {
				
				$param ['sign_id'] = $this->sign_id;
				$param ['id'] = $act == 'add' ? $res : $id;
				$param ['model'] = $this->model ['id'];
				$url = empty ( $sign ['jump_url'] ) ? U ( 'edit', $param ) : $sign ['jump_url'];
				
				$tip = ! empty ( $sign ['finish_tip'] ) ? $sign ['finish_tip'] : '提交成功，谢谢参与';
				$this->success ( $tip, $url, 5 );
			} else {
				$this->error ( $Model->getError () );
			}
			exit ();
		}
		
		$fields [] = array (
				'is_show' => 4,
				'name' => 'sign_id',
				'value' => $this->sign_id 
		);
		
		$this->assign ( 'fields', $fields );
		
		$this->display ();
	}
	function checkDate(){
		// 判断时间选择是否正确
		
		 if (strtotime ( I ( 'post.start_time' ) ) > strtotime ( I ( 'post.end_time' ) )) {
			$this->error ( '开始时间不能大于结束时间' );
		}

		if(!I('post.attr_title')){
			$this->error('必须填写活动申请表');
		}
	}
	
}
