<?php

# all primitive types are copied on assignment.
$s1 = "s1";
$s2 = $s1;
$s2 .= "-appended";
echo "s1=$s1\n"; # => s1=s1
echo "s2=$s2\n"; # => s2=s1-appended

# all objects types are reference copied.
class A
{
    public $message;
    public function __construct($m) { $this->message = $m; }
    public function show_message() { echo "message=$this->message\n"; }
}
$o1 = new A("Hello, World!");
$o2 = $o1;
$o2->message = "olá mundo!";
$o1->show_message(); # => message=olá mundo
$o2->show_message(); # => message=olá mundo

# CAVEAT: Event non-static methods can be called without $this object.
#         PHP will happly call them (this an exception is throwed when
#         the method uses $this.
#A::show_message(); # => Fatal error: Using $this when not in object context


# constructors.
class My
{
    public function __construct() { echo "My::__construct\n"; }
    public function __destruct() { echo "My::__destruct\n"; }
    public function x() { echo "\$my.x\n"; }
}
$my = new My();
$my2 = $my;
echo "before \$my=null\n";
$my = null;
echo "after \$my=null\n";
$my2->x();


Reflection::export(new ReflectionClass('Exception'));

?>