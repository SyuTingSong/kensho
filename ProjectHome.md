`KenshoLite` is here!!

`KenshoLite` is completely new code to build as a lightweight php5.3 supported validation library. It still build with the declare&use philosophy. Some new keywords has been added, and more powerful now.

for example
```
$test = array(
	'productName' => 'iPad',
	'uniqueNumber' => '15',
	'price' => '199.99',
	'orderInfo' => array(
		'product' => '1',
		'care_pack' => '0',
		'update_email' => '1',
	),
	'billDate' => '2011-04-11'
);
$validation = <<<VALIDATION
productName:string required, uniqueNumber:int on error set null,
price:float(>0), orderInfo:array(product:boolean),
care_pack:boolean, update_email:boolean,
billDate:date('Y-m-d') on missing set '0000-00-00' on error reject
VALIDATION;
$kl = new KenshoLite;
if(!$kl($test, $validation)) {
	var_dump($kl->error);
}
```
A new keyword **required** is added. False will be returned if such named-item is empty.
You can use on error set _value_ to ignore an error and set to default value.
Also, on missing set _value_ can be used to ignore missing value check.
Beyond **set**, there are two keywords after on error/missing, **reject** and **break**.
**reject** means record the name/index of error/missing item to `KenshoLite's` error/missing array, and continue validating.
**break** means record the name/index of the error/missing item to `KenshoLite's` error/missing array, and stop validating immediately.

---

The original Kensho provides a object-orientated validation library/framework for php.
You can use variable type definition string to validate variable/arrays.

for example
```
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
	echo "OK";
} else {
	echo "Error";
}
```

For now, kensho implemented type based validating, pattern validating for string, range and enum validating for int and float. Some more complex validation support is under develop.