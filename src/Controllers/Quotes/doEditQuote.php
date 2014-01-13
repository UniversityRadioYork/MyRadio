<?php
/**
 * Performs the actual editing of quotes.
 * @version 20131020
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyURY_Quote
 */

/*
 * Creates a new quote.
 *
 * @param $data  The data hash from the quotes form.
 *
 * @return Nothing.  This function writes directly to the database.
 */
function create_quote($data) {
  MyURY_Quote::create($data);
}

/*
 * Edits the quote with the given ID.
 *
 * @param $id    The ID of the quote to edit.
 * @param $data  The data hash from the quotes form.
 *
 * @return Nothing.  This function writes directly to the database.
 */
function edit_quote($id, $data) {
  $quote = MyURY_ChartRelease::getInstance($id);
  $quote
    ->setSource($data['source'])
    ->setText($data['text'])
    ->setDate($data['date']);
}


/*
 * END OF HELPER FUNCTIONS
 */

$form = MyURY_JsonFormLoader::loadFromModule(
  $module, 'quotefrm', 'doEditQuote', []
);

$data = $form->readValues();

empty($data['id'])) ? create_quote($data) : edit_quote($data['id'], $data);

CoreUtils::redirect($module);
