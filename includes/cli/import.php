<?php

// Init
require_once ('_init.php');

// Debug
oa_loudvoice_debug ($cli, 'Importing Comments, Start: ', date ("d.m.Y G:i:s"));

// Import
oa_loudvoice_import ($cli);

// Debug
oa_loudvoice_debug ($cli, 'Finished, End: ', date ("d.m.Y G:i:s"));