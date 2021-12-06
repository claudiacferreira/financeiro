<html>
<?php
/* -----------------------------------------------------------------------------------------------------
 RETORNO BANCÁRIO
 DATA: 13/02/2020
 POR: Cláudia C. Ferreira
 ULTIMA VERSÃO: 28/10/2021
 ----------------------------------------------------------------------------------------------------- */
date_default_timezone_set('America/Sao_Paulo');

echo '<pre>';



$raiz = 'cron/';

require $raiz.'funcoes.php';


$sql = "SELECT * FROM ret_retorno WHERE processamento_completo = 'N' ";
$result = mysqli_query($conn, $sql);



foreach ($result as $i => $line) {
	
	$tem_arquivo = 'sim';
	
	$sql3 = "UPDATE ret_retorno SET data_inicio_processamento='" . date("Y-m-d H:i:s") . "' WHERE handle='" . $line['handle'] . "'";
	mysqli_query($conn, $sql3);

	if ($line['tipo_arquivo'] == 2) {
		$data = processa_cnab_150($conn, $line['handle']);
	}
	elseif ($line['tipo_arquivo'] == 1) {
		$data = processa_cnab_240($conn, $line['handle'], $line['dado_banco']);
	}
	

}

if($tem_arquivo == 'sim'){
	$mensagem = 'Olá, bom dia!<br><br> O processamento do retorno bancário do dia '.pdata($dat_pagamento).' acabou de ser iniciado!<br>Aguarde em breve a conclusão.';
	
	echo $mensagem;


	envia_email($mensagem, '['.date('d/m/Y', strtotime('-1 days')).'] RETORNO - Processamento Iniciado', '[e-mail]', $cc);
}

$mensagem = 'Retorno processado.';
escreve_txt($raiz, $mensagem);



function processa_cnab_240($conn, $han_retorno, $banco = FALSE) {
	
	$sql2 = "SELECT * FROM ret_retorno_conteudo WHERE han_retorno = $han_retorno";
	$result2 = mysqli_query($conn, $sql2);
	

	$val_titulos = 0;
	$qtd_titulos_localizados = 0;
	$qnt_titulos_outras_divergencias = 0;
	$qtd_titulos_desconsiderados = 0;
	$valor_processado = 0;
	$valor_desconsiderado = 0;
	$total_tarifa = 0;
	
	$segue = 0;
	
	$edit_depois = '';
	$cont = 0;
	$dias_atraso = 0;
	
	
	
	foreach ($result2 as $i => $line) {

		//print_r($line);
		$linha = $line['conteudo'];
		$tu = substr($linha, 13, 1);
		if (($tu == 'T') OR ($tu == 'U')) {

			if ($tu == 'T') {
				
				$fatura = '';
			
				$val_titulo = 0;
				
				
				if($banco == '748'){
					$fatura = retira_zero_esquerda(substr($linha, 39, 6));
					$digito = substr($linha, 45, 1);
				}
				else{
					$fatura = retira_zero_esquerda(substr($linha, 36, 10));
					$digito = substr($linha, 46, 1);
				}
				$cliente = substr($linha, 148, 40);
				$dat_vencimento = modelo_data(substr($linha, 73, 8));
				$val_tarifa = modelo_moeda(substr($linha, 200, 13));
				$val_titulo = modelo_moeda(substr($linha, 83, 13));
				
				$cod_movimento = substr($linha, 15, 2);
				$id1 = $line['handle'];

				$segue = $segue + 1;
				
				if($cod_movimento == '28'){
					$val_tarifa = modelo_moeda(substr($linha, 198, 15));
					
					$edit_depois[$cont] = "UPDATE ret_retorno_processado SET val_tarifa = '".$val_tarifa."' WHERE fatura = '".$fatura."'";
					$cont = $cont+1;
				}
			} 
			else {
				
				//echo substr($linha, 79, 13);
				
				$dat_credito = modelo_data(substr($linha, 145, 8));
				$val_pago = modelo_moeda(substr($linha, 79, 13));
				$dat_pagamento = modelo_data(substr($linha, 137, 8));

				$id2 = $line['handle'];

				$segue = $segue + 1;
			}
			//echo $segue;
		

			if($val_pago == ''){
				$val_pago = 0;
			}

			if ($tu == 'U') {
				
				//echo '<b>CNAB 240</b><br>';
				
				$han_franquia 				= '';
				$han_cliente 				= '';
				$val_original 				= '';
				$pagamento_original 		= '';
				$han_carne 					= '';
				$dat_vencimento_original 	= '';
				
				$sql3 = "SELECT CC.handle, CC.han_franquia, FF.dat_vencimento, FF.val_fatura, FF.dat_pagamento, FF.han_carne
				FROM fin_fatura FF 
				INNER JOIN cad_cliente CC ON FF.han_cliente = CC.handle 
				WHERE FF.handle = $fatura";
				$result3 = mysqli_query($conn, $sql3);
				$row_cnt = mysqli_num_rows($result3);
				//echo $row_cnt."\n";
				
				$pagamento_original = '';

				while ($row = mysqli_fetch_assoc($result3)) {
					$han_franquia 				= $row['han_franquia'];
					$han_cliente 				= $row['handle'];
					$val_original 				= $row['val_fatura'];
					$pagamento_original 		= $row['dat_pagamento'];
					$han_carne 					= $row['han_carne'];
					$dat_vencimento_original	= $row['dat_vencimento'];
					$val_titulo 				= $row['val_fatura'];
				}
				
				
				//echo $pagamento_original. "\n";
				
			
				
				if($cod_movimento == '28'){
					
					$segue = 0;
				}
				else{
					
					if($cod_movimento == '06'){ // Liquidação
						
					
						// Verifica se cliente localizado
						if(($row_cnt == 0) OR ($han_cliente == '')){
							$han_tipo_divergencia = '1'; // NÃO LOCALIZADA
						}
						elseif($pagamento_original != ''){
							$han_tipo_divergencia = '4'; // DUPLICIDADE
						}
						else{
							if($val_pago > $val_original){
								$han_tipo_divergencia = '14'; // PAGAMENTO A MAIOR
							}
							elseif($val_pago < $val_original){
								$han_tipo_divergencia = '15'; // PAGAMENTO A MENOR
							}							
							elseif($val_pago == $val_original){
								$han_tipo_divergencia = '6'; // RECEBIDA
							}
							else{
								$han_tipo_divergencia = '3'; // DIVERGÊNCIA DE PAGAMENTO
							}
						}
					}
					
					elseif($cod_movimento == '09'){ // Baixa
						$han_tipo_divergencia = '2'; // BAIXADA
					}
					
					elseif($cod_movimento == '02'){
						$han_tipo_divergencia = '7'; // ENTRADA
						
						$sql9 = "UPDATE fin_fatura SET flg_registrado_banco = 'S' WHERE handle = '".$fatura."'";
						//echo $sql9 . "<br><br>";
						mysqli_query($conn, $sql9);
					}
					
					elseif($cod_movimento == '12'){
						$han_tipo_divergencia = '8'; // ABATIMENTO
					}
					
					elseif($cod_movimento == '14'){
						$han_tipo_divergencia = '9'; // PRORROGAÇÃO DE VENCIMENTO
					}
					
					elseif($cod_movimento == '03'){
						$han_tipo_divergencia = '10'; // ENTRADA REJEITADA
					}
					
					elseif($cod_movimento == '11'){
						$han_tipo_divergencia = '11'; // EM CARTEIRA
					}
					
					elseif($cod_movimento == '26'){
						$han_tipo_divergencia = '12'; // INSTRUÇÃO REJEITADA
					}
					
					elseif($cod_movimento == '30'){
						$han_tipo_divergencia = '13'; // ALTERAÇÃO REJEITADA
					}
					
					else{
						$han_tipo_divergencia = '5'; // OUTROS
					}
					
		
	
					$sql4 = "INSERT INTO ret_retorno_processado (han_retorno, han_franquia, han_cliente, cliente, fatura, digito, dat_vencimento, dat_pagamento, dat_credito, val_tarifa, val_titulo, val_pago, cod_movimento, han_tipo_divergencia) 
					VALUES ('" . $han_retorno . "', '" . $han_franquia . "', '" . $han_cliente . "', '" . rtrim($cliente) . "', '" . $fatura . "', '" . $digito . "', '" . $dat_vencimento . "', '" . $dat_pagamento . "', '" . $dat_credito . "', '" . $val_tarifa . "', '" . $val_titulo . "', '" . $val_pago . "', '".$cod_movimento."', '" . $han_tipo_divergencia . "')";
					//echo $sql4 . "<br>";
					
					
					
					$incluido = mysqli_query($conn, $sql4);
					echo $incluido.'<br>';
					print_r($sql4);
					echo '<br>';
					echo '<br>';
					
					
					
					
					$han_retorno_processado = mysqli_insert_id($conn);
					
				
					
				
					$has_data = $dat_pagamento;
					
	
					$sql5 = "UPDATE ret_retorno_conteudo SET processado = 'S', dat_hor_processado = '" . date('Y-m-d H:i:s') . "' WHERE handle=$id1";
					mysqli_query($conn, $sql5);
	
					$sql6 = "UPDATE ret_retorno_conteudo SET processado = 'S', dat_hor_processado = '" . date('Y-m-d H:i:s') . "' WHERE handle=$id2";
					mysqli_query($conn, $sql6);
	
					//echo $sql4.$sql5.$sql6;
	
					$segue = 0;
					
					$qtd_titulos = $qtd_titulos + 1;
					
					if(!is_numeric($val_pago)){
						$val_pago = 0;
					}
					
					
				}
			}
		}
		
		//echo "\n";
		
	}
	
	if($edit_depois <> ''){
		foreach ($edit_depois as $i => $linha) {
			//echo $linha.'\n';
			mysqli_query($conn, $linha);
		}
	}
	



	$sql7 = "UPDATE ret_retorno SET processamento_completo = 'S' WHERE handle='" . $han_retorno . "'";
	mysqli_query($conn, $sql7);
	
	return $dat_pagamento;

}



function processa_cnab_150($conn, $han_retorno) {

	$sql2 = "SELECT * FROM ret_retorno_conteudo WHERE han_retorno = $han_retorno";
	$result2 = mysqli_query($conn, $sql2);

	$segue = 0;
	foreach ($result2 as $i => $line) {
		if ($i > 0) {

			$linha = $line['conteudo'];
			//echo '<font color="red">'.$linha.'</font>';

			$id1 = $line['handle'];

			$dat_pagamento = modelo_data_invertida(substr($linha, 21, 8));
			$dat_credito = modelo_data_invertida(substr($linha, 29, 8));
			$val_pago = modelo_moeda(substr($linha, 81, 12));
			$val_tarifa = modelo_moeda(substr($linha, 93, 7));

			$cod_bar = substr($linha, 37, 44);

			$fatura = pega_fatura($cod_bar);
			$val_titulo = 0;
			$val_titulo = modelo_moeda(substr($cod_bar, 4, 11));

			if (($fatura <> '        ') AND ($fatura <> '') AND ($fatura <> 0)) {
				
				$cliente 			= '';
				$han_franquia 		= '';
				$han_cliente 		= '';
				$val_original 		= '';
				$pagamento_original = '';
				$dat_vencimento 	= '';		
				
				$sql3 = "SELECT FF.dat_vencimento, CC.nom_cliente, CC.handle, CC.han_franquia, FF.val_fatura, FF.dat_pagamento FROM fin_fatura FF INNER JOIN cad_cliente CC ON FF.han_cliente = CC.handle WHERE FF.handle = $fatura";
				$result3 = mysqli_query($conn, $sql3);
				
				$pagamento_original = '';

				while ($row = mysqli_fetch_assoc($result3)) {
					$cliente 				= $row['nom_cliente'];
					$dat_vencimento 		= $row['dat_vencimento'];
					$han_franquia 			= $row['han_franquia'];
					$han_cliente 			= $row['handle'];
					$val_original 			= $row['val_fatura'];
					$pagamento_original 	= $row['dat_pagamento'];
					$val_titulo				= $row['val_fatura'];
				}
				
				$divergencia = 2;
				
				if($han_cliente == ''){
					$han_tipo_divergencia = '1'; // NÃO LOCALIZADA
					$divergencia = 1;
				}	
				elseif($val_original <> $val_pago){
					$han_tipo_divergencia = '3'; // DIVERGÊNCIA DE PAGAMENTO
					$divergencia = 1;
				}
				elseif($pagamento_original <> ''){
					$han_tipo_divergencia = '4'; // DUPLICIDADE
					$divergencia = 1;
				}
				else{
					$han_tipo_divergencia = '6'; // RECEBIDA
					$divergencia = 2;
				}
				
				if($dat_credito == ' - - '){
					$dat_credito = '';
				}
				
				if($val_tarifa == '.'){
					$val_tarifa = '';
				}
				
				

				$sql4 = "INSERT INTO ret_retorno_processado (han_retorno, han_franquia, han_cliente, cliente, fatura, dat_vencimento, dat_pagamento, dat_credito, val_tarifa, val_titulo, val_juros, val_multa, val_pago, han_tipo_divergencia) 
				VALUES ('" . $han_retorno . "', '" . $han_franquia . "', '" . $han_cliente . "', '" . rtrim($cliente) . "', '" . $fatura . "', '" . $dat_vencimento . "', '" . $dat_pagamento . "', '" . $dat_credito . "', '" . $val_tarifa . "', '" . $val_titulo . "', '" . $val_juros . "', '" . $val_multa . "', '" . $val_pago . "', '" . $han_tipo_divergencia . "')";
				//echo 'CNAB 150<br>';
				//echo $sql4 . "\n";
				$result4 = mysqli_query($conn, $sql4);
				$han_retorno_processado = mysqli_insert_id($conn);

				$dat_pagto = $dat_pagamento;

				if($divergencia == 2){
					// Inclui Juros e Multa cada pagamento depois do dia do vencimento
					if(strtotime($dat_pagto) > strtotime($dat_vencimento)){
						// Inclui Multa
						$dsc_observacao = 'Multa referente a fatura de nr: '.$fatura;
						$val_multa = $val_titulo * 0.02;
						//inclui_movimento($conn, $han_franquia, $han_cliente, '25', 'D', $val_multa, $dsc_observacao, NULL);
						
						// Inclui Juros
						$dias = diasDatas($dat_vencimento, $dat_pagamento);
						$dsc_observacao = 'Juros referente a fatura nr: '.$fatura.'. Atraso de '.$dias.' dias';
						$val_juros = $val_titulo * 0.0025 * $dias;
						$val_juros = round($val_juros, 2);
						//inclui_movimento($conn, $han_franquia, $han_cliente, '24', 'D', $val_juros, $dsc_observacao, NULL);
					}
					else{
						//echo 'Boleto pago em dia '.$fatura."\n";
					}
					
					// Inclui Juros e Multa cada pagamento depois do dia do vencimento
					if($val_pago <> $val_titulo){
						if($val_pago > $val_titulo){
							// Inclui Multa
							$dsc_observacao = 'Cr&eacute;dito referente pagamento a maior da fatura nr: '.$fatura;
							$val_valor = $val_pago - $val_titulo;
							//inclui_movimento($conn, $han_franquia, $han_cliente, '26', 'C', $val_valor, $dsc_observacao, NULL);
						}
						elseif($val_pago < $val_titulo){
							// Inclui Multa
							$dsc_observacao = 'D&eacute;bito ref. recebimento a menor da fatura nr: '.$fatura;
							$val_valor = $val_titulo - $val_pago;
							//inclui_movimento($conn, $han_franquia, $han_cliente, '27', 'D', $val_valor, $dsc_observacao, NULL);
						}
					}
				}

			


				$sql5 = "UPDATE ret_retorno_conteudo SET processado = 'S', dat_hor_processado = '" . date('Y-m-d H:i:s') . "' WHERE handle=$id1";
				mysqli_query($conn, $sql5);
				
				if($divergencia == 1){
					$sql8 = "INSERT INTO ret_divergencias (han_retorno_processado, han_fatura, han_tipo_divergencia) VALUES ('" . $han_retorno_processado . "', '" . $fatura . "', '" . $han_tipo_divergencia . "')";
					//echo $sql8 . "\n";
					$result8 = mysqli_query($conn, $sql8);
				}
				else{
					
					//$sql9 = "UPDATE fin_fatura SET dat_pagamento = '".$dat_pagamento."', val_pago = '".$val_pago."', val_tarifa_bancaria = '".$val_tarifa."', han_retorno_auto = '".$han_retorno."' WHERE handle = '".$fatura."'";
					
					//echo $sql9 . "\n";
					//mysqli_query($conn, $sql9);
					
				}
			}
		}

		//echo "\n";

	}

	
	

	
	$sql7 = "UPDATE ret_retorno SET processamento_completo = 'S' WHERE handle='" . $han_retorno . "'";
	mysqli_query($conn, $sql7);
	
	//echo $dat_pagto."\n";
	return $dat_pagto;

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
