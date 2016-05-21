<?php
return array (
		'score' => array (
				'title' => '初始化积分:',
				'type' => 'radio',
				'options' => array ( 
						'0' => '0' 
				),
				'value' => '0'
		),
		'experience' => array (
				'title' => '初始化经历值:',
				'type' => 'radio',
				'options' => array ( 
						'0' => '0' 
				),
				'value' => '0'
		),
		'need_bind' => array ( // 配置在表单中的键名 ,这个会是config[random]
				'title' => '是否开启绑定:', // 表单的文字
				'type' => 'radio', // 表单的类型：text、textarea、checkbox、radio、select等
				'options' => array ( // select 和radion、checkbox的子选项
						'0' => '否' 
				),
				'value' => '0' 
		), // 表单的默认值
		'bind_start' => array (
				'title' => '绑定触发条件:',
				'type' => 'radio',
				'options' => array (
						'2' => '全部' 
				),
				'value' => '2' 
		),
		'jumpurl' => array (
				'title' => '绑定成功后默认跳转的地址:',
				'type' => 'text',
				'value' => '',
				'tip' => '为空时跳转到wesite' 
		) 
);
					