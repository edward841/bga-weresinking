/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * weresinking implementation : © Edward Niemann <edward.niemann841@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * weresinking.js
 *
 * weresinking user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
	"ebg/stock",
	getLibUrl('bga-animations', '1.x'),
	getLibUrl('bga-cards', '1.x'),
	getLibUrl('bga-dice', '1.x'),
],
function (dojo, declare, gamegui, counter, stock, BgaAnimations, BgaCards, BgaDice) {
    return declare("bgagame.weresinking", ebg.core.gamegui, {
        constructor: function(){
            console.log('weresinking constructor');
              
            // Here, you can init the global variables of your user interface
			this.cardWidth = 120;
			this.cardHeight = 168;
			this.diceWidth = 30;
			this.smallCardGap = 24 / 100. * this.cardHeight;
			this.bigCardGap = 38 / 100. * this.cardHeight;
			
			this.handSizeCounters = {};
			this.chestSizeCounters = {};

			// Initialize stock:
			this.playerHand = null;
			this.waterDeck = null;
			this.waterDiscard = null;
			this.waterColumn = null;
			this.treasureColumn = null;
			this.breachesDeck = null;
			this.breaches = null;
			this.operationalCannons = null;
			this.bustedCannons = null;
			this.specialLocation = null;
		
			// This is the backbone of the getCardUniqueId for easily displaying any given item card.
			// Dead simple but effective: a list of the items in the order they occur in the sprite image. Split on space and find index of item in question
			var itemsString = 'backside boneClub harpoon trustyCarrot cutlass grenado spyGlass ruby sapphire emerald topaz amethyst decoyCannon fishingNet spareBarrel somberSkull bottleORum waterFlask rubberDucky stickyStarfish woodenMallet metalMallet treasureMap silverDoubloon captainsKey gemSifter crackedCompass moldyMop fishingRod grabbyCrabby smellySponge flintPistol waterPistol warDrum hurdyGurdy cheekyChum fishyBait sirenSilencers sirenShiner cursedAmulet';
			this.items = itemsString.split(' ');	
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
			var playerCount = Object.values(gamedatas.players).length;
			console.log( "Starting game setup" );
			console.log( `Enemy is ${gamedatas.globals.enemy}.`);
			console.log(gamedatas);
			const playerColor = gamedatas.players[gamedatas.currentPlayer].color;

			document.getElementById('game_play_area').insertAdjacentHTML('beforeend', `
			<div id="gameCenter"> 
				<div id="thresholdSheet" class="sheet threshold_${playerCount}players_level${gamedatas.globals.threshold}"></div>
				<div id="gameCore"> 
					<div id="gameboard"></div>
					<div id="cardsOnBoardWrapper" class="flexRow">
						<div id="waterDrawPile"></div>
						<div id="waterDiscardPile"></div>
						<div id="breachesDrawPile"></div>
					</div>
					<div id="columns" class="flexRow">
						<div id="waterColumnWrapper">
							<div id="waterColumn" class="column"></div>
							<div id="waterColumnDials" class="dialColumn"></div>
						</div>
						<div id="treasureColumnWrapper">
							<div id="treasureColumn" class="column"></div>
							<div id="treasureColumnDials" class="dialColumn"></div>
						</div>
						<div id="breachesColumnWrapper" class="column">
							<div id="permanentBreaches"></div>
							<div id="bustedCannonsWrapper">
								<div id="bustedCannons"></div>
								<div id="bustedDice" class="diceColumn"></div>
							</div>
							<div id="breaches"></div>
							<div id="breachesColumnDials" class="dialColumn"></div>
						</div>
						<div id="cannonsColumnWrapper" class="column">
							<div id="cannonsColumn"></div>
							<div id="operationalDice" class="diceColumn"></div>
							<div id="cannonsColumnDials" class="dialColumn"></div>
						</div>
					</div>
				</div>
				<div id="enemySheetWrapper">
					<div id="enemySheet" class="sheet enemy${gamedatas.globals.enemy}Front">
						<div id="damageTokenSpaces" class="enemy${gamedatas.globals.enemyHP}HP damageCounter${gamedatas.globals.enemy}"></div>
					</div>
					<div id="enemyDice"></div>
				</div>
			</div>
			<div id="sirensScreechDialsWrapper" class="whiteblock hide">
				<b id="sirensScreechDialsLabel">${_('Declared Dials')}</b>
				<div id="sirensScreechDials"></div>
			</div>
			<div id="myHandWrapper" class="whiteblock">
				<b id="myHandLabel">${_('My hand')}</b>
				<div id="myHand"></div>
			</div>
			<div id="myCharacterWrapper" class="whiteblock">
				<b id="myCharacterLabel">${_('My character')}</b>
				<div id="myCharacterItemsWrapper" class="flexRow">
					<div id="myCharacter" class="sheet characterSheet actionSide" data-color="${playerColor}"></div>
				</div>
			</div>
			<div id="myCrewWrapper" class="whiteblock">
				<b id="myCrewLabel">${_('My crew')}</b>
				<div id="myCrew" class="flexRow"></div>
			</div>
			`);
			
			// Adds the special location if we are playing the shark or skullsairs
			// (Yes this feels slightly out of place, but it has to be before setupCards call because it attaches a bga-cards object to the div)
			if (gamedatas.globals.hasOwnProperty('specialLocation'))
				dojo.create('div', {'id': 'specialLocation', 'class': 'card ' + gamedatas.globals.enemy + ' front'}, 'enemySheetWrapper');

			// Essential Setup: Managers, cards, dice: ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ // create the animation manager, and bind it to the `game.bgaAnimationsActive()` function
			this.animationManager = new BgaAnimations.Manager({
				animationsActive: () => this.bgaAnimationsActive(),
			});

			// create the card managers 
			this.setupManagers();

			// Create the stocks and populate them
			this.setupCards(gamedatas);
			this.setupDice(gamedatas);

           	// Notificataions
			this.setupNotifications();

			// Additional UI modifications to fine tune the look further ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
			// Sirens Screech attack modifies UI slightly if we are currently declaring dials during a Screech attack!
			if (gamedatas.globals.hasOwnProperty('screech') && gamedatas.globals.screech && gamedatas.gamestate.id <= gamedatas.constants.STATE_DECLARE_DIAL)
				this.addScreechEffect();

			// Add permanent breaches
			for (let i = 0; i < gamedatas.globals.permanentBreaches; i++)
			{
				dojo.create("div", {class: "permanentBreach"}, "permanentBreaches");
			}
			
			// Place the Duties Checklist in my character panel iff i am first mate
			if (gamedatas.currentPlayer === gamedatas.globals.firstMate)
				dojo.create("div", {class: "dutiesChecklist"}, "myCharacterItemsWrapper");

			// Place dials if necessary (handling the special case of the screech of course)
			for (let i = 0; i < gamedatas.dials.length; i++)
			{
				var parentElement = (gamedatas.globals.hasOwnProperty('screech') && gamedatas.globals.screech) ? 'sirensScreechDials' : '';
				var dial = gamedatas.dials[i];
				if (dial['dial_location'] !== 'player')
				{
					if (parentElement === '' || gamedatas.gamestate.id > gamedatas.constants.STATE_DECLARE_DIAL)
						parentElement = this.actionToColumn([dial['dial_location']]) + 'Dials';
				}
				else if (dial['id'] === (gamedatas.currentPlayer + ''))
					parentElement = 'myCharacterItemsWrapper';
				else
					continue; // For spectators when no dial should be displayed!
				dojo.create("div", {id: `dial_${dial['id']}`, class: "dial", 'data-value': dial['dial_value'], 'data-color': gamedatas.players[dial['id']].color}, parentElement);
			}

			// Controls the amount of space for cards (the height of the gap between the board and the player hand)
			this.correctGapUnderBoard();
			
			// Add character sheets for my crew
			for (var player_id in gamedatas.players)
			{
				if (player_id === gamedatas.currentPlayer + '')
					continue;

				var color = gamedatas.players[player_id].color;
				dojo.create("div", {class: "sheet characterSheet backstorySide", 'data-color': color}, 'myCrew');
			}

			// Player Panels!
			for (var playerId in gamedatas.players)
			{
				// Hide the first mate for all players except for the first mate
				var player = gamedatas.players[playerId];
				var hideFirstMate = "";
				if (playerId !== gamedatas.globals.firstMate + '')
					hideFirstMate = " hide";

				// Hide the dial icon if the player has already declared their dial
// For now, keep the code ready here but hide the dial icon
//				var hideDialIcon = "";
//				var i = 0;
//				for (; i < this.gamedatas.playerorder.length; i++)
//					if (gamedatas.dials[i].id + '' === playerId + '')
//						break;
//				if (gamedatas.dials[i]['dial_location'] !== 'player')
//					hideDialIcon = " hide";
//				<div id="dialIcon_${playerId}" class="dialIcon${hideDialIcon}"></div>

				// The meat and potatoes of the player panels
				this.getPlayerPanelElement(playerId).innerHTML = `
				<div class="flexRow iconsWrapper">
					<div class="icon characterIcon" data-color="${player.color}"></div>
					<div class="flexRow">
						<div class="icon handSizeIcon"></div>
						<span id="handSize_${playerId}" class="iconText">0</span>
					</div>
					<div class="flexRow">
						<div class="icon chestsSizeIcon"></div>
						<span id="chestSize_${playerId}" class="iconText">0</span>
					</div>
					<div class="firstMateIcon${hideFirstMate}"></div>
				</div>
				`;
				
				// Counter for hand size
				this.handSizeCounters[playerId] = new counter();
				this.handSizeCounters[playerId].create('handSize_' + playerId);
				this.handSizeCounters[playerId].setValue(Number(gamedatas.players[playerId].handSize));
				
				// Counter for chest size
				this.chestSizeCounters[playerId] = new counter();
				this.chestSizeCounters[playerId].create('chestSize_' + playerId);
				this.chestSizeCounters[playerId].setValue(Number(gamedatas.players[playerId].chestSize));
			}

            console.log( "Ending game setup" );
        },
		
		setupManagers: function()
		{
			// I was trying to be fancier and repeat less but I guess I'll just do the boring long way and move on
				
			this.waterManager = new BgaCards.Manager({
				animationManager: this.animationManager,
				type: `weresinking-water-card`,
				getId: (card) => card.id,
				setupFrontDiv: (card, div) => {
					// The 10s being the number of cards across the spritesheet is and 7 being the number of rows of sprites in the spritesheet
					div.style.backgroundPositionX = `${(10 - (this.waterDeckIndexCard(card) % 10)) * this.cardWidth}px`;
					div.style.backgroundPositionY = `${(7 - Math.floor(this.waterDeckIndexCard(card) / 10)) * this.cardHeight}px`;
					this.addTooltipHtml(div.id, `tooltip of ${card.type}`);
				},
				setupBackDiv: (card, div) => {},
				isCardVisible: card => Boolean(card.type) && card.type !== 'backside',
				cardWidth: this.cardWidth,
				cardHeight: this.cardHeight,
				cardBorderRadius: '5px',
				cardClickEventFilter: 'all', 
			});

			this.cannonsManager = new BgaCards.Manager({
				animationManager: this.animationManager,
				type: `weresinking-cannon-card`,
				getId: (card) => card.id,
				setupFrontDiv: (card, div) => {
					div.style.backgroundPositionX = `${(4 - card.type) * this.cardWidth}px`;
					this.addTooltipHtml(div.id, `tooltip of ${card.type}`);
				},
				setupBackDiv: (card, div) => {
					div.style.backgroundPositionX = `${(7 - card.type) * this.cardWidth}px`;
					this.addTooltipHtml(div.id, `tooltip of ${card.type}`);
				},
				// Front side is operational, backside is busted
				onCardClick: (card) => this.onCardClick('cannon', card),
				isCardVisible: (card) => card.location === 'cannonsColumn',
				cardWidth: this.cardWidth,
				cardHeight: this.cardHeight,
				cardBorderRadius: '5px',
				cardClickEventFilter: 'all', 
			});

			this.breachesManager = new BgaCards.Manager({
				animationManager: this.animationManager,
				type: `weresinking-breach-card`,
				getId: (card) => card.id,
				setupFrontDiv: (card, div) => {
					div.style.backgroundPositionX = `${(9 - card.type_arg) * this.cardWidth}px`;
					this.addTooltipHtml(div.id, `tooltip of ${card.type}`);
				},
				setupBackDiv: (card, div) => {},
				onCardClick: (card) => this.onCardClick('breach', card),
				isCardVisible: (card) => card.location === 'breachesColumn',
				cardWidth: this.cardWidth,
				cardHeight: this.cardHeight,
				cardBorderRadius: '5px',
				cardClickEventFilter: 'all', 
			});

			this.diceManager = new BgaDice.Manager({
				animationManager: this.animationManager,
				type: 'weresinking-die',
				size: this.diceWidth,
				borderRadius: 15,
				getId: (die) => die.id,
				setupDieDiv: (die, element) => {
					element.dataset.color = die.color ?? "Basic";
				},
			});
		},

		setupCards: function(gamedatas)
		{
			const gapBetweenCardsAndDials = 0;
			const gapBetweenCannonsAndBreaches = (gamedatas.globals.permanentBreaches > 0) ? 30 : 20;

			this.waterDeck = this.setupDeck('waterDrawPile', this.waterManager, gamedatas.deckCount.water);
			this.waterDeck.onCardClick = (card) => {this.onCardClick('deck', card);};
			
			this.waterDiscard = new BgaCards.DiscardDeck(this.waterManager, document.getElementById('waterDiscardPile'), {});
			this.waterDiscard.addCards(gamedatas.discardDeck);

			this.waterColumn = this.setupColumnStock('waterColumn', null, this.waterManager);
			this.waterColumn.onCardCountChange = (cardCount) => {dojo.style('waterColumnDials', 'marginTop', `${this.calculateStockHeight(cardCount, this.smallCardGap) + gapBetweenCardsAndDials}px`)};
			this.treasureColumn = this.setupColumnStock('treasureColumn', null, this.waterManager);
			this.treasureColumn.onCardCountChange = (cardCount) => {dojo.style('treasureColumnDials', 'marginTop', `${this.calculateStockHeight(cardCount, this.smallCardGap) + gapBetweenCardsAndDials}px`)};
		
			const playerHandStock = BgaCards.LineStock;
			this.playerHand = new playerHandStock(this.waterManager, document.getElementById('myHand'), {
				autoPlace: card => card.location === 'hand' && card.location_arg === this.player_id,
				//fanShaped: true,
			});
			this.playerHand.onCardClick = (card) => {this.onCardClick('myHand', card);};
			this.playerHand.onSelectionChange = (selection, lastChange) => this.updatePageTitle();

			//this.cannonsDeck = this.setupDeck('cannonDrawPile', this.cannonManager, 1);	
			this.bustedCannons = this.setupColumnStock('breachesColumn', 'bustedCannons', this.cannonsManager, this.bigCardGap);
			this.bustedCannons.onCardCountChange = (cardCount) => {
				dojo.style('breaches', 'marginTop', `${this.calculateStockHeight(cardCount, this.bigCardGap) + gapBetweenCannonsAndBreaches}px`); 
				dojo.style('breachesColumnDials', 'marginTop', `${this.calculateStockHeight(cardCount, this.bigCardGap) + this.calculateStockHeight(this.breaches.getCardCount(), this.bigCardGap) + gapBetweenCannonsAndBreaches + gapBetweenCardsAndDials}px`); 
			};
			this.bustedCannons.setSort((a, b) => a.type - b.type);
			this.operationalCannons = this.setupColumnStock('cannonsColumn', null, this.cannonsManager, this.bigCardGap);
			this.operationalCannons.onCardCountChange = (cardCount) => {dojo.style('cannonsColumnDials', 'marginTop', `${this.calculateStockHeight(cardCount, this.bigCardGap) + gapBetweenCardsAndDials}px`)};
			this.operationalCannons.onSelectionChange = (selection, lastChange) => this.updatePageTitle();

			this.breachesDeck = this.setupDeck('breachesDrawPile', this.breachesManager, gamedatas.deckCount.breaches);
			this.breaches = this.setupColumnStock('breachesColumn', 'breaches', this.breachesManager, this.bigCardGap);
			this.breaches.onCardCountChange = (cardCount) => {dojo.style('breachesColumnDials', 'marginTop', `${this.calculateStockHeight(cardCount, this.bigCardGap) + this.calculateStockHeight(this.bustedCannons.getCardCount(), this.bigCardGap) + gapBetweenCannonsAndBreaches + gapBetweenCardsAndDials}px`); };

			// Set it up so you cannot select a cannon and breach at the same time
			this.resetBreachesSelection();
			this.bustedCannons.onSelectionChange = (selection, lastChanged) => {
				if (selection.length > 0) 
					this.breaches.unselectAll();
				this.updatePageTitle();
			};

			if (gamedatas.globals.enemy === 'Shark')
			{
				this.specialLocation = new BgaCards.DiscardDeck(this.waterManager, document.getElementById('specialLocation'), {});
				for (i = 0; i < gamedatas.globals.specialLocation; i++)
					this.specialLocation.addCard({'id': -i, 'type': 'backside', 'type_arg': 0});
			}
			else if (gamedatas.globals.enemy === 'Skullsairs')
			{
				this.specialLocation = new BgaCards.DiscardDeck(this.waterManager, document.getElementById('specialLocation'), {});
				gamedatas.globals.specialLocation.forEach((card) => {this.specialLocation.addCard(card)});
			}

			this.populateStock(this.waterColumn, gamedatas.waterColumn);
			this.populateStock(this.treasureColumn, gamedatas.treasureColumn);
			this.populateStock(this.playerHand, gamedatas.hand);

			this.populateStock(this.bustedCannons, gamedatas.bustedCannons);
			this.populateStock(this.operationalCannons, gamedatas.operationalCannons);

			this.populateStock(this.breaches, gamedatas.breaches);
		},
		
		setupColumnStock: function(column, divId = null, manager, gap = this.smallCardGap)
		{
			if (divId == null)
				divId = column;

			// Function for manually positioning cards
			const manualPositionStockUpdateDisplay = (element, cards, lastCard, stock) => {
				cards.forEach((card, index) => {
					const cardDiv = stock.getCardElement(card);
					cardDiv.style.top = `${index*gap}px`;
				});
			};

			const stock = new BgaCards.ManualPositionStock(manager, document.getElementById(divId), undefined, manualPositionStockUpdateDisplay);
			stock.setSelectionMode('none');

			// Event listener for user clicks
			stock.onCardClick = (card) => {this.onCardClick(divId, card);};

			// Correct formatting when cards are removed (otherwise the tops of the cards will still be offset for the stock and the whitespace will be all wrong)
			stock.onCardRemoved = (card) => {
				var element = stock.getCardElement(card);
				if (element != null)
					element.style.top = '0px';
			};

			return stock;
		},

		setupDeck: function(divId, manager, cardNbr)
		{
			const deck = new BgaCards.Deck(manager, document.getElementById(divId), {
				cardNumber: cardNbr,
			});

			return deck;
		},

		populateStock: function(stock, cards)
		{
			console.log('Populating column...');
			for (var i in cards) 
			{
				var card = cards[i];
				this.printCard(card);
				stock.addCard(card);
			}
		},

		setupDice: function(gamedatas)
		{
			const diePerspective = 0;
			this.enemyDice = new BgaDice.LineStock(this.diceManager, document.getElementById('enemyDice'), {
				gap: '4px',
				perspective: diePerspective,
			});	
			this.bustedDice = new BgaDice.LineStock(this.diceManager, document.getElementById('bustedDice'), {
				direction: "column",
				gap: (this.bigCardGap - this.diceWidth)+'px',
				perspective: diePerspective,
				sort: (a, b) => parseInt(a.color.slice(-1)) - parseInt(b.color.slice(-1)),
			});
			this.operationalDice = new BgaDice.LineStock(this.diceManager, document.getElementById('operationalDice'), {
				direction: "column",
				gap: (this.bigCardGap - this.diceWidth)+'px',
				perspective: diePerspective,
			});
			
			this.enemyDice.addDice(gamedatas.attackDice);
			this.bustedDice.addDice(gamedatas.bustedDice);
			this.operationalDice.addDice(gamedatas.operationalDice);
		},

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function(stateName, args)
        {
            switch(stateName)
            {
				case 'resolvePatch':
					if (this.isCurrentPlayerActive() && args.args.possibleActions.includes('Patch'))
					{
						this.bustedCannons.setSelectionMode('single', args.args.possibleToPatch.cannon);	
						this.breaches.setSelectionMode('single', args.args.possibleToPatch.breach);
					}
					else if (args.args.possibleActions.includes('ContributeHammer'))
					{
						// Select the breach in question, forcefully disable deselecting the card, and mark everything else as not selectable
						this.breaches.setSelectionMode('single', [args.args.card]);	
						this.breaches.selectCard(args.args.card);
						this.breaches.onSelectionChange = (selection, lastChange) => {if (selection.length == 0) this.breaches.selectCard(args.args.card)};
						this.bustedCannons.setSelectionMode('single', []);
					}
					break;

				case 'resolveFire':
					if (this.isCurrentPlayerActive() && args.args.possibleActions.includes('ShootYeTreasure'))
					{
						this.playerHand.setSelectionMode('single', args.args.possibleDiscard);
						this.operationalCannons.setSelectionMode('single', args.args.possibleToFireCannons);
					}
					break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch(stateName)
            {
				case 'resolvePatch': 
					this.resetBreachesSelection();
					this.bustedCannons.setSelectionMode('none');
					this.breaches.setSelectionMode('none');
					break;

				case 'resolveFire':
					this.playerHand.setSelectionMode('none');
					this.operationalCannons.setSelectionMode('none');	
					break;

				// Reset dials for a new round
				case 'upkeep':
					var dialId = `dial_${this.player_id}`;
					var currentPlayersValue = null; 
					for (var dial in this.gamedatas.dials)
					{
						if (this.gamedatas.dials[dial].id === this.player_id + '')
						{
							currentPlayersValue = this.gamedatas.dials[dial].dial_value;
							break;
						}
					}

					dojo.query('.dial').forEach(dojo.destroy);
					dojo.create('div', {
						'id': dialId, 
						'class': 'dial',
						'data-value': currentPlayersValue, 
						'data-color': this.gamedatas.players[this.player_id].color,
					}, 'myCharacterItemsWrapper');
					break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName, args );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
//                 case 'playerTurn':    
//                    const playableCardsIds = args.playableCardsIds; // returned by the argPlayerTurn
//
//                    // Add test action buttons in the action status bar, simulating a card click:
//                    playableCardsIds.forEach(
//                        cardId => this.statusBar.addActionButton(_('Play card with id ${card_id}').replace('${card_id}', cardId), () => this.onCardClick(cardId))
//                    ); 
//
//                    this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction("actPass"), { color: 'secondary' }); 
//                    break;
					case 'declareDial':
						this.statusBar.addActionButton(_('Bucket'), () => this.bgaPerformAction("actDeclareDial", {value: 'bucket', location: 'bucket'}));
						this.statusBar.addActionButton(_('Plunder'), () => this.bgaPerformAction("actDeclareDial", {value: 'plunder', location: 'plunder'}));
						if (args.possibleActions.includes('patch'))
							this.statusBar.addActionButton(_('Patch'), () => this.bgaPerformAction("actDeclareDial", {value: 'patch', location: 'patch'}));
						if (args.possibleActions.includes('fire'))
							this.statusBar.addActionButton(_('Fire'), () => this.bgaPerformAction("actDeclareDial", {value: 'fire', location: 'fire'}));
						break;

					case 'resolvePlunder':
						if (args.possibleActions.includes('Pass'))
							this.statusBar.addActionButton(_('Pass'), () => {this.bgaPerformAction('actPass');}, {color: 'secondary'});
						break;

					case 'resolvePatch':
						if (args.possibleActions.includes('Patch'))
							this.statusBar.addActionButton(_('Patch'), () => {
								card = this.bustedCannons.getSelection().concat(this.breaches.getSelection())[0];
								this.bgaPerformAction("actPatch", {
									'cardId': card.id,
									'type': card.type,
								});
								this.bustedCannons.unselectAll();
								this.breaches.unselectAll();
							}, {color: 'primary', disabled: this.bustedCannons.getSelection().length != 1 && this.breaches.getSelection().length != 1});
						else if (args.possibleActions.includes('ContributeHammer'))
						{
							this.statusBar.addActionButton(_('Yes'), () => {
								this.bgaPerformAction('actContributeHammer', {'contribute': true});
							}, {color: 'primary'});
							this.statusBar.addActionButton(_('No'), () => {
								this.bgaPerformAction('actContributeHammer', {'contribute': false});
							}, {color: 'secondary'});
						}
						break;

					case 'resolveFire':
						const possibleActions = args.possibleActions;
						console.log(possibleActions);
						if (possibleActions.includes("Fire"))
							this.statusBar.addActionButton(_('Fire'), () => this.bgaPerformAction("actFire"), { color: 'primary'});
						if (possibleActions.includes("ShootYeTreasure"))
						{
							this.statusBar.addActionButton(_('Shoot Ye Treasure'), () => {
								this.bgaPerformAction("actShootYeTreasure", {
									'cardId': this.playerHand.getSelection()[0].id,
									'cannonId': this.operationalCannons.getSelection()[0].id,
									});
								this.operationalCannons.unselectAll();
								this.playerHand.unselectAll();
							}, {color: 'primary', disabled: this.operationalCannons.getSelection().length != 1 || this.playerHand.getSelection().length != 1}); 
						}
						if (possibleActions.includes("Pass"))
							this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction("actPass"), { color: 'secondary' }); 
						break;

//					case 'resolveBucket':
//						const currentAction = args.possibleActions[0];
//						this.statusBar.addActionButton(_(currentAction), () => this.bgaPerformAction("act" + currentAction, {cardId: }));
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods/ Helper Functions!
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */
		// Gives the index of the appropriate image in the WaterDeck.jpg (0 indexed)
		waterDeckIndexCard(card)
		{
			if (card.type === 'clearWater')
				return Number(card.type_arg) + 40;
			else if (this.items.includes(card.type))
				return this.items.indexOf(card.type);
//			else if (card.id === 'waterDrawPile-fake-top-card')
//				return this.waterDeckIndexCard('backside');
			else
			{
				console.log(`waterDeckIndexCard: card not recognized.`);
				this.printCard(card);	
			}
		},

		printCard: function(card)
		{
			console.log(`card.type: ${card.type}, card.type_arg: ${card.type_arg}, card.id: ${card.id}`); 
		},
		
		// Animation helpers
		dealWaterCardAnimation: function(cards, destination)
		{
			let cardsNumber = this.waterDeck.getCardNumber();

			if (cardsNumber >= cards.length)
				destination.addCards(cards, {fromStock: this.waterDeck}, true); 
			else
				console.log('dealWaterCardAnimation: not enough cards. Need ' + cards.length + ' but only have ' + cardsNumber);
		},

		correctGapUnderBoard: function()
		{
			var water = 0, treasure = 0, breaches = 0, cannons = 0;
			if (this.waterColumn.getCardCount() > 0)
				water = this.calculateStockHeight(this.waterColumn.getCardCount(), this.smallCardGap);

			if (this.treasureColumn.getCardCount() > 0)
				treasure = this.calculateStockHeight(this.treasureColumn.getCardCount(), this.smallCardGap);
		
			const permanentBreachNbr = dojo.query('.permanentBreach').length;
			if (permanentBreachNbr > 0)
				// 34 is height of a breach, 5 is margin-bottom, and +10 accounts for the slightly larger gap between permanent breaches and the next thing 
				breaches += permanentBreachNbr * (34 + 5) + 10; 
			if (this.bustedCannons.getCardCount() > 0)
				breaches +=	this.calculateStockHeight(this.bustedCannons.getCardCount(), this.bigCardGap); 
			if (this.breaches.getCardCount() > 0)
				breaches +=	this.calculateStockHeight(this.breaches.getCardCount(), this.bigCardGap); 
			if (this.bustedCannons.getCardCount() > 0 && this.breaches.getCardCount() > 0)
				breaches += 20; // Account for the gap between busted cannons and breaches?
			
			if (this.operationalCannons.getCardCount() > 0)
				cannons = this.calculateStockHeight(this.operationalCannons.getCardCount(), this.bigCardGap); 

			// Account for dials in columns	
			var dialsCounter = {'bucket': 0, 'plunder': 0, 'patch': 0, 'fire': 0};
			for (var action in dialsCounter)
				dialsCounter[action] += dojo.query(`#${this.actionToColumn(action)}Dials > div.dial`).length; 
			var dialHeight = 102 + 10;
			water += dialsCounter['bucket'] * dialHeight;
			treasure += dialsCounter['plunder'] * dialHeight;
			breaches += dialsCounter['patch'] * dialHeight;
			cannons += dialsCounter['fire'] * dialHeight;

			gap = Math.max(water, treasure, breaches, cannons);
			gap += 30; // For the gap at the top and a gap at the bottom
			console.log(`water:${water}\ntreasure:${treasure}\nbreaches:${breaches}\ncannons:${cannons}\ngap:${gap}`);

			dojo.style('gameCenter', 'marginBottom', `${gap}px`);
		},

		calculateStockHeight: function(count, gap)
		{
			return (count == 0) ? 0 : this.cardHeight + (count - 1) * gap;
		},

		actionToColumn: function(action)
		{
			const actionToColumnMapping = {'bucket': 'waterColumn', 'plunder': 'treasureColumn', 'patch': 'breachesColumn', 'fire': 'cannonsColumn'};
			return actionToColumnMapping[action];
		},

		resetBreachesSelection: function()
		{
			this.breaches.onSelectionChange = (selection, lastChanged) => {
				if (selection.length > 0) 
					this.bustedCannons.unselectAll();
				this.updatePageTitle();
			};
			this.breaches.unselectAll();
		},

		addScreechEffect: function()
		{
			dojo.removeClass('sirensScreechDialsWrapper', 'hide');
			this.bga.gameArea.addLastTurnBanner('No talking until dials are revealed!');
		},

		removeScreechEffect: function()
		{
			dojo.addClass('sirensScreechDialsWrapper', 'hide');
			this.bga.gameArea.removeLastTurnBanner();
		},

        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
		onCardClick: function(parentDiv, card)
		{
			console.log(`Clicked card of type ${parentDiv}`);	
			if (!this.current_player_is_active)
				return;
	

			// Active player is attempting to take their turn
			// We need to verify that this move is allowed and then hand it off to backend
			const args = this.gamedatas.gamestate.args;

			// Intervene for deck, there is only one option of what that card could be
			if (parentDiv === 'deck')
				card = {'id': args.possibleIdsDraw[0], 'type': 'backside', 'type_arg': '0'};
			console.log('printing the card:');
			console.log(card);

			// TODO Consider keeping these additional safety checks in the final version? Would make troubleshooting harder right now tho
			if (args.possibleActions.includes('Draw') && parentDiv === args.location) //&& args.possibleIdsDraw.includes(parseInt(card.id)))
				this.bgaPerformAction('actDraw', {cardId: card.id, location: parentDiv,});
			else if (args.possibleActions.includes('Discard') && parentDiv === 'myHand') //&& args.possibleIdsDiscard.includes(parseInt(card.id)))
				this.bgaPerformAction('actDiscard', {cardId: card.id});
			else if (args.possibleActions.includes('TemptingTune') && parentDiv === 'deck')
				this.bgaPerformAction('actTemptingTune', {});
		},

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your weresinking.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
        	this.bgaSetupPromiseNotifications();    

			// Ignore notifications! These notifications have private versions, communicating private information privy only to the current player
			this.notifqueue.setIgnoreNotificationCheck('actDraw', (notif) => (notif.args.player_id == this.player_id));
			this.notifqueue.setIgnoreNotificationCheck('actDiscard', (notif) => (notif.args.player_id == this.player_id));
        },  
        
        // From this point and below, you can write your game notifications handling methods
		notif_checkForBreaches: function(notif)
		{
			console.log('notif_checkForBreaches');
			console.log(notif);
			
			this.dealWaterCardAnimation(notif.cards, this.waterColumn);
			this.correctGapUnderBoard();
		},
		
		// Sinking procedures animation!
		// STEP 1) Remove the owest section of the ship from the game and take out its two Chest Tokens (without revealing them)
		// STEP 2) Place the Chest Tokens face-down at the bottom of the Breaches Column
		// STEP 3) Move the Threshold Sheet to the next level by either rotating or flipping it to its other side. Then tuck half of the sheet back under the Game Board so that only the current level shows and faces the same direction as the Game Board
		// STEP 4) Shuffle all cards in the Water Deck, Discard Pile, and the Water and Treasure Columns to create a new Water Deck
		// STEP 5) IF there are any Breach cards in the Breaches Column, discard all breach cards and gain 1 Permanent Breach Token. Add the Permanent Breach Token to the top of the Breaches Column.
		// STEP 6) Flip over the First Mate Scroll and continue the round on Step 3 of the duties checklist.
		notif_sinkingProcedures: function(notif)
		{
			console.log('notif_sinkingProcedures');
			console.log(notif);

			// Adjust water threshold sheet
			switch (notif.thresholdLevel)
			{	
				case 1: case 3:
					dojo.style('thresholdSheet', 'transform', 'rotateZ(180deg)');
					dojo.addClass('thresholdSheet', `threshold_${notif.playerNbr}players_level${notif.thresholdLevel+1}`);	
					dojo.removeClass('thresholdSheet', `threshold_${notif.playerNbr}players_level${notif.thresholdLevel}`);	
					break;

				case 2:
					dojo.style('thresholdSheet', 'transform', 'rotateX(180deg)');

					dojo.addClass('thresholdSheet', `threshold_${notif.playerNbr}players_level${notif.thresholdLevel+1}`);	
					dojo.removeClass('thresholdSheet', `threshold_${notif.playerNbr}players_level${notif.thresholdLevel}`);	
					dojo.style('thresholdSheet', 'transform', 'rotateX(0deg)');
					break;
			}

			// Rebuild the water deck with all available cards (water column, treasure column, discard, deck) and shuffle
			this.waterDeck.addCards(this.waterDiscard.getCards().map(card => ({id: card.id})));
			this.waterDeck.addCards(this.waterColumn.getCards().map(card => ({id: card.id})));
			this.waterDeck.addCards(this.treasureColumn.getCards().map(card => ({id: card.id})));
			//this.waterDeck.setCardNumber(notif.deckNbr);
			this.waterDeck.shuffle().then(() => console.log('Water deck shuffled'));
			
			this.correctGapUnderBoard();
			
			// Manage the breaches as well
			if (this.breaches.getCardCount() > 0)
			{
				this.breachesDeck.addCards(this.breaches.getCards().map(card => ({id: card.id,})));
				dojo.create('div', {class: 'permanentBreach'}, 'permanentBreaches');
			}
		},

		notif_testUpdate: function(notif)
		{
			console.log('TESTING UPDATING THRESHOLD');
			console.log(notif);

			// Trying to figure out how to update threshold sheet
			switch (notif.thresholdLevel)
			{	
				case 1: case 3:
					dojo.style('thresholdSheet', 'transform', 'rotateZ(180deg)');
					dojo.addClass('thresholdSheet', `threshold_${notif.playerNbr}players_level${notif.thresholdLevel+1}`);	
					dojo.removeClass('thresholdSheet', `threshold_${notif.playerNbr}players_level${notif.thresholdLevel}`);	
					break;

				case 2:
					dojo.style('thresholdSheet', 'transform', 'rotateX(180deg)');

					dojo.addClass('thresholdSheet', `threshold_${notif.playerNbr}players_level${notif.thresholdLevel+1}`);	
					dojo.removeClass('thresholdSheet', `threshold_${notif.playerNbr}players_level${notif.thresholdLevel}`);	
					dojo.style('thresholdSheet', 'transform', 'rotateX(0deg)');
					break;
			}
		},

		notif_dealWaterAndTreasure: function(notif)
		{
			console.log('notif_dealWaterAndTreasure');
			console.log(notif);

			// Deal the necessary water and treasure cards
			notif.cards.forEach(card => {
				if (card.type === 'backside' || card.type === 'clearWater')
					this.waterColumn.addCard(card, {fromStock: this.waterDeck,});
				else
					this.treasureColumn.addCard(card, {fromStock: this.waterDeck,});
			});
			
			this.correctGapUnderBoard();
		},

		notif_rollEnemyDice: function(notif)
		{
			console.log('notif_rollEnemyDice');
			console.log(notif);
		
			// Get the enemy dice, update their face values to match the new values from the notif, and play the die rolling animation
			// Now the dice looks complicated because we roll only the dice which are specified in the notif, filtering out the rest
			var dice = this.enemyDice.getDice().map((die) => notif.diceRollMapping.hasOwnProperty(die.id) ? die : null);
			dice = dice.filter(die => die != null);
			dice.forEach(die => die.face = notif.diceRollMapping[die.id]);
			this.enemyDice.rollDice(dice, {duration: [800, 1200]});


		},

		notif_firedCannons: function(notif)
		{
			console.log('notif_firedCannons');
			console.log(notif);
			
			var dice = this.operationalDice.getDice().map((die) => notif.rolls.hasOwnProperty(die.id) ? die : null);
			dice = dice.filter(die => die != null);
			dice.forEach(die => die.face = notif.rolls[die.id].value);
			this.operationalDice.rollDice(dice, {duration: [800, 1200]});

			if (notif.hitNbr > 0)
				dojo.addClass('damageTokenSpaces', 'enemy' + notif.newHp + 'HP');
		},

		notif_resolveBasicWater: function(notif)
		{
			console.log('notif_resolveBasicWater');
			console.log(notif);

			this.dealWaterCardAnimation(notif.card, this.waterColumn);
			this.correctGapUnderBoard();
		},

		notif_resolveBasicBreach: function(notif)
		{
			console.log('notif_resolveBasicBreach');
			console.log(notif);

			this.breaches.addCard(notif.card, {fromStock: this.breachesDeck}, true);
			this.correctGapUnderBoard();
		},

		notif_resolveBasicCannon: function(notif)
		{
			console.log('notif_resolveBasicCannon');
			console.log(notif);
		
			this.bustedCannons.addCard(notif.card, {fromStock: this.cannons});
			this.bustedDice.addDie({'id': notif.card.id, 'color': 'Cannon' + notif.card.type});
			this.correctGapUnderBoard();
		},

		notif_actDeclareDial: async function(notif)
		{
			console.log('notif_actDeclareDial');
			console.log(notif);
		
			// Necessary info
			var dialId = `dial_${notif.player_id}`;
			var tempId = dialId + 'temp';
			var color = this.gamedatas.players[Number(notif.player_id)].color;

			var targetLocation = 'sirensScreechDials';
			if (!notif.screech)
			{
				targetLocation = `${notif.dial_location}Dials`.replace(' ', '');
				targetLocation = targetLocation.charAt(0).toLowerCase() + targetLocation.slice(1);
			}
			
			// Hide the dial icon in the corresponding player panel
			//dojo.addClass(`dialIcon_${notif.player_id}`, 'hide');
		
			// If the current player just declared, change the id of the dial in their character panel to avoid naming conflicts
			if (this.player_id == notif.player_id)
			{
				dojo.attr(dialId, 'data-value', 'backside');
				$(dialId).id = tempId; 
			}
			// Else (a player besides the current player declared) create a temporary dial above their player panel
			else
			{
				var playerPanel = this.getPlayerPanelElement(notif.player_id);
				dojo.create('div', {
					'id': tempId, 
					'class': 'dial',
					'data-value': 'backside', 
					'data-color': color,
				}, playerPanel);
				//this.placeOnObject(tempId, playerPanel);
			}

			// Prepare the dial in the column
			dojo.create('div', {
				'id': dialId, 
				'class': 'dial hide',
				'data-value': 'backside', 
				'data-color': color,
			}, targetLocation);
			
			// Correct whitespace
			this.correctGapUnderBoard();
			
			// Move the temp dial into the column
			// Mobile, targetLocation, duration, delay
			await this.slideToObjectAndDestroy(tempId, dialId, 2000).promise;
			dojo.removeClass(dialId, 'hide');
		},

		// TODO This will need more attention when I finish declaring dials in frontend
		notif_revealDials: function(notif)
		{
			console.log('notif_revealDials');
			console.log(notif);

			for (var player in notif.dials)
			{
				dojo.attr(`dial_${player}`, 'data-value', notif.dials[player]['dial_value']);
			}	

			if (notif.screech)
				this.removeScreechEffect();
		},

		notif_actDraw: function(notif)
		{
			console.log('notif_actDraw');
			console.log(notif);

			var card = {'id': Number(notif.card.id), 'type': notif.card.type, 'type_arg': Number(notif.card.type_arg)};

			var source = null;
			switch (notif.card.location)
			{
				case 'waterColumn':
					source = this.waterColumn;
					break;
					
				case 'treasureColumn':
					source = this.treasureColumn;
					break;

				default:
					source = this.waterDeck;
					break;
			}
			if (source != this.waterDeck)	
				source.removeCard(card);
			
			// TODO Need to animate moving it to player panel
			this.handSizeCounters[notif.player_id].incValue(1);
			this.correctGapUnderBoard();
		},

		notif_actDrawPrivate: function(notif)
		{
			console.log('notif_actDrawPrivate');
			console.log(notif);
			
			var card = {'id': notif.card.id, 'type': notif.card.type, 'type_arg': notif.card.type_arg};
			console.log(card);
		
			this.playerHand.addCard(card);
			this.handSizeCounters[this.player_id].incValue(1);
			this.correctGapUnderBoard();
		},

		notif_actDiscard: function(notif)
		{
			console.log('notif_actDiscard');
			console.log(notif);
				
			card = {'id': notif.card.id, 'type': notif.card.type, 'type_arg': notif.card.type_arg};
			if (notif.location === 'discard')
			{
				this.waterDiscard.addCard(card);
				this.waterDiscard.setCardVisible(card, false);
			}
			else if (notif.location === 'sharksBelly')
			{
				this.specialLocation.addCard(card);
				this.specialLocation.setCardVisible(card, false);
			}
			else if (notif.location === 'skullsairsStash')
			{
				this.specialLocation.addCard(card);
				this.specialLocation.setCardVisible(card, true);
			}
		},

		notif_actDiscardPrivate: function(notif)
		{
			console.log('notif_actDiscardPrivate');
			console.log(notif);
	
			card = {'id': notif.card.id, 'type': notif.card.type, 'type_arg': notif.card.type_arg};
			if (notif.location === 'discard')
				this.waterDiscard.addCard(card);
			else if (notif.location === 'sharksBelly')
				this.specialLocation.addCard(card);
			this.waterDiscard.setCardVisible(card, false);
		},

		notif_discard: function(notif)
		{
			console.log('notif_discard');
			console.log(notif);

			card = {'id': notif.card.id, 'type': notif.card.type, 'type_arg': notif.card.type_arg};
			if (notif.location === 'discard')
			{
				this.waterDiscard.addCard(card);
				this.waterDiscard.setCardVisible(card, false);
			}
			else if (notif.location === 'sharksBelly')
			{
				this.specialLocation.addCard(card);
				this.specialLocation.setCardVisible(card, false);
			}
			else if (notif.location === 'skullsairsStash')
			{
				this.specialLocation.addCard(card);
				this.specialLocation.setCardVisible(card, true);
			}
		},

		notif_actPatch: function(notif)
		{
			console.log('notif_actPatch');
			console.log(notif);
			
			switch (notif.problem)
			{
				case 'cannon':
					this.operationalCannons.addCard(notif.card);
					break;

				case 'breach':
					// Fix breaches selection (since it may be forcefully selecting a multi-breach)
					this.resetBreachesSelection();
					this.breachesDeck.addCard(notif.card);
					break;
			}
			
		},

		// Move all cards from the Shark's Belly into the Water Column
		notif_theSharkReactsToDamage: function(notif)
		{
			console.log('notif_theSharkReactsToDamage');
			console.log(notif);

			notif.cardIds.forEach((id) => this.waterColumn.addCard({'id': id, 'type': 'backside', 'type_arg': 0}));
		},

		notif_resolveScreech: function(notif)
		{
			console.log('notif_resolveScreech');
			console.log(notif);
			
			this.addScreechEffect();
		},

		// Skullsairs
		notif_resolveBoardingParty: function(notif)
		{
			console.log('notif_resolveBoardingParty');
			console.log(notif);

			this.specialLocation.addCard(notif.card);			
		},
   });             
});
