<?php

namespace Addons\Pages\Controller;

use Home\Controller\AddonsController;

class BaseController extends AddonsController {
    var $config;
    function _initialize() {
        parent::_initialize ();
        
        $controller = strtolower ( _CONTROLLER );
        $this->assign ( 'nav', array () );
        
        $config = getAddonConfig ( 'Pages' );
        $this->config = $config;
        $this->assign ( 'config', $config );
        
        // 定义模板常量
        $act = strtolower ( _ACTION );
        $temp = $config ['template_' . $act];
        $act = ucfirst ( $act );
        $this->assign ( 'page_title', $config ['title'] );
        define ( 'CUSTOM_TEMPLATE_PATH', ONETHINK_ADDON_PATH . 'Pages/View/default' );
    }
}
