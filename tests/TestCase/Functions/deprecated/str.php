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
debug_assert(
	eq_dg(
		str_filter_dg(
			not_dg( and_dg( eq_dg(tuple_get(),return_dg('-')), eq_dg(tuple_carry(),tuple_get()) ) ),
			'0A--B'
		),
		return_dg('0A-B')
	)
);
debug_assert(
	eq_dg(
		str_filter_dg(
			not_dg( and_dg( eq_dg(tuple_get(),return_dg('-')), eq_dg(tuple_carry(),tuple_get()) ) ),
			return_dg('0A--B')
		),
		return_dg('0A-B')
	)
);
debug_assert(
	call_chain(
		return_dg('0A--B'),
		str_filter_dg(
			not_dg( and_dg( eq_dg(tuple_get(),return_dg('-')), eq_dg(tuple_carry(),tuple_get()) ) )
		),
		eq_dg(
			tuple_get(0),
			return_dg('0A-B')
		)
	)
);