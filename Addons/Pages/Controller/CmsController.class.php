<?php

namespace Addons\Pages\Controller;

use Addons\Pages\Controller\BaseController;

class CmsController extends BaseController {
    function _initialize() {
        $this->model = $this->getModel ( 'custom_reply_news' );
        $this->model["list_grid"] = "id:5%ID\r\ntitle:70%标题\r\nview_count:8%浏览数\r\nids:操作:[EDIT]|编辑,[DELETE]|删除,detail&_addons=Pages&_controller=Pages&id=[id]|复制链接";
        parent::_initialize ();
    }
    // 通用插件的列表模型
    public function lists() {
                
        $map ['token'] = get_token ();
        session ( 'common_condition', $map );
        
        $list_data = $this->_get_model_list ( $this->model );
        
        // 分类数据
        $map ['is_show'] = 1;
        
        
        $this->assign ( $list_data );
        // dump ( $list_data );
        
        $templateFile = $this->model ['template_list'] ? $this->model ['template_list'] : '';
        $this->display ( $templateFile );
    }
    // 通用插件的编辑模型
    public function edit() {
        $model = $this->model;
        $id = I ( 'id' );
        
        if (IS_POST) {
            $Model = D ( parse_name ( get_table_name ( $model ['id'] ), 1 ) );
            // 获取模型的字段信息
            $Model = $this->checkAttr ( $Model, $model ['id'] );
            if ($Model->create () && $Model->save ()) {
                D ( 'Common/Keyword' )->set ( $_POST ['keyword'], _ADDONS, $id, 0, 'custom_reply_news' );
                
                $this->success ( '保存' . $model ['title'] . '成功！', U ( 'lists?model=' . $model ['name'] ) );
            } else {
                $this->error ( $Model->getError () );
            }
        } else {
            $fields = get_model_attribute ( $model ['id'] );
            unset($fields['intro']);
            unset($fields['cate_id']);
            unset($fields['cover']);
            unset($fields['sort']);
            unset($fields['jump_url']);
            unset($fields['author']);
            $fields['view_count']['is_show'] = 1;
            $fields['keyword_type']['is_show'] = 0;
            $fields['keyword']['is_show'] = 0;
            

            // 获取数据
            $data = M ( get_table_name ( $model ['id'] ) )->find ( $id );
            $data || $this->error ( '数据不存在！' );
            
            $token = get_token ();
            if (isset ( $data ['token'] ) && $token != $data ['token'] && defined ( 'ADDON_PUBLIC_PATH' )) {
                $this->error ( '非法访问！' );
            }            
            
            $this->assign ( 'fields', $fields );
            $this->assign ( 'data', $data );
            $this->meta_title = '编辑' . $model ['title'];
            
            $this->display ();
        }
    }
    
    // 通用插件的增加模型
    public function add() {
        $model = $this->model;
        $Model = D ( parse_name ( get_table_name ( $model ['id'] ), 1 ) );
        
        if (IS_POST) {
            // 获取模型的字段信息
            $Model = $this->checkAttr ( $Model, $model ['id'] );
            if ($Model->create () && $id = $Model->add ()) {
                // keyword_type
                // 0:完全匹配
                // 1:左边匹配
                // 2:右边匹配
                // 3:模糊匹配
                // 4:正则匹配
                // 5:随机匹配
                // D ( 'Common/Keyword' )->set ( $_POST ['keyword'], _ADDONS, $id, $_POST ['keyword_type'], 'custom_reply_news' );
                D ( 'Common/Keyword' )->set ( $_POST ['keyword'], _ADDONS, $id, 0, 'custom_reply_news' );
                
                $this->success ( '添加' . $model ['title'] . '成功！', U ( 'lists?model=' . $model ['name'] ) );
            } else {
                $this->error ( $Model->getError () );
            }
        } else {
            $fields = get_model_attribute ( $model ['id'] );
            unset($fields['intro']);
            unset($fields['cate_id']);
            unset($fields['cover']);
            unset($fields['sort']);
            unset($fields['jump_url']);
            unset($fields['author']);
            $fields['view_count']['is_show'] = 1;
            $fields['keyword_type']['is_show'] = 0;
            $fields['keyword']['is_show'] = 0;
        
            $this->assign ( 'fields', $fields );
            $this->meta_title = '新增' . $model ['title'];
            // $this->display (ONETHINK_ADDON_PATH . 'Pages/View/default/add.html');

            $this->display ();
        }
    }
    
    // 通用插件的删除模型
    public function del() {
        parent::common_del ( $this->model );
    }
}