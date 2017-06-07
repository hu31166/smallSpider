<?php
$a = ['a', 'b', 'c'];
echo current($a);
foreach ($a as $value) {
    reset($a);
    echo current($a);
}
?>