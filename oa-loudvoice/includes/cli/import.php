<?php

/*
 * Can only be ran from the command line
 */

// Init
require_once ('_init.php');

// This is a flag for _init.php
define ('OA_LOUDVOICE_CLI', true );

// Debug
oa_loudvoice_debug ($verbose, 'Importing Comments, Start: ' . date ("d.m.Y G:i:s"));

// Import
oa_loudvoice_import ($verbose);

// Debug
oa_loudvoice_debug ($verbose, 'Finished, End: ', date ("d.m.Y G:i:s"));