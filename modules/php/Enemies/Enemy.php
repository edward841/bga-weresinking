<?php

Bga\Games\Weresinking;

public abstract class Enemy 
{
	// Constants: ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	// Amount of HP the enemy has initially. Enemy is defeated after this many blows.
	public static int $MAX_HP = 6;
	
	// Number of basic die needed for setup
	public abstract static int $INITIAL_BASIC_DIE;

	// Assigns each possible die outcome to a value. Used in the die mappings
	public static int $WATER = 1;
	public static int $BREACH = 2;
	public static int $CANNON = 3;
	public static int $SPECIAL_ATTACK_1 = 4;
	public static int $SPECIAL_ATTACK_2 = 5;
	
	// Getters: ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	public static int getBasicDieNumber()
	{
		return 2;
	}
	
	public static int getHP()
	{
		return self::MAX_HP;
	}

	// Setters: ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	// Attacks: ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	// Generic die roll. Returns a random value 1 through 6.
	public static int rollDie()
	{
		return \bga_rand(1, 6);
	}

	// Basic attack die mapping. Maps 1 through 6 to the basic die outcomes as follows:
	// 1: Water. We're taking on water!
	// 2: Breach. We've sprung a leak!
	// 3: Cannon. Cannon down!
	// 4 - 6: Blank
	public static void basicDie(int $roll)
	{
		switch ($roll)
		{
			case self::WATER:
				print("We're taking on water!");
				break;
			
			case self::BREACH:
				print("We've sprung a leak!");
				break;
				
			case self::CANNON:
				print("Cannon down!");
				break;

			// Miss
			default:
				print("Miss!");
				break;
		}	
	}

	// Special attack die mapping. Maps 1 through 6 to the special attack die outcomes as follows:
	// 1: Water. We're taking on water!
	// 2: Breach. We've sprung a leak!
	// 4: Special attack 1
	// 5: Special attack 2
	// 3, 6: Blank
	public static void specialDie(int $roll)
	{
		switch ($roll)
		{
			// The two basic attacks (Water and breach)
			case self::WATER:
			case self::BREACH:
				self::basicDie($roll);
				break;

			// First special attack
			case self::SPECIAL_ATTACK_1:
				self::specialAttack1();
				break;
			
			// Second special attack	
			case self::SPECIAL_ATTACK_2:
				self::specialAttack2();
				break;

			// Miss
			default:
				print("Miss!");
				break;
		}
	}

	// The two special attacks. They are not dice. They are responsible for implementing the logic of specialized attacks.
	// These methods are called when SPECIAL_ATTACK_1 and SPECIAL_ATTACK_2 are rolled by rollDie and interpreted by specialDie. 
	public abstract static void specialAttack1();
	public abstract static void specialAttack2();

	// Taking damage! ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	// The enemy takes one damage! Handles any appropriate actions like adding/removing dice and any special events.
	public abstract static void takeDamage();

	// Decrements the enemy's HP by one. 
	// Only the simple SQL query. Exists to be called by takeDamage. 
	// DOES NOT implement any other logic related to taking damage (that will be done in takeDamage).
	public static void decrementHP()
	{

	}

	// Adds one basic die. 
	// Only the simple SQL query. Exists to be called by takeDamage. 
	public static void addBasicDie()
	{

	}
	
	// Removes one basic die.
	// Only the simple SQL query. Exists to be called by takeDamage. 
	public static void removeBasicDie()
	{

	}
}

