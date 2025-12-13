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
			$water += $this->tokens['breaches'][$details['type']]['scale'];

		// Notify client to deal water cards to the Water Column
		$this->notify->all('checkForBreachesMessage', clienttranslate('Step 1). Check For Breaches'));
		$this->notify->all('checkForBreaches', clienttranslate('${waterNbr} card(s) to the Water Column'), array(
			'waterNbr' => $water, // Needed for the notification's message
			'cards' => array_map(fn($id): array => ['id' => $id, 'type' => 'backside', 'type_arg' => 0], $this->pickCardsForWaterColumn($water)),
		));
		$this->gamestate->nextState();
	}

	public function stCheckWaterThreshold()
	{
		$waterCardNbr = $this->water->countCardInLocation('waterColumn');	

		// Check the water threshold. If equal to or greater, then carry out sinking procedures.
		$numPlayers = $this->getPlayersNumber();
		$thresholdLevel = $this->globals->get('THRESHOLD_LEVEL');
		$waterThreshold = (int) $this->tokens['thresholdSheets']["$numPlayers players"]["level $thresholdLevel"]['threshold'];

		$this->notify->all('checkWaterThresholdMessage', clienttranslate('Step 2). Check Water Threshold:'));
		$this->notify->all('checkWaterThreshold', clienttranslate('Cards in Water Column: ${waterCardNbr}, Water Threshold: ${waterThreshold}'), array(
			'waterCardNbr' => $waterCardNbr,
			'waterThreshold' => $waterThreshold,	
		));
		if ($waterCardNbr >= $waterThreshold)
		{
			$this->notify->all('sinkingProcedures', clienttranslate('The number of cards is equal to or greater than the Water Threshold. Continue on to sinking procedures.'), array(
				'playerNbr' => $this->getPlayersNumber(),
				'thresholdLevel' => $this->globals->get('THRESHOLD_LEVEL'),
				'deckNbr' => $this->water->countCardInLocation('deck') + $this->water->countCardInLocation('discard') + $this->water->countCardInLocation('waterColumn') + $this->water->countCardInLocation('treasureColumn'),
			));

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
					$this->breaches->insertCardOnExtremePosition($card['id'], 'deck', false);
				$this->globals->inc('PERMANENT_BREACHES', 1);
			}

			// STEP 6: Flip over the First Mate scroll and continue the round on Step 3 of the Duties Checklist.
		}
		else 
			$this->notify->all('checkWaterThreshold', clienttranslate('The number of cards is less than the Water Threshold. Continue on to the Threat Phase'));

		$this->gamestate->nextState();
	}	
	
	public function stDealWaterAndTreasure()
	{
		$this->notify->all('dealWaterAndTreasureMessage', clienttranslate('Step 3.) Deal Water and Treasure'));
		
		// Making a list of all the cards added for the client to do proper animations
		$cards = [];
		$thresholdLevel = (int) $this->globals->get('THRESHOLD_LEVEL');
		$thresholdPanelInfo = $this->tokens['thresholdSheets']["{$this->getPlayersNumber()} players"]["level $thresholdLevel"];
		
		// Pick the correct number of cards for the water column according to the threshold panel
		$waterIds = $this->pickCardsForWaterColumn((int) $thresholdPanelInfo['water']);
		foreach ($waterIds as $id)
			$cards[] = ['id' => $id, 'type' => 'backside', 'type_arg' => 0];

		// Draw the correct number of cards for the treasure column. If you find a clear water, put it in the water column and keep drawing.
		// (since the default value of card_face_up is true, the waters we find will have the proper card_face_up value by default)
		$remainingTreasures = (int) $thresholdPanelInfo['treasure'];
		$clearWaterNbr = 0;
		$faceupCardIds = [];
		while ($remainingTreasures > 0)
		{
			// Whether its a clear water or a treasure, this will work either way.
			$card = $this->water->getCardOnTop('deck');
			$cards[] = ['id' => (int) $card['id'], 'type' => $card['type'], 'type_arg' => $card['type_arg']];
			$faceupCardIds[] = $card['id'];

			if ($card['type'] === 'clearWater')
			{
				$this->addToColumn('waterColumn');
				$clearWaterNbr++;
			}
			else
			{
				$this->addToColumn('treasureColumn');
				$remainingTreasures--;
			}
		}
		$this->setCardsOrientation($faceupCardIds, true);
		$this->notify->all('dealWaterAndTreasure', clienttranslate('Dealt ${waterNbr} cards to Water Column. Dealt ${treasureNbr} Treasures. Dealt ${clearWaterNbr} additional Clear Waters.'), array(
			'waterNbr' => $thresholdPanelInfo['water'],
			'treasureNbr' => $thresholdPanelInfo['treasure'],
			'clearWaterNbr' => $clearWaterNbr,
			'cards' => $cards,
		));	
		$this->gamestate->nextState();
	}

	public function stRollEnemyDice()
	{
		$this->notify->all('rollEnemyDiceMessage', clienttranslate('Step 4). Roll and Resolve Enemy Dice'));

		// Get the ids of all the attack die (both basic and special attack dice)
		// Generate the correct number of random values 
		// $$\forall x \in $rolls, x \in [1,6] $$
		$diceIds = $this->getCollectionFromDB("SELECT `die_id`, `type` FROM `dice` WHERE `type` IN ('basic', 'special')");
		$rolls = array();
		for ($x = 0; $x < count($diceIds); $x++)
		{
			$rolls[] = \bga_rand(1,6);	
		}

		// Update the dice values in the database with the new values
		$diceRollMapping = array();
		$updateString = '';
		foreach (array_keys($diceIds) as $id)
		{
			$roll = array_pop($rolls);
			$diceRollMapping[$id] = $roll;
			$updateString .= "WHEN $id THEN $roll ";
		}
		$spliced = implode(',', array_keys($diceIds));
		$this->DbQuery("UPDATE `dice` SET `value` = CASE `die_id` $updateString END WHERE `die_id` in ($spliced)");

		// So far we have only worked with the DB die values (1-6)
		// We now need to interpret those values in the context of the die types so that it means something in the notification
		$enemy = $this->globals->get('ENEMY');
		$attacks = array();
		foreach (array_keys($diceIds) as $id)
		{
			$attack = $this->tokens['diceMappings'][$diceIds[$id]['type']][$diceRollMapping[$id]];
			if ($attack == null)
				$attack = clienttranslate('Blank');
			else if (strlen($attack) == 1)
				$attack = $this->tokens['enemySheets'][$enemy]["specialAttack$attack"]['name'];	

			$attacks[] = $attack;
		}
		$this->notify->all('rollEnemyDice', clienttranslate('Rolled ${rollResult}'), array(
			'rollResult' => implode(', ', $attacks),
			'diceRollMapping' => $diceRollMapping,
		));
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
		// Location: where the dial was placed initially
		// Value: the dial's proper home for that round

		// Correct the turn order to align with the current turn order and dial locations
		// Determine proper order
		$playerInfo = $this->getCollectionFromDB('SELECT `player_id`, `dial_location`, `dial_value` FROM `player` ORDER BY `custom_order`');
		$sorted = ['bucket' => [], 'plunder' => [], 'patch' => [], 'fire' =>[]];	
		foreach ($playerInfo as $playerId => $details)
			$sorted[$details['dial_value']][] = $playerId;
		$sorted = array_merge($sorted['bucket'], $sorted['plunder'], $sorted['patch'], $sorted['fire']);

		// Update the database to reflect new order
		$updateString = '';
		foreach ($sorted as $order => $playerId)
			$updateString .= "WHEN $playerId THEN " . $order+1 . ' ';
		$this->DbQuery("UPDATE `player` SET `custom_order` = CASE `player_id` $updateString END");
		$this->globals->set('PREVIOUS_PLAYER', 'none');
	
		// Notify the front end
		$this->notify->all('revealDials', '', array(
			'new_turn_order' => $sorted,
			'dials' => $playerInfo,
		));

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
				$this->globals->set('PREVIOUS_PLAYER', $nextPlayer);

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
					$treasureNbr = $this->water->countCardInLocation('treasureColumn');
					$moveOnToNextAction = false;

					// 1. Only 1 plunderer! Give them all that precious treasure ARGH!!	
					//    (no need for player states, just move on to the next helper state
					if ($plunderingPlayersNbr == 1)
					{
						// We need the cards in question (in card format for the notifications)
						$cardsDrawn = $this->water->getCardsInLocation('treasureColumn');

						// Move the cards to the player's hand
						$this->water->moveAllCardsInLocation('treasureColumn', 'hand', null, $nextPlayer);

						// Notify the frontend of the cards drawn
						$this->notifyForCardsDrawn($nextPlayer, $cardsDrawn);
					
						// Update the database so that those cards are facedown (minor but keeps the obfuscation right)
						// Must occur AFTER the notification: since they were faceup, the cards drawn are not obfuscated from other players
						$this->setCardsOrientation(array_column($cardsDrawn, 'id'), false);

						$moveOnToNextAction = true;
					}
					// 2. Several plunderers with enough to go around
					else if ($treasureNbr >= $plunderingPlayersNbr)
					{
						// Setting FLAG to false indicates treasure is getting divided (Makes future stResolvePlunderHelper visits much simpler)
						$this->globals->set('FLAG', false);	
						$this->globals->set('PREVIOUS_PLAYER', $nextPlayer);
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
		$nextPlayer = $this->getNextPlayer();
		$nextAction = 'upkeep';
		//var_dump('nextPlayer', $nextPlayer);
		
		if ($nextPlayer > 0)
		{
			$nextAction = $this->getUniqueValueFromDB("SELECT `dial_value` FROM `player` WHERE `player_id`='$nextPlayer'");
			if ($nextAction === 'patch')
			{
				// Update active player
				$this->gamestate->changeActivePlayer($nextPlayer);
				$this->globals->set('PREVIOUS_PLAYER', $nextPlayer); 

				// Set FLAG to true (indicates that the player needs to draw now)
				$this->globals->set('FLAG', true);
			}
		}	

		// Commence state change
		// If the next player is doing a Patch action, the prepwork is done and this moves to STATE_RESOLVE_PATCH
		// If the next player is doing a different action, redirects to the appropriate game state STATE_RESOLVE_X_HELPER
		// If there is not another player, goes to STATE_UPKEEP
		$this->gamestate->nextState($nextAction);
	}

	public function stResolveFireHelper()
	{
		$nextPlayer = $this->getNextPlayer();
		$nextAction = 'upkeep';
		
		if ($nextPlayer > 0)
		{
			$nextAction = $this->getUniqueValueFromDB("SELECT `dial_value` FROM `player` WHERE `player_id`='$nextPlayer'");
			if ($nextAction === 'fire')
			{
				// Update active player
				$this->gamestate->changeActivePlayer($nextPlayer);
				$this->globals->set('PREVIOUS_PLAYER', $nextPlayer); 

				// Set FLAG to true (indicates that the player needs to draw now)
				$this->globals->set('FLAG', true);
			}
		}	

		// Commence state change
		// If the next player is doing a Fire action, the prepwork is done and this moves to STATE_RESOLVE_FIRE
		// If there is not another player, goes to STATE_UPKEEP
		$this->gamestate->nextState($nextAction);
	}

	public function stUpkeep()
	{
		// Check hand size
		$playerCardNbr = $this->water->countCardsByLocationArgs('hand');
		foreach ($playerCardNbr as $playerId => $nbr)
		{
			// If you are below 2 cards, you must draw cards from the Water Deck until you have 2 cards
			if ($nbr < 2)
				$this->notifyForCardsDrawn($playerId, $this->water->pickCards(2 - $nbr, 'deck', $playerId));

			// If you have over 10 cards, you must randomly discard cards until you have 10 cards
			else if ($nbr > 10)
			{
				$discards = [];
				while ($nbr-- > 10)
					$discards[] = $this->discard($this->getRandomCardFrom($playerId));
				$this->notifyForCardsDiscarded($playerId, $discards);
			}
		}

		// Pass First Mate to the left
		$playerInfo = $this->getCollectionFromDB("SELECT `player_id`, `player_no` FROM player ORDER BY `player_no`", true);
		$oldFirstMate = $this->globals->get('FIRST_MATE');
		$newFirstMateIndex = $playerInfo[$oldFirstMate] % count($playerInfo); // Going from 1 indexed to 0 indexed, so we don't need the + 1 you'd expect here
		$newFirstMate = array_keys($playerInfo)[$newFirstMateIndex];
		$this->globals->set('FIRST_MATE', $newFirstMate);

		// Update the custom_order field to reflect the new First mate
		$updateString = '';
		$newFirstMateIndex++; // Adjust for player_no and custom_order being 1 indexed instead of 0 indexed
		for ($i = 1; $i <= count($playerInfo); $i++)
			$updateString .= "WHEN $i THEN " . ($i - $newFirstMateIndex + 60) % count($playerInfo) + 1 . ' '; 
		//  The + 60 is to avoid the negative domain of %, LCM({3,4,5,6}) = 60. Really it could have been any multiple of current player count
		$this->DbQuery("UPDATE `player` SET `custom_order` = CASE `player_no` $updateString END");

		// Reset globals for a new round
		$this->DbQuery("UPDATE `player` SET `dial_location` = 'player'");
		$this->globals->set('PREVIOUS_PLAYER', 'none');
		$this->globals->set('FLAG', true);
		$this->globals->set('COUNTER', 0);

		// Enemy items	
		$this->globals->set('KRAKEN_ANGERED', 0);
		$this->globals->set('SHARK_CHOMP_CHOMP', 0);
		$this->globals->set('SHARK_SUBMERGED', 0);
		$this->globals->set('SIRENS_TEMPTING_TUNE', 0);
		$this->globals->set('SIRENS_SCREECH', 0);

		$this->gamestate->nextState();
	}
	
	public function actDeclareDial(string $value, string $location)
	{
		$this->debug("actDeclareDial: value: $value, location $location");

		$this->checkAction('actDeclareDial');
		$currentActions = $this->argDeclareDial()['possibleActions'];
		if (!in_array($value, $currentActions, true) || !in_array($location, $currentActions, true))
			throw new \BgaSystemException("actDeclareDial: value: '$value', location: '$location' not allowed");

		$activePlayer = $this->getActivePlayerId();
		$this->DbQuery("UPDATE `player` SET `dial_value`='$value', `dial_location`='$location' WHERE `player_id`=$activePlayer");

		$actionToColumn = [
			'bucket' => 'Water Column',
			'plunder' => 'Treasure Column',
			'patch' => 'Breaches Column',
			'fire' => 'Cannons Column',
		];	
		$this->notify->all('actDeclareDial', clienttranslate('${player_name} placed their dial in the ${dial_location}'),
			array(
				'player_id' => $activePlayer,
				'player_name' => $this->getPlayerNameById($activePlayer),
				'dial_location' => $actionToColumn[$location],
			));

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
		// It might seem odd to have different arg functions, but this simple draw is an allowed action in 3 of the 4 different dial actions (bucket, plunder, patch)
		// 	and they all work the same at their core but have access to different cards and have little details to control flow
		$playerId = $this->getActivePlayerId();
		$currentState = $this->getStateName();
		$argFunction = 'arg' . ucfirst($currentState);
		$args = $this->$argFunction();

		if ($location === 'deck')
			$cardId = $args['possibleIdsDraw'][0];

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
		else if (!in_array((int) $cardId, $args['possibleIdsDraw'], true))
		{
			$possibleIds = implode(',', $args['possibleIdsDraw']);
			$message = "CardId given: $cardId, expected to be one of <$possibleIds>";
		}
		
		if ($message !== '')
			throw new \BgaSystemException("actDraw: cardId: '$cardId', location: '$location' not allowed in state {$this->getStateName()}\n($message)");

		// Proceed now that all input has been verified: Move the indicated card to the active player's hand, notify frontend, and mark the card as facedown
		$card = $this->water->getCard($cardId);
		$this->notifyForCardsDrawn(intval($playerId), array($card));
		$this->setCardOrientation(intval($cardId), false);

		// Deck has to be a separate case because $cardId is a dummy value if the location is the deck!
		if ($location === 'deck')
			$this->water->pickCard('deck', $this->getActivePlayerId());
		else
			$this->water->moveCard($cardId, 'hand', $this->getActivePlayerId());


		// If COUNTER decremented is 0, then move on to whatever comes next
		$again = false;
		if ($this->globals->inc('COUNTER', -1) <= 0)
		{
			if ($currentState === 'resolveBucket')
			{
				// Determine how many cards need to be discarded (lastPersonToBucket ? 2 : 1)
				$nextPlayer = $this->getNextPlayer();
				$scale = ($nextPlayer > 0 && $this->getUniqueValueFromDB("SELECT `dial_value` FROM `player` WHERE `player_id`='$nextPlayer'") === 'bucket') ? 1 : 2;
				$this->globals->set('COUNTER', $scale);

				// Indicates that the first part of the multi-part action is done
				$this->globals->set('FLAG', false);
				$again = true;
			}
			else if ($currentState === 'resolvePatch')
			{
				// TODO Temp fix. THIS CLAUSE WILL NEED DELETED WHEN PATCH IS FULLY IMPLEMENTED.
				// Moves on to the next player after the draw. Will have to remove it and implement the patching action later
				$this->globals->set('FLAG', true);
			}
		}
		else
			// The player still needs to draw COUNTER > 0 more cards! Draw another!
			$again = true;

		// If $again == true, then the player is not done completing this action yet. They may need to draw another card, discard card(s), or fix something!
		// Otherwise, safely move on to the next action
		($again) ? $this->gamestate->nextState('again') : $this->gamestate->nextState('next');

	}

	public function actDiscard(int $cardId)
	{
		// Check that the paramaters are allowed by redirecting to the correct arg function with functional programming technique
		$currentState = $this->getStateName(); 
		$argFunction = 'arg' . ucfirst($currentState);
		$args = $this->$argFunction();

		// Fail the draw if: Discard is not in the arg's possibleActions or the given cardId is wrong
		$message = '';
		if (!in_array('Discard', $args['possibleActions'], true)) 
		{
			$possibleActions = implode(',', $args['possibleActions']);
			$message = "Discard not in possibleActions: <$possibleActions>";
		}
		else if (!in_array((int) $cardId, $args['possibleIdsDiscard'], true))
		{
			$possibleIds = implode(',', $args['possibleIdsDiscard']);
			$message = "CardId given: $cardId, expected to be one of <$possibleIds>";
		}

		if ($message !== '')
			throw new \BgaSystemException("actDiscard: cardId: '$cardId' not allowed in state {$this->getStateName()}\n($message)");
		
		// Proceed now that all input has been verified: Move the indicated card to the discard pile
		$playerId = $this->getActivePlayerId();
		$card = $this->water->getCard($cardId);
		$this->notifyForCardsDiscarded(intval($playerId), array($card));
		$this->discard($cardId);
		
		// Handles specific states
		$again = false;
		if ($this->getStateName() === 'resolveBucket')
		{
			if($this->globals->inc('COUNTER', -1) <= 0)
			{
				// Done with this player's turn!
				// Update FLAG to get it ready for the next helper state
				// TODO when do i really need to reset the FLAG for a new state? Can this be done in the game state that handles player changes?
				$this->globals->set('FLAG', true);
			}
			else
				$again = true;
		}
		else if ($this->getStateName() === 'resolvePatch')
		{
			// TODO These flags are temporarily backwards to skip the fixing part of patch actions 
			//$this->globals->set('FLAG', false);
			//$again = true; 
			$this->globals->set('FLAG', true);
		}
		$again ? $this->gamestate->nextState('again') : $this->gamestate->nextState('next');
	}

	public function actPatch()
	{
	}

	public function actFire()
	{
		$args = $this->argResolveFire();

		$message = '';
		if (!in_array('Fire', $args['possibleActions']))
			$message = 'Fire is not in the possible actions';
		else if (count($args['operableCannons']) == 0)
			$message = 'There are no operable cannons';

		if ($message !== '')
			throw new \BgaSystemException("Fire not allowed in state {$this->getStateName()}\n($message)");

		$this->fireCannons($args['operableCannons']);
		$this->globals->set('FLAG', false);		
		$this->gamestate->nextState('again');
	}

	public function actShootYeTreasure(int $cardId, int $cannonId)
	{
		$args = $this->argResolveFire();

		// Fail if: ShootYeTreasure is not in the arg's possibleActions, the given cardId is invalid, or the given cannonId is invalid
		// No need to check if the given card is a treasure because 'possibleIdsDiscard' from args will only be viable treasure cards
		$message = '';
		if (!in_array('ShootYeTreasure', $args['possibleActions'], true)) 
		{
			$possibleActions = implode(',', $args['possibleActions']);
			$message = "ShootYeTreasure not in possibleActions: <$possibleActions>";
		}
		else if (!in_array($cardId . '', array_column($args['possibleDiscard'], 'id'), true))
		{
			$possibleIds = implode(',', array_column($args['possibleDiscard'], 'id'));
			$message = "CardId given: $cardId, expected to be one of <$possibleIds>";
		}
		else if (!in_array($cannonId, $args['operableCannons']))
			$message = "You must choose a cannon in the Cannons Column";
		else if (in_array($cannonId, $this->globals->get('LIST')))
			$message = "You have already activated this cannon. Please choose one you have not activated yet";

		if ($message !== '')
			throw new \BgaSystemException("ShootYeTreasure: cardId: '$cardId' not allowed in state {$this->getStateName()}\n($message)");

		// Actually execute the action now that we have verified the input
		// If it was a miss, mark that the cannon was activated (if successful, it was marked as activated by fireCannons)
		$this->notifyForCardsDiscarded(intval($this->getActivePlayerId()), array($this->water->getCard($cardId)));
		$this->discard($cardId);
		if (!$this->fireCannons(array($cannonId)))
			$this->addToLIST($cannonId);

		$this->gamestate->nextState('again');
	}

	public function actPass()
	{
		$this->globals->set('FLAG', true);
		$this->globals->set('COUNTER', 0);
		$this->globals->set('LIST', []);
		$this->gamestate->nextState('next');
	}
	
	public function argDeclareDial()
	{
		$possibleActions =  ['bucket', 'plunder'];
	
		// TODO incorporate chests here
		if ($this->breaches->countCardInLocation('breachesColumn') > 0 || $this->cannons->countCardInLocation('breachesColumn') > 0)
			array_push($possibleActions, 'patch');
		
		if ($this->cannons->countCardInLocation('cannonsColumn') > 0)
			array_push($possibleActions, 'fire');

		return ['possibleActions' => $possibleActions];
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
		if ($flag)
			$args['possibleIdsDraw'] = array_keys($this->water->getCardsInLocation('waterColumn'));
		else
			$args['possibleIdsDiscard'] = array_keys($this->water->getPlayerHand($this->getActivePlayerId()));

		$args['possibleActions'] = [$flag ? 'Draw' : 'Discard'];

		// We're creating the perfect balance of simplicity and informative descriptionmyturn
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
		$args['possibleIdsDraw'] = array_keys($this->water->getCardsInLocation('treasureColumn'));
			
		return $args;
	}

	public function argResolvePatch()
	{
		// Indicates which action the player needs to do right now (True for draw/discard 1, false for patch)
		$flag = $this->globals->get('FLAG'); 

		$args = [];
		$args['possibleActions'] = $flag ? ['Draw', 'Discard'] : ['Patch'];
		$args['location'] = $flag ? 'deck' : 'breachesColumn';
		
		if ($flag)
		{
			$topCard = $this->water->getCardOnTop('deck');
			$args['possibleIdsDraw'] = [intval($topCard['id'])];
			$args['possibleIdsDiscard'] = array_keys($this->water->getPlayerHand($this->getActivePlayerId()));
		}
		else
			$args['possibleIdsPatch'] = [];

		$args['actiondescription'] = $flag ? clienttranslate('draw a card from the Water Deck or Discard a card from your hand') : clienttranslate('use your hammer to Patch');
		return $args;
	}

	public function argResolveFire()
	{
		$args = [];
		$flag = $this->globals->get('FLAG');
		
		$operableCannons = $this->getNonEmptyCollectionFromDB("SELECT dice.die_id id, cannon.card_type, cannon.card_type_arg FROM `dice` INNER JOIN `cannon` ON dice.die_id = cannon.card_id WHERE cannon.card_location = 'cannonsColumn'");
		$args['operableCannons'] = array_keys($operableCannons);

		if ($flag)
		{
			$args['possibleActions'] = ['Fire'];
			$args['instruction'] = clienttranslate('must fire');
		}
		else
		{
			$activePlayer = $this->getActivePlayerId();
			$treasure = array_keys($this->getCollectionFromDB("SELECT `card_id` FROM `water` WHERE `card_location`='hand' AND `card_location_arg`='$activePlayer' AND `card_type` != 'clearWater'"));
			$alreadyActivated = (array) $this->globals->get('LIST');
			
			// nbr indicates how many more times the player can shoot their treasure
			// This is either the number of treasure cards they have or the number of ive cannons they haven't re-rolled yet, whichever is less
			$nbr = min(count($treasure), count($args['operableCannons']) - count($alreadyActivated));
			$notYetActivated = [];
			if ($nbr > 0)
			{
				foreach (array_diff(array_keys($operableCannons), $alreadyActivated) as $id)
					$notYetActivated[] = $operableCannons[$id];
			}
			
			$args['possibleActions'] = $nbr > 0 ? ['ShootYeTreasure', 'Pass'] : ['Pass'];
			$args['possibleDiscard'] = array_values($this->water->getCards($treasure));
			$args['possibleToFireCannons'] = $notYetActivated;
			$args['instruction'] = $nbr > 0 ? clienttranslate("may shoot ye treasure up to $nbr times") : clienttranslate('must pass');
		}

		return $args;
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
		$currentPlayerId = (int) $this->getCurrentPlayerId();
		$result['currentPlayer'] = $currentPlayerId;

        // Get information about players.
        // NOTE: you can retrieve some extra field you added for "player" table in `dbmodel.sql` if you need it.
		$result['players'] = $this->getCollectionFromDb('SELECT `player_id` `id`, `player_score` `score`, `player_color` `color` FROM `player`');

		// Hand size and chest size for player panels...
		foreach (array_keys($result['players']) as $id)
		{
			$result['players'][$id]['handSize'] = $this->water->countCardInLocation('hand', $id);
			$result['players'][$id]['chestSize'] = 0;
		}

		// Dials!
		// Tell the client what dials to place where, and what value they should display
		// This is a bit trickier than it sounds. We have to be careful not to tell the client more than the player should know at the current moment. This means hiding the value of the dial of other players if it is too early (or if the sirens' screech attack is active)
		$dialsInfo = $this->getCollectionFromDb('SELECT `player_id` `id`, `dial_location`, `dial_value` FROM `player`');
		$dials = [];
		foreach ($dialsInfo as $id => $details)
		{
			$stateId = $this->gamestate->getCurrentMainStateId();
			
			// Handle current player correctly: Always show the value, except only show backside after they declared and before it is revealed
			if ($id == $currentPlayerId)
			{
				$dial = ['id' => $details['id'], 'dial_location' => $details['dial_location'], 'dial_value' => $details['dial_value']];

				// Show the backside after the current player has declared but we haven't revealed dials yet (it is still upside down on the table)
				$haveAlreadyDeclared = $dial['dial_location'] !== 'player';
				if (($stateId == STATE_DECLARE_DIAL || $stateId == STATE_DECLARE_DIAL_HELPER) && $haveAlreadyDeclared)
					$dial['dial_value'] = 'backside';

				$dials[] = $dial;
			}
			// If we have not revealed dials yet, hide the value of the other players' dials
			else if ($stateId < STATE_REVEAL_DIAL)
				$dials[] = ['id' => $details['id'], 'dial_location' => $details['dial_location'], 'dial_value' => 'backside'];	
			// Else reveal all info (we are either currently in or past STATE_REVEAL_DIAL, so dial values and locations are all known at this point
			else 
				$dials[] = ['id' => $details['id'], 'dial_location' => $details['dial_location'], 'dial_value' => $details['dial_value']];
		}
		$result['dials'] = $dials;
		
        // Gather all information about current game situation (visible by player $currentPlayerId).
		$globals['threshold'] = $this->globals->get('THRESHOLD_LEVEL');
		$globals['enemy'] = $this->globals->get('ENEMY');
		$globals['enemyHP'] = $this->globals->get('ENEMY_HP');
		$globals['permanentBreaches'] = $this->globals->get('PERMANENT_BREACHES');
		$globals['firstMate'] = $this->globals->get('FIRST_MATE');
		$result['globals'] = $globals;

		// Cards in the discard deck
		$discardDeck = array();
		foreach ($this->water->getCardsInLocation('discard') as $id => $details)
			$discardDeck[] = ['id' => $id, 'type' => 'backside', 'type_arg' => 0];
		$result['discardDeck'] = $discardDeck;

		// NOTE: I had to reverse the cards sent to columns to get the proper order! Keep an eye on this...
		// Cards in the waterColumn, either 'backside' or a clear water
		// It is important we only tell the client backside or face up clear water. Anything more would be revealing more info than we should,
		// telling them information hidden to the players (known to the backend of course)
		// TODO Actually we might be telling them more than we want by showing the card_type_arg... Do we need to obfuscate this?
		$waterColumn = array();
		$cards = $this->getCollectionFromDB("SELECT `card_id`, `card_face_up`, `card_type_arg`, `card_location_arg` FROM `water` WHERE `card_location`='waterColumn' ORDER BY `card_location_arg`");
		foreach ($cards as $id => $details)
		{
			if ($details['card_face_up'] === "1")
				$waterColumn[$id] = ['id' => $id, 'type' => 'clearWater', 'type_arg' => $details['card_type_arg']];
			else
				$waterColumn[$id] = ['id' => $id, 'type' => 'backside', 'type_arg' => 0];
		}
		$result['waterColumn'] = array_reverse(array_values($waterColumn));

		// We can tell the client all details of the treasureColumn, breachColumn, and cannonsColumn
		// since all cards in these locations are known. We are not revealing any info the players should not have.

		// Treasure column
		$result['treasureColumn'] = array_reverse($this->water->getCardsInLocation('treasureColumn', null, 'location_arg'));

		// Breaches
		$result['breaches'] = array_reverse($this->breaches->getCardsInLocation('breachesColumn', null, 'location_arg'));

		// Cannons
		$cannons = [
			'busted' => $this->cannons->getCardsInLocation('breachesColumn', null, 'location_arg'),
			'operational' => $this->cannons->getCardsInLocation('cannonsColumn', null, 'location_arg'),
		];
		$result['bustedCannons'] = array_reverse($cannons['busted']);
		$result['operationalCannons'] = array_reverse($cannons['operational']);
		
		// This player's hand
		$result['hand'] = $this->water->getPlayerHand($currentPlayerId);

		// Deck info
		$result['deckCount'] = [
			'water' => $this->water->countCardInLocation('deck'),
			'breaches' => $this->breaches->countCardInLocation('deck'),
			'cannons' => $this->cannons->countCardInLocation('deck'),
		];

		// Cannon dice	
		$diceInfo = $this->getCollectionFromDB("SELECT `die_id`, `type`, `value` FROM `dice`");
		foreach (['busted', 'operational'] as $cannonType)
		{
			$dice = [];
			foreach ($cannons[$cannonType] as $card)
			{
				$dice[] = ['id' => $card['id'], 'color' => 'Cannon' . $diceInfo[$card['id']]['type'], 'face' => $diceInfo[$card['id']]['value']];
			}
			$result[$cannonType . 'Dice'] = $dice;
		}

		// Enemy dice
		$dice = [];
		foreach ($diceInfo as $id => $details)
		{
			switch ($details['type'])
			{
				case 'special': 
					$dice[] = ['id' => $id, 'color' => $result['globals']['enemy'], 'face' => $details['value']];
					break;

				case 'basic':
					$dice[] = ['id' => $id, 'color' => 'Basic', 'face' => $details['value']];
					break;
			}
		}
		$result['attackDice'] = $dice;

		// Developer Help: (may want to delete or comment for final version)
		$result['constants'] = get_defined_constants(true)['user'];

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

		foreach ($players as $player_id => $player) 
		{
            // Now you can access both $player_id and $player array
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
				addslashes($player["player_avatar"]),
				// My custom additions: custom_order, dial_value, and dial_location
				$player_id,
				'bucket',
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
		$this->globals->set('LIST', []); 
		
		// Lingering enemy effects are currently in effect iff their value is positive 
		// Ones with multiplicity are indicated by their value (double angered would be KRAKEN_ANGERED: 2)
		// Items that can ignore lingering effects simply adjust the global value (by decrementing)
		$this->globals->set('KRAKEN_ANGERED', 0);
		$this->globals->set('SHARK_CHOMP_CHOMP', 0);
		$this->globals->set('SHARK_SUBMERGED', 0);
		$this->globals->set('SIRENS_TEMPTING_TUNE', 0);
		$this->globals->set('SIRENS_SCREECH', 0);

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
		// (and mark them as faceup for information control/obfuscation later)
		$this->water->shuffle('deck');
		$this->addToColumn('treasureColumn');
		$this->addToColumn('treasureColumn');
		$this->DbQuery("UPDATE `water` SET `card_face_up`=TRUE WHERE `card_location`='treasureColumn'");

		// STEP I, J: Assemble waterDeck and water column
		// Now we just need to add the clear waters back into the deck, shuffle, and put one in the water column.
		$this->water->moveAllCardsInLocation('clearWaterDeck', 'deck');
		$this->water->shuffle('deck');
		$this->addToColumn('waterColumn');

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
		$this->addToColumn('breachesColumn', $this->breaches, (int) array_pop($minorBreaches)['id']);
		$this->breaches->shuffle('deck');

		// Assemble Cannons! ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		// STEP N, O: Setup cannons, both busted and operational
		$cannons = [];
		for ($strength = 1; $strength <= 3; $strength++)
		{
			$cannons[] = ['type' => $strength, 'type_arg' => 0, 'nbr' => 3];	
		}	
		$this->cannons->createCards($cannons, 'deck');
		$singleShots = $this->cannons->getCardsOfType(1);
		$doubleShots = $this->cannons->getCardsOFType(2);
		$this->addToColumn('breachesColumn', null, (int) array_pop($singleShots)['id']);
		$this->addToColumn('breachesColumn', null, (int) array_pop($doubleShots)['id']);
		$this->addToColumn('cannonsColumn', null, (int) array_pop($singleShots)['id']);

		// Dice!!!! ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		// All dice are initialized with a random value. 
		// This will have no actual effect on the game outcome, its just to match the look of a physical game where the dice would all begin with random values
		$sql = "INSERT INTO dice (`die_id`, `type`, `value`) VALUES ";
		$dice = array();
		
		// Assign the ids of the attack die to be greater than all the cannon ids
		$cannonInfo = $this->getCollectionFromDB("SELECT `card_id` FROM `cannon`");
		$cannonInfo = array_map('intval', array_keys($cannonInfo));
		$die_id = max($cannonInfo) + 1;

		// Create the special attack die
		for ($x = 0; $x < 2; $x++, $die_id++)
		{
			$value = \bga_rand(1, 6);
			$dice[] = "('$die_id', 'special', '$value')";
		}

		// Create the basic attack die (yes we still have the enemy from setting up the cards)
		for ($x = 0; $x < $this->tokens['enemyInfo'][$enemy]['basicDice']; $x++, $die_id++)
		{
			$value = \bga_rand(1, 6);
			$dice[] = "('$die_id', 'basic', '$value')";
		}

		// Create the cannon die: 3 of each type
		for ($strength = 1; $strength <= 3; $strength++)
		{
			$cannons = $this->cannons->getCardsOfType($strength);
			for ($x = 0; $x < 3; $x++)
			{
				$value = \bga_rand(1,6);
				$dice[] = "('" . array_pop($cannons)['id'] . "', '$strength', '$value')";
			}
		}

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

		if ($state["type"] === "activeplayer") 
		{
			switch ($state_name)
		   	{
                default:
                {
                    $this->gamestate->nextState("zombiePass");
                    break;
                }
            }

            return;
        }

        // Make sure player is in a non-blocking status for role turn.
		if ($state["type"] === "multipleactiveplayer")
	   	{
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

	public function testFire()
	{
		//$operableCannons = $this->getNonEmptyCollectionFromDB("SELECT dice.die_id FROM `cannon` INNER JOIN `dice` ON cannon.card_id = dice.die_id WHERE cannon.card_location = 'cannonsColumn'");
		
		$operableCannons = $this->argResolveFire()['operableCannons'];
		$this->fireCannons($operableCannons);
	}

	public function testUpdate()
	{
		$this->notify->all('testUpdate', '', array(
			'playerNbr' => $this->getPlayersNumber(),
			'thresholdLevel' => $this->globals->get('THRESHOLD_LEVEL'),
		));
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
			$theresStillEnoughTreasure = $this->water->countCardInLocation('treasureColumn') >= $plunderingPlayersNbr;
				
			if ($nobodyPlundersNext && $theresStillEnoughTreasure)
			{
				$nextPlayer = array_keys($playerInfo)[array_search('plunder', $dialValues)];
			}
		}

		return (int) $nextPlayer;
	}
	
	public function discard(int $cardId): array
	{
		if ($this->globals->get('KRAKEN_ANGERED') > 0)
		{
			$this->debug('KrakenAngered!');
			// The card does properly discard, but then it triggers basic die rolling and resolving
			$this->water->playCard($cardId);
			$dice = array_slice(array_keys($this->getCollectionFromDB("SELECT `die_id` FROM `dice` WHERE `type`='basic'")), 0, $this->globals->get('KRAKEN_ANGERED'));
			$sqlFilter = implode(',', $dice); 
			$diceRollMapping = []; $cases = ''; $attacks = [];
			while (count($dice) > 0)
			{
				$die = array_pop($dice);
				$roll = \bga_rand(1,6);
				$diceRollMapping[$die] = $roll;
				$cases .= "WHEN $die THEN $roll ";
				$attacks[$die] = $this->tokens['diceMappings']['basic'][$roll] ?? clienttranslate('Blank');
			}
			$this->DbQuery("UPDATE `dice` SET `value` = CASE `die_id` $cases END WHERE `die_id` IN ($sqlFilter)"); 
			$this->notify->all('rollEnemyDice', clienttranslate('Rolled ${rollResult}'), array(
				'rollResult' => implode(', ', array_values($attacks)),
				'diceRollMapping' => $diceRollMapping,
			));

			// TODO somehow the player should have a way to ignore a result if they have the right item (stickyStarfish or decoyCannon)
			// It would prompt them here: after the dice rolled notif, but before it is resolved
			// Probably we need to break and give the player a chance to give input, and then resolve somehow after their input is given

			// Now actually resolve the rolls!
			foreach ($attacks as $id => $result)
			{
				if ($result !== 'Blank')
				{
					$attack = "resolveBasic$result";
					$this->$attack();
				}
			}
		}
		else if ($this->globals->get('SHARK_CHOMP_CHOMP') > 0)
		{
			$this->water->insertCardOnExtremePosition($cardId, 'sharksBelly', true);
			$this->notify->all('sharkChompChomp', clienttranslate('A card was discarded into the Shark\'s Belly.'), array());
		}
		else
			$this->water->playCard($cardId);

		return $this->water->getCard($cardId);
	}

	public function addToColumn(string $column, \Deck $component = null, $cardId = null): int
	{
		if ($component == null)
			$component = ($column === 'waterColumn' || $column === 'treasureColumn') ? $this->water : $this->cannons;
		if ($cardId == null)
			$cardId = $component->getCardOnTop('deck')['id'];
		$component->insertCardOnExtremePosition($cardId, $column, COLUMN_BOTTOM);

		if ($column === 'treasureColumn' && $this->water->countCardInLocation('treasureColumn') > 5)
			$this->discard((int) $this->water->getCardOnTop('treasureColumn')['id']);
		return (int) $cardId;
	}

	public function setCardOrientation(int $cardId, bool $faceUp = false)
	{
		$faceUp = $faceUp ? 1 : 0;
		$this->DbQuery("UPDATE `water` SET `card_face_up`='$faceUp' WHERE `card_id`='$cardId'");
	}

	public function setCardsOrientation(array $cardIds, bool $faceUp = false)
	{
		$faceUp = $faceUp ? 1 : 0;
		$sqlData = implode(',', $cardIds);	
		$this->DbQuery("UPDATE `water` SET `card_face_up`='$faceUp' WHERE `card_id` IN ($sqlData)");
	}
	
	public function pickCardsForWaterColumn(int $number): array
	{
		$cardIds = [];
		while ($number-- > 0)
			$cardIds[] = $this->addToColumn('waterColumn');
		return $cardIds;
	}

	public function getRandomCardFrom(int $playerId)
	{
		$hand = $this->water->getPlayerHand($playerId);
		return array_keys($hand)[\bga_rand(0, count($hand) - 1)];
	}

	public function fireCannons(array $cannonIds) : bool 
	{
		// Roll each given cannon and update the database with the new values
		if ($cannonIds == null)
		{
			throw new \BgaSystemException('Null cannonIds');
		}
		else if (count($cannonIds) == 1)
			$this->DbQuery("UPDATE `dice` SET `value` = " . \bga_rand(1,6) . " WHERE `die_id` = '{$cannonIds[0]}'");
		else
		{
			$updateString = '';
			for ($i = 0; $i < count($cannonIds); $i++)
				$updateString .= "WHEN {$cannonIds[$i]} THEN " . \bga_rand(1,6) . ' ';
			$this->DbQuery("UPDATE `dice` SET `value` = CASE `die_id` $updateString END WHERE `die_id` IN ('". implode("','", $cannonIds)."')");
		}
	
		// Deal a damage to the enemy for each successful hit!
		$rolls = $this->getNonEmptyCollectionFromDB("SELECT `die_id`, `type`, `value` FROM `dice` WHERE `die_id` in ('" . implode("','", $cannonIds) . "')"); 
		$successes = 0;

		foreach ($rolls as $id => $details) 
		{
			// A single shot cannon succeeds when the value is 1, A double shot
			// cannon succeeds when the value is a 1 or 2, A triple shot cannon
			// succeeds when the value is a 1, 2, or 3
			if ((int) $details['value'] <= (int) $details['type'])
			{
				$successes++;
				$this->damageEnemy();
				$this->addToLIST($id);
			}
		}
		$this->notify->all('firedCannons', clienttranslate('Fired ${totalNbr} cannons, ${hitNbr} enemy hits'), array(
			'totalNbr' => count($cannonIds),
			'hitNbr' => $successes,
			'rolls' => $rolls,
		));
		return $successes > 0;
	}
	
	public function damageEnemy()
	{
		if ($this->globals->get('ENEMY_HP') > 0)
		{
			$hp = $this->globals->inc('ENEMY_HP', -1);
			$enemy = $this->globals->get('ENEMY');
			$enemyInfo = $this->tokens['enemyInfo'][$enemy];

			// Add/Remove a basic die if necessary
			if (array_key_exists($hp, $enemyInfo['adjustBasicDice']))
			{
				$dieIds = array_keys($this->getCollectionFromDB("SELECT `die_id` FROM `dice`"));
				$dieIds = array_map('intval', $dieIds);
				$maxDieId = max($dieIds);

				switch($enemyInfo['adjustBasicDice'][$hp])
				{
					case 1:
						$maxDieId++;
						$this->DbQuery("INSERT INTO `dice` VALUES ('$maxDieId', 'basic', '" . \bga_rand(1,6) . "')");
						break;

					case -1:
						$this->DbQuery("DELETE FROM `dice` WHERE `die_id` = '$maxDieId'");
						break;

					default:
						var_dump($enemyInfo['adjustBasicDice'][$hp]);
				}
			}

			// Enemy reaction
			// If this enemy has triggers (meaning it has an effect that can be triggered by taking damage) and its triggers contains $hp, then the enemy reacts to damage!
			if (array_key_exists('triggers', $this->tokens['enemyInfo'][$enemy]) === true && array_search($hp, $this->tokens['enemyInfo'][$enemy]['triggers']) !== false)
			{
				$reaction = "the{$enemy}ReactsToDamage";
				$this->$reaction();
			}
		}
	}

	public function notifyForCardsDrawn(int $playerId, array $cards)
	{
		foreach ($cards as $card)
		{	
			// Obfuscated version for the other players
			$cardId = $card['id'];
			$cardDescription = $this->tokens['waterDeck'][$card['type']]['name'];
			$cardDescriptionObfuscated = $cardDescription;
			$cardObfuscated = $card;

			// If the card was face-down, obfuscate the details for the other players
			// If it was already facep-up (treasure column card or revealed clear water), then there is no need to obfuscate
			if (intval($this->getUniqueValueFromDB("SELECT `card_face_up` FROM `water` WHERE `card_id`='$cardId'")) == 0)
			{
				$cardObfuscated['type'] = 'backside';
				$cardObfuscated['type_arg'] = 0;
				$cardDescriptionObfuscated = clienttranslate('an unknown card');
			}

			$this->notify->all('actDraw', clienttranslate('${player_name} drew ${card_description}'), array(
				'player_id' => $playerId,
				'card_description' => $cardDescriptionObfuscated,
				'player_name' => $this->getPlayerNameById($playerId),
				'card' => $cardObfuscated,	
			));	
			$this->notify->player(intval($playerId), 'actDrawPrivate', clienttranslate('You drew ${card_description}'), array(
				'card_description' => $cardDescription,
				'card' => $card,
			));
		}
	}

	// In all cases, the card discarded is unknown to everyone except for $playerId
	// Therefore all sensitive info must be obfuscated from everyone else	
	public function notifyForCardsDiscarded(int $playerId, array $cards)
	{
		foreach ($cards as $card)
		{
			// Make an obfuscated version for the other players
			$cardDescription = $this->tokens['waterDeck'][$card['type']]['name'];
			$cardObfuscated = $card;
			$cardObfuscated['type'] = 'backside';
			$cardObfuscated['type_arg'] = 0;

			$this->notify->all('actDiscard', clienttranslate('${player_name} discarded a card'), array(
				'player_id' => $playerId,
				'player_name' => $this->getPlayerNameById($playerId),
				'card' => $cardObfuscated,	
			));	
			$this->notify->player($playerId, 'actDiscardPrivate', clienttranslate('You discarded ${card_description}'), array(
				'card_description' => $cardDescription,
				'card' => $card,
			));
		}
	}

	public function addToLIST($element)
	{
		$list = (array) $this->globals->get('LIST');
		$list[] = $element;
		$this->globals->set('LIST', $list);
	}

	public function removeFromLIST($element): bool
	{
		$list = (array) $this->globals->get('LIST');
		$key = array_search($element, $list);
		if ($key === false)
			return false;
		unset($list[$key]);
		$this->globals->set('LIST', $list);
		return true;
	}

	// Enemies! ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	// Basic Enemy Dice:
	public function resolveBasicWater(): void
	{
		$this->debug("\nResolving basic water...\n");
		$this->notify->all('resolveBasicWater', clienttranslate('Resolved water die result: Dealt a card to water column'), array(
			'card' => ['id' => $this->addToColumn('waterColumn'), 'type' => 'backside', 'type_arg' => 0],
		));
	}

	public function resolveBasicBreach(): void
	{
		if ($this->removeFromLIST('ignoreBreach'))
		{
			$this->notify->all('resolveBasicBreach', clienttranslate('Successfully ignored 1 breach die roll'), array());
			return;
		}
		$this->debug("\nResolving basic breach...\n");
		$id = $this->addToColumn('breachesColumn', $this->breaches);
		$card = $this->breaches->getCard($id);
		$this->notify->all('resolveBasicBreach', clienttranslate('Resolved breach die result: Dealt a ${type} to the Breaches Column'), array(
			'id' => $id,
			'card' => $card,
			'type' => $this->tokens['breaches'][$card['type']]['name'],
		));
	}

	public function resolveBasicCannon(): void
	{
		$this->debug("\nResolving basic cannon...\n");
		if ($this->removeFromLIST('ignoreCannon'))
			$this->notify->all('resolveBasicCannonFailed', clienttranslate('Sucessfully ignored one cannon die roll'), array());	
		else if ($this->cannons->countCardsInLocation('cannonsColumn') == 0)
			$this->notify->all('resolveBasicCannonFailed', clienttranslate('Resolved basic cannon die result (no cannons in cannon column)'), array()); 
		else
		{
			$card = $this->cannons->getCardOnTop('cannonsColumn');
			$id = (int) $card['id'];
			$this->cannons->moveCard($id, 'breachesColumn');
			$this->notify->all('resolveBasicCannon', clienttranslate('Resolved basic cannon die result: a ${type} was damaged'), array(
				'type' => $this->tokens['cannons'][$this->cannons->getCard($id)['type']],
				'card' => $card,
				'dieValue' => $this->getUniqueValueFromDB("SELECT `value` FROM `dice` WHERE `die_id`='$id'"),
			));
		}
	}

	// Items!?!
	// All items with actual effects (not just points), in alphabetical order.
	// I'll slowly fill in the logic for each... sounds like fun!
	public function itemsDoStuffIGuess(array $input): bool 
	{
		$message = ''; 
		$item = $input['item']; 
		$itemId = $input['itemId'];
		$card = $this->water->getCard($itemId);

		if ($item == null || !array_key_exists($item, $tokens['waterDeck']))
			$message = 'Item does not exist!';
		else if ($card['location'] != 'hand' || $card['location_arg'] != $input['sourcePlayer'])
			$message = "Source player does not have the card in their hand!";
		else if (!$in_array($input['condition'], $tokens['waterDeck'][$item]['condition']))
			$message = "Item must be played in reaction to the condition {$tokens['waterDeck'][$item]['condition']}, not {input['condition']}";
		// TODO check that the needed player input are incliuded in $input (a player, a dial value, etc)

		if ($message !== '')
		{
			$this->dump('input', $input);
			throw new \BgaSystemException("itemsDoStuffIGuess: item: {$item}, sourcePlayer: {$input['sourcePlayer']} not allowed in state {$this->getStateName()}\n($message)");
		}

		switch ($item)
		{
			// Heavily based on bottleORum
			case 'boneClub':
				if ($this->water->countCardInLocation('hand', $input['targetPlayer']) == 0 || $this->DbQuery("SELECT `dial_value` FROM `player` WHERE `player_id`='{$input['targetPlayer']}'", true) != 'plunder')
					return false;
				$newCard = $this->getRandomCardFrom($input['targetPlayer']);
				$this->water->moveCard($newCard['id'], 'hand', $input['sourcePlayer']);
				// TODO notif???
				break;

			// Very similar to boneClub
			case 'bottleORum':
				// If the target player has any cards, get a random one, and swap it with the bottleORum
				if ($this->water->countCardInLocation('hand', $input['targetPlayer']) == 0)
					return false;
				$newCard = $this->getRandomCardFrom($input['targetPlayer']);
				$this->water->moveCard($newCard['id'], 'hand', $input['sourcePlayer']);
				$this->water->moveCard($itemId, 'hand', $input['targetPlayer']);
				// TODO notif???
				break;

			case 'captainsKey':
				break;

			case 'cheekyChum':
				$this->globals->inc('SHARK_SUBMERGED', -2);
				break;

			case 'crackedCompass':
				break;

			case 'cutlass':
				break;

			case 'decoyCannon':
				$this->addToLIST('ignoreCannon');
				break;

			case 'fishingNet':
				break;

			case 'fishingRod':
				break;

			case 'fishyBait':
				break;

			case 'flintPistol':
				break;

			case 'gemSifter':
				break;

			case 'grabbyCrabby':
				break;

			case 'grenado':
				break;

			case 'harpoon':
				break;

			case 'hurdyGurdy':
				$this->globals->inc('KRAKEN_ANGERED', -1);
				break;

			case 'metalMallet':
				break;

			case 'moldyMop':
				break;

			case 'silverDoubloon':
				break;

			case 'sirenShiner':
				$dice = $this->getCollectionFromDB("SELECT `die_id`, `value` FROM `dice` WHERE `type`='basic'", true);
				$updateString = '';
				foreach (array_keys($dice) as $id)
				{
					$dice[$id] = 7 - intval($dice[$id]);
					$updateString .= "WHEN $id THEN {$dice[$id]} ";
				}
				$ids = implode(',', $array_keys($dice));
				$this->DbQuery("UPDATE `dice` SET `value` = CASE `die_id` $updateString END WHERE `die_id` IN ($ids)");
				break;

			case 'sirenSilencers':
				$this->globals->inc('SIREN_SCREECH', -2);
				break;

			case 'smellySponge':
				break;

			case 'spareBarrel':
				break;

			case 'spyGlass':
				break;

			case 'stickyStarfish':
				$this->addToLIST('ignoreBreach');
				break;

			case 'trustyCarrot':
				break;

			case 'warDrum':
				$this->globals->set('FLAG', true);
				break;

			case 'waterPistol':
				break;

			case 'woodenMallet':
				break;
		}
	}

// Well just save that there in case we need that later... maybe for setting up their args? or a helper function?
//	public function itemsDoStuffIGuess($item)
//	{
//		switch ($item)
//		{
//			case 'boneClub':
//				break;
//
//			case 'bottleORum':
//				break;
//
//			case 'captainsKey':
//				break;
//
//			case 'cheekyChum':
//				break;
//
//			case 'crackedCompass':
//				break;
//
//			case 'cutlass':
//				break;
//
//			case 'decoyCannon':
//				break;
//
//			case 'fishingNet':
//				break;
//
//			case 'fishingRod':
//				break;
//
//			case 'fishyBait':
//				break;
//
//			case 'flintPistol':
//				break;
//
//			case 'gemSifter':
//				break;
//
//			case 'grabbyCrabby':
//				break;
//
//			case 'grenado':
//				break;
//
//			case 'harpoon':
//				break;
//
//			case 'hurdyGurdy':
//				break;
//
//			case 'metalMallet':
//				break;
//
//			case 'moldyMop':
//				break;
//
//			case 'silverDoubloon':
//				break;
//
//			case 'sirenShiner':
//				break;
//
//			case 'sirenSilencers':
//				break;
//
//			case 'smellySponge':
//				break;
//
//			case 'spareBarrel':
//				break;
//
//			case 'spyGlass':
//				break;
//
//			case 'stickyStarfish':
//				break;
//
//			case 'trustyCarrot':
//				break;
//
//			case 'warDrum':
//				break;
//
//			case 'waterPistol':
//				break;
//
//			case 'woodenMallet':
//				break;
//		}
//	}

	// Special Enemy Dice: ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	// The Kraken	
	// Splash: Place the top card in the Treasure Column face-down into the Water Column.
	public function resolveKrakenAttack1(): void
	{
		$this->debug("\nResolving Kraken's special attack #1!\n");
		$card = $this->water->getCardOnTop('treasureColumn');
		$this->addToColumn('waterColumn', null, $card['id']);
		$this->setCardOrientation((int) $card['id'], false);
		$this->notify->all('resolveKrakenSplash', clienttranslate('Resolved Splash die result: Moved ${card_description} from the top of the Treasure Column face-down into the Water Column'), array(
			'id' => $card['id'],
			'card_description' => $this->tokens['waterDeck'][$card['type']]['name'],
		));
	}
	
	// Angered: When a card is added to the discard pile this round, immediately roll and resolve 1 Basic Attack Die. (Place this die on the discard pile as a reminder.)
	public function resolveKrakenAttack2(): void
	{
		$this->debug("\nResolving Kraken's special attack #2!\n");
		$this->globals->inc('KRAKEN_ANGERED', 1);
		// TODO put something in the notif to indicate the multiplicity of the attack (are there one or two angered attacks this round?)
		// I'm thinking where it says "roll and resolve 1 Basic Attack Die", replace the 1 with multiplicity?
		$this->notify->all('resolveKrakenAngered', clienttranslate('Resolved Angered die result: ${explanation}'), array(
			'explanation' => $this->tokens['enemySheets']['Kraken']['specialAttack2']['effect'],
		));
	}
	
	// The Shark!
	// Chomp, Chomp!: All cards discarded this round go to the Shark's Belly face-down
	public function resolveSharkAttack1(): void 
	{
		$result = $this->globals->inc('SHARK_CHOMP_CHOMP', 1);	
		if ($result == 1)
			$this->notify->all('resolveSharkChompChomp', clienttranslate('Resolved Chomp, Chomp! die result: ${explanation}'), array(
				'explanation' => $this->tokens['enemySheets']['Shark']['specialAttack1']['effect'],
			));
	}

	// Submerged: When firing at the Shark this round, only Double-shot and Triple-Shot cannons can deal damage
	public function resolveSharkAttack2(): void 
	{
		$result = $this->globals->inc('SHARK_SUBMERGED', 1);	
		if ($result == 1)
			$this->notify->all('resolveSharkSubmerged', clienttranslate('Resolved Submerged die result: ${explanation}'), array(
				'explanation' => $this->tokens['enemySheets']['Shark']['specialAttack2']['effect'],
			));
	}

	// When the shark receives damage and there is an *, the shark reacts to taking damage by moving all cards from the shark's belly to the water column
	public function theSharkReactsToDamage(): void 
	{
		$sharksBelly = $this->water->getCardsInLocation('sharksBelly', null, 'location_arg');	
		$this->notify->all('theSharkReactsToDamage', clienttranslate('${explanation} Moved ${nbr} cards to the Water Column.'), array(
			'explanation' => $this->tokens['enemySheets']['Shark']['reactToDamage'],
			'nbr' => count($sharksBelly),
		));
		foreach ($sharksBelly as $card)
			$this->addToColumn('waterColumn', null, $card['id']);
	}


	// The Sirens!
	public function resolveSirensAttack1(): void {}
	public function resolveSirensAttack2(): void {}

	// The Skullsairs!	
	public function resolveSkullsairsAttack1(): void {}
	public function resolveSkullsairsAttack2(): void {}
	public function theSkullsairsReactsToDamage(): void { return; }
}
