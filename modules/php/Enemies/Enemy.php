<?php

declare(strict_types=1);
namespace Bga\Games\weresinking;

public abstract class Enemy 
{
	// Abstract instance members: ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	// Number of basic die needed for setup
	public abstract int $INITIAL_BASIC_DIE;

	// Indicates at which HP to add/remove basic attack die
	const abstract int[] $ADJUST_ACTIVE_DICE;

	// Handle to the Game object
	public MyGame $game;

	// Amount of HP the enemy has initially. Enemy is defeated after this many blows.
	const int $MAX_HP = 6;
	
	// Maps the possible die outcomes (1-6) to the corresponding outcome on a basic die.
	// Note: The mapping was specifically chosen to have the same topology of a regular D6 
	// i.e. Water and Cannon are on opposite sides of the physical die, so they correspond to 1 and 6 (values opposite each other on a D6)
	const int[] basicDieMapping = [1 => 'Water', 2 => 'Breach', 3 => null, 4 => null, 5 => null, 6 => 'Cannon'];
	
	// Maps the possible die outcomes (1-6) to the corresponding outcome on a special die.
	// Note: The mapping was specifically chosen to have the same topology of a regular D6 
	// i.e. Water and Breach are on opposite sides of the physical die, so they correspond to 1 and 6 (values opposite each other on a D6)
	const int[] specialDieMapping = [1 => 'Water', 2 => 'SpecialAttack1', 3 => null, 4 => null, 5 => 'SpecialAttack2', 6 => 'Breach'];

	// Assigns each possible die outcome to an outcome
//	const int WATER = 1;
//	const int BREACH = 2;
//	const int CANNON = 6;
//
//	const int SPECIAL_WATER = 1;
//	const int SPECIAL_BREACH = 6;
//	const int SPECIAL_ATTACK_1 = 2;
//	const int SPECIAL_ATTACK_2 = 5;

		
	// Getters: ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	public function getHP(): int 
	{
		return $this->game->globals->get('ENEMY_HP');
	}

	// Attacks: ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	// Generic die roll. Returns a random value 1 through 6. Topology identical to a standard D6.
	public function rollDie(): int 
	{
		return \bga_rand(1, 6);
	}

	public function resolveBasicDie(int $roll): void 
	{
		$result = Enemy::basicDieMapping[$roll];
		if ($result != null)
			$this->resolve{$result}();
	}

	public function resolveSpecialDie(int $roll): void 
	{
		$result = Enemy::specialDieMapping[$roll];
		if ($result != null)
			$this->resolve{$result}();
	}

//	public resolveBasicDie(int $roll): void 
//	{
//		switch ($roll)
//		{
//			case Enemy::WATER:
//				$this->resolveWater();	
//				break;
//
//			case Enemy::BREACH:
//				$this->resolveBreach();
//				break;
//
//			case Enemy::CANNON:
//				$this->resolveCannon();
//				break;
//		}
//	}

	private function resolveWater(): void 
	{
		$this->game->water->pickCardsForWaterColumn(1);		
	}

	private function resolveBreach(): void 
	{
		$this->game->breaches->pickCardForLocation('deck', 'breachesColumn');
	}

	private function resolveCannon(): void 
	{

	}

	// The first special attack listed on the enemy card. Responsible for implementing the logic of specialized attack.
	protected function abstract resolveSpecialAttack1(): void;
	
	// The second special attack listed on the enemy card. Responsible for implementing the logic of specialized attack.
	protected function abstract resolveSpecialAttack2(): void;

	// Taking damage! ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	// The enemy takes one damage! Handles any appropriate actions like adding/removing dice and any special events.
	public function takeDamage(): void 
	{
		$this->decrementHP();
		$HP = $this->getHP();

		switch ($this->ADJUST_ACTIVE_DICE[$HP])
		{
			case 1:
				$this->addBasicDie();
				break;

			case -1:
				$this->removeBasicDie();
				break;
		}
			
		$this->reactToDamage($HP);
	}

	// Decrements the enemy's HP by one. 
	// Only the simple SQL query. Exists to be called by takeDamage. 
	// DOES NOT implement any other logic related to taking damage (that will be done in takeDamage).
	private function decrementHP(): void 
	{
		$this->game->globals->inc('ENEMY_HP', -1);
	}

	// Adds one basic die. 
	// Only the simple SQL query. Exists to be called by takeDamage. 
	private function addBasicDie(): void 
	{

	}
	
	// Removes one basic die.
	// Only the simple SQL query. Exists to be called by takeDamage. 
	private function removeBasicDie(): void 
	{

	}

	// Handles any action(s) that occur when the Enemy receives damage (The * fine print on the bottom of the sheet)
	// If there is a secondary action, it should occur when the Enemy's HP is $HP
	// 
	// Ex: For the Shark, move cards from the Shark's belly to the Water column
	protected function abstract reactToDamage(int $HP): void;
}

