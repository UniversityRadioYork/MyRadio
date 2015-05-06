<?php
class A {
	public $foo = 'default value';
}


class B {
	private $a;
	public function setObj(A $a) {
		$this->a = $a;
	}

	public function whatIsIt() {
		echo $this->a->foo;
	}

	public function getObj() {
		return $this->a;
	}
}


$obj = new A;
echo $obj->foo . "\n";

$b = new B;
$b->setObj($obj);

$obj1 = $b->getObj();
$obj1->foo = 'mutated value';

$b->whatIsIt();
