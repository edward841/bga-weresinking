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
    private static array $CARD_TYPES;

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

        self::$CARD_TYPES = [
            1 => [
                "card_name" => clienttranslate('Troll'), // ...
            ],
            2 => [
                "card_name" => clienttranslate('Goblin'), // ...
            ],
            // ...
        ];
		
		$this->water = $this->getNew('module.common.deck');
		$this->water->init('water');

		$this->breaches = $this->getNew('module.common.deck');
		$this->breaches->init('breach');
    }

    /**
     * Player action, example content.
     *
     * In this scenario, each time a player plays a card, this method will be called. This method is called directly
     * by the action trigger on the front side with `bgaPerformAction`.
     *
     * @throws BgaUserException
     */
    public function actPlayCard(int $card_id): void
    {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // check input values
        $args = $this->argPlayerTurn();
        $playableCardsIds = $args['playableCardsIds'];
        if (!in_array($card_id, $playableCardsIds)) {
            throw new \BgaUserException('Invalid card choice');
        }

        // Add your game logic to play a card here.
        $card_name = self::$CARD_TYPES[$card_id]['card_name'];

        // Notify all players about the card played.
        $this->notify->all("cardPlayed", clienttranslate('${player_name} plays ${card_name}'), [
            "player_id" => $player_id,
            "player_name" => $this->getActivePlayerName(), // remove this line if you uncomment notification decorator
            "card_name" => $card_name, // remove this line if you uncomment notification decorator
            "card_id" => $card_id,
            "i18n" => ['card_name'], // remove this line if you uncomment notification decorator
        ]);

        // at the end of the action, move to the next state
        $this->gamestate->nextState("playCard");
    }

    public function actPass(): void
    {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // Notify all players about the choice to pass.
        $this->notify->all("pass", clienttranslate('${player_name} passes'), [
            "player_id" => $player_id,
            "player_name" => $this->getActivePlayerName(), // remove this line if you uncomment notification decorator
        ]);

        // at the end of the action, move to the next state
        $this->gamestate->nextState("pass");
    }

    /**
     * Game state arguments, example content.
     *
     * This method returns some additional information that is very specific to the `playerTurn` game state.
     *
     * @return array
     * @see ./states.inc.php
     */
    public function argPlayerTurn(): array
    {
        // Get some values from the current game situation from the database.

        return [
            "playableCardsIds" => [1, 2],
        ];
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
     * Game state action, example content.
     *
     * The action method of state `nextPlayer` is called everytime the current game state is set to `nextPlayer`.
     */
    public function stNextPlayer(): void {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // Give some extra time to the active player when he completed an action
        $this->giveExtraTime($player_id);
        
        $this->activeNextPlayer();

        // Go to another gamestate
        // Here, we would detect if the game is over, and in this case use "endGame" transition instead 
        $this->gamestate->nextState("nextPlayer");
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
		$waterColumn = array();
		$cards = $this->getCollectionFromDB("SELECT `card_id`, `card_face_up`, `card_type_arg` FROM `water` WHERE `card_location`='waterColumn' ORDER BY `card_location_arg`");
		foreach ($cards as $id => $details)
		{
			if ($details['card_face_up'] == "1")
				$waterColumn[$id] = ['id' => $id, 'type' => 'clearWater', 'type_arg' => $details['card_type_arg']];
			else
				$waterColumn[$id] = ['id' => $id, 'type' => 'backside', 'type_arg' => 0];
		}
		$result['waterColumn'] = $waterColumn;
	
		// Treasure column
		$result['treasureColumn'] = $this->water->getCardsInLocation('treasureColumn');

		// Breaches
		$result['breaches'] = $this->breaches->getCardsInLocation('breachesColumn');
		
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
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        // Create players based on generic information.
        //
        // NOTE: You can add extra field on player table in the database (see dbmodel.sql) and initialize
        // additional fields directly here.
        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

		// Init global values with their initial values.

        // Dummy content.
        $this->setGameStateInitialValue("my_first_global_variable", 0);
		
		$enemyNumber = $this->tableOptions->get(100);
		if ($enemyNumber == 5)
			$enemyNumber = \bga_rand(1,4);
		$enemies = [1=>'Kraken', 2=>'Shark', 3=>'Sirens', 4=>'Skullsairs'];
		$this->globals->set('ENEMY', $enemies[$enemyNumber]);
		
		$this->globals->set('ENEMY_HP', 6);
		$this->globals->set('THRESHOLD_LEVEL', 1);
		$this->globals->set('PERMANENT_BREACHES', 0);
		
        // Init game statistics.
        //
        // NOTE: statistics used in this file must be defined in your `stats.inc.php` file.

        // Dummy content.
        // $this->initStat("table", "table_teststat1", 0);
        // $this->initStat("player", "player_teststat1", 0);

		// TODO: Setup the initial game situation here.
		$this->populateDatabase();

        // Activate first player once everything has been initialized and ready.
        $this->activeNextPlayer();
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
}
