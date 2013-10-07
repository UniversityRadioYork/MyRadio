<?php
/**
 * This file is pretty much the single most important file in URY Web Services.
 * It basically everything. If you're here to learn about MyURY's Core, then read on
 * 
 * As you can see in the code for this file, MyURY has very little public-facing server code. This file simply starts
 * us up with a referral to our MVC framework by passing to the root controller. If you want to learn more about
 * how the Controller sets things up, then head over to it an take a look at its documentation.
 * 
 * For every module, the following global variables are configured by the MyURY Environment:<br>
 * - $member - The current User<br>
 * - $module - The module requested<br>
 * - $action - The action requested
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130525
 * @package MyURY_Core
 */

//Refer straight to the root Controller
require __DIR__.'/../Controllers/root.php';
