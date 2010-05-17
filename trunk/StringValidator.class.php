<?php
class StringValidator extends TypeValidator {
	public function validate(&$value, $validation) {
		return is_string($value);
	}
	public function compile(&$value, $validation) {
	}
}
?>