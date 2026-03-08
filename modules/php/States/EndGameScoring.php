<?php
declare(strict_types=1);

namespace Bga\Games\weresinking\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\Games\weresinking\Game;
use Bga\GameFramework\GameResult\GameResult;
use Bga\GameFramework\GameResult\Player;

class EndGameScoring extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: 98,
            type: StateType::GAME,
            description: '',
            transitions: ['gameEnd' => 99],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        // the data sent to the front when entering the state
        return [];
    } 

	function onEnteringState() 
	{
		$enemyDefeated = $this->game->globals->get('ENEMY_HP') <= 0;
		$shipSinks = $this->game->globals->get('THRESHOLD_LEVEL') > 4;
		if (!$enemyDefeated && !$shipSinks)
			throw new \BgaSystemException('EndGameScoring onEnteringState: Enemy not defeated and ship not sunk! Should not be here!');
		$this->game->bga->tableStats->set("enemy_defeated", $enemyDefeated);
		
		$endScores = $this->game->getEndScores();

		// If $enemyDefeated then greatest pointCount wins and least handCount is tiebreaker
		// If $shipSinks then least handCount wins and greatest pointCount is tiebreaker
		foreach ($endScores as $id => $details)
		{
			$this->game->bga->playerScore->set($id, $enemyDefeated ? $details['pointCount'] : $details['handCount']);
			$this->game->bga->playerScoreAux->set($id, $enemyDefeated ? $details['handCount'] : $details['pointCount']);
		}

		$hands = array();
		foreach (array_keys($endScores) as $id)
			$hands[$id] = $this->game->water->getPlayerHand($id);	
		$this->game->notify->all('endScores', '', ['endScores' => $endScores, 'hands' => $hands, 'enemyDefeated' => $enemyDefeated]);

		// Stats work
		foreach ($endScores as $id => $details)
		{
			$this->game->bga->playerStats->set('final_hand_size', $details['handCount']);
			$this->game->bga->playerStats->set('final_value_of_treasure', $details['pointCount']);
		}

		// Straight from the docs for how to handle reverse scoring or reverse aux scoring (https://en.doc.boardgamearena.com/Main_game_logic:_Game.php#Tie_breaker)
		$playersDb = $this->game->getCollectionFromDB("SELECT * FROM `player`");
		$players = Player::fromPlayersDb($playersDb);
		return $enemyDefeated ? GameResult::individualRanking($players, reverseScoreAux: true) : GameResult::individualRanking($players, reverseScore: true);

    }   
}
