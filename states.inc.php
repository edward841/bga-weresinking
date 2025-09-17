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

	define('STATE_CHECK_FOR_BREACHES', 11);
	define('STATE_CHECK_WATER_THRESHOLD', 12);
	define('STATE_DEAL_WATER_AND_TREASURE', 13);
	define('STATE_ROLL_ENEMY_DICE', 14);
	define('STATE_RESOLVE_ENEMY_DICE', 15);
	define('STATE_DECLARE_DIAL_HELPER', 20);
	define('STATE_DECLARE_DIAL', 22);
	define('STATE_REVEAL_DIAL', 24);
	define('STATE_RESOLVE_BUCKET_HELPER', 26);	
	define('STATE_RESOLVE_BUCKET', 28);
	define('STATE_RESOLVE_PLUNDER_HELPER', 30);
	define('STATE_RESOLVE_PLUNDER', 32);
	define('STATE_RESOLVE_PATCH_HELPER', 34);
	define('STATE_RESOLVE_PATCH', 36);
	define('STATE_RESOLVE_FIRE_HELPER', 38);
	define('STATE_RESOLVE_FIRE', 40);
	define('STATE_UPKEEP', 50);

	define('STATE_END_GAME_SCORING', 98);
	define('STATE_END_GAME', 99);
}





$machinestates = [

    // The initial state. Please do not modify.

    STATE_START_GAME => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => ["" => STATE_CHECK_FOR_BREACHES],
    ),

	STATE_CHECK_FOR_BREACHES => array(
		'name' => 'checkForBreaches',
		'description' => '',
		'type' => 'game',
		'action' => 'stCheckForBreaches',
		'transitions' => ['' => STATE_CHECK_WATER_THRESHOLD],
	),

	STATE_CHECK_WATER_THRESHOLD => array (
		'name' => 'checkWaterThreshold',
		'description' => '',
		'type' => 'game',
		'action' => 'stCheckWaterThreshold',
		'transitions' => ['' => STATE_DEAL_WATER_AND_TREASURE],
	),

	STATE_DEAL_WATER_AND_TREASURE => array(
		'name' => 'dealWaterAndTreasure',
		'description' => '',
		'type' => 'game',
		'action' => 'stDealWaterAndTreasure',
		'transitions' => ['' => STATE_ROLL_ENEMY_DICE],
	),

	STATE_ROLL_ENEMY_DICE => array(
		'name' => 'rollEnemyDice',
		'description' => '',
		'type' => 'game',
		'action' => 'stRollEnemyDice',
		'transitions' => ['' => STATE_RESOLVE_ENEMY_DICE],
	),

	STATE_RESOLVE_ENEMY_DICE => array(
		'name' => 'resolveEnemyDice',
		'description' => '',
		'type' => 'game',
		'action' => 'stResolveEnemyDice',
		'transitions' => ['' => STATE_DECLARE_DIAL_HELPER],
	),

	STATE_DECLARE_DIAL_HELPER => array(
		'name' => 'declareDialHelper',
		'description' => '',
		'type' => 'game',
		'action' => 'stDeclareDialHelper',
		'transitions' => ['declareDial' => STATE_DECLARE_DIAL, 'revealDial' => STATE_REVEAL_DIAL],
	),

	STATE_DECLARE_DIAL => array(
		'name' => 'declareDial',
		'description' => clienttranslate('${actplayer} must declare their action'),
		'descriptionmyturn' => clienttranslate('${you} must declare your action'),
		'type' => 'activeplayer',
		'possibleactions' => ['actDeclareDial'],
		'args' => 'argDeclareDial',
		'transitions' => ['next' => STATE_DECLARE_DIAL_HELPER],
	), 

	STATE_REVEAL_DIAL => array(
		'name' => 'revealDial',
		'description' => '',
		'type' => 'game',
		'action' => 'stRevealDial',
		'transitions' => ['resolveBucketHelper' => STATE_RESOLVE_BUCKET_HELPER],
	), 

	STATE_RESOLVE_BUCKET_HELPER => array(
		'name' => 'resolveBucketHelper',
		'description' => '',
		'type' => 'game',
		'action' => 'stResolveBucketHelper',
		'transitions' => [	
			'bucket' => STATE_RESOLVE_BUCKET, 
			'plunder' => STATE_RESOLVE_PLUNDER_HELPER,
			'patch' => STATE_RESOLVE_PATCH_HELPER,
			'fire' => STATE_RESOLVE_FIRE_HELPER, 
			'upkeep' => STATE_UPKEEP,
		],
		//'transitions' => ['resolveAction' => STATE_RESOLVE_BUCKET, 'nextAction' => STATE_RESOLVE_PLUNDER_HELPER],
	), 

	STATE_RESOLVE_BUCKET => array(
		'name' => 'resolveBucket',
		'description' => clienttranslate('${actplayer} must Bucket'),
		'descriptionmyturn' => clienttranslate('Bucket: ${you} must ${verb} ${nbr} card(s)${ending}'),
		'type' => 'activeplayer',
		'possibleactions' => ['actDraw', 'actDiscard'],
		'args' => 'argResolveBucket',
		'transitions' => ['next' => STATE_RESOLVE_BUCKET_HELPER],
	), 

	STATE_RESOLVE_PLUNDER_HELPER => array(
		'name' => 'resolvePlunderHelper',
		'description' => '',
		'type' => 'game',
		'action' => 'stResolvePlunderHelper',
		'transitions' => [	
			'plunder' => STATE_RESOLVE_PLUNDER,
			'patch' => STATE_RESOLVE_PATCH_HELPER,
			'fire' => STATE_RESOLVE_FIRE_HELPER, 
			'upkeep' => STATE_UPKEEP,
		],
	), 

	STATE_RESOLVE_PLUNDER => array(
		'name' => 'resolvePlunder',
		'description' => clienttranslate('${actplayer} must Plunder'),
		'descriptionmyturn' => clienttranslate('Plunder: ${you} must choose 1 card from the Treasure Column'),
		'type' => 'activeplayer',
		'possibleactions' => ['actDraw'],
		'args' => 'argResolvePlunder',
		'transitions' => ['next' => STATE_RESOLVE_PLUNDER_HELPER],
	), 

	STATE_RESOLVE_PATCH_HELPER => array(
		'name' => 'resolvePatchHelper',
		'description' => '',
		'type' => 'game',
		'action' => 'stResolvePatchHelper',
		'transitions' => [	
			'patch' => STATE_RESOLVE_PATCH,
			'fire' => STATE_RESOLVE_FIRE_HELPER, 
			'upkeep' => STATE_UPKEEP,
		],
	), 

	STATE_RESOLVE_PATCH => array(
		'name' => 'resolvePatch',
		'description' => clienttranslate('${actplayer} must Patch'),
		'descriptionmyturn' => clienttranslate('${you} must ${actiondescription}'),
		'type' => 'activeplayer',
		'possibleactions' => ['actDraw', 'actDiscard', 'actPatch'],
		'args' => 'argResolvePatch',
		'transitions' => ['next' => STATE_RESOLVE_PATCH_HELPER],
	),

	STATE_RESOLVE_FIRE_HELPER => array(
		'name' => 'resolveFireHelper',
		'description' => '',
		'type' => 'game',
		'action' => 'stResolveFireHelper',
		'transitions' => [
			'fire' => STATE_RESOLVE_FIRE,
			'upkeep' => STATE_UPKEEP,
		],
	),

	STATE_RESOLVE_FIRE => array(
		'name' => 'resolveFire',
		'description' => clienttranslate('${actplayer} ${verb} Fire'),
		'descriptionmyturn' => clienttranslate('${you} ${instruction}'),
		'type' => 'activeplayer',
		'possibleactions' => ['actFire', 'actShootYeTreasure', 'actPass'],
		'args' => 'argResolveFire',
		'transitions' => ['next' => STATE_RESOLVE_FIRE_HELPER],
	),

	STATE_UPKEEP => array(
		'name' => 'upkeep',
		'description' => '',
		'type' => 'game',
		'action' => 'stUpkeep',
		'transitions' => ['' => STATE_CHECK_FOR_BREACHES],
	),

//	STATE_END_GAME_SCORING => array(
//	),

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



