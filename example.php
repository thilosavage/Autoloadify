<?php
// require Autoloadify
require_once 'my-includes-folder/Autoloadify.php';
// if your includes folder is already being autoloaded, ignore this

$Autoloadify = new Autoloadify(realpath(__DIR__));
//	If this line is NOT in your root level, you can hardcode the path
//	$Autoloadify = new Autoloadify("/root/level/of/my/app);

// instantiating an object with an undefined class
$meh = new Bsdlasdsdah;
?>