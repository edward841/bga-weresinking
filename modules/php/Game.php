<?php
/**
 * ------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * weresinking implementation : © Edward Niemann <edward.niemann841@gmail.com>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */
declare(strict_types=1);
namespace Bga\Games\weresinking;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");

class Game extends \Table
{
    /**
     * Your global variables labels:
     *
     * Here, you can assign labels to global variables you are using for this game. You can use any number of global
     * variables with IDs between 10 and 99. If your game has options (variants), you also have to associate here a
     * label to the corresponding ID in `gameoptions.inc.php`.
     *
     * NOTE: afterward, you can get/set the global variables with `getGameStateValue`, `setGameStateInitialValue` or
     * `setGameStateValue` functions.
     */
    public function __construct()
    {
        parent::__construct();

        $this->initGameStateLabels([
            "my_first_global_variable" => 10,
            "my_second_global_variable" => 11,
            "my_first_game_variant" => 100,
            "my_second_game_variant" => 101,
        ]);        

		// Initialize the three decks: Water, Breaches, and Cannons
		$this->water = $this->getNew('module.common.deck');
		$this->water->init('water');

		$this->breaches = $this->getNew('module.common.deck');
		$this->breaches->init('breach');
		
		$this->cannons = $this->getNew('module.common.deck');
		$this->cannons->init('cannon');
	}

	public function stCheckForBreaches()
	{
		// There is a water card for each permanent breach 
		$water = (int) $this->globals->get('PERMANENT_BREACHES');

		// For each breach card in the breaches column, add a water card for each water symbol (denoted by 'scale' in the material file)
		foreach ($this->breaches->getCardsInLocation('breachesColumn') as $cardId => $details)
		{
			$water += $this->tokens['breaches'][$details['type']]['scale'];
		}

		// Deal $water number of water cards from the deck to the waterColumn
		$this->pickCardsForWaterColumn($water);

		$this->gamestate->nextState();
	}

	public function stCheckWaterThreshold()
	{
		// Check the water threshold. If equal to or greater, then carry out sinking procedures.
		$numPlayers = $this->getPlayersNumber();
		$thresholdLevel = $this->globals->get('THRESHOLD_LEVEL');
		$waterThreshold = (int) $this->tokens['thresholdSheets']["$numPlayers players"]["level $thresholdLevel"]['threshold'];
	
		if ($this->water->countCardInLocation('waterColumn') >= $waterThreshold)
		{
			// Sinking procedures here
			// STEP 1: Remove the lowest section of the ship from the game and take out its two Chest Tokens (without revealing them)
			// STEP 2: Place the Chest Tokens face-down in the bottom of the Breaches Column
			// TODO Implement chests...

			// STEP 3: Move the Threshold Sheet to the next level
			$this->globals->inc('THRESHOLD_LEVEL', 1);

			// STEP 4: Shuffle all cards in the Water deck, disard pile, and water and treasure columns to create a new water deck
			$this->water->moveAllCardsInLocation('discard', 'deck');
			$this->water->moveAllCardsInLocation('waterColumn', 'deck');
			$this->water->moveAllCardsInLocation('treasureColumn', 'deck');
			$this->water->shuffle('deck');

			// STEP 5: If there are any Breach cards in the Breaches column, discard all Breach cards and gain 1 Permanent Breach Token. Add the Permanent Breach Token to the top of the Breaches columm.
			if ($this->breaches->countCardInLocation('breachesColumn') > 0)
			{
				foreach ($this->breaches->getCardsInLocation('breachesColumn') as $card)
				{
					$this->breaches->insertCardOnExtremePosition($card['id'], 'deck', false);
				}

				$this->globals->inc('PERMANENT_BREACHES', 1);
			}

			// STEP 6: Flip over the First Mate scroll and continue the round on Step 3 of the Duties Checklist.
		}

		$this->gamestate->nextState();
	}	
	
	public function stDealWaterAndTreasure()
	{
		$numPlayers = $this->getPlayersNumber();
		$thresholdLevel = (int) $this->globals->get('THRESHOLD_LEVEL');
		$thresholdPanelInfo = $this->tokens['thresholdSheets']["$numPlayers players"]["level $thresholdLevel"];
		
		// Pick the correct number of cards for the water column according to the threshold panel
		$this->pickCardsForWaterColumn((int) $thresholdPanelInfo['water']);

		// Draw the correct number of cards for the treasure column. If you find a clear water, put it in the water column and keep drawing.
		// (since the default value of card_face_up is true, the waters we find will have the proper card_face_up value by default)
		$remainingTreasures = (int) $thresholdPanelInfo['treasure'];
		while ($remainingTreasures > 0)
		{
			$card = $this->water->getCardOnTop('deck');
			if ($card['type'] === 'clearWater')
				$this->water->insertCardOnExtremePosition($card['id'], 'waterColumn', COLUMN_BOTTOM);
			else
			{
				$this->addToTreasureColumn((int) $card['id']);
				$remainingTreasures--;
			}
		}
		
		$this->gamestate->nextState();
	}

	public function stRollEnemyDice()
	{
		// Get the ids of all the attack die (both basic and special attack dice)
		// Generate the correct number of random values 
		// $$\forall x \in $rolls, x \in [1,6] $$
		$diceIds = $this->getCollectionFromDB("SELECT `die_id` FROM `dice` WHERE `type` IN ('basic', 'special')");
		$rolls = array();
		for ($x = 0; $x < count($diceIds); $x++)
		{
			$rolls[] = \bga_rand(1,6);	
		}

		// Update the dice values in the database with the new values
		$updateString = '';
		foreach (array_keys($diceIds) as $id)
		{
			$updateString .= "WHEN $id THEN " . array_pop($rolls) . " ";
		}
		$spliced = implode(',', array_keys($diceIds));
		$this->DbQuery("UPDATE `dice` SET `value` = CASE `die_id` $updateString END WHERE `die_id` in ($spliced)");
		$this->gamestate->nextState();
	}

	public function stResolveEnemyDice()
	{
		// Get the dice data from database
		$diceIds = $this->getCollectionFromDB("SELECT `die_id`, `type`, `value` FROM `dice` WHERE `type` IN ('basic', 'special')");

		// Convert this complex array to a simple array of just the values we need (one of 1, 2, Water, Breach, Cannon, null to indicate die results)
		$dice = array();
		foreach ($diceIds as $id => $details)
		{
			$dice[$id] = $this->tokens['diceMappings'][$details['type']][$details['value']];
		}

		// Sort the results by the order given in material.inc.php (special attack 1, special attack 2, Water, Breach, Cannon, blank)
		// (rulebook specifies results should be resolved in order of special attack 1, special attack 2, and then everything else in no particular order)
		// Default sorting algorithm should be fine for such a small list (6 values at longest)
		uasort($dice, function($a, $b) {
			return $this->tokens['diceOrder'][$a] <=> $this->tokens['diceOrder'][$b];
		});
		$this->debug('Dice roll: {' . implode(',', array_values($dice)) . '}');

		// This game's enemy
		$enemy = $this->globals->get('ENEMY');

		// Resolve each result
		foreach ($dice as $id => $result)
		{
			// Functional programming method for redirecting to the correct function to resolve the die roll
			// null indicates blank roll, basic attack types are denoted plainly ('water', 'breach', or 'cannon'), and special attack are indicated by either '1' or '2'
			// The three basic types (Water, Breach, Cannon) get redirected to their resolveBasic{attack} functions
			// The two special attacks get directed to the proper enemy's attack (e.g. resolveKrakenAttack1)
			$attack = '';
			if ($result === null)
				// We can break because all the null (blank dice) are sorted to the end of the results list
				break; 
			else if (strlen($result) > 1)
				$attack = "resolveBasic{$result}";
			else
				$attack = "resolve{$enemy}Attack{$result}";

			$this->$attack();
		}

		$this->gamestate->nextState();
	}

	public function stDeclareDialHelper()
	{
		$playerInfo = $this->getCollectionFromDB('SELECT `player_id`, `custom_order`, `dial_location` FROM `player` ORDER BY `custom_order`');
		
		// If anyone still needs to declare their dial, give the next person a turn
		// Else go to the next state
		$readyForNextStep = true;
		foreach ($playerInfo as $playerId => $details)
		{
			if ($details['dial_location'] === 'player')
			{
				$this->gamestate->changeActivePlayer($playerId);
				$readyForNextStep = false;
				break;
			}	
		}

		if ($readyForNextStep)
			$this->gamestate->nextState('revealDial');
		else
			$this->gamestate->nextState('declareDial');
	}

	public function stRevealDial()
	{
		// Set the dial's location to match its value (honest pirates will already match, this corrects the location of the liars)
		$this->DbQuery('UPDATE `player` SET `dial_location`=`dial_value`');

		// Correct the turn order to align with the current turn order and dial locations
		// Determine proper order
		$playerInfo = $this->getCollectionFromDB('SELECT `player_id`, `dial_location` FROM `player` ORDER BY `custom_order`', true);
		$sorted = ['bucket' => [], 'plunder' => [], 'patch' => [], 'fire' =>[]];	
		foreach ($playerInfo as $playerId => $location)
			$sorted[$location][] = $playerId;
		$sorted = array_merge($sorted['bucket'], $sorted['plunder'], $sorted['patch'], $sorted['fire']);

		// Update the database to reflect new order
		$updateString = '';
		foreach ($sorted as $order => $playerId)
			$updateString .= "WHEN $playerId THEN " . $order+1 . ' ';
		$this->DbQuery("UPDATE `player` SET `custom_order` = CASE `player_id` $updateString END");
		$this->globals->set('PREVIOUS_PLAYER', 'none');
		$this->gamestate->nextState('resolveBucketHelper');
	}

	public function stResolveBucketHelper()
	{
		$nextPlayer = $this->getNextPlayer();
		$nextAction = 'upkeep';
		
		if ($nextPlayer > 0)
		{
			$nextAction = $this->getUniqueValueFromDB("SELECT `dial_value` FROM `player` WHERE `player_id`='$nextPlayer'");
			if ($nextAction === 'bucket')
			{
				// Update active player
				$this->gamestate->changeActivePlayer($nextPlayer);
				$this->globals->set('PREVIOUS_PLAYER', $this->getActivePlayerId());

				// Set FLAG to true (indicates that the player needs to draw now)
				$this->globals->set('FLAG', true);

				// Set COUNTER to indicate how many draws are needed
				// (and remember $nextPlayer needs updated since we changed active player)
				$nextPlayer = $this->getNextPlayer();
				$scale = ($nextPlayer > 0 && $this->getUniqueValueFromDB("SELECT `dial_value` FROM `player` WHERE `player_id`='$nextPlayer'") === 'bucket') ? 1 : 2;
				$this->globals->set('COUNTER', $scale);
			}
		}	

		// Commence state change
		// If the next player is doing a Bucket action, the prepwork is done and this moves to STATE_RESOLVE_BUCKET
		// If the next player is doing a different action, redirects to the appropriate game state STATE_RESOLVE_X_HELPER
		// If there is not another player, goes to STATE_UPKEEP
		$this->gamestate->nextState($nextAction);
	}

	public function stResolvePlunderHelper()
	{
		$nextPlayer = $this->getNextPlayer();
		$nextAction = 'upkeep';
		
		if ($nextPlayer > 0)
		{
			$nextAction = $this->getUniqueValueFromDB("SELECT `dial_value` FROM `player` WHERE `player_id`='$nextPlayer'");
			if ($nextAction === 'plunder')
			{
				// If FLAG is true, then this is the first time here this round and we need to decide what happens:
				// 1. If there is only 1 person plundering, they get all the treasure
				// 2. Treasure nbr >= plundering players nbr, let the players select their treasure
				// 3. Treasure nbr < plundering players nbr, discard all treasure and move on to the next action
				if ($this->globals->get('FLAG'))
				{
					$playerInfo = $this->getCollectionFromDB('SELECT `player_id`, `custom_order`, `dial_location` FROM `player` ORDER BY `custom_order`');
					$dialValues = array_column(array_values($playerInfo), 'dial_location'); 
					$plunderingPlayersNbr = array_count_values($dialValues)['plunder'];
					$treasureNbr = $this->water->countCardsInLocation('treasureColumn');
					$moveOnToNextAction = false;

					// 1. Only 1 plunderer! Give them all that precious treasure ARGH!!	
					//    (no need for player states, just move on to the next helper state
					if ($plunderingPlayersNbr == 1)
					{
						$this->water->moveAllCardsInLocation('treasureColumn', 'hand', null, $nextPlayer);
						$moveOnToNextAction = true;
					}
					// 2. Several plunderers with enough to go around
					else if ($treasureNbr >= $plunderingPlayersNbr)
					{
						// Setting FLAG to false indicates treasure is getting divided (Makes future stResolvePlunderHelper visits much simpler)
						$this->globals->set('FLAG', false);	
						$this->gamestate->changeActivePlayer($nextPlayer);
					}
					// 3. Too many plunderers, not enough treasure! 
					//    (no need for player states, just move on to the next helper state
					else 
					{
						$this->water->moveAllCardsInLocation('treasureColumn', 'discard');	
						$moveOnToNextAction = true;
					}

					// This action was resolved entirely in the game state (either 1 player or too many players plundered)
					if ($moveOnToNextAction)
					{
						$nextAction = 'patch';
						$lastPlunderer = array_keys($playerInfo)[count($dialValues) - 1 - array_search('plunder', array_reverse($dialValues))];
						$this->globals->set('PREVIOUS_PLAYER', $lastPlunderer);
						// No need to update FLAG since it should still be true
					}

				}
				// If FLAG is false: we're dividing treasure among several players. Give the next player a turn	
				// This looks simple because the logic for determining whether it should loop around or not is all in the getNextPlayer
				else
				{
					$this->gamestate->changeActivePlayer($nextPlayer);
					$this->globals->set('PREVIOUS_PLAYER', $nextPlayer);
				}
			}
		}

		// Commence state change
		// If the next player is doing a plunder action, the prepwork is done and this moves to STATE_RESOLVE_PLUNDER
		// If the next player is doing a different action, redirects to the appropriate game state STATE_RESOLVE_X_HELPER
		// If there is not another player, goes to STATE_UPKEEP
		$this->gamestate->nextState($nextAction);
	}

	public function stResolvePatchHelper()
	{
	}

	public function stResolveFireHelper()
	{
	}

	public function stUpkeep()
	{

	}

	// This dummy state is designed to be a void of nothingness for the FSM to get stuck in.
	// This might sound silly but it helps test functions in isolation without distractions and complications from the FSM.
	public function stDummyState()
	{
		// Intentionally does nothing!
		// Intentionally has no escape!

		// Mawahahahaaa! You cannot escape me!!!
	}

	public function actDeclareDial(string $value, string $location)
	{
		$this->debug("actDeclareDial: value: $value, location $location");

		$this->checkAction('actDeclareDial');
		$currentActions = $this->argDeclareDial()['possibleMoves'];
		if (!in_array($value, $currentActions, true) || !in_array($location, $currentActions, true))
			throw new \BgaSystemException("actDeclareDial: value: '$value', location: '$location' not allowed");

		$activePlayer = $this->getActivePlayerId();
		$this->DbQuery("UPDATE `player` SET `dial_value`='$value', `dial_location`='$location' WHERE `player_id`=$activePlayer");
		$this->gamestate->nextState('next');
	}

	// This is yet untested, but I'm considering a different architecture as a possible solution to the question:
	// In the player states with multiple things, should I separate each thing into its own action? 
	// (e.g. 'draw water from column, then discard from hand' or 'either draw 1 or discard 1. Then perform patch')
	// I'm pretty sure its either that or clientside states, and I'd prefer splitting the actions over messing with clientside states...
	// Pros: Possible actions would be clearer, the backend implementation would be much cleaner, you could enforce the order by arg functions, should be straightforward to display the currently allowed actions in frontend using the same arg function for input
	// Cons: more functions to implement, could be more confusing, not sure how arg function would know what is currently allowed (maybe a global flag?)
	

	public function actDraw(string $cardId, string $location)
	{
		// Check that the paramaters are allowed by redirecting to the correct arg function with functional programming technique
		$currentState = $this->getStateName();
		$argFunction = 'arg' . ucfirst($currentState);
		$args = $this->$argFunction();

		// Fail the draw if: Draw is not in the arg's possibleActions, the given location is wrong, or the given cardId is wrong
		// It might seem silly to check if actDraw is in possibleActions array since we already verified it is allowed by the state. 
		// This lets us to control at what point the player can do each subaction (managing the order of operations for multistep actions like bucket)
		$message = '';
		if (!in_array('Draw', $args['possibleActions'], true)) 
		{
			$possibleActions = implode(',', $args['possibleActions']);
			$message = "Draw not in possibleActions: <$possibleActions>";
		}
		else if ($location !== $args['location'])
			$message = "Location given: $location, expected <{$args['location']}>";
		else if (!in_array((int) $cardId, $args['possibleIds'], true))
		{
			$possibleIds = implode(',', $args['possibleIds']);
			$message = "CardId given: $cardId, expected to be one of <$possibleIds>";
		}
		
		if ($message !== '')
			throw new \BgaSystemException("actDraw: cardId: '$cardId', location: '$location' not allowed in state {$this->getStateName()}\n($message)");

		// Proceed now that all input has been verified: Move the indicated card to the active player's hand
		$this->water->moveCard($cardId, 'hand', $this->getActivePlayerId());

		// If COUNTER decremented is 0, then move on to whatever comes next
		if ($this->globals->inc('COUNTER', -1) <= 0)
		{
			// These two states have two steps to their actions so we need to update FLAG to indicate the first part is done (e.g. draw or discard, then patch)
			if ($currentState === 'resolveBucket' || $currentState === 'patch')
				$this->globals->set('FLAG', false);

			if ($currentState === 'resolveBucket')
			{
				// Determine how many cards need to be discarded (lastPersonToBucket ? 2 : 1)
				$nextPlayer = $this->getNextPlayer();
				$scale = ($nextPlayer > 0 && $this->getUniqueValueFromDB("SELECT `dial_value` FROM `player` WHERE `player_id`='$nextPlayer'") === 'bucket') ? 1 : 2;
				$this->globals->set('COUNTER', $scale);
			}
		}

	}

	public function actDiscard(int $cardId)
	{
		// Check that the paramaters are allowed by redirecting to the correct arg function with functional programming technique
		$currentState = $this->getStateName(); 
		$argFunction = 'arg' . ucfirst($currentState);
		$args = $this->$argFunction();

		// Fail the draw if: Discard is not in the arg's possibleActions or the given cardId is wrong
		// It might seem silly to check if actDraw is in possibleActions array since we already verified it is allowed by the state. 
		// This lets us to control at what point the player can do each subaction (managing the order of operations for multistep actions like bucket)
		$message = '';
		if (!in_array('Discard', $args['possibleActions'], true)) 
		{
			$possibleActions = implode(',', $args['possibleActions']);
			$message = "Discard not in possibleActions: <$possibleActions>";
		}
		else if (!in_array((int) $cardId, $args['possibleIds'], true))
		{
			$possibleIds = implode(',', $args['possibleIds']);
			$message = "CardId given: $cardId, expected to be one of <$possibleIds>";
		}

		if ($message !== '')
			throw new \BgaSystemException("actDiscard: cardId: '$cardId' not allowed in state {$this->getStateName()}\n($message)");
		
		// Proceed now that all input has been verified: Move the indicated card to the discard pile
		$this->discard($cardId);

		// Handles specific states
		if ($this->getStateName() === 'resolveBucket')
		{
			if($this->globals->inc('COUNTER', -1) == 0)
			{
				// Done with this player's turn!
				// Update FLAG to get it ready for the next helper state
				$this->globals->set('FLAG', true);
				$this->gamestate->nextState('next');
			}
		}

	}

	public function actPatch()
	{
	}

	public function actFire()
	{
	}
	
	public function argDeclareDial()
	{
		$possibleMoves =  ['bucket', 'plunder', 'patch', 'fire'];
		// TODO Remove any which are disallowed right now (i.e. the column is empty)
		return ['possibleMoves' => $possibleMoves];
	}

	public function argResolveBucket()
	{
		// Indicates which action the player needs to do right now (True for draw, false for discard)
		$flag = $this->globals->get('FLAG'); 

		// location is for fact checking move validity
		// possibleIds is for fact checking move validity (front and backend)
		// possible actions is another layer to control what order actions occur in
		$args = ['location' => 'waterColumn'];

		// We only really need the card ids, all the other info is not necessary
		$args['possibleIds'] = $flag ? array_keys($this->water->getCardsInLocation('waterColumn')) : array_keys($this->water->getCardsInLocation('hand', $this->getActivePlayerId()));
		$args['possibleActions'] = [$flag ? 'Draw' : 'Discard'];

		// Were creating the perfect balance of simplicity and informative descriptionmyturn
		// descriptionmyturn = '${you} must ${verb} ${nbr} card(s)${ending}'
		$args['verb'] = $flag ? 'draw' : 'discard';
		$args['nbr'] = $this->globals->get('COUNTER');
		$args['ending'] = $flag ? ' from the Water Column' : '';
		
		return $args;
	}

	public function argResolvePlunder()
	{
		$args = [];
		$args['possibleActions'] = ['Draw'];
		$args['location'] = 'treasureColumn';
		$args['possibleIds'] = array_keys($this->water->getCardsInLocation('treasureColumn'));
			
		return $args;
	}

	public function argResolvePatch()
	{
		return [];
	}

	public function argResolveFire()
	{
		return [];
	}

    /**
     * Compute and return the current game progression.
     *
     * The number returned must be an integer between 0 and 100.
     *
     * This method is called each time we are in a game state with the "updateGameProgression" property set to true.
     *
     * @return int
     * @see ./states.inc.php
     */
    public function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }

    /**
     * Migrate database.
     *
     * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
     * method is called everytime the system detects a game running with your old database scheme. In this case, if you
     * change your database scheme, you just have to apply the needed changes in order to update the game database and
     * allow the game to continue to run with your new version.
     *
     * @param int $from_version
     * @return void
     */
    public function upgradeTableDb($from_version)
    {
//       if ($from_version <= 1404301345)
//       {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//       }
//
//       if ($from_version <= 1405061421)
//       {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//       }
    }

    /*
     * Gather all information about current game situation (visible by the current player).
     *
     * The method is called each time the game interface is displayed to a player, i.e.:
     *
     * - when the game starts
     * - when a player refreshes the game page (F5)
     */
    protected function getAllDatas(): array
    {
        $result = [];

        // WARNING: We must only return information visible by the current player.
        $current_player_id = (int) $this->getCurrentPlayerId();

        // Get information about players.
        // NOTE: you can retrieve some extra field you added for "player" table in `dbmodel.sql` if you need it.
        $result["players"] = $this->getCollectionFromDb(
            "SELECT `player_id` `id`, `player_score` `score` FROM `player`"
        );

        // TODO: Gather all information about current game situation (visible by player $current_player_id).
		$globals['threshold'] = $this->globals->get('THRESHOLD_LEVEL');
		$globals['enemy'] = $this->globals->get('ENEMY');
		$globals['enemyHP'] = $this->globals->get('ENEMY_HP');
		$globals['permanentBreaches'] = $this->globals->get('PERMANENT_BREACHES');
		$result['globals'] = $globals;
		
		// Cards in the waterColumn, either 'backside' or a clear water
		// It is important we only tell the client backside or face up clear water. Anything more would be revealing more info than we should,
		// telling them information hidden to the players (known to the backend of course)
		$waterColumn = array();
		$cards = $this->getCollectionFromDB("SELECT `card_id`, `card_face_up`, `card_type_arg` FROM `water` WHERE `card_location`='waterColumn' ORDER BY `card_location_arg`");
		foreach ($cards as $id => $details)
		{
			if ($details['card_face_up'] === "1")
				$waterColumn[$id] = ['id' => $id, 'type' => 'clearWater', 'type_arg' => $details['card_type_arg']];
			else
				$waterColumn[$id] = ['id' => $id, 'type' => 'backside', 'type_arg' => 0];
		}
		$result['waterColumn'] = $waterColumn;

		// We can tell the client all details of the treasureColumn, breachColumn, and cannonsColumn
		// since all cards in these locations are known. We are not revealing any info the players should not have.

		// Treasure column
		$result['treasureColumn'] = $this->water->getCardsInLocation('treasureColumn');

		// Breaches
		$result['breaches'] = $this->breaches->getCardsInLocation('breachesColumn');

		// Cannons
		$result['bustedCannons'] = $this->cannons->getCardsInLocation('breachesColumn');
		$result['operationalCannons'] = $this->cannons->getCardsInLocation('cannonsColumn');
		
		// This player's hand
		$result['hand'] = $this->water->getCardsInLocation('hand', $current_player_id);

        return $result;
    }

    /**
     * Returns the game name.
     *
     * IMPORTANT: Please do not modify.
     */
    protected function getGameName()
    {
        return "weresinking";
    }

    /**
     * This method is called only once, when a new game is launched. In this method, you must setup the game
     *  according to the game rules, so that the game is ready to be played.
     */
    protected function setupNewGame($players, $options = [])
    {
        // Set the colors of the players with HTML color code. The default below is red/green/blue/orange/brown. The
        // number of colors defined here must correspond to the maximum number of players allowed for the gams.
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        foreach ($players as $player_id => $player) {
            // Now you can access both $player_id and $player array
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
				addslashes($player["player_avatar"]),
				// My custom additions: custom_order, dial_value, and dial_location
				$player_id,
				'water',
				'player',	
            ]);
        }

        // Create players based on generic information.
        //
        // NOTE: You can add extra field on player table in the database (see dbmodel.sql) and initialize
        // additional fields directly here.
        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar, custom_order, dial_value, dial_location) VALUES %s",
                implode(",", $query_values)
            )
		);

		static::DbQuery('UPDATE `player` SET custom_order=player_no');

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

		// Init global values with their initial values.

        // Dummy content.
        $this->setGameStateInitialValue("my_first_global_variable", 0);
		
		// Select this game's Enemy:		
		$enemyNumber = $this->tableOptions->get(100);
		if ($enemyNumber === 5)
			$enemyNumber = \bga_rand(1,4);
		$enemies = [1=>'Kraken', 2=>'Shark', 3=>'Sirens', 4=>'Skullsairs'];
		$this->globals->set('ENEMY', $enemies[$enemyNumber]);

		// Initialize globals	
		// Basic universal info
		$this->globals->set('ENEMY_HP', 6);
		$this->globals->set('THRESHOLD_LEVEL', 1);
		$this->globals->set('PERMANENT_BREACHES', 0);
		$this->globals->set('FIRST_MATE', (int) array_keys($players)[0]);

		// Less obvious behind the scenes stuff	
		$this->globals->set('PREVIOUS_PLAYER', 'none');
		$this->globals->set('COUNTER', 0);
		$this->globals->set('FLAG', true);
		
		// Lingering enemy effects are currently in effect iff their value is true
		// Kraken's Angered (corresponds to resolveKrakenAttack2)
		$this->globals->set('KRAKEN_ANGERED', 0);

		$this->populateDatabase();

        // Activate first player once everything has been initialized and ready.
        //$this->activeNextPlayer();
	}

	/**
	 * Responsible for populating the Database to reflect initial game state. 
	 * Called in setupNewGame.
	 * See pages 6-7 of the rule book for details on game setup.
	 */
	protected function populateDatabase()
	{
		// Setup the water cards ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		// Build the deck, make player hands, setup the water and treasure columns
		$waterDeckCards = [];

		// Randomly choose the apropriate number of clear water cards. (0 indexed, so 0-29)
		// (randomized to preserve the fun detail of unique text on each water card, indicated by type_arg)
		$clearWaterToRemoveCt = $this->tokens['waterDeck']['clearWater']['remove'][$this->getPlayersNumber()];
		$deckContainsCard = array_fill(0, 30, true);
		while ($clearWaterToRemoveCt > 0)
		{
			$rand = \bga_rand(0,29);
			if ($deckContainsCard[$rand])
			{
				$deckContainsCard[$rand] = false;
				$clearWaterToRemoveCt--;
			}
		}
		foreach ($deckContainsCard as $cardNo => $contains)
		{
			if ($contains)
				$waterDeckCards[] = ['type' => 'clearWater', 'type_arg' => $cardNo, 'nbr' => 1];
		}

		// Add gem and item cards
		$enemy = $this->globals->get('ENEMY');
		foreach ($this->tokens['waterDeck'] as $cardType => $details)
		{
			switch($details['type'])
			{
				case 'gem':
					$waterDeckCards[] = ['type' => $cardType, 'type_arg' => 0, 'nbr' => $details['quantity']];
					break;

				case 'item':
				case 'player item':
					$waterDeckCards[] = ['type' => $cardType, 'type_arg' => 0, 'nbr' => 1];
					break;	

				case 'enemy item':
					if ($details['enemy'] === $enemy)
						$waterDeckCards[] = ['type' => $cardType, 'type_arg' => 0, 'nbr' => $details['quantity']];
			}
		}

		$this->water->createCards($waterDeckCards, 'deck');

		// STEP G: Create players' hands
		// First we set aside all the clear water cards in their own temporary deck
		// Then we give each player their special item and 3 clear water cards.
		$this->water->moveCards(array_column($this->water->getCardsOfType('clearWater'), 'id'), 'clearWaterDeck');
		$this->water->shuffle('clearWaterDeck');
		foreach($this->loadPlayersBasicInfos() as $playerId => $details)
		{
			$itemInArray = $this->water->getCardsOfType($this->tokens['player_sheets'][$details['player_color']]['item']);
			$this->water->moveCard(array_pop($itemInArray)['id'], 'hand', $playerId);
			$this->water->pickCards(3, 'clearWaterDeck', $playerId);	
		}

		// STEP H: Draw water cards for the water and treasure columns
		// Because the clear waters were set aside in their own buffer deck and player hands are done, 
		// the regular deck only has gems, basic items, unused character items, and enemy items. 
		// Exactly what we need for the treasure column!
		$this->water->shuffle('deck');
		$this->water->pickCardsForLocation(2, 'deck', 'treasureColumn');

		// STEP I, J: Assemble waterDeck and water column
		// Now we just need to add the clear waters back into the deck, shuffle, and put one in the water column.
		$this->water->moveAllCardsInLocation('clearWaterDeck', 'deck');
		$this->water->shuffle('deck');
		$this->water->pickCardForLocation('deck', 'waterColumn');
		$this->DbQuery("UPDATE `water` SET `card_face_up`='0' WHERE `card_location`='waterColumn'");

		// Now create the breaches deck ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		// STEP K: Create the breaches deck and place one minor breah in the breaches column
		$breachDeckCards = [];
		$playerCounts = ['all', $this->getPlayersNumber()];

		// For each size breach ('minor', 'major', 'massive', 'monster'),
		foreach ($this->tokens['breaches'] as $breachSize => $details)
		{
			// For each appropriate player count value that exists,
			foreach ($playerCounts as $count)
			{
				if (!array_key_exists($count, $details['player counts']))
					continue;
				// For each type_arg, create a breach card 
				foreach ($details['player counts'][$count] as $cardTypeArg)
				{
					$breachDeckCards[] = ['type' => $breachSize, 'type_arg' => $cardTypeArg, 'nbr' => 1];
				}
			}
		}
		$this->breaches->createCards($breachDeckCards, 'deck');
		// Put one minor breach into the breachs column
		$minorBreaches = $this->breaches->getCardsOfType('minor');
		$initialBreach = array_pop($minorBreaches);
		$this->breaches->moveCard($initialBreach['id'], 'breachesColumn');
		$this->breaches->shuffle('deck');

		// Assemble Cannons! ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		// STEP N, O: Setup cannons, both busted and operational
		$cannons = [];
		for ($strength = 1; $strength < 4; $strength++)
		{
			$cannons[] = ['type' => $strength, 'type_arg' => 0, 'nbr' => 3];	
		}	
		$this->cannons->createCards($cannons, 'deck');
		$singleShots = $this->cannons->getCardsOfType(1);
		$doubleShots = $this->cannons->getCardsOFType(2);
		$this->cannons->moveCard(array_pop($singleShots)['id'], 'breachesColumn');
		$this->cannons->moveCard(array_pop($doubleShots)['id'], 'breachesColumn');
		$this->addToCannonsColumn((int) array_pop($singleShots)['id']);

		// Dice!!!! ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		$sql = "INSERT INTO dice (type, value) VALUES ";
		$dice = array();

		// Create the basic attack die
		$enemy = $this->globals->get('ENEMY');
		for ($x = 0; $x < $this->tokens['enemyInfo'][$enemy]['basicDice']; $x++)
		{
			$value = \bga_rand(1, 6);
			$dice[] = "('basic', '$value')";
		}

		// Create the special attack die
		for ($x = 0; $x < 2; $x++)
		{
			$value = \bga_rand(1, 6);
			$dice[] = "('special', '$value')";
		}

		// TODO: Create the cannon die

		$sql .= implode(',', $dice);
		$this->DbQuery($sql);
	}

    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: pass).
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, otherwise it will fail with a
     * "Not logged" error message.
     *
     * @param array{ type: string, name: string } $state
     * @param int $active_player
     * @return void
     * @throws feException if the zombie mode is not supported at this game state.
     */
    protected function zombieTurn(array $state, int $active_player): void
    {
        $state_name = $state["name"];

        if ($state["type"] === "activeplayer") {
            switch ($state_name) {
                default:
                {
                    $this->gamestate->nextState("zombiePass");
                    break;
                }
            }

            return;
        }

        // Make sure player is in a non-blocking status for role turn.
        if ($state["type"] === "multipleactiveplayer") {
            $this->gamestate->setPlayerNonMultiactive($active_player, '');
            return;
        }

        throw new \feException("Zombie mode not supported at this game state: \"{$state_name}\".");
	}		

	// Developer functions! TODO Probably remove or comment these in the final build
	public function giveCurrentPlayerCards($nbr)
	{
		$this->water->pickCardsForLocation($nbr, 'deck', 'hand', $this->getActivePlayerId());
	}

	// Helper Functions! ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	public function getStateName()
	{
		return $this->gamestate->state()['name'];
	}

	public function printStateName()
	{
		$state = $this->getStateName();
		$this->debug("Current game state: $state");	
	}

	public function getNextPlayer(): int
	{
		$playerInfo = $this->getCollectionFromDB('SELECT `player_id`, `custom_order`, `dial_location` FROM `player` ORDER BY `custom_order`');
		$previousPlayer = $this->globals->get('PREVIOUS_PLAYER');
		$nextPlayer = '';
		
		// If previousPlayer is none, then the next player is the first player
		if ($previousPlayer === 'none')
			$nextPlayer = array_key_first($playerInfo);

		// If previousPlayer is the last person in turn order, then return -1 as a flag
		else if ($previousPlayer === array_key_last($playerInfo))
			$nextPlayer = -1;

		// Nontrivial general case: who is next??
		else
		{
			$targetOrder = (int) $playerInfo[$previousPlayer]['custom_order'];
			$nextPlayer = array_keys($playerInfo)[$targetOrder];
		}
		
		// Special case: previous player plundered (Does it need to wrap around for more plundering?)
		if ($previousPlayer !== 'none' && $playerInfo[$previousPlayer]['dial_location'] === 'plunder')
		{
			// If the next player is plundering, then it couldn't possibly wrap
			$nobodyPlundersNext = $nextPlayer <= 0 || $playerInfo[$nextPlayer]['dial_location'] !== 'plunder';

			// Dial values of each player in order (e.g. ['bucket', 'plunder', 'plunder', 'patch', 'fire'])
			$dialValues = array_column(array_values($playerInfo), 'dial_location'); 
			$plunderingPlayersNbr = array_count_values($dialValues)['plunder'];
			$theresStillEnoughTreasure = $this->water->countCardsInLocation('treasureColumn') >= $plunderingPlayersNbr;
				
			if ($nobodyPlundersNext && $theresStillEnoughTreasure)
			{
				$nextPlayer = array_keys($playerInfo)[array_search('plunder', $dialValues)];
			}
		}

		return (int) $nextPlayer;
	}
	
	public function pickCardsForWaterColumn(int $number)
	{
		$cardIds = [];
		while ($number > 0)
		{
			$cardId = $this->water->getCardOnTop('deck')['id'];
			$this->water->insertCardOnExtremePosition($cardId, 'waterColumn', COLUMN_BOTTOM);
			$cardIds[] = $cardId;
			$number--;
		}
		
		$implodedCardIds = implode(',', $cardIds);	
		$this->DbQuery("UPDATE `water` SET `card_face_up`=FALSE WHERE `card_id` IN ($implodedCardIds)");
	}

	public function addToTreasureColumn(int $cardId)
	{
		$this->water->insertCardOnExtremePosition($cardId, 'treasureColumn', COLUMN_BOTTOM);
		if ($this->water->countCardInLocation('treasureColumn') > 5)
			$this->discard((int) $this->water->getCardOnTop('waterColumn')['id']);
	}

	public function discard(int $cardId): void
	{
		$this->water->playCard($cardId);
		if ($this->globals->get('KRAKEN_ANGERED'))
		{
			$this->debug('KrakenAngered here...');
		}
	}

	public function addToCannonsColumn(int $cardId)
	{
		$this->cannons->insertCardOnExtremePosition($cardId, 'cannonsColumn', COLUMN_BOTTOM);
	}

	public function removeFromCannonsColumn(): int
	{
		$topCannon = (int) $this->cannons->getCardOnTop('cannonsColumn')['id'];
		$this->debug("Top cannon id: $topCannon;");
		$this->cannons->moveCard($topCannon, 'breachesColumn');
		return $topCannon;
	}
	
	// Enemies! ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	// Basic Enemy Dice:
	public function resolveBasicWater(): void
	{
		$this->debug("\nResolving basic water...\n");
		$this->pickCardsForWaterColumn(1);
	}

	public function resolveBasicBreach(): void
	{
		$this->debug("\nResolving basic breach...\n");
		$this->breaches->pickCardForLocation('deck', 'breachesColumn');
	}

	public function resolveBasicCannon(): void
	{
		$this->debug("\nResolving basic cannon...\n");
		$this->removeFromCannonsColumn();
	}

	// Special Enemy Dice: ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	// The Kraken	
	// Splash: Place the top card in the Treasure Column face-down into the Water Column.
	public function resolveKrakenAttack1(): void
	{
		$this->debug("\nResolving Kraken's special attack #1!\n");
		$treasureCard = $this->water->getCardOnTop('treasureColumn')['id'];
		$this->water->insertCardOnExtremePosition($treasureCard, 'waterColumn', COLUMN_BOTTOM);
		$this->DbQuery("UPDATE `water` SET `card_face_up`=FALSE WHERE `card_id`=$treasureCard");
	}
	
	// Angered: When a card is added to the discard pile this round, immediately roll and resolve 1 Basic Attack Die. (Place this die on the discard pile as a reminder.)
	public function resolveKrakenAttack2(): void
	{
		$this->debug("\nResolving Kraken's special attack #2!\n");
		$this->globals->inc('KRAKEN_ANGERED', 1);
	}

	public function theKrakenReactsToDamage(): void { return; }
	
	// The Shark!
	public function resolveSharkAttack1(): void {}
	public function resolveSharkAttack2(): void {}
	public function theSharkReactsToDamage(): void { return; }

	// The Sirens!
	public function resolveSirensAttack1(): void {}
	public function resolveSirensAttack2(): void {}
	public function theSirensReactsToDamage(): void { return; }

	// The Skullsairs!	
	public function resolveSkullsairsAttack1(): void {}
	public function resolveSkullsairsAttack2(): void {}
	public function theSkullsairsReactsToDamage(): void { return; }
}
