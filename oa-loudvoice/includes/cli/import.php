<?php

// Init
require_once ('_init.php');

// Debug
oa_loudvoice_debug ($verbose, 'Importing Comments, Start: ', date ("d.m.Y G:i:s"));

// Import
oa_loudvoice_import ($verbose);

// Debug
oa_loudvoice_debug ($verbose, 'Finished, End: ', date ("d.m.Y G:i:s"));