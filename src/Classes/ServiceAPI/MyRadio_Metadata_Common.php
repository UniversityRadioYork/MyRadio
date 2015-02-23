<?php

/**
 * Provides the Metadata Common class for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio\ServiceAPI;

/**
 * The Metadata_Common class is used to provide common resources for
 * URY assets that utilise the Metadata system.
 *
 * The metadata system is a used to attach common attributes to an item,
 * such as a title or description. It includes versioning in the form of
 * effective_from and effective_to field, storing a history of previous values.
 *
 * @package MyRadio_Scheduler
 * @uses    \Database
 */
abstract class MyRadio_Metadata_Common extends ServiceAPI
{
    use MyRadio_Creditable;
    use MyRadio_MetadataSubject;
}
