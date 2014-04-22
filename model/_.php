<?php

if( !class_exists( 'Lib_Model' ) )
{
	require_once( implode( DIRECTORY_SEPARATOR, array(__DIR__,'model.php') ) );
}
if( !class_exists( 'Lib_Model_Set' ) )
{
	require_once( implode( DIRECTORY_SEPARATOR, array(__DIR__,'set.php') ) );
}

require_once( implode( DIRECTORY_SEPARATOR, array(__DIR__,'array','_.php') ) );
require_once( implode( DIRECTORY_SEPARATOR, array(__DIR__,'db','_.php') ) );