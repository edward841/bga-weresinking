<?php

Bga\Games\Weresinking;

public abstract class Enemy 
{
	public MyGame $game;

	// Constants: ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	// Amount of HP the enemy has initially. Enemy is defeated after this many blows.
	const int $MAX_HP = 6;
	
	// Number of basic die needed for setup
	public abstract int $INITIAL_BASIC_DIE;
	
	const int[] basicDieMapping = [1 => 'Water', 2 => 'Breach', 3 => null, 4 => null, 5 => null, 6 => 'Cannon'];
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

	const abstract string $SPECIAL_ATTACK_1_NAME;
	const abstract string $SPECIAL_ATTACK_2_NAME;
	const abstract int[] $ADJUST_ACTIVE_DICE;
		
	// Getters: ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	public int getBasicDieNumber()
	{
		return 2;
	}
	
	public int getHP()
	{
		return $this->game->globals->get('ENEMY_HP');
	}

	// Setters: ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	// Attacks: ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	// Generic die roll. Returns a random value 1 through 6.
	public int rollDie()
	{
		return \bga_rand(1, 6);
	}

	public void resolveBasicDie(int $roll)
	{
		$result = Enemy::basicDieMapping[$roll];
		if ($result != null)
			$this->resolve{$result}();
	}

	public void resolveSpecialDie(int $roll)
	{
		$result = Enemy::specialDieMapping[$roll];
		if ($result != null)
			$this->resolve{$result}();
	}

//	public void resolveBasicDie(int $roll)
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


	private void resolveWater()
	{
		$this->game->water->pickCardForLocation('deck', 'waterColumn');		
	}

	private void resolveBreach()
	{
		$this->game->breaches->pickCardForLocation('deck', 'breachesColumn');
	}

	private void resolveCannon()
	{

	}

	// The two special attacks. They are not dice. They are responsible for implementing the logic of specialized attacks.
	// These methods are called when SPECIAL_ATTACK_1 and SPECIAL_ATTACK_2 are rolled by rollDie and interpreted by specialDie. 
	public abstract void resolveSpecialAttack1();
	public abstract void resolveSpecialAttack2();

	// Taking damage! ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	// The enemy takes one damage! Handles any appropriate actions like adding/removing dice and any special events.
	public void takeDamage()
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
	private void decrementHP()
	{
		$this->game->globals->inc('ENEMY_HP', -1);
	}

	// Adds one basic die. 
	// Only the simple SQL query. Exists to be called by takeDamage. 
	private void addBasicDie()
	{

	}
	
	// Removes one basic die.
	// Only the simple SQL query. Exists to be called by takeDamage. 
	private void removeBasicDie()
	{

	}

	protected abstract reactToDamage(int $HP);
}

