<?php

/**
 * Allows the addition of new quotes.
 *
 * @version 20140113
 * @author  Matt Windsor <mattbw@ury.org.uk>
 * @package MyRadio_Quotes
 */

MyRadio_JsonFormLoader::loadFromModule(
    $module,
    'addQuote',
    'doAddQuote',
    []
)->render();

?>
