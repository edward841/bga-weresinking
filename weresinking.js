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

			// Initialize stock:
			this.playerHand = null;
			this.waterDeck = null;
			this.waterColumn = null;
			this.treasureColumn = null;
			this.breachDeck = null;
			this.breaches = null;
			this.operationalCannons = null;
			this.bustedCannons = null;
		
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
			console.log( `There are ${playerCount} players!`);

			document.getElementById('game_play_area').insertAdjacentHTML('beforeend', `
			<div id="gameCenter"> 
				<div id="thresholdSheet" class="sheet threshold_${playerCount}players_level${gamedatas.globals.threshold}"></div>
				<div id="gameCore"> 
					<div id="gameboard"></div>
					<div id="cardsOnBoardWrapper">
						<div id="waterDrawPile"></div>
						<div id="waterDiscardPile"></div>
						<div id="breachesDrawPile"></div>
					</div>
					<div id="columns">
						<div id="waterColumn" class="column"></div>
						<div id="treasureColumn" class="column"></div>
						<div id="breachesColumn" class="column">
							<div id="bustedCannons"></div>
							<div id="bustedDice" class="diceColumn"></div>
							<div id="breaches"></div>
						</div>
						<div id="cannonsColumnWrapper" class="column">
							<div id="cannonsColumn"></div>
							<div id="operationalDice" class="diceColumn"></div>
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
			<div id="myHandWrapper" class="whiteblock">
				<b id="myHandLabel">${_('My hand')}</b>
				<div id="myHand"></div>
			</div>
			`);

			// create the animation manager, and bind it to the `game.bgaAnimationsActive()` function
			this.animationManager = new BgaAnimations.Manager({
				animationsActive: () => this.bgaAnimationsActive(),
			});

			// create the card managers 
			this.setupManagers();

			// Create the stocks and populate them
			this.setupCards(gamedatas);
			this.setupDice(gamedatas);

			dojo.style('gameCenter', 'marginBottom', `200px`)

            this.setupNotifications();

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
				isCardVisible: (card) => card.location === 'cannonsColumn',
				cardWidth: this.cardWidth,
				cardHeight: this.cardHeight,
				cardBorderRadius: '5px',
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
				isCardVisible: (card) => card.location === 'breachesColumn',
				cardWidth: this.cardWidth,
				cardHeight: this.cardHeight,
				cardBorderRadius: '5px',
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
			this.waterDeck = this.setupDeck('waterDrawPile', this.waterManager, gamedatas.deckCount.water);
			this.waterDiscard = null;
			this.waterColumn = this.setupColumnStock('waterColumn', null, this.waterManager); 
			this.treasureColumn = this.setupColumnStock('treasureColumn', null, this.waterManager);
		
			const playerHandStock = BgaCards.LineStock;
			this.playerHand = new playerHandStock(this.waterManager, document.getElementById('myHand'), {
				autoPlace: card => card.location === 'hand' && card.location_arg === this.player_id,
				//fanShaped: true,
			});

			//this.cannonsDeck = this.setupDeck('cannonDrawPile', this.cannonManager, 1);	
			this.bustedCannons = this.setupColumnStock('breachesColumn', 'bustedCannons', this.cannonsManager, this.bigCardGap);
			this.bustedCannons.onCardCountChange = (cardCount) => {dojo.style('breaches', 'marginTop', `${this.cardHeight + this.bigCardGap * (cardCount-1) + 20}px`)};
			this.operationalCannons = this.setupColumnStock('cannonsColumn', null, this.cannonsManager, this.bigCardGap);

			this.breachesDeck = this.setupDeck('breachesDrawPile', this.breachesManager, gamedatas.deckCount.breaches);
			this.breaches = this.setupColumnStock('breachesColumn', 'breaches', this.breachesManager, this.bigCardGap);

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

			return stock;
		},

		setupDeck: function(divId, manager, cardNbr)
		{
			const deck = new BgaCards.Deck(manager, document.getElementById(divId), {
				cardNumber: cardNbr,
//				counter: {
//					position: 'center',
//					extraClasses: 'text-shadow',
//				},
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

// 				Clearly this doesnt work, but why???
//				if (card.card_type === 'backside')
//					this.flipCard(this.waterManager, card);
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
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName, args );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
           
           
            case 'dummy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
            case 'dummy':
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
						this.statusBar.addActionButton(_('Patch'), () => this.bgaPerformAction("actDeclareDial", {value: 'patch', location: 'patch'}));
						this.statusBar.addActionButton(_('Fire'), () => this.bgaPerformAction("actDeclareDial", {value: 'fire', location: 'fire'}));
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
			console.log('New dealWaterCardAnimation');
			let cardNumber = this.waterDeck.getCardNumber();

			if (cardNumber >= cards.length)
				destination.addCards(cards, {fromStock: this.waterDeck}, true); 
			else
				console.log('dealWaterCardAnimation: not enough cards. Need ' + cards.length + ' but only have ' + cardNumber);
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
        
        // Example:
        
        onCardClick: function( card_id )
        {
            console.log( 'onCardClick', card_id );

            this.bgaPerformAction("actPlayCard", { 
                card_id,
            }).then(() =>  {                
                // What to do after the server call if it succeeded
                // (most of the time, nothing, as the game will react to notifs / change of state instead)
            });        
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
        },  
        
        // From this point and below, you can write your game notifications handling methods
        
        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */
		
		notif_checkForBreaches: function(notif)
		{
			console.log('notif_checkForBreaches');
			console.log(notif);
			
			this.dealWaterCardAnimation(notif.cards, this.waterColumn);	
		},

		notif_sinkingProcedures: function(notif)
		{
			console.log('notif_sinkingProcedures');
			console.log(notif);

			// Rebuild the water deck with all available cards (water column, treasure column, discard, deck) and shuffle
			this.waterDeck.addCards(this.waterColumn.getCards().map(card => ({id: card.id,})));
			this.waterDeck.addCards(this.treasureColumn.getCards().map(card => ({id: card.id,})));
			// TODO: also add cards from the discard pile
			this.waterDeck.shuffle().then(() => console.log('Water deck shuffled'));
			
			// Manage the breaches as well
			this.breachesDeck.addCards(this.breaches.getCards().map(card => ({id: card.id,})));
		},

		notif_dealWaterAndTreasure: function(notif)
		{
			console.log('notif_dealWaterAndTreasure');
			console.log(notif);

			// Deal the necessary water and treasure cards
			this.dealWaterCardAnimation(notif.ids, this.waterColumn);
		},

		notif_rollEnemyDice: function(notif)
		{
			console.log('notif_rollEnemyDice');
			console.log(notif);
		
			// Get the enemy dice, update their face values to match the new values from the notif, and play the die rolling animation
			const dice = this.enemyDice.getDice();
			dice.forEach(die => die.face = notif.diceRollMapping[die.id]);
			this.enemyDice.rollDice(dice, {duration: [800, 1200]});
		},

		notif_resolveBasicWater: function(notif)
		{
			console.log('notif_resolveBasicWater');
			console.log(notif);

			this.dealWaterCardAnimation(notif.cards, this.waterColumn);
		},

		notif_resolveBasicBreach: function(notif)
		{
			console.log('notif_resolveBasicBreach');
			console.log(notif);

			this.breaches.addCard(notif.ids[0], {fromStock: this.breachesDeck}, true);
		},

		notif_resolveBasicCannon: function(notif)
		{
			console.log('notif_resolveBasicCannon');
			console.log(notif);

			this.bustedCannons.addCard(notif.ids[0], {fromStock: this.cannons}, true);
		},
   });             
});
