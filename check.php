<?
	include('data.php');

	$data = $jumbles[$_REQUEST['id']];

	$test = $_REQUEST['word'] == 'x' ? $data[solution][2] : $data[words][$_REQUEST[word]][2];

	if ($test == $_REQUEST['test']){

		echo "{ok:1, pass:1}";
	}else{
		echo "{ok:1, pass:0}";
	}
?>