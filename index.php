<html>
<head>
<style>

body {
	margin: 0;
	padding: 0;
	border: 0;
}

.letter {
	position: absolute; 
	width: 28px;
	height: 28px;
	border: 0;
	text-align: center;
	font-size: 25px;
	color: red;
}


</style>
</head>
<body>

<?
	include('data.php');

	$data = $jumbles[$_GET['id']];

	if (!$data[img]){
		die("jumble not found");
	}

?>
<div style="position: relative; width: <?=$data[w]?>px; height: <?=$data[h]?>px; background-image: url(<?=$data[img]?>)">

<?
		$ids = array();
		$word_ids = array();

		foreach ($data[words] as $k => $word){

			$x = $word[0];
			$y = $word[1];
			$len = strlen($word[2]);
			$circles = $word[3];

			display_word($k, $x, $y, $len, $circles);
		}

		display_word('x', $data[solution][0], $data[solution][1], strlen($data[solution][2]), range(1, strlen($data[solution][2])));


		function display_word($k, $x, $y, $len, $circles){

			global $word_ids, $ids;

			$bg_x = $x - 1;
			$bg_y = $y - 1;
			$bg_w = (30 * $len);
			$bg_h = 30;

			echo "\t<div style=\"position: absolute; left: {$bg_x}px; top: {$bg_y}px; width: {$bg_w}px; height: {$bg_h}px; background-color: #939393\"></div>\n";

			$word_ids_temp = array();

			for ($i=1; $i<=$len; $i++){

				$circle = in_array($i, $circles);
				$col = $circle ? 'background-image: url(circle.gif)' : 'background-color: #fff';

				$id = "w{$k}-l{$i}";
				$ids[] = $id;
				$word_ids_temp[] = $id;

				echo "\t<input type=\"text\" id=\"$id\" class=\"letter\" style=\"left: {$x}px; top: {$y}px; $col\" onkeyup=\"return input_key_up(event, '{$id}');\" onkeypress=\"return input_key_press(event, '{$id}');\" />\n";	

				$x += 30;
			}

			$word_ids[] = "$k : ['".implode("', '", $word_ids_temp)."']";

			echo "\n";
		}
?>
</div>

<script>

var g_id = '<?=$_GET['id']?>';
var ids = ['<?=implode("', '", $ids)?>'];
var words = {<?=implode(", ", $word_ids)?>};
var locked = {};
var words_total = <?=count($word_ids)?>;
var words_locked = 0;

window.onload = function(){

	for (var i=0; i<ids.length; i++){
		document.getElementById(ids[i]).value = '';
	}
};

function get_next_id(id){
	var found = false;
	for (var i=0; i<ids.length; i++){
		if (found) return ids[i];
		if (ids[i] == id) found = true;
	}
	return ids[ids.length - 1];
}

function get_prev_id(id){
	var prev = null;
	for (var i=0; i<ids.length; i++){
		if (ids[i] == id) return prev ? prev : ids[0];
		prev = ids[i];
	}
	return ids[0];
}

function input_key_up(e, id){

	if (!e) var e = window.event;

	if (e.keyCode == 8 || e.keyCode == 46 || e.keyCode == 63272){

		if (!locked[id]){

			// delete or backspace

			var prev = get_prev_id(id);

			if (document.getElementById(id).value){
				document.getElementById(id).value = '';
				document.getElementById(prev).focus();
			}else{
				if (!locked[prev]){
					document.getElementById(prev).focus();
					document.getElementById(prev).value = '';
				}
			}
		}

		return false;
	}


	if (e.keyCode == 37 || e.keyCode == 63234){

		// left
		var prev = get_prev_id(id);
		document.getElementById(prev).focus();
		return false;
	}

	if (e.keyCode == 39 || e.keyCode == 63235){

		// right
		var next = get_next_id(id);
		document.getElementById(next).focus();
		return false;
	}

}

function input_key_press(e, id){

	if (!e) var e = window.event;

	var code = e.charCode || e.keyCode;

	if (code >= 97 && code <= 122){
		code -= 32;
	}

	if (code >= 65 && code <= 90){

		if (!locked[id]){

			document.getElementById(id).value = String.fromCharCode(code);
			var next = get_next_id(id);
			document.getElementById(next).focus();
			check_solution(id);
		}
	}else{
		//document.getElementById(id).value = '';
	}

	return false;
}

function check_solution(id){

	// get the word id for this box...
	var word = 999;
	for (var i in words){
		for (var k=0; k<words[i].length; k++){
			if (words[i][k] == id) word = i;
		}
	}
	if (word == 999) return;


	// check that the word has all letters...
	var check = '';
	for (var i=0; i<words[word].length; i++){
		var char = document.getElementById(words[word][i]).value;
		if (!char) return;
		check += char;
	}


	// check solution
	ajaxify("./check.php", {
		'id'	: g_id,
		'word'	: word,
		'test'	: check
	}, function(o){

		if (o.ok && o.pass){
			lock_word(word);
		}
	});
}

function lock_word(word){

	for (var i=0; i<words[word].length; i++){

		locked[words[word][i]] = 1;
		var elm = document.getElementById(words[word][i]);
		elm.style.color = '#00EC00';
		elm.style.backgroundColor = '#eee';
	}

	words_locked++;
	if (words_total == words_locked){
		alert('Puzzle Complete!');
	}
}

function ajaxify(url, args, handler){

	var req = new XMLHttpRequest();

	req.onreadystatechange = function(){

		var l_f = handler;

		if (req.readyState == 4){
			if (req.status == 200){

				this.onreadystatechange = null;
				try {
					eval('var obj = '+req.responseText);
					l_f(obj);
				} catch (e){
					l_f({
						'ok'	: 0,
						'error'	: "Exception: "+e
					});
				}
			}else{
				l_f({
					'ok'	: 0,
					'error'	: "Non-200 HTTP status: "+req.status,
					'debug'	: req.responseText
				});
			}
		}
	}

	req.open('POST', url, 1);
	req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	var args2 = [];
	for (i in args){
		args2[args2.length] = escape(i)+'='+encodeURIComponent(args[i]);
	}

	req.send(args2.join('&'));
}

</script>

</body>
</html>