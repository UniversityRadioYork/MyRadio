<?php
/**
 * Edit an Officer.
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Officer;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_Officer::getForm()->readValues();

    if (empty($data['id'])) {
        //create new
        $officer = MyRadio_Officer::createOfficer(
            $data['name'],
            $data['description'],
            $data['alias'],
            $data['ordering'],
            $data['team'],
            $data['type'],
            (int) $data['num_places']
        );
    } else {
        //submit edit
        $officer = MyRadio_Officer::getInstance($data['id']);

        // update officer
        $officer
            ->setName($data['name'])
            ->setDescription($data['description'])
            ->setAlias($data['alias'])
            ->setOrdering($data['ordering'])
            ->setTeam($data['team'])
            ->setType($data['type'])
            ->setStatus($data['status'])
            ->setNumPlaces((int) $data['num_places']);

        // remove empty permissions values
        $data['permissions'] = array_filter($data['permissions']['permission']) ?: [];

        // get IDs of current officer permissions
        $currentPerms = [];
        $officerPerms = $officer->getPermissions();
        foreach ($officerPerms as $perm) {
            $currentPerms[] = (int) $perm['value'];
        }

        // Get permissions to add or remove
        $addPerms = array_diff($data['permissions'], $currentPerms);
        $remPerms = array_diff($currentPerms, $data['permissions']);

        // Add permissions
        if (!empty($addPerms)) {
            foreach ($addPerms as $perm) {
                $officer->addPermission($perm);
            }
        }
        // Remove permissions
        if (!empty($remPerms)) {
            foreach ($remPerms as $perm) {
                $officer->revokePermission($perm);
            }
        }
    }

    URLUtils::backWithMessage('Officer Updated!');
} else {
    //Not Submitted

    if (isset($_REQUEST['officerid'])) {
        //edit form
        $officer = MyRadio_Officer::getInstance($_REQUEST['officerid']);

        $officer
            ->getEditForm()
            ->render();
    } else {
        //create form
        MyRadio_Officer::getForm()->render();
    }
}
