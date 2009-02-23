<?php
/* jogo de truco
 *
 * http://en.wikipedia.org/wiki/Truco#Truco_in_Brazil
 *
 */
# define cards
//use simple array.
//first element card value
// 0 - Q
// 1 - J
// 2 - K
// 3 - A
// 4 - 2
// 5 - 3
// second is naipe
// 0 - diamonds picafumo
// 1 - swords espa
// 2 - hearts copas
// 3 - club paus

class Card {
	var $value;
	var $naipe;
	function __construct( $int ){
		$this->value = (int)floor( $int / 4 );
		$this->naipe = $int % 4;
	}
	public function __toInt(){
		return $this->value * ( $this->naipe + 1 );
	}
	public function __toString(){
		$o = '';
		switch($this->value){
		case '0':
			$o .= 'Q';
			break;
		case '1':
			$o .= 'J';
			break;
		case '2':
			$o .= 'K';
			break;
		case '3':
			$o .= 'A';
			break;
		case '4':
			$o .= '2';
			break;
		case '5':
			$o .= '3';
			break;
		}
		switch($this->naipe){
		case '0':
			$o .= '&diams;';
			break;
		case '1':
			$o .= '&spades;';
			break;
		case '2':
			$o .= '&hearts;';
			break;
		case '3':
			$o .= '&clubs;';
			break;
		}
		return $o;
	}
}

class Deck {
	var $cards;
	function __construct(){
		$this->reset();
	}
	public function reset(){
		$this->cards = Array();
		for( $i = 0; $i < 24; $i++ ){
			$this->cards[] = new Card( $i );
		}
		$this->shuffle();
	}
	public function shuffle(){
		shuffle( $this->cards );
	}
	public function draw(){
		return array_pop( $this->cards );
	}
}

class Game {
	var $created;
	var $players;
	var $deck;
	var $hands;
	var $score;
	var $matchs; // cada partida dentro do jogo q vai ate 12
	var $match; //handle to last one (active)
	function __construct(){
		$this->create = time();
		$this->players = Array(0, 1);// TODO: player objects
		$this->deck = new Deck();
		$this->hands = array_fill( 0, count($this->players), Array(null, null, null) );
		$this->score = array_fill( 0, count($this->players), 0 );
		$this->matchs = Array();
		$this->newMatch();
	}
	public function newMatch(){
		$this->deck->reset();
		$this->matchs[] = new Match( $this );
		$this->match = & $this->matchs[ count( $this->matchs ) - 1 ];
		for( $i = 0; $i < count( $this->players ); $i++ ){
			$this->hands[$i] = Array( $this->deck->draw(), $this->deck->draw(), $this->deck->draw() ); 
		}
	}
	public function isPlayerTurn( $player ){
		echo $this->match->turn;
		return $this->match->turn % count($this->players) === $player;
	}
	public function play( $player, $card ){
		# TODO: validate
		if( empty( $this->hands[$player][$card] ) ) return false;
		$this->match->play( $this->hands[$player][$card] );
		$this->hands[$player][$card] = null;
	}
	private function finishTurn(){
		
	}
}

class Match{
	var $wins;
	var $value;
	var $table;
	var $players;
	var $turn;
	var $turns;
	var $mao;
	var $vira;
	var $p;
	function __construct( $parent ){
		$this->turn = 0;
		$this->p = $parent;
		$this->players = count( $this->p->players );
		$this->wins = array_fill( 0, 3, 0 ); // quem ganhou cada mao
		$this->mao = 0;
		$this->reset();
	}
	private function reset(){
		$this->vira = $this->p->deck->draw();
		$this->table = Array();
		$this->turns = Array();
	}
	private function isManilha( $card ){
		if( $this->vira->value === 5 ){ //virou o 3
			return $card->value === 0; // manilha eh Q
		}
		return $card->value === ( $this->vira->value + 1 );
	}
	public function play( $card ){
		$this->table[] = $card;

		// alternat check for bellow is turns % players == 0
		if( count($this->table) === $this->players ){ // everyone played, see who won
			$lastTop = 0;
			for( $i = 1; $i < count($this->players); $i++ ){
				$last = & $this->table[$lastTop];
				$contender = & $this->table[$i];
				// primeiro regra para manilhas.
				if( $this->isManilha($last) && $this->isManilha($contender) ){
					if( $contender->naipe > $last->naipe ){
						$lastTop = $i;
						continue;
					}else{
						continue;
					}
				}
				if( $this->isManilha($last) ){
					continue;
				}elseif( $this->isManilha($contender) ){
					$lastTop = $i;
					continue;
				}
				// depois regras comuns
				// TODO: aqui pode ter empate!!! esqueci do empate FILHA DA PUTA!!!
				if( $last->value <= $contender->value ){
					if( $contender->value > $last->value ){
						$lastTop = $i; 
						continue;
					}else{
						continue;
					}
				}
			}
			#$this->wins[(int)floor($this->turns / $this->players)] = $lastTop;
			$this->wins[ $this->mao++ ] = $lastTop;
			$this->reset();

			//TODO: calculate who win the match
			//
		}
		$this->turn++;
	}
}

# initialize
# initialize: env
$is_player = false;
$nick = '';
$player = (int)$_GET['player'];



# check users
//TODO: do it. maybe password for allow users to sit on the table?
// for now assume everyone in on the same table. and everyone is player 3
$is_player = true;
$nick = 'gabriel';


# load or create
$gameid = $_GET['id'] or die('Indigente');
$m = new Memcache;
$m->connect('localhost', 11211) or die ('alzheimer\'s');
$game = @$m->get($gameid); //damn warning on not found.
if( false === $game ){
	#TODO: if( $user === $owner ){ // user is the owner of the table? info cames from open social.
	$game = new Game();
}

# any action
if( $player !== false ){
	if( $game->isPlayerTurn($player) ){ # are we waiting for the player to play?
		echo " sua vez";
		if( isset($_GET['play']) ){ #player is playing a card
			$play = (int)$_GET['play'];
			var_dump($play);
			$game->play( $player, $play );
		}
	}
}
#update chat here
#TODO:


# save state
$m->set( $gameid, $game, null, 3600 );
# AJAX handlers:
if( isset($_GET['j']) ){
	unset($game->deck);
	#echo 'serverResponse = '.json_encode($game); //TODO: remove some itens from the game object to prevent cheating.
	echo json_encode($game); //TODO: remove some itens from the game object to prevent cheating.
	exit(0);
}
?>


<html>
<head>
<meta http-equiv="refresh" content="2;url=http://localhost/truco/truco.php?id=<?=$gameid?>&player=<?=$player?>"/>

<style>
#container { width: 460px; height: 400px; border: 1px solid black; }
.left { width: 150px; height:400px; float: left;  border: 1px solid #ccc; }
.right {width: 150px; height:400px; float: right; border: 1px solid #ccc;  }

input {width: 80px; }

#hand1 { color: red; }
#hand2 { color: purple; }
#vira { display: inline; background-color: green; color: white; }

#mesa { margin: 10px 0 10px 0; }

#hand .card a { visibility: hidden; }
#hand #card1:hover a { visibility: visible; }

</style>
<!-- Dependencies -->
<script src="http://yui.yahooapis.com/2.6.0/build/yahoo/yahoo-min.js"></script>

<!-- Source file -->
<script src="http://yui.yahooapis.com/2.6.0/build/json/json-min.js"></script>

<!-- Used for Custom Events and event listener bindings -->
<script src="http://yui.yahooapis.com/2.6.0/build/event/event-min.js"></script>

<!-- Source file -->
<script src="http://yui.yahooapis.com/2.6.0/build/connection/connection-min.js"></script>

</head><body>
<div id=container>

<!--<div id=chat class=right>
guest: bla bla bla<br/>
Plateia: yada yada yada<br/>
eles podem ver as maos? ...apenas de quem eles forem amigos?
</div>
<div id=playchat class=left>
<input><button>falar</button>
Player 1: truco!<br/>
Player 2: seis!<br/>
player 3: nove!<br/>
player 1: doze marreco!
</div>-->
<div id=x class=middle>
	<div id=score>
		<table><tr><td>player</td><td>Score</td><td colspan=3>rounds</td></tr>
		<?php foreach( $game->players as $p ){ ?>
		<tr><td>player</td>
				<td><?=$game->score[0]?>
				<td><?=$game->match->score[0]===$p?'&#x2713;':'-'?>
				<td><?=$game->match->score[1]===$p?'&#x2713;':'-'?>
				<td><?=$game->match->score[2]===$p?'&#x2713;':'-'?>
		</td></tr>
		<?php } ?>
		</table>
	</div>


<?php foreach( $game->players as $p ){ if( $p != $player ){ ?>
<div id=hand1 class=hand><?=$game->players[$p]?>'s hand: <?=count($game->hands[$p])?> cards</div>
<?php }} ?>
<hr>
<div id=vira class=card>vira: <?=$game->match->vira;?></div>

mesa:
<div id=table>
		<?php $i = 0; foreach( $game->match->table as $c ){ ?>
			<div class=card><?=$game->players[$i++ % count($game->players)]?> played: <?=$c?></div>
		<?php } ?>
</div>
<hr>

	<div id=hand class=playerhand>Your hand:
		<div id=card1 class=card><?=$game->hands[$player][0]?> <a href="http://localhost/truco/table.php?id=<?=$gameid?>&player=<?=$player?>&play=0">jogar</a></div>
		<div id=card1 class=card><?=$game->hands[$player][1]?> <a href="http://localhost/truco/table.php?id=<?=$gameid?>&player=<?=$player?>&play=1">jogar</a></div>
		<div id=card1 class=card><?=$game->hands[$player][2]?> <a href="http://localhost/truco/table.php?id=<?=$gameid?>&player=<?=$player?>&play=2">jogar</a></div>
	</div>
</div>

<script type="text/javascript">

play = false; // card player will play
tm = false; // time handler
game = {};

var callbacks = {
	// Successful XHR response handler
	success: function(o){

		var messages = [];
		// Use the JSON Utility to parse the data returned from the server
		try{
			messages = YAHOO.lang.JSON.parse(o.responseText);
		}catch(x){
			alert("JSON Parse failed!");
			alert( o.responseText );
			return;
		}

		// update scores
		// update hands
		// update table
		alert( o.responseText );
		document.getElementById('table').innerHTML = messages.table;

		tm = setTimeout( gameLoop, 1000*10 );
		return;

		// The returned data was parsed into an array of objects.
		// Add a P element for each received message
		for (var i = 0, len = messages.length; i < len; ++i) {
			var m = messages[i];
			var p = document.createElement('p');
			var message_text = document.createTextNode(
				m.animal + ' says "' + m.message + '"');
			p.appendChild(message_text);
			msg_section.appendChild(p);
		}
	}
};



function gameLoop(){
	clearTimeout( tm );
	// Make the call to the server for JSON data
	if( play !== false ){ // just update
		YAHOO.util.Connect.asyncRequest('GET','/truco/table.php?j&player=1&id=1', callbacks);
	}else{
		YAHOO.util.Connect.asyncRequest('GET','/truco/table.php?j&player=1&id=1&play='+play, callbacks);
	}
}
</script>
</body>
</html>
