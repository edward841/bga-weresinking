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
	// Key is the english name of their color. Will probably change lateplayer_sheets' => [
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

	// Critical info needed for the breaches: Organized by type of breach:
	// 	name, scale, and player counts. The player counts element is designed
	// 	for setting up a new game. An n player game would need the breaches from
	//
	// 	['breaches']['minor'][n],
	// 	['breaches']['minor']['all'],
	// 	['breaches']['major'][n],
	// 	['breaches']['major']['all'],
	// 	.
	// 	.
	// 	['breaches']['monster']['all']
	//
	// *only applies to those cases where the key exists
	
	// Ex: ['breaches']['minor']['all'] -> [0, 0] means the following:
	//  for every player count, the breach deck has two minor breaches (the array's length) and the associated image is located at index 0 for both.
	'breaches' => [
		'minor' => [
			'name' => 'Minor Breach',
			'scale' => 1,
			'player counts' => [
				'all' => [0, 0],
				3 => [1, 2, 2, 3],
				4 => [2,2,3],
				5 => [3]
				]
			],
		'major' => [
			'name' => 'Major Breach',
			'scale' => 2,
			'player counts' => [
				'all' => [4, 4, 4],
				4 => [5]
				]
			],
		'massive' => [
			'name' => 'Massive Breach',
			'scale' => 3,
			'player counts' => [
				5 => [6, 7, 7],
				6 => [7, 7]
				]
			],
		'monster' => [
			'name' => 'Monster Breach',
			'scale' => 4,
			'player counts' => [
				6 => [8, 8]
				]
			]
	],

	'treasure' => [
		'amethyst' => [
			'type' => 'gem',
			'value' => 2,
			'quantity' => 9,
			],
		'topaz' => [
			'type' => 'gem',
			'value' => 3,
			'quantity' => 7,
			],
		'sapphire' => [
			'type' => 'gem',
			'value' => 4,
			'quantity' => 5,
			],
		'emerald' => [
			'type' => 'gem',
			'value' => 5,
			'quantity' => 3,
			],
		'ruby' => [
			'type' => 'gem',
			'value' => 6,
			'quantity' => 2,
			],

		'bottle o\' rum' => [
			'type' => 'item',
			'value' => 1,
			],
		'captain\'s key' => [
			'type' => 'item',
			'value' => 1,
			],		
		'cracked compass' => [
			'type' => 'item',
			'value' => 1,
			],
		'decoy cannon' => [
			'type' => 'item',
			'value' => 0,
			],
		'fishing net' => [
			'type' => 'item',
			'value' => 0,
			],
		'fishing rod' => [
			'type' => 'item',
			'value' => 1,
			],
		'flint pistol' => [
			'type' => 'item',
			'value' => 1,
			],
		'gem sifter' => [
			'type' => 'item',
			'value' => 0,
			],
		'grabby crabby' => [
			'type' => 'item',
			'value' => 0,
			],
	],

];


