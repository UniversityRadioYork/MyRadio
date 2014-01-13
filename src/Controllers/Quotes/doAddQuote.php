<?php

/**
 * Performs the addition of new quotes.
 *
 * @version 20140113
 * @author  Matt Windsor <mattbw@ury.org.uk>
 * @package MyRadio_Quotes
 */

$data = MyRadio_JsonFormLoader::loadFromModule(
    $module,
    'addQuote',
    'doAddQuote',
    []
)->readValues();

MyRadio_Quote::create($data);

CoreUtils::backWithMessage('Quote added.');

?>
