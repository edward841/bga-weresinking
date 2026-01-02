<?php

namespace Bga\Games\weresinking;

if (!defined('RED'))
{
	define('RED', 'ff5165');
	define('ORANGE','f19c27');
	define('GREEN','00c398');
	define('BLUE','4ccaf2');
	define('PURPLE', 'af73b1');
	define('GRAY', '646D74');
	define('COLUMN_BOTTOM', false);
	define('COLUMN_TOP', true);
}

$this->tokens = [
	// Encodes the information in the threshold sheets 
	// (for each player count and level: threshold, water, treasure)
	// Organization is $this->tokens['threshold_sheets'][{# players}]['threshold']
	'thresholdSheets' => [
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
		RED => [
			'name'=> clienttranslate('\'Honest\' Pete'),
			'job'=> clienttranslate('The Boatswain'),
			'item'=> 'cutlass' 
			],

		ORANGE => [
			'name'=> clienttranslate('Frankie \'Forks\''),
			'job'=> clienttranslate('The Cook'),
			'item'=> 'trustyCarrot' 
			],

		GREEN => [
			'name'=> clienttranslate('Billy \'Bones\''),
			'job'=> clienttranslate('The Heavy'),
			'item'=> 'boneClub' 
			],

		BLUE => [
			'name'=> clienttranslate('\'Netty\' Arnetta'),
			'job'=> clienttranslate('The Fisher'),
			'item'=> 'harpoon'
			],
	
		PURPLE => [
			'name'=> clienttranslate('\'Gunny\' Genny'),
			'job'=> clienttranslate('The Gunner'),
			'item'=> 'grenado' 
			],

		GRAY => [
			'name'=> clienttranslate('\'Questy\' Quinn'),
			'job'=> clienttranslate('The Navigator'),
			'item'=> 'spyGlass'
			],
	],

	'enemySheets' => [
		'Kraken' => [
			'name' => clienttranslate('The Kraken'),
			'specialAttack1' => [
				'name' => clienttranslate('Splash'),
				'effect' => clienttranslate('Place the top card in the Treasure Column face-down into the Water Column.'),
				],
			'specialAttack2' => [
				'name' => clienttranslate('Angered'),
				'effect' => clienttranslate('When a card is added to the discard pile this round, immediately roll and resolve 1 Basic Attack Die (Place this die on the discard pile as a reminder.)'),
				],
			'complexity' => 1,	
			'description' => clienttranslate('It hates when you discard your trash into the sea. Making it angry will only cause it to grow stronger.'),
			'prologue' => clienttranslate('Some local folk are saying the Deep Sea King has awoken from its slumber. Sailors always tell tall tales, but the number of stories that keep emerging in the ports these days... Some say they\'ve seen bones of ships, smashed to splinters on calm waters. Only soothing melodies can calm this restless beast. Hopefully it won\'t come to... CRAAAACK... WE\'RE SINKING!!!'),
			'setup' => clienttranslate('Shuffle the War Drum and Hurdy Gurdy into the Water Deck. Start with 4 active enemy dice: 2 Kraken dice and 2 Basic dice.'),
		],

		'Shark' => [
			'name' => clienttranslate('The Shark'),
			'specialAttack1' => [
				'name' => clienttranslate('Chomp, Chomp!'),
				'effect' => clienttranslate('All cards discarded this round go to the Shark\'s Belly face-down. (Place this die on the discard pile as a reminder.)'),
				],
			'specialAttack2' => [
				'name' => clienttranslate('Submerged'),
				'effect' => clienttranslate('When firing at the Shark this round, only Double-shot and Triple-shot cannons can deal damage. (Place this die at the top of the Cannons column as a reminder.)'),
				],
			'reactToDamage' => clienttranslate('When the Shark takes damage, move all cards in the Shark\'s Belly face-down to the Water column.'),
			'complexity' => 2,
			'description' => clienttranslate('It wants to eat all the things! Nom, Nom, Nom! When angered, it will hurl its food back up at you.'),
			'prologue' => clienttranslate('The sailors are saying that the gigantic, ship-eating megalodon shark is back after vanishing for over twenty years. Barnacle Joe swears he saw it swallow a merchant vessel whole. He claims its teeth are as tall as a man and sharper than a silver blade. Rumor has it, it can stalk its preyfor miles with a single drop of blood. Captain, is that a splinter? WHOOSH-CRAASH... WE\'RE SINKING!!!'),
			'setup' => clienttranslate('Place the Shark\'s Belly card next to this sheet. Shuffle the Fishy Bait and the Cheeky Chum into the Water Deck. Start with 4 active enemy dice: 2 Shark dice and 2 Basic dice.'),
		],

		'Sirens' => [
			'name' => clienttranslate('The Sirens'),
			'specialAttack1' => [
				'name' => clienttranslate('Tempting Tune'),
				'effect' => clienttranslate('The last player to resolve a Plunder this round may also draw 2 cards from the Water Deck.'),
				],
			'specialAttack2' => [
				'name' => clienttranslate('Screech!'),
				'effect' => clienttranslate('Players cannot talk until Dials are revealed. Players declare actions by placing their Dial in front of them instead of in columns. Once all Dials are revealed, place them in their matching columns in player order, starting with the First Mate.'),
				],
			'reactToDamage' => clienttranslate('None'),
			'complexity' => 2,
			'description' => clienttranslate('Their deadly tunes can spread serious confusion and greed across the crew.'),
			'prologue' => clienttranslate('Stories speak of the sonorous songs of the Sirens on a full moon. Their tunes can be heard for miles on a calm sea and can drive a whole crew mad--they will do anything to get closer to the source. These beauties are responsible for wrecking hundreds of ships on the jagged rocks they prowl.n The myth of their beauty grows, as does the ship graveyard around them. Wait a minute, is it a full moon? What\'s that noise? It\'s so... beautiful.. CRAASH... Oops, WE\'RE SINKING!!!'),
			'setup' => clienttranslate('Shuffle the Siren Silencers and Siren Shiner into the Water Deck. Start with 6 active enemy dice: 2 Siren dice and 4 Basic dice.'),
		],
		
		'Skullsairs' => [
			'name' => clienttranslate('The Skullsairs'),
			'specialAttack1' => [
				'name' => clienttranslate('Cursed Search'),
				'effect' => clienttranslate('All players must reveal 1 Cursed Amulet in their hand if able. If they have one, they must reveal a card at random from their hand. If it\'s a Treasure, add it to the Skullsair\'s Stash.'),
				],
			'specialAttack2' => [
				'name' => clienttranslate('Boarding Party'),
				'effect' => clienttranslate('Move the lowest card in the Treasure column to the Skullsair\'s Stash.'),
				],
			'reactToDamage' => clienttranslate('The player who deals damage may choose 1 card from the Skullsair\'s Stash and place it into their hand.'),
			'complexity' => 3,
			'description' => clienttranslate('They will steal your loot in search for their cursed amulets to become whole again. Don\'t get caught with them!'),
			'prologue' => clienttranslate('There is no pirate crew more feared than the bone-chilling Skullsairs. Legend has it they roamed uncharted seas seeking the legendary Amulets of Immortality for decades only to discover thier true, cursed nature. Yea, so we might have accidentally stolen those very amulets from them, and now they\'re hot on our tracks! I think they want them back? BOOOM, CRAAASH... Uh-oh, WE\'RE SINKING!!!'),
			'setup' => clienttranslate('Place the Skullsairs Stash card next to this sheet. Shuffle the 6 Cursed Amulets into the Water Deck. Start with 6 active dice: 2 Skullsairs dice and 4 Basic dice.'),
		],
	],

	// 'adjustBasicDice' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
	// 'basicDice': number of basic die it starts the game with
	// 'adjustBasicDice': indicates exactly when to add/remove basic dice. 
	// 		Think of it as 'when the enemy is damaged and now has x HP, add y basic dice' for the entry x => y
	// 	'triggers': at what HP levels does the enemy react with some secondary effect (the * at the bottom of the sheet)
	//
//			if (array_key_exists('triggers', $this->tokens['enemyInfo'][$enemy]) === true 
//				&& array_search($hp, $this->tokens['enemyInfo'][$enemy]['triggers']) !== false)
//
	'enemyInfo' => [
		'Kraken' => [
			'basicDice' => 2,
			'adjustBasicDice' => [5 => 1, 2 => 1],
		],
		'Shark' => [
			'basicDice' => 2,
			'adjustBasicDice' => [4 => 1, 2 => 1],
			'triggers' => [5, 3, 1],
		],
		'Sirens' => [
			'basicDice' => 4,
			'adjustBasicDice' => [4 => -1, 2 => -1],
		],
		'Skullsairs' => [
			'basicDice' => 4,
			'adjustBasicDice' => [3 => -1, 1 => -1],
			'triggers' => [5,4,3,3,2,1],
		],
	],

	// The dice have the same topology as a regular D6
	// These mappings are designed to translate the physical dice directly
	// e.g. on the basic die, water and cannon are on opposite sides. 
	// Here they correspond to a 1 and 6, values on opposite sides of a regular D6.
	// null represents the blank sides
	'diceMappings' => [
		'basic' => [1 => 'Water', 2 => 'Breach', 3 => null, 4 => null, 5 => null, 6 => 'Cannon'],
		'special' => [1 => 'Water', 2 => '1', 3 => null, 4 => null, 5 => '2', 6 => 'Breach']
	],

	// Dice are evaluated by a specific order (The rules requires special attacks 1, then special attacks 2, then everything else)
	// This implementation strictly enforces this order: special 1, special 2, Water, Breach, Cannon, Blank
	'diceOrder' => ['1' => 0, '2' => 1, 'Water' => 2, 'Breach' => 3, 'Cannon' => 4, null => 5],

	'cannons' => [
		1 => clienttranslate('Single-Shot Cannon'),
		2 => clienttranslate('Double-Shot Cannon'),
		3 => clienttranslate('Triple-Shot Cannon'),		
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
			'name' => clienttranslate('Minor Breach'),
			'scale' => 1,
			'player counts' => [
				'all' => [0, 0],
				3 => [1, 2, 2, 3],
				4 => [2,2,3],
				5 => [3]
				]
			],
		'major' => [
			'name' => clienttranslate('Major Breach'),
			'scale' => 2,
			'player counts' => [
				'all' => [4, 4, 4],
				4 => [5]
				]
			],
		'massive' => [
			'name' => clienttranslate('Massive Breach'),
			'scale' => 3,
			'player counts' => [
				5 => [6, 7, 7],
				6 => [7, 7]
				]
			],
		'monster' => [
			'name' => clienttranslate('Monster Breach'),
			'scale' => 4,
			'player counts' => [
				6 => [8, 8]
				]
			]
	],
	
	// Info about all the cards in the water deck. Clear waters, gems, basic items,  character items, enemy cards
	'waterDeck' => [
		// Clear Water
		'clearWater' => [
			'name' => clienttranslate('Clear Water'),
			'type' => 'water',
			'remove' => [
				3 => 15,
				4 => 10,
				5 => 5,
				6 => 0,
				],
			],

		// Gems
		'amethyst' => [
			'name' => clienttranslate('Amethyst'),
			'type' => 'gem',
			'value' => 2,
			'quantity' => 9,
			],
		'topaz' => [
			'name' => clienttranslate('Topaz'),
			'type' => 'gem',
			'value' => 3,
			'quantity' => 7,
			],
		'sapphire' => [
			'name' => clienttranslate('Sapphire'),
			'type' => 'gem',
			'value' => 4,
			'quantity' => 5,
			],
		'emerald' => [
			'name' => clienttranslate('Emerald'),
			'type' => 'gem',
			'value' => 5,
			'quantity' => 3,
			],
		'ruby' => [
			'name' => clienttranslate('Ruby'),
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
			'text' => clienttranslate('Ignore 1 cannon result.'),
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
		'metalMallet' => [
			'name' => clienttranslate('Metal Mallet'),
			'type' => 'item',
			'value' => 1,
			'trigger' => 'reveal fire',
			'text' => clienttranslate('Fix 1 Busted Cannon.'),
			],
		'moldyMop' => [
			'name' => clienttranslate('Moldy Mop'),
			'type' => 'item',
			'value' => 0,
			'trigger' => 'resolve bucket',
			'text' => clienttranslate('Discard up to 3 face-up Clear Water cards from the Water Column.'),
			],
		'rubberDucky' => [
			'name' => clienttranslate('Rubber Ducky'),
			'type' => 'item',
			'value' => 0,
			'trigger' => 'none',
			'text' => clienttranslate('Clear water cards from your hand do not count toward your end-game hand size.'),
			],
		'silverDoubloon' => [
			'name' => clienttranslate('Silver Doubloon'),
			'type' => 'item',
			'value' => 3,
			'trigger' => 'reveal dial',
			'text' => clienttranslate('Give this card to another player. They must change their Dial to a different action.'),
			],
		'smellySponge' => [
			'name' => clienttranslate('Smelly Sponge'),
			'type' => 'item',
			'value' => 0,
			'trigger' => 'resolve bucket',
			'text' => clienttranslate('Discard 1 face-up Clear Water card in the Water Column or from your hand.'),
			],
		'somberSkull' => [
			'name' => clienttranslate('Somber Skull'),
			'type' => 'item',
			'value' => 0,
			'trigger' => 'none',
			'text' => clienttranslate('"Just a dark reminder of your impending doom."'),
			],
		'spareBarrel' => [
			'name' => clienttranslate('Spare Barrel'),
			'type' => 'item',
			'value' => 0,
			'trigger' => 'resolve patch',
			'text' => clienttranslate('Upgrade a Cannon card in the Cannon Column.'),
			],
		'stickyStarfish' => [
			'name' => clienttranslate('Sticky Starfish'),
			'type' => 'item',
			'value' => 0,
			'trigger' => 'enemy dice rolled',
			'text' => clienttranslate('Ignore 1 Breach result.'),
			],
		'treasureMap' => [
			'name' => clienttranslate('Treasure Map'),
			'type' => 'item',
			'value' => -1,
			'trigger' => 'none',
			'text' => clienttranslate('Worth 5 Victory points if you have no Clear Water cards in your hand.'),
			],
		'waterFlask' => [
			'name' => clienttranslate('Water Flask'),
			'type' => 'item',
			'value' => -1,
			'trigger' => 'none',
			'text' => clienttranslate('Worth 1 Victory point for each Clear Water card in your hand.'),
			],
		'waterPistol' => [
			'name' => clienttranslate('Water Pistol'),
			'type' => 'item',
			'value' => 1,
			'trigger' => 'resolve bucket',
			'text' => clienttranslate('Give 1 Clear Water card in your hand to another player.'),
			],
		'woodenMallet' => [
			'name' => clienttranslate('Wooden Mallet'),
			'type' => 'item',
			'value' => 0,
			'trigger' => 'reveal patch',
			'text' => clienttranslate('Gain 1 extra Hammer this round.'),
			],

			// Enemy Cards! ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		// Kraken cards
		'warDrum' => [
			'name' => clienttranslate('War Drum'),
			'type' => 'enemy item',
			'enemy' => 'Kraken',
			'quantity' => 1,
			'value' => 0,
			'trigger' => 'enemy dice rolled',
			'text' => clienttranslate('Ignore 1 Splash result this round.'),
			],
		'hurdyGurdy' => [
			'name' => clienttranslate('Hurdy Gurdy'),
			'type' => 'enemy item',
			'enemy' => 'Kraken',
			'quantity' => 1,
			'value' => 0,
			'trigger' => 'enemy dice rolled',
			'text' => clienttranslate('Ignore 1 Angered result this round.'),
			],
		// Shark cards	
		'fishyBait' => [
			'name' => clienttranslate('Fishy Bait'),
			'type' => 'enemy item',
			'enemy' => 'Shark',
			'quantity' => 1,
			'value' => 0,
			'trigger' => 'resolve water',
			'text' => clienttranslate('Move the top 3 cards from the Shark\'s Belly to the Discard.'),
			],
		'cheekyChum' => [
			'name' => clienttranslate('Cheeky Chum'),
			'type' => 'enemy item',
			'enemy' => 'Shark',
			'quantity' => 1,
			'value' => 0,
			'trigger' => 'resolve water',
			'text' => clienttranslate('Ignore all Submerged results this round.'),
			],
		// Sirens' cards
		'sirenSilencers' => [
			'name' => clienttranslate('Siren Silencers'),
			'type' => 'enemy item',
			'enemy' => 'Sirens',
			'quantity' => 1,
			'value' => 0,
			'trigger' => 'enemy dice rolled',
			'text' => clienttranslate('Ignore all Screech results this round.'),
			],
		'sirenShiner' => [
			'name' => clienttranslate('Siren Shiner'),
			'type' => 'enemy item',
			'enemy' => 'Sirens',
			'quantity' => 1,
			'value' => 0,
			'trigger' => 'enemy dice rolled',
			'text' => clienttranslate('Before resolving dice, flip all Basic Attack dice to their opposite sides.'),
			],
		// Skullsairs	
		'cursedAmulet' => [
			'name' => clienttranslate('Cursed Amulet'),
			'type' => 'enemy item',
			'enemy' => 'Skullsairs',
			'quantity' => 6,
			'value' => 0,
			'trigger' => 'none',
			'text' => clienttranslate('Collect more for a combined value: 1=1VP, 2=4VP, 4=12VP, 6=24VP.'),
			],

		// Player cards! ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~	
		'cutlass' => [
			'name' => clienttranslate('Cutlass'),
			'type' => 'player item',
			'player' => RED,
			'value' => 0,
			'trigger' => 'reveal plunder',
			'text' => clienttranslate('Move your Dial to the top of the Treasure Column.'),
			],
		'trustyCarrot' => [
			'name' => clienttranslate('Trusty Carrot'),
			'type' => 'player item',
			'player' => ORANGE,
			'value' => 0,
			'trigger' => 'reveal patch',
			'text' => clienttranslate('Discard a Minor Breach from the Breaches Column.'),
			],
		'boneClub' => [
			'name' => clienttranslate('Bone Club'),
			'type' => 'player item',
			'player' => GREEN,
			'value' => 0,
			'trigger' => 'resolve patch OR resolve fire',
			'text' => clienttranslate('Steal 1 random card from a player who resolved a Treasure this round.'),
			],
		'harpoon' => [
			'name' => clienttranslate('Harpoon'),
			'type' => 'player item',
			'player' => BLUE,
			'value' => 0,
			'trigger' => 'resolve bucket',
			'text' => clienttranslate('Reveal a card in the Water Column. If it\'s a Treasure, add it to your hand.'),
			],
		'grenado' => [
			'name' => clienttranslate('Grenado'),
			'type' => 'player item',
			'player' => PURPLE,
			'value' => 0,
			'trigger' => 'resolve fire',
			'text' => clienttranslate('Roll 1 Triple-Shot die against the enemy. On a miss, deal a Breach card to the Breaches Column.'),
			],
		'spyGlass' => [
			'name' => clienttranslate('Spy Glass'),
			'type' => 'player item',
			'player' => GRAY,
			'value' => 0,
			'trigger' => 'declare dial',
			'text' => clienttranslate('Reveal a player\'s Dial. If they lied, draw 2 cards from the Water Deck. Otherwise, discard 2 cards.'),
			],
	],

];


