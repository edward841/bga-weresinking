<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * weresinking implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * states.inc.php
 *
 * weresinking game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: $this-checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

use Bga\GameFramework\StateType;

// Constants for state ids:
if (!defined('STATE_END_GAME'))
{
	define('STATE_START_GAME', 1);
	define('STATE_CHECK_FOR_BREACHES', 10);
	define('STATE_CHECK_WATER_THRESHOLD', 20);
	define('STATE_DEAL_WATER_AND_TREASURE', 30);
	define('STATE_ROLL_ENEMY_DICE', 40);
	define('STATE_RESOLVE_ENEMY_DICE', 45);
	define('STATE_DECLARE_ACTIONS', 50);
	define('STATE_REVEAL_ACTIONS', 60);
	define('STATE_RESOLVE_ACTIONS', 65);
	define('STATE_END_GAME', 99);
}





$machinestates = [

    // The initial state. Please do not modify.

    STATE_START_GAME => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => ["" => 90] //STATE_CHECK_FOR_BREACHES]
    ),

	STATE_CHECK_FOR_BREACHES => array(
		'name' => 'checkForBreaches',
		'description' => '',
		'type' => StateType::GAME,
		'action' => 'stCheckForBreaches',
		'transitions' => ['' => STATE_CHECK_WATER_THRESHOLD]
	),

	STATE_CHECK_WATER_THRESHOLD => array (
		'name' => 'checkWaterThreshold',
		'description' => '',
		'type' => StateType::GAME,
		'action' => 'stCheckWaterThreshold',
		'transitions' => ['' => STATE_DEAL_WATER_AND_TREASURE]
	),

	STATE_DEAL_WATER_AND_TREASURE => array(
		'name' => 'dealWaterAndTreasure',
		'description' => '',
		'type' => StateType::GAME,
		'action' => 'stDealWaterAndTreasure',
		'transitions' => ['' => STATE_ROLL_ENEMY_DICE]
	),

	STATE_ROLL_ENEMY_DICE => array(
		'name' => 'rollEnemyDice',
		'description' => '',
		'type' => StateType::GAME,
		'action' => 'stRollEnemyDice',
		'transitions' => ['' => STATE_RESOLVE_ENEMY_DICE]
	),

	STATE_RESOLVE_ENEMY_DICE => array(
		'name' => 'resolveEnemyDice',
		'description' => '',
		'type' => StateType::GAME,
		'action' => 'stResolveEnemyDice',
		'transitions' => ['' => STATE_CHECK_FOR_BREACHES]
	),

	90 => array(
		'name' => 'dummyState',
		'description' => '',
		'type' => StateType::GAME,
		'action' => 'stDummyState',
		'transitions' => ['' => STATE_END_GAME]
	),

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    STATE_END_GAME => [
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    ],

];



