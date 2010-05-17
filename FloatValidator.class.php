<?php
class FloatValidator extends TypeValidator {
	public function validate(&$value, $validation) {
		return preg_match('/^\d*.?\d+$/', $value)?true:false;
	}
	public function compile(&$value, $validation) {
		
	}
}
?>