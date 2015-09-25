<?php

require __DIR__.'/../debug.php';
require __DIR__.'/../arr.php';

debug_assert( array_eq( array_first( range(1,5), 3 ), [1,2,3] ) );
debug_assert( array_eq( array_initial( range(1,5), 3 ), [1,2] ) ); //TODO: array_to
debug_assert( array_eq( array_last( range(1,5), 3 ), [3,4,5] ) );
//debug_assert( array_eq( array_to( range(1,5), 3 ), [1,2,3] ) );
//debug_assert( array_eq( array_from( range(1,5), 3 ), [3,4,5] ) ); // TODO: array_rest
//debug_assert( array_eq( array_before( range(1,5), 3 ), [1,2] ) );
//debug_assert( array_eq( array_after( range(1,5), 3 ), [4,5] ) );