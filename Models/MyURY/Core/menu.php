<?php
//NetBeans may whine, because this only became valid in PHP 5.4
$menu = (new MyURYMenu())->getMenuWithPermissions(User::getAllPermissions());