<?php

$this->tokens = [
	// Encodes the information in the threshold sheets 
	// (for each player count and level: threshold, water, treasure)
	// Organization is $this->tokens['threshold_sheets']['# players']['threshold']
	'threshold_sheets' => [
		'3 players' => [
			'level 1' => ['threshold'=>7, 'water'=>2, 'treasure'=>2],
			'level 2' => ['threshold'=>8, 'water'=>2, 'treasure'=>3],
			'level 3' => ['threshold'=>9, 'water'=>3, 'treasure'=>2],
			'level 4' => ['threshold'=>10, 'water'=>3, 'treasure'=>3],
			],

		'4 players' => [
			'level 1' => ['threshold'=>6, 'water'=>2, 'treasure'=>2],
			'level 2' => ['threshold'=>7, 'water'=>2, 'treasure'=>3],
			'level 3' => ['threshold'=>8, 'water'=>3, 'treasure'=>2],
			'level 4' => ['threshold'=>9, 'water'=>3, 'treasure'=>3],
			],
		
		'5 players' => [
			'level 1' => ['threshold'=>5, 'water'=>3, 'treasure'=>2],
			'level 2' => ['threshold'=>6, 'water'=>3, 'treasure'=>3],
			'level 3' => ['threshold'=>7, 'water'=>3, 'treasure'=>3],
			'level 4' => ['threshold'=>8, 'water'=>4, 'treasure'=>3],
			],

		'6 players' => [
			'level 1' => ['threshold'=>4, 'water'=>3, 'treasure'=>2],
			'level 2' => ['threshold'=>5, 'water'=>3, 'treasure'=>3],
			'level 3' => ['threshold'=>6, 'water'=>4, 'treasure'=>3],
			'level 4' => ['threshold'=>7, 'water'=>4, 'treasure'=>4],
			]	
	],
	
	// Encodes information in the player sheets (name, job, starting item).
	// Key is the english name of their color. Will probably change later
	'player_sheets' => [
		'red' => [
			'name'=>'\'Honest\' Pete',
			'job'=>'The Boatswain',
			'item'=>'Cutlass',
			],

		'orange' => [
			'name'=>'Frankie \'Forks\'',
			'job'=>'The Cook',
			'item'=>'Trusty Carrot',
			],

		'green' => [
			'name'=>'Billy \'Bones\'',
			'job'=>'The Heavy',
			'item'=>'Bone Club',
			],

		'blue' => [
			'name'=>'\'Netty\' Arnetta',
			'job'=>'The Fisher',
			'item'=>'Harpoon',
			],
	
		'purple' => [
			'name'=>'\'Gunny\' Genny',
			'job'=>'The Gunner',
			'item'=>'Grenado',
			],

		'gray' => [
			'name'=>'\'Questy\' Quinn',
			'job'=>'The Navigator',
			'item'=>'Spy Glass',
			],
	],

];


