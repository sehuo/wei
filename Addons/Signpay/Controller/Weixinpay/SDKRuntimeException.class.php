<?php
namespace Addons\Signpay\Controller;
class  SDKRuntimeException extends \Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}

}

?>