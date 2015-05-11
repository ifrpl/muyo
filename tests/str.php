<?php

require __DIR__.'/../file_system.php';
require __DIR__.'/../cli.php';
require __DIR__.'/../debug.php';
require __DIR__.'/../lst.php';
require __DIR__.'/../string.php';
require __DIR__.'/../bool.php';

debug_assert(
	str_filter(
		'0A--B',
		not_dg( and_dg( eq_dg(tuple_get(),return_dg('-')), eq_dg(tuple_carry(),tuple_get()) ) )
	) === '0A-B'
);