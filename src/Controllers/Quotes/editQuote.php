<?php
/**
 * Allows the editing of quotes.
 * @version 20131020
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyURY_Quotes
 */

$form = MyURY_JsonFormLoader::loadFromModule(
    $module,
    'quotefrm',
    'doEditQuote',
    []
);

if ($_REQUEST['quote_id']) {
    $quote = MyURY_ChartRelease::getInstance($_REQUEST['quote_id']);

    $form->editMode(
        $quote->getID(),
        array_merge(
            [
                'date'   => CoreUtils::happyTime($quote->getDate(), false),
                'source' => $quote->getSource(),
                'text'   => $quote->getText()
            ],
            $chart_rows_form
        )
    );

} else {
    $form->setTitle('Create Quote');
    $form->setFieldValue('date', CoreUtils::happyTime(time(), false));
}

$form->render();
