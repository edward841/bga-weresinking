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
],
function (dojo, declare, gamegui, counter, stock, BgaAnimations, BgaCards) {
    return declare("bgagame.weresinking", ebg.core.gamegui, {
        constructor: function(){
            console.log('weresinking constructor');
              
            // Here, you can init the global variables of your user interface
			this.cardWidth = 120;
			this.cardHeight = 168;

			// Initialize stock:
			this.playerHand = null;
			this.waterColumn = null;
			this.treasureColumn = null;
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
						<div id="waterDrawPile" class="card cardOnBoard"></div>
						<div id="waterDiscardPile" class="card cardOnBoard"></div>
						<div id="breachesDrawPile" class="card cardOnBoard"></div>
					</div>
					<div id="columns">
						<div id="waterColumn" class="column"></div>
						<div id="treasureColumn" class="column"></div>
						<div id="breachesColumn" class="column">
							<div id="bustedCannons"></div>
							<div id="breaches"></div>
						</div>
						<div id="cannonsColumn" class="column"></div>
					</div>
				</div>
				<div id="enemySheetWrapper">
					<div id="enemySheet" class="sheet enemy${gamedatas.globals.enemy}Front"></div>
					<div id="damageTokenSpaces" class="enemy${gamedatas.globals.enemyHP}HP damageCounter${gamedatas.globals.enemy}"></div>
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

			// create the card manager
			this.cardsManager = new BgaCards.Manager({
				animationManager: this.animationManager,
				type: 'weresinking-cannons-card',
				getId: (card) => card.id,
//				div.style.backgroundPositionX = ``,
//				div.style.backgroundPositionY = ``,
				setupFrontDiv: (card, div) => {
					div.style.background = 'blue';
					this.addTooltipHtml(div.id, `tooltip of ${card.type}`);
				},
				cardWidth: this.cardWidth,
				cardHeight: this.cardHeight,
				cardBorderRadius: '5px',
			});

			this.setupCards();
			//dojo.style('gameCenter', 'marginBottom', `${this.columnsHeight() + 10}px`)

            this.setupNotifications();

            console.log( "Ending game setup" );
        },

		setupCards: function(gamedatas)
		{
			this.playerHand = null;
			this.waterColumn = this.setupColumnStock('waterColumn'); 
			this.treasureColumn = null;
			this.breaches = null;
			this.operationalCannons = null;
			this.bustedCannons = null;
		},

//		manualPositionStockUpdateDisplay function(element, cards, lastCard, stock)
//		{
//			const gap = 20;
//			cards.forEach((card, index) => {
//				const cardDiv = stock.getCardElement(card);
//				cardDiv.style.left = `${index*gap}px`;
//			});
//		},
//
		setupColumnStock: function(divId)
		{
			const stock = new BgaCards.LineStock(this.cardsManager, document.getElementById(divId), {
				direction: 'column',
			});
			//const stock = new BgaCards.ManualPositionStock(this.cardsManager, document.getElementById(divId), undefined, manualPositionStockUpdateDisplay);
			stock.setSelectionMode('none');

			stock.addCards([
				{id: 1, type: 'ruby', type_arg: 0, location: 'waterColumn', location_arg: 0},
				{id: 2, type: 'fishingRod', type_arg: 0, location: 'waterColumn', location_arg: 0},
				{id: 3, type: 'clearWater', type_arg: 2, location: 'waterColumn', location_arg: 0},
			]);

			return stock;
		},


//		setupStocks: function(gamedatas)
//		{
//			// Initialize ~~~~~~~~~~~~~~~~~~~~~~~~~~~
//			console.log("Setting up stocks...");
//			this.waterColumn = this.initializeCardStock('waterColumn');
//			this.treasureColumn = this.initializeCardStock('treasureColumn');
//			this.bustedCannons = this.initializeCardStock('bustedCannons', 6);
//			this.operationalCannons = this.initializeCardStock('cannonsColumn', 6);
//			this.breaches = this.initializeCardStock('breaches');
//			this.playerHand = this.initializeCardStock('myHand');
//
//			// Busted Cannons, Breaches, and Player hand are ordered by a static ordering
//			// Water column, treasure column, cannons column are ordered by deck order (impacts gameplay)
//			// 		*Right now the latter ordering is actually a lack of ordering with weight of 0, may cause issues later? Maybe unstable? Not sure yet
//
//			// Create card types ~~~~~~~~~~~~~~~~~~~~
//			// Card Backside
//			this.waterColumn.addItemType(this.getCardUniqueId('backside'), 0, g_gamethemeurl + 'img/WaterDeckItems.jpg', 0);
//
//			// Clear Water
//			for (var i = 0; i < 30; i++)
//			{
//				var cardTypeId = this.getCardUniqueId('clearWater', i);
//				this.waterColumn.addItemType(cardTypeId, 0, g_gamethemeurl + 'img/WaterDeckClearWater.jpg', i);
//				this.playerHand.addItemType(cardTypeId, cardTypeId, g_gamethemeurl + 'img/WaterDeckClearWater.jpg', i);
//			}
//			// Items
//			for (var i = 1; i <= 39; i++)
//			{
//				this.treasureColumn.addItemType(i, 0, g_gamethemeurl + 'img/WaterDeckItems.jpg', i);
//				this.playerHand.addItemType(i, i, g_gamethemeurl + 'img/WaterDeckItems.jpg', i);
//			}
//
//			// Cannons
//			for (var strength = 1; strength < 4; strength++)
//			{
//				this.operationalCannons.addItemType(strength, 0, g_gamethemeurl + 'img/Cannons.jpg', strength+2);
//				this.bustedCannons.addItemType(strength, strength, g_gamethemeurl + 'img/Cannons.jpg', strength-1);
//			}
//
//			// Breaches
//			for (var i = 1; i < 10; i++)
//			{
//				this.breaches.addItemType(i, i, g_gamethemeurl + 'img/BreachDeck.jpg', i);
//			}
//
//			// Populate the stocks: ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//			this.populateStock(this.waterColumn, gamedatas.waterColumn);
//			this.populateStock(this.treasureColumn, gamedatas.treasureColumn);
//			this.populateStock(this.bustedCannons, gamedatas.bustedCannons);
//			this.populateStock(this.breaches, gamedatas.breaches);
//			this.populateStock(this.operationalCannons, gamedatas.operationalCannons);
//
//			this.populateStock(this.playerHand, gamedatas.hand);
//
//			// Add the card class to all stock items
//			dojo.query('.stockitem').forEach(node=>dojo.addClass(node, 'card'));
//			// Add the cardInHand to all the children divs of #myHand
//			dojo.query('#myHand div').forEach(node=>dojo.addClass(node, 'cardInHand'));
//		},
//
//		initializeCardStock: function(div_container, itemsPerRow)
//		{
//			var stock = new ebg.stock();
//			stock.create(this, $(div_container), this.cardWidth, this.cardHeight);
//			if (itemsPerRow == undefined)
//				itemsPerRow = 10;
//			stock.image_items_per_row = itemsPerRow;
//			stock.setSelectionMode(1);
//			
//			if (div_container != 'myHand')
//			{
//				stock.container_div.width = "120px"; // enough just for 1 card
//				stock.autowidth = false; // this is required so it obeys the width set above
//				stock.use_vertical_overlap_as_offset = false; // this is to use normal vertical_overlap
//				stock.vertical_overlap = 75; // overlap
//				stock.horizontal_overlap  = -1; // current bug in stock - this is needed to enable z-index on overlapping items
//				stock.item_margin = 0; // has to be 0 if using overlap
//			}
//
//			return stock;
//		},
//
//		populateStock: function(stockVariable, stockCards)
//		{
//			console.log(`Populating ${stockVariable.control_name}:`);
//			for (var i in stockCards) 
//			{
//				var card = stockCards[i];
//				this.printCard(card);
//				stockVariable.addToStockWithId(this.getCardUniqueId(card.type, card.type_arg), card.id);
//			}
//		},
		
		getCardUniqueId: function(type, type_arg)
		{
			if (type == 'clearWater')
				return 100 + Number(type_arg);
			else if (type == 'backside')
				return 99;
			else if (type == 'minor' || type == 'major' || type == 'massive' || type == 'monster')
				return 1 + Number(type_arg);
			else if (type == '1' || type == '2' || type == '3')
				return Number(type);
			// TODO: Maybe remove this check later for slight performance boost?
			else if (this.items.includes(type))
				return this.items.indexOf(type);
			else
				console.log(`getCardUniqueId: type <${type}> not recognized.`);
		},

		printCard: function(card)
		{
				console.log(`card.type: ${card.type}, card.type_arg: ${card.type_arg}, card.id: ${card.id}, jsId: ${this.getCardUniqueId(card.type, card.type_arg)}`);
		},

//		stockHeight: function(stock)
//		{
//			return (1 - stock.vertical_overlap / 100) * stock.item_height * (stock.count() - 1) + stock.item_height;
//		},
//
//		columnsHeight: function()
//		{
//			columnHeights = [
//				this.stockHeight(this.waterColumn),
//				this.stockHeight(this.treasureColumn),
//				this.stockHeight(this.bustedCannons) + this.stockHeight(this.breaches) + 10,
//				this.stockHeight(this.operationalCannons)
//			];
//			
//			// The +15 accounts for the bottom padding of the #gameboard 
//			return Math.max(...columnHeights) + 15;
//		},

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
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */


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
            
            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
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
   });             
});
