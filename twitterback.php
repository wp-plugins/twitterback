<?php
/*
Plugin Name: TwitterBack
Plugin URI: http://leobaiano.com/download
Description: Envia um reply para os usuários do twitter citados no post informando que ele foi citado no texto.
Version: 1.2
Author: Leo Baiano
Author URI: http://leobaiano.com/
*/

class twitterBack{
	static public $usuario,$senha,$mensagem;
	
	function enviarMensagem($usuario,$senha,$mensagem,$citado,$url) {
		$mensagem = "@".$citado.", ".$mensagem.": ".$url;

		$saida = "POST http://twitter.com/statuses/update.json HTTP/1.1\r\n";
		$saida .= "Host: twitter.com\r\n";
		$saida .= "Authorization: Basic ".base64_encode ("$usuario:$senha")."\r\n";
		$saida .= "Content-type: application/x-www-form-urlencoded\r\n";
		$saida .= "Content-length: ".strlen("status=$mensagem")."\r\n";
		$saida .= "Connection: Close\r\n\r\n";
		$saida .= "status=$mensagem";
		$fp = fsockopen ("twitter.com", 80);
		if (fwrite($fp, $saida)) {
			return true;
		}
		fclose($fp);
	}
	function usuariosCitados($texto) {
		preg_match_all("#(http:\/\/|https:\/\/)([^\s<>\.]+)\.([^\s\n<>\"\']+)#sm", $texto, $urls);
		$citados = array();
		foreach($urls[0] as $url) {
			$twitter = "twitter.com";
			if (eregi($twitter,$url)) {
				$varurl = explode("/",$url);
				$citados[] = $varurl[3];
			}
		}
		return $citados;
	}
	function migrarUrl($url) {
		$endereco = "http://migre.me/api.xml?url=".urlencode($url);
		$xml = simplexml_load_file($endereco);
		return $xml->migre;
	}
	function TinyURL($u){
		return file_get_contents('http://tinyurl.com/api-create.php?url='.$u);
	}
}
function twitterBackOpt() {
		if (function_exists('add_options_page')) {
			add_options_page('Options', 'TwitterBack', 5, 'twitterback.php', 'twitterBackMenu');
	    }
	}
	function twitterBackMenu() {
		$user = '';
		$passw = '';
		$msg = 'você foi citado em um texto meu, para conferir acesse o link';
		add_option('twitterBack_user', $user);
		twitterBack::$usuario = stripslashes(get_option('twitterBack_user'));
		
		add_option('twitterBack_senha', $passw);
		twitterBack::$senha = stripslashes(get_option('twitterBack_senha'));
		
		add_option('twitterBack_mensagem', $msg);
		twitterBack::$mensagem = stripslashes(get_option('twitterBack_mensagem'));
		
		if (isset($_POST['atualizarDados'])) {
			update_option('twitterBack_user', stripslashes(trim($_POST['usuario'])));
			update_option('twitterBack_senha', stripslashes(trim($_POST['senha'])));
			update_option('twitterBack_mensagem', stripslashes(trim($_POST['mensagem'])));
			
			twitterBack::$usuario = stripslashes(get_option('twitterBack_user'));
			twitterBack::$senha = stripslashes(get_option('twitterBack_senha'));
			twitterBack::$mensagem = stripslashes(get_option('twitterBack_mensagem'));
			
			echo '<div>' ."\n";
			echo '<p><strong>' ."\n";
			echo 'Atualizado com sucesso!' ."\n";
			echo '</strong></p>' ."\n";
			echo '</div>';
		}
		echo '<div class="wrap">' ."\n";
		echo '<form name="TWITTERBACK" method="post">' ."\n";
		echo '<h2>TwitterBack</h2>' ."\n";
		echo '<hr>'."\n";
		echo '<p>Informe a mensagem que deseja enviar como reply para os usuários do Twitter citados nos seus posts, seu usuário e senha do Twitter<p/>'."\n";
		echo '<div>'."\n";
		echo '<table>' ."\n";
		echo '<tr><td>Usuário: </td>' ."\n";
		echo '<td><input type="text" size="40" name="usuario" value="'.twitterBack::$usuario.'" placeholder="Seu usuário twitter"></td></tr>' ."\n";
		echo '<tr><td>Senha: </td>' ."\n";
		echo '<td><input type="password" size="40" name="senha" value="'.twitterBack::$senha.'" placeholder="Sua senha"></td></tr>' ."\n";
		echo '<tr><td>Mensagem: </td>' ."\n";
		echo '<td><input type="text" size="40" name="mensagem" value="'.twitterBack::$mensagem.'" maxlength="80"></td></tr>' ."\n";
		echo '</table>' ."\n";
		echo '</div>' ."\n";  
		echo '<div>' ."\n";
		echo '<input type="submit" name="atualizarDados" value="Salvar" />' ."\n";
		echo '</div>' ."\n";
		echo '</form>' ."\n";
		echo '</div>' ."\n";    
	}
	function iniciar($id_post) {
		
		$post = get_post($id_post);
		$texto = $post->post_content;
		$endereco = get_permalink($id_post);
		
		$citados = twitterBack::usuariosCitados($texto);
		if ($citados !="") {
			$url = twitterBack::TinyURL($endereco);
			//$url = twitterBack::migrarUrl($endereco);
		}

		foreach($citados as $citado) {
			twitterBack::enviarMensagem(get_option('twitterBack_user'),get_option('twitterBack_senha'),get_option('twitterBack_mensagem'),$citado,$url);
		}
		return true;
	}
	
add_action('admin_menu', 'twitterBackOpt');
add_action('publish_post', 'iniciar');
?>