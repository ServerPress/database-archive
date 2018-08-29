<?php

/**
 * Process on Create, copy, import, and move events.
 */
global $ds_runtime;
//dbarchive_debug('last event: ' . var_export($ds_runtime->last_ui_event, TRUE));

#if ( FALSE === $ds_runtime->last_ui_event ) {
//	dbarchive_debug('event: ' . $ds_runtime->last_ui_event->action);
	$ds_runtime->add_action('init', array('DS_Database_Archive', 'check_perform_archive'));
#}

function dbarchive_debug($msg)
{
return;
	if (function_exists('trace'))
		trace('dbarchive: ' . $msg);

}
