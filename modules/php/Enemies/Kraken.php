<?php

Bga\Games\Weresinking;

// Implementation of the fearsom Kraken!
//
// Special attacks: Splash and Angered
// Items added: War Drum and Hurdy Gurdy
public class Kraken extends Enemy 
{
	// Number of basic die needed for setup
	public static int $INITIAL_BASIC_DIE = 2;
		
	// Implements the Splash.
	// Place the top card in the treasure column face-down into the water column.
	public static void specialAttack1()
	{

	}

	// Implements the Angered.
	// When a card is added to the discard pile this round, immediately roll and resolve 1 basic attack die.
	public static void specialAttack2()
	{

	}

	// The enemy is dealt a blow. Handles all logic of taking damage.	
	public static takeDamage()
	{
		self::decrementHP();
		
		int $hp = self::getHP();
		if ($hp == 5 || $hp == 2)
		{
			self::addBasicDie();
		}
	}
}



