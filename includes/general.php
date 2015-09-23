<?php

/**
 * Initialise
 */
function oa_loudvoice_init ()
{
	// Add language file.
	if (function_exists ('load_plugin_textdomain'))
	{
		load_plugin_textdomain ('oa_loudvoice', false, OA_LOUDVOICE_BASE_PATH . '/languages/');
	}
	
	// Launch the integration
	oa_loudvoice_main ();
}

/**
 * Main
 */
function oa_loudvoice_main ()
{
}