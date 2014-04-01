<?php
/**
 * Send an email to a mailing list
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130526
 * @package MyRadio_Mail
 */

if (!isset($_REQUEST['list'])) {
    throw new MyRadioException('List ID was not provided!', 400);
}
if (!MyRadio_List::getInstance($_REQUEST['list'])->isPublic()) {
    CoreUtils::requirePermission(AUTH_MAILALLMEMBERS);
}

MyRadio_JsonFormLoader::loadFromModule(
    $module,
    'send',
    'doSend'
)->setFieldValue(
    'list',
    $_REQUEST['list']
)->setTemplate(
    'Mail/send.twig'
)->render(
    ['rcpt_str' => MyRadio_List::getInstance($_REQUEST['list'])->getName()]
);
