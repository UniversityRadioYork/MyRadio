<?php
/**
 * This page enables Users to create a new Term or edit a Term that already exists.
 * It can take one parameter, $_REQUEST['termid']
 * which should be the ID of the Term to edit.
 */
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Term;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_Term::getTermForm()->readValues();

    if (empty($data['id'])) {
        //create new
        $term = MyRadio_Term::addTerm($data['start'], $data['descr'], $data['numweeks']);
        if (is_numeric($term)) {
            URLUtils::redirectWithMessage('Scheduler', 'listTerms', 'Term '.$data['descr'].', has been added.');
        } else {
            throw new MyRadioException('Error creating term.', 500);
        }
    } else {
        /*
        * @todo
        */
        throw new MyRadioException('Not Implemented');
        //submit edit

        URLUtils::backWithMessage('Show Updated!');
    }
} else {
    //Not Submitted
    if (isset($_REQUEST['termid'])) {
        MyRadio_Term::getTermEditForm($_REQUEST['termid'])->render();
    } else {
        //create form
        MyRadio_Term::getTermForm()
            ->render();
    }
}
