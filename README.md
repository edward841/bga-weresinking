# bga-weresinking

It is time to bring We're Sinking to Board Game Arena! But it seems there is still quite a bit to do...

## Pre-alpha:
Implement all game rules, frontend is complete enough for the player to play the game, no gamebreaking bugs or errors

### Backend:
- [x] Fire Action
- [x] Patching Action
- [ ] Special Attacks
- [ ] Game end condition/scoring
- [ ] Item plays
- [ ] Chests

### Frontend:
- [ ] Buttons, clicks, animations!
- [ ] Separating dial_location and dial_value for players
- [ ] Verify data access is correct (nobody has too much or too little game state info) 
- [ ] Player aid card
- [ ] Translations... Actual proper translations not this mess...
- [ ] Basic tooltips to make the text understandable...

### Known Issues:
- [ ] Why dont the treasure column cards discard down to 5?
- [ ] That one JS error when the ship sinks? What is happening there anyways?
- [ ] Translations are definitely waaaaay wrong... Should be fun to fix
- [ ] There is definitely info being shared with players in notifications that they shouldn't have access to... (deck counts, normal little notifications not set up right, etc.)
- [ ] The cannon dice are all a mess visually. They are displayed in the wrong order, don't update when the cannons change, etc. You have to refresh the page to put them in the proper columns, but that still doesn't put them in the correct order
- [ ] All all animations will need quite a bit of work. Especially cards moving around, as they often go too fast to tell what is happening. The sinking procedures animation will need a total makeover, it goes so fast its hard to tell what happened. I want the cards slowed down, animaitons timed well, all the steps done sequentially in the proper order.

## Alpha:
- [ ] Fix bugs
- [ ] Add the ship image!
- [ ] Combine all the cards into one spritesheet? Water deck, breach deck, cannons, special locations, player aid card
- [ ] Symbols in the frontend
- [ ] Joseph's plan for an available hammer popup?
- [ ] Code documentation
- [ ] Deep backend refactoring... Optimizing for runtime/memory. Simplify the FSM as much as possible...

## Beta:
- [ ] Convince frineds and family to playtest (fam, church, coworkers, Ludamas Games discord, etc.)
- [ ] Small UI improvements
- [ ] Anything minor still left from Alpha?
- [ ] Not bug fixes because theres no more bugs! ...right?? :D
- [ ] What else goes here? I dont even know yet... Lots of fun to come!
