<?php
class IntegerValidator extends TypeValidator {
	public function validate(&$value, $validation) {
		return ctype_digit($value) || is_integer($value);
	}
	public function compile(&$value, $validation) {
		
	}
}
class IntValidator extends IntegerValidator {
}
?>