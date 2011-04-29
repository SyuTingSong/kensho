<?php
class KenshoLite {
	const OK = 1;
	const ERROR = 2;
	const MISSING = 3;

	public $error = array();
	public $missing = array();
	private $stopValidating = false;

	public function __construct() { }
	public function __invoke(&$var, $validation) {
		return $this->validate($var, $validation);
	}
	public function validate(&$var, $validation) {
		if($this->__process_array($var, $validation) == KenshoLite::OK)
			return true;
		else
			return false;
	}
	function parseName(&$vStr) {
		return $this->parseString($vStr, ':');
	}
	function parseIndex(&$vStr) {
		return $this->parseString($vStr, ':');
	}
	function parseString(&$vStr, $end='', $cutEnd='cutEnd') {
		$vStr = ltrim($vStr);
		$endArray = strlen($end)>1?str_split($end, 1):array($end);
		if(in_array($vStr[0], array('"', '`', "'"))) {
			$len = strlen($vStr);
			for($pos = 1; $pos < $len; $pos++) {
				if ( $vStr[$pos] == '\\' ) $pos++;
				else if ( $vStr[$pos] == $vStr[0] ) break;
			}
			if($pos >= $len) return false;
			$string = substr($vStr, 1, $pos - 1);
		} else {
			$len = strlen($vStr);
			$delimiter = array_merge(array("\r", "\n", "\t", " "), $endArray);
			for($i = 0; $i < $len; $i++) {
				if(in_array($vStr[$i], $delimiter)) break;
			}
			$string = substr($vStr, 0, $i);
			$pos = $i - 1;
		}
		$vStr = (string) substr($vStr, $pos + 1);
		if(!empty($end)) {
			$vStr = ltrim($vStr);
			if(!in_array($vStr[0], $endArray)) {
				if(!in_array(' ', $endArray)) return false;
			} else if($cutEnd == 'cutEnd'){
				$vStr = (string) substr($vStr, 1);
			}
		}
		return $string;
	}
	function parseDatatype(&$vStr) {
		return $this->parseString($vStr, '(, ', 'leaveEnd');
	}
	function parseSubValidation(&$vStr) {
		$vStr = ltrim($vStr);
		if($vStr[0] == ',') return '';
		if($vStr[0] != '(') return '';
		$counter = 1;
		for($i = 1; $i < strlen($vStr); $i++) {
			if($vStr[$i] == '\\')
				$i++;
			else if($vStr[$i] == '(')
				$counter++;
			else if($vStr[$i] == ')')
				$counter--;
			if($counter == 0) break;
		}
		if($i >= strlen($vStr)) return false;
		$string = (string) substr($vStr, 1, $i - 1);
		$vStr = (string) substr($vStr, $i + 1);
		return $string;
	}

	function processFiledValidation(&$var, $type, &$validation) {
		if ( $type == 'array' ) {
			$subTag = $this->parseName($validation);
			if($subTag === false)
				throw new ParseFailedException('You have a syntax error in your validation near '.$validation);
		} else if ( $type == 'index' ) {
			$subTag = $this->parseIndex($validation);
			if($subTag === false)
				throw new ParseFailedException('You have a syntax error in your validation near '.$validation);
		}
		$datatype = $this->parseDatatype($validation);
		$subValidation = $this->parseSubValidation($validation);
		$method = '__process_'.$datatype;
		$result = $this->$method($var[$subTag], $subValidation);

		//// process suffix
		$predefined = array(
			'missing' => array(
				'action'  => 'reject',
				'value'   => '',
				'defined' => false,
			),
			'error' => array(
				'action'  => 'reject',
				'value'   => '',
				'defined' => false,
			),
		);
		do {
			$keyword = strtolower($this->parseString($validation, ', ', 'leaveEnd'));
			if ( $keyword == 'required' ) {
				if($predefined['missing']['defined'])
					throw new ParseFailedException('Duplicated defination for required / on missing near '.$validation);
				$predefined['missing']['defined'] = true;
				$predefined['missing']['action'] = 'break';
			} else if ( $keyword == 'on' ) {
				$what = strtolower($this->parseString($validation, ', ', 'leaveEnd'));
				if ( $what == 'missing' || $what == 'error' ) {
					if($predefined[$what]['defined'])
						throw new ParseFailedException("Duplicated defination for on {$what} near ".$validation);
				} else {
					throw new ParseFailedException("You have a syntax error in your validation, unknown keyword {$what} near ".$validation);
				}
				$action = strtolower($this->parseString($validation, ', ', 'leaveEnd'));
				if ( $action == 'break' || $action == 'reject' ) {
					$predefined[$what]['action'] = $action;
				} else if($action == 'set') {
					$value = strtolower($this->parseString($validation, ', ', 'leaveEnd'));
					if($value === false) {
						throw new ParseFailedException("You have a syntax error in your validation, missing value after set near ".$validation);
					}
					if($value == 'null') $value = null;
					$predefined[$what]['action'] = 'set';
					$predefined[$what]['value'] = $value;
				} else {
					throw new ParseFailedException("You have a syntax error in your validation, unknown keyword {$action} near ".$validation);
				}
			}
		} while ( $validation[0] != ',' && $validation != '' );

		if ( $result == KenshoLite::ERROR ) {
			$what = 'error';
		} else if ( $result == KenshoLite::MISSING ) {
			$what = 'missing';
		} else if ( $result == KenshoLite::OK ) {
			$this->parseString($validation, ',');
			return true;
		} else {
			throw new ParseFailedException('The datatype checking function returns an unknown value');
		}
		switch($predefined[$what]['action']) {
			case 'break':
				$this->stopValidating = true;
			case 'reject':
				array_push($this->$what, $subTag);
				$this->parseString($validation, ',');
				return false;
				break;
			case 'set':
				$var[$subTag] = $predefined[$what]['value'];
		}
		$this->parseString($validation, ',');
	}

	function __process_array(&$var, $subv, $type='array') {
		if ( empty($var) ) return KenshoLite::MISSING;
		if ( !is_array($var) ) return KenshoLite::ERROR;
		if ( empty($subv) ) return KenshoLite::OK;
		$this->stopValidating = false;
		$result = KenshoLite::OK;
		do {
			$subv = trim($subv);
			$success = $this->processFiledValidation($var, $type, $subv);
			if(!$success) $result = KenshoLite::ERROR;
		} while ( !empty($subv) && !$this->stopValidating );
		return $result;
	}
	function __process_index(&$var, $subv) {
		$this->__process_array($var, $subv, 'index');
	}
	function __process_string(&$var, $subv) {
		if ( empty($var) ) return KenshoLite::MISSING;
		if ( !empty($subv) && !preg_match($subv, $var) )
			return KenshoLite::ERROR;
		return KenshoLite::OK;
	}
	function __process_date(&$var, $subv) {
		if(empty($subv)) $subv = 'Y-m-d';
		if(empty($var))
			return KenshoLite::MISSING;
		else if($this->parseDate($subv, $var) === false)
			return KenshoLite::ERROR;
		else
			return KenshoLite::OK;
	}
	private function parseDate($format, $var) {
		if(function_exists('date_parse_from_format')) {
			return date_parse_from_format($format, $var);
		} else {
			return date_parse($var);
		}
	}
	function __process_email(&$var, $subv) {
		if ( empty($var) ) return KenshoLite::MISSING;
		if ( filter_var($var, FILTER_VALIDATE_EMAIL) === false)
			return KenshoLite::ERROR;
		return KenshoLite::OK;
	}
	function __process_url(&$var, $subv) {
		if ( empty($var) ) return KenshoLite::MISSING;
		if ( filter_var($var, FILTER_VALIDATE_URL) === false )
			return KenshoLite::ERROR;
		return KenshoLite::OK;
	}
	function __process_number(&$var, $subv) {
		if(empty($var) && $var !== 0) return KenshoLite::MISSING;
		if(!is_numeric($var)) return KenshoLite::ERROR;
		if(!empty($subv)) {
			$conditions = array();
			/// parse the number sub validation stmt
			$allowedOperators = array(
				'>', '<', '=', '==', '!=',
				'>=', '<=', '><', '<>', 'in'
			);
			do {
				$opt = $this->parseString($subv, '(0123456789abcdefABCDEF', 'leaveEnd');
				$opt = strtolower($opt);
				if(in_array($opt, $allowedOperators)) {
					if($opt == 'in') {
						$numbers = $this->parseSubValidation($subv);
						$numberArr = explode(',', $numbers);
						foreach($numberArr as &$number) {
							$number = trim($number);
							if(!ctype_alnum($number))
								throw new ParseFailedException('You have a syntax error in your subvalidation for number, invalid number-list. near '.$subv);
						}
						$conditions[$opt] = $numberArr;
					} else {
						$number = $this->parseString($subv, ', ', 'cutEnd');
						$conditions[$opt] = $number;
					}
				} else {
					throw new ParseFailedException('You have a syntax error in your subvalidation for number, invalid operator. near '.$subv);
				}
				$subv = ltrim($subv);
			} while(strlen($subv) > 0);
			var_dump($conditions);
			//// validate the number
			foreach($conditions as $operator => $number) {
				if (
					($operator == 'in' && !in_array($var, $number))
					||
					($var == $number && in_array($operator, array('!=', '<>', '><', '>', '<')))
					||
					($var > $number && in_array($operator, array('<=', '<')))
					||
					($var < $number && in_array($operator, array('>=', '>')))
				) return KenshoLite::ERROR;
			}
		}
		return KenshoLite::OK;
	}
	function __process_integer(&$var, $subv) {
		if(empty($var) && $var !== 0) return KenshoLite::MISSING;
		if(!is_int($var) && !ctype_xdigit($var)) return KenshoLite::ERROR;
		if(!empty($subv))
			return $this->__process_number($var, $subv);
		return KenshoLite::OK;
	}
	function __process_int(&$var, $subv) {
		return $this->__process_integer($var, $subv);
	}
	function __process_dec(&$var, $subv) {
		if(empty($var) && $var !== 0) return KenshoLite::MISSING;
		if(!is_int($var) && !ctype_digit($var)) return KenshoLite::ERROR;
		if(!empty($subv))
			return $this->__process_number($var, $subv);
		return KenshoLite::OK;
	}
	function __process_oct(&$var, $subv) {
		if(empty($var) && $var !== 0) return KenshoLite::MISSING;
		if(!is_int($var) && !preg_match('/^[0-7]+$/', $var)) return KenshoLite::ERROR;
		if(!empty($subv))
			return $this->__process_number($var, $subv);
		return KenshoLite::OK;
	}
	function __process_hex(&$var, $subv) {
		return $this->__process_integer($var, $subv);
	}
	function __process_bin(&$var, $subv) {
		if(empty($var) && $var !== 0) return KenshoLite::MISSING;
		if(!is_int($var) && !preg_match('/^[01]+$/', $var)) return KenshoLite::ERROR;
		if(!empty($subv))
			return $this->__process_number($var, $subv);
		return KenshoLite::OK;
	}
	function __process_float(&$var, $subv) {
		if(empty($var) && $var !== 0) return KenshoLite::MISSING;
		if(!is_int($var) && !preg_match('/^\d*\.?\d+$/', $var)) return KenshoLite::ERROR;
		if(!empty($subv))
			return $this->__process_number($var, $subv);
		return KenshoLite::OK;
	}
	function __process_bool(&$var, $subv) {
		$this->__process_boolean($var, $subv);
	}
	function __process_boolean(&$var, $subv) {
		if(empty($var) && $var !== 0) return KenshoLite::MISSING;
		$v = (string) strtolower($var);
		if(!in_array($v, '1', '0', 'on', 'off', 'true', 'false', 'yes', 'no', ''))
			return KenshoLite::ERROR;
		return KenshoLite::OK;
	}
}
