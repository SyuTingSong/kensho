<?php
include_once('TypeValidator.class.php');
include_once('ArrayValidator.class.php');
include_once('NumberValidator.class.php');
include_once('FloatValidator.class.php');
include_once('IntegerValidator.class.php');
include_once('StringValidator.class.php');
include_once('ValidatorNotDefinedException.class.php');
include_once('ParseFailedException.class.php');

$test = array(
	'productName' => 'iPad',
	'uniqueNumber' => '15',
	'price' => '199.99',
	'orderInfo' => array(
		'product' => '1',
		'care_pack' => '0',
		'update_email' => '1',
	)
);
$postDef = <<<POSTDEF
productName:string, uniqueNumber:int, price:float(>0),
orderInfo:array(product:int(in (0,1)), care_pack:int(in (0,1)),
update_email:int(in (0, 1)))
POSTDEF;

$v = new ArrayValidator;
if($v->validate($test, $postDef)) {
	echo "success";
}

?>