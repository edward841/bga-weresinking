<?php

declare(strict_types=1);
namespace Bga\Games\weresinking;

// Implementation of the fearsom Kraken!
//
// Special attacks: Splash and Angered
// Items added: War Drum and Hurdy Gurdy
class Kraken extends Enemy 
{
	// Constructor: ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	public function __construct(Game $game)
	{
		$initialBasicDice = 2;
		$adjustActiveDice = [5 => 1, 4 => 0, 3 => 0, 2 => 1, 1 => 0, 0 => 0];
		parent::__construct($game, $initialBasicDice, $adjustActiveDice);
	}

	// Abstract methods: ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	// The first special attack listed on the enemy card. Responsible for implementing the logic of specialized attack.
	// Implements the Splash:
	// Place the top card in the treasure column face-down into the water column.
	protected function resolveSpecialAttack1(): void 
	{
		$this->game->debug("\n\nSpecial Attack 1 here!!\n\n");
	}

	// The second special attack listed on the enemy card. Responsible for implementing the logic of specialized attack.
	// Implements the Angered:
	// When a card is added to the discard pile this round, immediately roll and resolve 1 basic attack die.
	protected function resolveSpecialAttack2(): void 
	{
		$this->game->debug("\n\nSpecial Attack 2 here!!\n\n");
	}
	
	// Handles any action(s) that occur when the Enemy receives damage (The * fine print on the bottom of the sheet)
	// If there is a secondary action, it should occur when the Enemy's HP is $HP
	// 
	// Ex: For the Shark, move cards from the Shark's belly to the Water column
	//
	// Not applicable. The Kraken has no such action.
	protected function reactToDamage(int $HP): void 
	{
		return;
	}
}
