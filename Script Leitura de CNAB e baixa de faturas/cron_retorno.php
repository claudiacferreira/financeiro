<html>
<?php
/* -----------------------------------------------------------------------------------------------------
RETORNO BANCÁRIO
DATA: 13/02/2020
POR: Cláudia C. Ferreira
ULTIMA VERSÃO: 28/10/2021
----------------------------------------------------------------------------------------------------- */
date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: charset=utf-8');


$raiz = 'cron/';
$fator_pasta = 6;
$path = $raiz."docs_retorno/";
$diretorio = dir($path);
$envia_email = 0;

require $raiz.'funcoes.php';

$titulo_msg = '<b>-- IMPORTA&Ccedil;&Atilde;O DOS ARQUIVOS DE RETORNO --</b><br><br><br>';


while($arquivo = $diretorio -> read()){
	if(($arquivo <> '.') AND ($arquivo <> '..') AND ($arquivo <> 'processados')){
		$mensagem .= processa_arquivo($conn,$path,$arquivo,$raiz,$fator_pasta);
		$envia_email = 1;
	}
}

if ($mensagem){
}
else{
	$mensagem .= '* Tentativa de upload em: '.date('d/m/Y H:i:s').' --------------------------------------------------------------<br>';
}

echo $titulo_msg.$mensagem;

if ($envia_email == 1){
	
	envia_email($titulo_msg.$mensagem, '['.date('d/m/Y', strtotime('-1 days')).'] RETORNO - Arquivos Importados', 'claudiacarolinaferreira@gmail.com', $cc);
}

echo escreve_txt($raiz, $mensagem);


function processa_arquivo($conn, $path, $arquivo, $raiz, $fator_pasta){
	
	$arquivo = verifica_extensao($path, $arquivo);
	$nom_antigo = $arquivo;
	$aux = explode(".", $arquivo);
	$extensao = $aux[1];
	$arquivo = $path.$arquivo;
	
	if(($extensao == 'TXT') OR ($extensao == 'txt') OR ($extensao == 'RET') OR ($extensao == 'ret') OR ($extensao == 'CRT') OR ($extensao == 'crt')){
	
	
		$filename = $arquivo;

		$lines = file ($filename);

		$indica_banco = 0;
		foreach ($lines as $line_num => $line) {
			
			if($line <> ''){
				if($indica_banco == 0){
					
					// SICOOB CEDENTE
					if(substr($line, 0, 3) == '756'){
						$aux_num_retorno = substr($line, 158, 6);
						$aux_dado_conta = substr($line, 59, 12);
						$aux_dado_conta = ltrim($aux_dado_conta, "0");// conta
						$aux_dado_banco = '756';
					}
					
					// SICREDI
					elseif(substr($line, 0, 3) == '748'){
						$aux_num_retorno = substr($line, 158, 6);
						$aux_dado_conta = substr($line, 59, 12);
						$aux_dado_conta = ltrim($aux_dado_conta, "0");// conta
						$aux_dado_banco = '748';
					}
					
					// ARRECADAÇÃO (Convênio)
					else{
						// CAIXA ECONOMICA FEDERAL
						if(substr($line, 42, 3) == '104'){
							$aux_dado_conta = '378';
							$aux_dado_banco = '104';
						}
						// SICOOB
						elseif(substr($line, 42, 3) == '756'){
							$aux_dado_conta = '00378';
							$aux_dado_banco = '756';
						}
						// TRIBANCO
						elseif(substr($line, 42, 3) == '634'){
							$aux_dado_conta = '00003378';
							$aux_dado_banco = '634';
						}
						
						$aux_num_retorno = substr($line, 74, 6);
					}
					$indica_banco = 1;
					break;
				}
			}
		}


		$sql1 = "SELECT handle FROM ".$banco_dados.".ret_retorno WHERE num_retorno = '".$aux_num_retorno."' AND dado_conta = '".$aux_dado_conta."'";
		$result1 = mysqli_query($conn, $sql1);
		$row_cnt = mysqli_num_rows($result1);

		//echo $row_cnt;
		
		
		if($row_cnt < 1){
			//$mensagem .= 'Arquivo ok!<br>';
		
			$sql = "INSERT INTO ".$banco_dados.".ret_retorno (nom_arquivo) VALUES ('".htmlspecialchars($vet_arquivo[9])."')";
			$consultar = mysqli_query($conn, $sql);
			
			if ($consultar) {
				
				$id = mysqli_insert_id($conn);
				$destino = $raiz.'docs_retorno/processados/'.$vet_arquivo[9]."<br>";
				$origem = $arquivo."<br>";
				
			} 
			else {
				$mensagem .= "Error: " . $sql . "<br>" . mysqli_error($consultar).'***<br>';
			}
		
		
			$indica_banco = 0;
			
			foreach ($lines as $line_num => $line) {
				
				
				
				if($line <> ''){
					$linha = $line_num+1;
					

				   // echo "Linha #<b>{$line_num}</b> : " . $line . "<br>\n";
					
					$sql2 = "INSERT INTO ret_retorno_conteudo (han_retorno, linha, conteudo) VALUES ('".$id."', '".$linha."', '".$line."')";
					mysqli_query($conn, $sql2);
					
					
					if($indica_banco == 0){
						
						
						
						
						// SICOOB CEDENTE
						if(substr($line, 0, 3) == '756'){
							
							$mensagem .= '--- Upload em: '.date('d/m/Y H:i:s').' -------------------------------------------------------------<br>';
							
							$x_dia = substr($line, 143, 2);
							$x_mes = substr($line, 145, 2);
							$x_ano = substr($line, 147, 4);
							$nome_arquivo = $x_ano.$x_mes.$x_dia;
							$dado_banco = '756';
							$num_retorno = substr($line, 158, 6);
							$dado_agencia = substr($line, 53, 6);
							$dado_conta = substr($line, 59, 12);
							$dado_conta = ltrim($dado_conta, "0");
							$flg_convenio = 'B';
							$tipo_arquivo = '1';
							$mensagem .= '<font color="red">Arquivo <b>Sicoob Cedende</b> processado com sucesso!</font>';
							
							if($dado_conta == '37346'){
								$pasta = 'SICOOB_756_37346';
							}
							elseif($dado_conta == '34282'){
								$pasta = 'SICOOB_756_34282';
							}
						}
						
						// SICREDI
						elseif(substr($line, 0, 3) == '748'){
							
							$mensagem .= '--- Upload em: '.date('d/m/Y H:i:s').' -------------------------------------------------------------<br>';
							
							$x_dia = substr($line, 143, 2);
							$x_mes = substr($line, 145, 2);
							$x_ano = substr($line, 147, 4);
							$nome_arquivo = $x_ano.$x_mes.$x_dia;
							$dado_banco = '748';
							$num_retorno = substr($line, 158, 6);
							$dado_agencia = substr($line, 53, 6);
							$dado_conta = substr($line, 59, 12);
							$dado_conta = ltrim($dado_conta, "0");
							$flg_convenio = 'B';
							$tipo_arquivo = '1';
							$mensagem .= '<font color="red">Arquivo <b>Sicredi</b> processado com sucesso!</font>';

							$pasta = 'SICREDI_748_286605';

						}
						
						// ARRECADAÇÃO (Convênio)
						else{
							
							$mensagem .= '--- Upload em: '.date('d/m/Y H:i:s').' -------------------------------------------------------------<br>';
							
							// CAIXA ECONOMICA FEDERAL
							if(substr($line, 42, 3) == '104'){
								
								$dado_banco = '104';
								$dado_agencia = '0100';
								$dado_conta = '378';

								$mensagem .= '<font color="red">Arquivo <b>Caixa Economica Federal</b> processado com sucesso!</font>';
								
								$pasta = 'CAIXA_104_378';
							}
							
							// SICOOB
							elseif(substr($line, 42, 3) == '756'){
								
								$dado_banco = '756';
								$dado_agencia = '0104';
								$dado_conta = '00378';

								$mensagem .= '<font color="red">Arquivo <b>Sicoob (Convenio)</b> processado com sucesso!</font>';
								
								$pasta = 'SICOOB_756_378';
							}
							
							// TRIBANCO
							elseif(substr($line, 42, 3) == '634'){
								
								$dado_banco = '634';
								$dado_agencia = '0104';
								$dado_conta = '00003378';

								$mensagem .= '<font color="red">Arquivo <b>Tribanco</b> processado com sucesso!</font>';
								
								$pasta = 'TRIBANCO_634_3378';
							}
							
							$flg_convenio = 'A';
							$tipo_arquivo = '2';
							$num_retorno = substr($line, 74, 6);
							$x_dia = substr($line, 71, 2);
							$x_mes = substr($line, 69, 2);
							$x_ano = substr($line, 65, 4);
							$nome_arquivo = $x_ano.$x_mes.$x_dia;
							
						}
						$indica_banco = 1;
						
						
						
						$sql3 = "UPDATE ret_retorno SET caminho='".$pasta.'/'.$nome_arquivo."', dado_banco='".$dado_banco."', dado_agencia='".$dado_agencia."', dado_conta='".$dado_conta."', flg_convenio = '".$flg_convenio."', num_retorno = '".$num_retorno."', tipo_arquivo = '".$tipo_arquivo."' WHERE handle=$id";
						//echo '<br>'.$sql3.'<br>';
						mysqli_query($conn, $sql3);
					
					
					
					}
				}
				
			}

			$destino = verfica_pasta($pasta, $nome_arquivo, $raiz).'/'.$nom_antigo;
			
			$mensagem .= '<br>'.$destino.'<br><br><br>';

			copy($arquivo, $destino);
			if (file_exists($destino)) {// testo se a pasta existe
				unlink($arquivo);
			} 
		
			
			
		}

		else{
			$mensagem .= '--- '.date('d/m/Y H:i:s').' ------------------------------------------------------------------------<br>';
			$mensagem .= '>> Retorno já importado.<br>';
			$sql = "INSERT INTO ".$banco_dados.".ret_retorno_erro (nom_arquivo, erro, dado_banco, dado_conta, num_retorno) VALUES ('".htmlspecialchars($vet_arquivo[9])."', 'Retorno já importado', '".$aux_dado_banco."', '".$aux_dado_conta."', '".$aux_num_retorno."')";
			mysqli_query($conn, $sql);
			unlink($arquivo);
		}
		
	}
	else{
		$mensagem .= '>> Arquivo não compatível.<br>';
	}
	return $mensagem;
	
}



function verfica_pasta($pasta, $nome_arquivo, $raiz){
	
	$year = $nome_arquivo[0].$nome_arquivo[1].$nome_arquivo[2].$nome_arquivo[3];
	$month = $nome_arquivo[4].$nome_arquivo[5];
	$day = $nome_arquivo[6].$nome_arquivo[7];
	
	$hash_past = $raiz.'docs_retorno/processados/';
	
	
	if (file_exists($hash_past.$pasta.'/'.$year)) {// testo se a pasta existe
        //echo "<br>ja existe ".$year.'<br>';
    } 
    else {            
        mkdir($hash_past.$pasta.'/'.$year, 0777);
   	}
	
	if (file_exists($hash_past.$pasta.'/'.$year.'/'.$month)) {// testo se a pasta existe
        //echo "ja existe ".$month.'<br>';
    } 
    else {            
        mkdir($hash_past.$pasta.'/'.$year.'/'.$month, 0777);
   	}
	
	if (file_exists($hash_past.$pasta.'/'.$year.'/'.$month.'/'.$day)) {// testo se a pasta existe
       // echo "ja existe ".$day.'<br>';
    } 
    else {            
        mkdir($hash_past.$pasta.'/'.$year.'/'.$month.'/'.$day, 0777);
   	}


	return $hash_past.$pasta.'/'.$year.'/'.$month.'/'.$day;
}




function escreve_txt($raiz, $mensagem){
	
	// Fornece: <body text='black'>
	$mensagem = str_replace("<br>", "\n", $mensagem);
	$mensagem = str_replace('<font color="red">', '', $mensagem);
	$mensagem = str_replace('</font>', '', $mensagem);
	$mensagem = str_replace('<b>', '', $mensagem);
	$mensagem = str_replace('</b>', '', $mensagem);
	
	
	// abre o arquivo colocando o ponteiro de escrita no final
	$arquivo = fopen($raiz.'logs_retorno/log_'.date('Ymd').'.txt','a');
	if ($arquivo) {
		if (!fwrite($arquivo, $mensagem)) die($result = 'Não foi possível atualizar o arquivo.');
		$result = 'Arquivo atualizado com sucesso';
		fclose($arquivo);
	}
	return $result;
}

	
	
	
?>
