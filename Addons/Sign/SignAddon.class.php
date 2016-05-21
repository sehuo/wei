<?php

namespace Addons\Sign;
use Common\Controller\Addon;

/**
 * 报名插件
 */

class SignAddon extends Addon{

    public $info = array(
        'name'=>'Sign',
        'title'=>'报名活动',
        'description'=>'',
        'status'=>1,
        'author'=>'遒',
        'version'=>'0.1',
        'has_adminlist'=>1,
        'type'=>0         
    );

	public function install() {
		$install_sql = './Addons/Sign/install.sql';
		if (file_exists ( $install_sql )) {
			execute_sql_file ( $install_sql );
		}
		return true;
	}
	public function uninstall() {
		$uninstall_sql = './Addons/Sign/uninstall.sql';
		if (file_exists ( $uninstall_sql )) {
			execute_sql_file ( $uninstall_sql );
		}
		return true;
	}

    //实现的weixin钩子方法
    public function weixin($param){

    }

}