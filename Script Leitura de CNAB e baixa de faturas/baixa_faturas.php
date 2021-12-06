<style>
	table{
		border-collapse: collapse;
	}
	
	table tr td{
		border: 1px solid #000;
		padding:10px;
	}
</style>

<?php
/* -----------------------------------------------------------------------------------------------------
 RETORNO BANCÁRIO
 DATA: 13/02/2020
 POR: Cláudia C. Ferreira
 ULTIMA VERSÃO: 28/10/2021
 ----------------------------------------------------------------------------------------------------- */
date_default_timezone_set('America/Sao_Paulo');



$raiz = 'cron/';

$processa = 1;

require $raiz.'funcoes.php';
echo '<pre>';

$sql = "SELECT handle FROM ret_retorno WHERE data_final_processamento IS NULL";

$result = mysqli_query($conn, $sql);

$qtd_titulos_nao_localizados = 0;
$qtd_titulos_pg_divergencia = 0;
$qtd_titulos_duplicidade = 0;
$qtd_titulos_baixados = 0;

$envia_email = 0;

if($processa == 1){
	echo '<h2>MODO PROCESSAR</h2>';
}
else{
	echo '<h2>MODO TESTE</h2>';
}


foreach ($result as $i => $line) {
	
	$saida .= '<table>';
		$saida .= '<td><b>FATURA</b></td>';
		$saida .= '<td><b>FRANQ<b></td>';
		$saida .= '<td><b>CLIENTE<b></td>';
		$saida .= '<td><b>VALOR<b></td>';
		$saida .= '<td><b>PAGO<b></td>';
		$saida .= '<td><b>VENCTO<b></td>';
		$saida .= '<td><b>PAGTO<b></td>';
		$saida .= '<td><b>MOV<b></td>';
		$saida .= '<td><b>DIVERGÊNCIA<b></td>';
		$saida .= '<td><b>MOVIMENTO<b></td>';
		$saida .= '<td><b>SITUAÇÃO<b></td>';
		$saida .= '<td><b>DUPLICIDADE<b></td>';
	
		$total_tarifa = 0;
		$val_titulos = 0;
		$dat_credito_sfin = '';		
		
		$han_retorno = $line['handle'];

		$sql2 = "SELECT RP.handle, RP.han_franquia, RP.han_cliente, RP.cliente, RP.fatura, RP.digito, RP.dat_vencimento, RP.dat_pagamento, RP.dat_credito, RP.val_tarifa, RP.val_titulo, RP.val_pago, RP.cod_movimento, RP.han_tipo_divergencia, D.divergencia
		FROM ret_retorno_processado RP
		LEFT JOIN ret_tipo_divergencia D ON RP.han_tipo_divergencia = D.handle
		WHERE RP.han_retorno = ".$han_retorno."
		ORDER BY RP.han_tipo_divergencia ASC";
		$result2 = mysqli_query($conn, $sql2);

		foreach ($result2 as $i2 => $line2) {
			
			$dat_ja_pago = '';
			$saida .= '<tr>';
			
			
			$han_retorno_processado = $line2['handle'];
			$han_franquia 			= $line2['han_franquia'];
			$han_cliente 			= $line2['han_cliente'];
			$cliente 				= $line2['cliente'];
			$fatura 				= $line2['fatura'];
			$digito 				= $line2['digito'];
			$dat_vencimento 		= $line2['dat_vencimento'];
			$dat_pagamento 			= $line2['dat_pagamento'];
			$dat_credito 			= $line2['dat_credito'];
			$val_tarifa 			= $line2['val_tarifa'];
			$val_titulo 			= $line2['val_titulo'];
			$val_pago 				= $line2['val_pago'];
			$cod_movimento 			= $line2['cod_movimento'];
			$han_tipo_divergencia 	= $line2['han_tipo_divergencia'];
			$divergencia			= $line2['divergencia'];
			
			$total_tarifa 			= $total_tarifa + $val_tarifa;
			$val_titulos 			= $val_titulos + $val_pago;
			
			
			if(($dat_credito <> '0000-00-00') AND ($dat_credito <> '')){
				$dat_credito_sfin = $dat_credito;
			}
			if($cod_movimento == '06'){
				if(strtotime($dat_pagamento) > strtotime($dat_vencimento)){
					$cor = '#F6CECE';
				}
				else{
					$cor = '#FFF';
				}
			}
			else{
				$cor = '#FFF';
			}
			
			$saida .= '<td style="background-color:'.$cor.'">'.$fatura.'-'.$digito.'</td>';
			$saida .= '<td style="background-color:'.$cor.'">'.$han_franquia.'</td>';
			$saida .= '<td style="background-color:'.$cor.'">'.$cliente.'</td>';
			$saida .= '<td style="background-color:'.$cor.'">'.$val_titulo.'</td>';
			$saida .= '<td style="background-color:'.$cor.'">'.$val_pago.'</td>';
			$saida .= '<td style="background-color:'.$cor.'">'.pdata($dat_vencimento).'</td>';
			$saida .= '<td style="background-color:'.$cor.'">'.pdata($dat_pagamento).'</td>';
			$saida .= '<td style="background-color:'.$cor.'">'.$cod_movimento.'</td>';
			$saida .= '<td style="background-color:'.$cor.'">'.$han_tipo_divergencia.' - '.$divergencia.'</td>';
			
			
			if($line2['han_tipo_divergencia'] == 1){ // NÃO LOCALIZADA 
				$qtd_titulos_nao_localizados = grava_divergencia($conn, $qtd_titulos_nao_localizados, $han_retorno_processado, $fatura, 1, $processa);
				grava_situacao($conn, $fatura, $cod_movimento, 9, $han_retorno_processado, $processa);
				$saida .= '<td></td>';
				$saida .= '<td>grava_situacao 9</td>';
			}
			
			elseif($line2['han_tipo_divergencia'] == 2){ // BAIXADA
				$qtd_titulos_baixados = grava_divergencia($conn, $qtd_titulos_baixados, $han_retorno_processado, $fatura, 2, $processa);
				grava_situacao($conn, $fatura, $cod_movimento, 11, $han_retorno_processado, $processa);
				$saida .= '<td></td>';
				$saida .= '<td>grava_situacao 11</td>';
			}
			
			elseif($line2['han_tipo_divergencia'] == 4){ // DUPLICIDADE
				$qtd_titulos_duplicidade = grava_divergencia($conn, $qtd_titulos_duplicidade, $han_retorno_processado, $fatura, 4, $processa);
				grava_situacao($conn, $fatura, $cod_movimento, 7, $han_retorno_processado, $processa);
				$saida .= '<td></td>';
				$saida .= '<td>grava_situacao 7</td>';
			}
			
			elseif(($line2['han_tipo_divergencia'] == 6) OR ($line2['han_tipo_divergencia'] == 14) OR ($line2['han_tipo_divergencia'] == 15)){ // RECEBIDA - RECEBIDA A MAIOR - RECEBIDA A MENOR
				
				$sql3 = "SELECT dat_vencimento, val_fatura, han_carne, val_multa_antecipado, dat_pagamento FROM fin_fatura WHERE handle = ".$fatura;
				$result3 = mysqli_query($conn, $sql3);
				while ($row = mysqli_fetch_assoc($result3)) {
					$han_carne 					= $row['han_carne'];
					$val_original 				= $row['val_fatura'];
					$val_multa_antecipado 		= round($row['val_multa_antecipado'], 2);
					$dat_vencimento_original 	= $row['dat_vencimento'];
					$dat_ja_pago			 	= $row['dat_pagamento'];
				}
				
	
				
				// INCLUI MOVIMENTOS DE CARNÊ -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
				if($han_carne > 0){
					
					
					$diff_maior = 0;
					$diff_menor = 0;
					
					if($line2['han_tipo_divergencia'] == 14){ // RECEBIDA A MAIOR
						$obs_atraso = '';
						$dias_atraso = diasDatas($dat_vencimento_original, $dat_pagamento);
						if(($dias_atraso > 0) AND ($val_pago > $val_original)){
							$obs_atraso = 'Atraso: '.$dias_atraso.' dias';
						}
					
						//echo '<b>#CARNE: '.$han_carne.' * '.$dias_atraso.' * '.$val_pago.' * '.$dat_vencimento_original.'</b><br>';
						
						if($val_pago > ($val_original + $val_multa_antecipado)){
							$juros = $val_pago - $val_original - $val_multa_antecipado;
							$juros = round($juros, 2);	
							// Inclui movimento de Juros
							$dsc_observacao = "Juros ref. a fatura ".$fatura.". ".$obs_atraso;
							inclui_movimento($conn, $han_franquia, $han_cliente, '73', 'D', $juros, $dsc_observacao, $fatura, $processa);
							
							// Inclui movimento de Multa
							$dsc_observacao = "Multa: atraso de pagto ref. fatura ".$fatura." ".$obs_atraso;
							inclui_movimento($conn, $han_franquia, $han_cliente, '74', 'D', $val_multa_antecipado, $dsc_observacao, $fatura, $processa);
							altera_movimento($conn, $val_pago, $fatura, $processa);
							
				
							
							$saida .= "<td>* Juros de ".$juros." e Multa de ".$val_multa_antecipado." ref. a fatura ".$fatura.". ".$obs_atraso."</td>";

						}
						elseif($val_pago == ($val_original + $val_multa_antecipado)){
							
							// Inclui movimento de Multa
							$dsc_observacao = "Multa atraso de pagto ref. fatura ".$fatura." ".$obs_atraso;
							inclui_movimento($conn, $han_franquia, $han_cliente, '74', 'D', $val_multa_antecipado, $dsc_observacao, $fatura, $processa);
							altera_movimento($conn, $val_pago, $fatura, $processa);
							
							$saida .= "<td>* Multa de ".$val_multa_antecipado." ref. a fatura ".$fatura.". ".$obs_atraso."</td>";
						}
						else{
							$juros = $val_pago - $val_original;
							$juros = round($juros, 2);
							
							// Inclui movimento de Juros
							$dsc_observacao = "Juros ref. a fatura ".$fatura.". ".$obs_atraso;
							inclui_movimento($conn, $han_franquia, $han_cliente, '73', 'D', $juros, $dsc_observacao, $fatura, $processa);
							altera_movimento($conn, $val_pago, $fatura, $processa);
							
							$saida .= "<td>* Juros de ".$juros." ref. a fatura ".$fatura.". ".$obs_atraso."</td>";
						}
						
					}
					elseif($line2['han_tipo_divergencia'] == 15){ // RECEBIDA A MENOR
						$diff_menor = $val_original - $val_pago;
						// Inclui movimento de diferença de pagamento
						$dsc_observacao = "Recebimento a menor ref. a fatura ".$fatura." ";
						inclui_movimento($conn, $han_franquia, $han_cliente, '27', 'D', $diff_menor, $dsc_observacao, '', $processa);
						
						$saida .= "<td>Recebimento de ".$diff_menor." a menor ref. a fatura ".$fatura." </td>";
					}
					else{
						$saida .= '<td></td>';
					}
					
				}
				
				
				// INCLUI MOVIMENTOS DE FATURA -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
				else{
					
					$diff_maior = 0;
					$diff_menor = 0;
					//echo '<br><b>#FATURA: pago '.$val_pago.' * '.$val_titulo.'</b><br>';
					
					if($line2['han_tipo_divergencia'] == 14){ // RECEBIDA A MAIOR
						$diff_maior = $val_pago - $val_original;
						// Inclui movimento de diferença de pagamento
						$dsc_observacao = 'Cr&eacute;dito ref. pagamento a maior da fatura '.$fatura;
						inclui_movimento($conn, $han_franquia, $han_cliente, '26', 'C', $diff_maior, $dsc_observacao, '', $processa);
						
						$saida .= "<td>Cr&eacute;dito de ".$diff_maior." ref. pagamento a maior da fatura ".$fatura."</td>";
					}
					
					elseif($line2['han_tipo_divergencia'] == 15){ // RECEBIDA A MENOR
						$diff_menor = $val_original - $val_pago;
						// Inclui movimento de diferença de pagamento
						$dsc_observacao = 'D&eacute;bito ref. recebimento a menor da fatura '.$fatura;
						inclui_movimento($conn, $han_franquia, $han_cliente, '27', 'D', $diff_menor, $dsc_observacao, '', $processa);
						
						$saida .= "<td>D&eacute;bito de ".$diff_menor." ref. recebimento a menor da fatura ".$fatura."</td>";
					}
					
					else{
						// Inclui Juros e Multa cada pagamento depois do dia do vencimento
						if(strtotime($dat_pagamento) > strtotime($dat_vencimento)){ // Boleto vencido
							//echo 'Atraso'.$dat_pagamento.' - '.$dat_vencimento.'<br>';
							// Inclui Multa
							$dsc_observacao = 'Multa ref. a fatura de '.$fatura;
							$val_multa = $val_titulo * 0.02;
							$val_multa = round($val_multa, 2);	
							inclui_movimento($conn, $han_franquia, $han_cliente, '25', 'D', $val_multa, $dsc_observacao, '', $processa);
							
							
							// Inclui Juros
							$dias = diasDatas($dat_vencimento, $dat_pagamento);
							$dsc_observacao = 'Juros ref. a fatura '.$fatura.'. Atraso de '.$dias.' dias';
							$val_juros = $val_titulo * 0.0025 * $dias;
							$val_juros = round($val_juros, 2);							
							inclui_movimento($conn, $han_franquia, $han_cliente, '24', 'D', $val_juros, $dsc_observacao, '', $processa);
							
						
							
							
							$saida .= "<td>Juros de ".$val_juros." e Multa de ".$val_multa." ref. a fatura ".$fatura." Atraso de ".$dias." dias</td>";
						}
						else{
							//echo 'Boleto pago em dia '.$fatura."\n";
							$saida .= "<td>Em dia</td>";
						}
					}
				}
				
				
				
			
				if($processa == 1){
					$sql9 = "UPDATE fin_fatura SET dat_pagamento = '".$dat_pagamento."',  val_pago = '".$val_pago."', val_tarifa_bancaria = '".$val_tarifa."', han_retorno_auto = '".$han_retorno."' WHERE handle = '".$fatura."'";
					mysqli_query($conn, $sql9);
					//echo $sql9 . "<br><br>";
				}

				grava_situacao($conn, $fatura, $cod_movimento, 6, $han_retorno_processado, $processa);
				
				

				$saida .= '<td>grava_situacao 6</td>';
				
			}
			
			elseif($line2['han_tipo_divergencia'] == 7){ // ENTRADA
				grava_situacao($conn, $fatura, $cod_movimento, 5, $han_retorno_processado, $processa);
				$saida .= '<td></td>';
				$saida .= '<td>grava_situacao 5</td>';
			}
			
			elseif($line2['han_tipo_divergencia'] == 8){ // ABATIMENTO	
				grava_situacao($conn, $fatura, $cod_movimento, 12, $han_retorno_processado, $processa);
				$saida .= '<td></td>';
				$saida .= '<td>grava_situacao 8</td>';
			}
			
			elseif($line2['han_tipo_divergencia'] == 9){ // PRORROGAÇÃO DE VENCIMENTO
				grava_situacao($conn, $fatura, $cod_movimento, 13, $han_retorno_processado, $processa);
				$saida .= '<td></td>';
				$saida .= '<td>grava_situacao 13</td>';
			}
			
			elseif($line2['han_tipo_divergencia'] == 10){ // ENTRADA REJEITADA
				grava_situacao($conn, $fatura, $cod_movimento, 10, $han_retorno_processado, $processa);
				$saida .= '<td></td>';
				$saida .= '<td>grava_situacao 10</td>';
			}
			
			elseif($line2['han_tipo_divergencia'] == 13){ // ALTERAÇÃO REJEITADA
				grava_situacao($conn, $fatura, $cod_movimento, 16, $han_retorno_processado, $processa);
				$saida .= '<td></td>';
				$saida .= '<td>grava_situacao 16</td>';
			}
			
			else{
				grava_situacao($conn, $fatura, $cod_movimento, 100, $han_retorno_processado, $processa);
				$saida .= '<td></td>';
				$saida .= '<td>grava_situacao 100</td>';
			}		
			$saida .= '<td>'.$dat_ja_pago.'</td>';

			$saida .= '</tr>';
		
		}
		

		lanca_sfin($conn, $han_retorno);
		
		if($processa == 1){
			$sql7 = "UPDATE ret_retorno SET data_final_processamento='" . date("Y-m-d H:i:s") . "', lancamento_sfin = 'S' WHERE handle='" . $han_retorno . "'";
			mysqli_query($conn, $sql7);
		}
		
		$envia_email = 1;
		
		
	
	
	$saida .= '</table>';
	
	$saida .= '<br><br>';

}


if($envia_email == 1){

	if (($dat_pagamento == '0000-00-00') OR ($dat_pagamento == '') OR ($dat_pagamento == '0')){
		$dat_pagamento = date('Y-m-d', strtotime('-1 days'));
	}
	if($processa == 1){
		$sql15 = "INSERT INTO ret_envia_resumo (data_retorno) VALUES ('".$dat_pagamento."')";
		mysqli_query($conn, $sql15);
		//echo $sql15."\n";
	}
	
	
	echo $saida;



	$mensagem = 'Olá, bom dia!<br><br> O retorno bancário do dia '.pdata($dat_pagamento).' acabou de ser processado!';
	
	echo $mensagem;


	envia_email($mensagem, '['.date('d/m/Y', strtotime('-1 days')).'] RETORNO - Processado', '[e-mail]', $cc);

}


















function grava_divergencia($conn, $cont, $han_retorno_processado, $fatura, $han_tipo_divergencia, $processa){
	if($processa == 1){
		$sql8 = "INSERT INTO ret_divergencias (han_retorno_processado, han_fatura, han_tipo_divergencia) VALUES ('" . $han_retorno_processado . "', '" . $fatura . "', '" . $han_tipo_divergencia . "')";
		$result8 = mysqli_query($conn, $sql8);
		//echo $sql8 . "<BR>";
	}
	return $cont+1;
}

function grava_situacao($conn, $fatura, $cod_movimento, $situacao, $han_retorno_processado = NULL, $processa){
	if($processa == 1){
		$sql18 = "INSERT INTO fin_fatura_situacao (han_fatura, han_situacao, cod_movimento) VALUES ('" . $fatura . "', '" . $situacao . "', '".$cod_movimento."')";
		mysqli_query($conn, $sql18);
		//echo $sql18 . "<BR>";
	}
}



function lanca_sfin($conn, $han_retorno){
	
	// Pega o último número de retorno
	$sql16 = "SELECT NUM_RETORNO FROM fin_retorno ORDER BY HANDLE DESC LIMIT 1";
	$result6 = mysqli_query($conn, $sql16);
	foreach ($result6 as $i => $line) {
		$num_retorno = $line['NUM_RETORNO'];
	}

	// Pega dados do banco
	$sql1 = "SELECT dado_agencia, dado_banco, dado_conta FROM ret_retorno WHERE handle = ".$han_retorno;
	$result1 = mysqli_query($conn, $sql1);
	foreach ($result1 as $j => $line) {
		$dado_agencia 	= $line['dado_agencia'];
		$dado_banco 	= $line['dado_banco'];
		$dado_conta 	= $line['dado_conta'];
		
		$num_retorno = $num_retorno + 1;
		
		// Lança no movimento do SFIN
		$dado_conta_padrao = modelo_conta($dado_conta);
		
		$sql2 = "SELECT handle FROM cad_conta_bancaria WHERE num_conta = '".$dado_conta_padrao."' LIMIT 1";
		//echo $sql2. "\n";
		$result2 = mysqli_query($conn, $sql2);

		foreach ($result2 as $i => $val) {
			$han_conta_bancaria = $val['handle'];
		}
		
		$sql3 = '
			SELECT
			R.dado_conta,
			SUM(RP.val_pago) AS som_pago,
			RP.dat_credito,
			COUNT(RP.handle) AS qtd_titulos,
			SUM(RP.val_tarifa) AS val_tarifa
			FROM ret_retorno_processado RP
			INNER JOIN ret_retorno R ON R.handle = RP.han_retorno
			LEFT JOIN cad_franquia F ON F.handle = RP.han_franquia
			WHERE RP.han_retorno = '.$han_retorno.'
			AND RP.cod_movimento = "06"
			GROUP BY R.dado_conta
		';
		//echo $sql3;
	
		$result3 = mysqli_query($conn, $sql3);
		foreach ($result3 as $i => $val) {
			
			if($val['som_pago'] > 0){
				$sql4 = "INSERT INTO fin_movimento (val_lancamento, dat_pagamento, flg_forma_pagamento, flg_tipo_lancamento, han_conta_bancaria, dsc_movimento, han_sub_plano, han_centro_custo, tipo_inclusao) VALUES
				('".$val['som_pago']."', '".$val['dat_credito']."', 'DC', 'C', '".$han_conta_bancaria."', 'PA - RETORNO - ".$val['qtd_titulos']." TITULOS - BANCO: ".$dado_banco." AG: ".$dado_agencia." CC: ".$dado_conta."', '72', '6', 'M')";
				mysqli_query($conn, $sql4);
				$result = $sql4 . " <br>";
			}
			
			if($val['val_tarifa'] > 0){
				$sql5 = "INSERT INTO fin_movimento (val_lancamento, dat_pagamento, flg_forma_pagamento, flg_tipo_lancamento, han_conta_bancaria, dsc_movimento, han_sub_plano, han_centro_custo, tipo_inclusao) VALUES
				('".$val['val_tarifa']."', '".$val['dat_credito']."', 'DC', 'D', '".$han_conta_bancaria."', 'PA - TARIFA REF. RETORNO - ".$qtd_titulos." TITULOS - BANCO: ".$dado_banco." AG: ".$dado_agencia." CC: ".$dado_conta."', '73', '6', 'M')";
				mysqli_query($conn, $sql5);
				$result .= $sql5 . "\n";
			}
		}
	}
	
	return $result;
	
}


function inclui_movimento($conn, $han_franquia, $han_cliente, $evento, $flg_dc, $val, $dsc_observacao, $han_fatura = NULL, $processa){
	if($processa == 1){
		if($han_fatura > 0){
			$sql = "INSERT INTO fin_movimentos (han_franquia, han_cliente, dat_lancamento, dat_referencia, dat_vencimento, han_evento, num_doc, flg_dc, val_lancamento, han_fatura, dsc_observacao, flg_origem) 
			VALUES ('".$han_franquia."', '".$han_cliente."', '".date('Y-m-d')."', '".date('Y-m-d')."', '".date('Y-m-d')."', '".$evento."', ".$han_fatura.", '".$flg_dc."', '".$val."', ".$han_fatura.",  '".$dsc_observacao."', 'A')";
		}
		else{
			$sql = "INSERT INTO fin_movimentos (han_franquia, han_cliente, dat_lancamento, dat_referencia, dat_vencimento, han_evento, flg_dc, val_lancamento, dsc_observacao, flg_origem) 
			VALUES ('".$han_franquia."', '".$han_cliente."', '".date('Y-m-d')."', '".date('Y-m-d')."', '".date('Y-m-d')."', '".$evento."', '".$flg_dc."', '".$val."', '".$dsc_observacao."', 'A')";
		}
		$result1 = mysqli_query($conn, $sql);
		return $sql;
	}
}

function altera_movimento($conn, $val_pago, $fatura, $processa){
	if($processa == 1){
		$sql = "UPDATE fin_movimentos SET val_lancamento = '".$val_pago."' WHERE han_fatura = '".$fatura."' AND han_evento = 21";
		$result = mysqli_query($conn, $sql);
		//echo $sql . "<br>";
	}
}



function diasDatas($data_inicial, $data_final) {
	$diferenca = strtotime($data_final) - strtotime($data_inicial);
	$dias = floor($diferenca / (60 * 60 * 24)); 
	return $dias;
}
	

?>
