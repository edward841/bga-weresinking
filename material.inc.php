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

	'water deck' => [
		// Clear Water
		'clear water' => [
			'quantity' => 30,
			'remove' => [
				3 => 15,
				4 => 10,
				5 => 5,
				6 => 0,
				],
			],

		// Gems
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

		// Basic Items
		'bottleORum' => [
			'name' => clienttranslate('Bottle O\' Rum'),
			'type' => 'item',
			'value' => 1,
			'trigger' => 'resolve bucket',
			'text' => clienttranslate('Swap this card with 1 random card in another player\'s hand.'),
			],
		'captainsKey' => [		
			'name' => clienttranslate('Captain\'s Key'),
			'type' => 'item',
			'value' => 1,
			'trigger' => 'reveal dial',
			'text' => clienttranslate('Take 1 Chest Token from the Breaches Column.'),
			],		
		'crackedCompass' => [
			'name' => clienttranslate('Cracked Compass'),
			'type' => 'item',
			'value' => 1,
			'trigger' => 'reveal dial',
			'text' => clienttranslate('Change your Dial to a different action.'),
			],
		'decoyCannon' => [
			'name' => clienttranslate('Decoy Cannon'),
			'type' => 'item',
			'value' => 0,
			'trigger' => 'enemy dice rolled',
			'text' => clienttranlsate('Ignore 1 cannon result.'),
			],
		'fishingNet' => [
			'name' => clienttranslate('Fishing Net'),
			'type' => 'item',
			'value' => 0,
			'trigger' => 'resolve bucket',
			'text' => clienttranslate('Peek at the top 5 cards of the Discard Pile. Reveal all Treasures and add them to your hand.'),
			],
		'fishingRod' => [
			'name' => clienttranslate('Fishing Rod'),
			'type' => 'item',
			'value' => 1,
			'trigger' => 'resolve bucket',
			'text' => clienttranslate('Peek at 1 random card in a player\'s hand. If it\'s a Treasure, swap it with a Treasure in your hand.'),
			],
		'flintPistol' => [
			'name' => clienttranslate('Flint Pistol'),
			'type' => 'item',
			'value' => 1,
			'trigger' => 'resolve dial',
			'text' => clienttranslate('Roll 1 Single-Shot die against the enemy for each cannon card in the Breaches Column.'),
			],
		'gemSifter' => [
			'name' => clienttranslate('Gem Sifter'),
			'type' => 'item',
			'value' => 0,
			'trigger' => 'resolve bucket',
			'text' => clienttranslate('Peek at the top 3 cards of the Water Deck. You may reveal 1 Gem and add it to your hand.'),
			],
		'grabbyCrabby' => [
			'name' => clienttranslate('Grabby Crabby'),
			'type' => 'item',
			'value' => 0,
			'trigger' => 'declare dial',
			'text' => clienttranslate('Swap this card with a card in the Treasure Column.'),
			],
			
		'cutlass' => [
			'name' => clienttranslate('Cutlass'),
			'type' => 'player item',
			'player' => 'red',
			'value' => 0,
			'trigger' => 'reveal plunder',
			'text' => clienttranslate('Move your Dial to the top of the Treasure Column.'),
			],
		'trustyCarrot' => [
			'name' => clienttranslate('Trusty Carrot'),
			'type' => 'player item',
			'player' => 'orange',
			'value' => 0,
			'trigger' => 'reveal patch',
			'text' => clienttranslate('Discard a Minor Breach from the Breaches Column.'),
			],
		'boneClub' => [
			'name' => clienttranslate('Bone Club'),
			'type' => 'player item',
			'player' => 'green',
			'value' => 0,
			'trigger' => 'resolve patch OR resolve fire',
			'text' => clienttranslate('Steal 1 random card from a player who resolved a Treasure this round.'),
			],
		'harpoon' => [
			'name' => clienttranslate('Harpoon'),
			'type' => 'player item',
			'player' => 'blue',
			'value' => 0,
			'trigger' => 'resolve bucket',
			'text' => clienttranslate('Reveal a card in the Water Column. If it\'s a Treasure, add it to your hand.'),
			],
		'grenado' => [
			'name' => clienttranslate('Grenado'),
			'type' => 'player item',
			'player' => 'purple',
			'value' => 0,
			'trigger' => 'resolve fire',
			'text' => clienttranslate('Roll 1 Triple-Shot die against the enemy. On a miss, deal a Breach card to the Breaches Column.'),
			],
		'spyGlass' => [
			'name' => clienttranslate('Spy Glass'),
			'type' => 'player item',
			'player' => 'gray',
			'value' => 0,
			'trigger' => 'declare dial',
			'text' => clienttranslate('Reveal a player\'s Dial. If they lied, draw 2 cards from the Water Deck. Otherwise, discard 2 cards.'),
			],
	],

];


