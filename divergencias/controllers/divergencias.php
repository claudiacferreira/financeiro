<?php

class Divergencias extends CI_Controller {

    function __construct() {
        parent::__construct();

        // carrega modelo - banco de dados;
        $this->load->model('divergencias_model', 'bd');
        $this->lang->load("divergencias");

        $this->load->helper('date');
        // função para montar título
        $this->template->write('titulo', create_breadcrumb());
    }

    function index() {
	
	}
	
	
	
	
	/* DUPLICIDADE -------------------------------------------------------------------------------------------------------------------------------------------------------- */
	
	function divergencias_duplicidade() {
    	
        // carrega tabela
        $this->template->add_js("asset/datatables/datatables.min.js");
		$this->template->add_css("asset/datatables/datatables.min.css");
		$this->template->add_css("asset/datatables/ajustes.css");
        $this->load->library('geradatatables');
		$table = new Geradatatables();
		$itens = array("<i class='fa fa-check'></i>","<i class='fa fa-search'></i>", "Fatura", "Cliente", "Franquia", "Vencimento", "Pagamento", "Valor Fatura", "Valor Pago", "Divergência");
		$html = $table->html("Listagem das Faturas com Divergência - DUPLICIDADE", "lista_faturas", $itens);
		$this->template->write('conteudo', $html);
        $this->template->add_js($this->config->item("js") . "jquery.lista_divergencias_duplicidade.js");
		
        $modal = '
		<div class="modal fade in" id="detalhes_cliente" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">
							<div style="width:500px;float:left;">
								<i class="fa fa-usd" style="color:#ccc"></i> &nbsp;
								Divergência - fatura: 
								<span id="id_fat"><span>
							</div>
							<div style="width:20px;float:right;color:#FFF;">
								<button type="button" class="btn btn-link" onclick="fecha_modal();" style="color:#FFF;">X</button>
							</div>
						</h4>
					</div>
					<div class="modal-body" id="dados_esta_fatura">
	
					</div>
					<div class="modal-footer" style="text-align:center;display:none;" id="btn_processa">
						<button type="button" class="btn btn-primary"  onclick="processa_duplicidade();">
							<i class="fa fa-check"></i> Confirma solução?
						</button>
						<input type="hidden" name="han_fatura" id="han_fat">
						<input type="hidden" name="id" id="handle_id">
						</form>
						
						<button type="button" class="btn btn-warning" onclick="fecha_modal();">
							<i class="fa fa-check"></i> Cancelar
						</button>
					</div>
				</div>
				
				
			</div>
		</div>';

        $this->template->write('conteudo', $modal);
        $this->template->render();
    }
	
	function lista_divergencias_duplicidade($acao = FALSE, $filtros = array()) {
        $faturas = $this->bd->busca_divergencias_duplicidade();
		
		//echo '<pre>'; echo $id; print_r($dados);
		
		foreach ($faturas as $i => $fatura) {
			if($fatura['solucionado'] == 'A'){
				$cor_botao = 'orange';
				$label_botao = 'Aguardando solução';		
			}
			else{
				$cor_botao = 'red';
				$label_botao = 'Solucionado?';
			}
			
        	$faturas[$i]['botoes'] = "<span class='alinhaicones'><a href='' onclick='abremodal2(".$fatura['fatura'].", ".$fatura['handle'].",".$fatura['han_tipo_divergencia'].");' title='".$label_botao."' style='color:".$cor_botao.";margin-left:3px'><i class='fa fa-warning' ></i></a></span>";
        }

        $json = '{"data":[';
        foreach ($faturas as $i => $fatura) {
            
            $virgula = ($i > 0) ? "," : "";
            $link_visualizacao = "<a title='Visualizar fechamento' href='$acao/{$fechamento['handle']}'><i class='fa fa-search txt-color-green'></i>";
            
            $json .= $virgula . '{"handle":"' . str_pad($fatura['fatura'],5,"0",STR_PAD_LEFT) . '",'
            . '"botoes":"' 			. $fatura['botoes'] . '",'
            . '"fatura":"' 			. $fatura['fatura'] . '",'
            . '"cliente":"' 		. $fatura['cliente'] . '",'
            . '"franquia":"' 		. $fatura['franquia'] . '",'
        	. '"dat_vencimento":"' 	. $this->pdata($fatura['dat_vencimento']) . '",'
        	. '"dat_pagamento":"' 	. $this->pdata($fatura['dat_pagamento']) . '",'
        	. '"val_fatura":"' 		. number_format($fatura['val_fatura'], 2, ',', '.') . '",'
        	. '"val_pago":"' 		. number_format($fatura['val_pago'], 2, ',', '.') . '",'
            . '"divergencia":"' 	. $fatura['divergencia'] . '"'
            . '}';
        }
		
        
        $json .=']}';
        
        $style = "style='width:100%; text-align:right; float:right;'";
        echo $json;
        
    }
	
	function liquida_fatura(){
		$dados = $this->input->post();
		
		//print_r($dados);
		
		$faturas = $this->bd->busca_dados_fatura($dados['fatura']);
		//print_r($faturas);
		foreach ($faturas as $i => $fatura) {
			
			$han_franquia 		= $fatura['han_franquia'];
			$han_cliente 		= $fatura['han_cliente'];
			$val_pago			= $fatura['val_pago'];
			$dat_pagamento		= $fatura['dat_pagamento'];
			$val_tarifa			= $fatura['val_tarifa'];
			$retorno			= $fatura['retorno'];
			
			if($fatura['carne'] > ''){
				$evento_maior = 77;
				$evento_menor = 78;
			}
			else{
				$evento_maior = 26;
				$evento_menor = 27;
			}
		}
		
		if($dados['modo'] == 'movimento'){
			
			
			if($fatura['carne'] == 'Sim'){
				$prox_fatura = $this->bd->busca_proxima_fatura_aberto($han_cliente, $dados['fatura']);
				if($prox_fatura['fatura'] != ''){
					if($val_pago < $prox_fatura['val_fatura']){
						$diferenca = $prox_fatura['val_fatura'] - $val_pago;
					
						/* Cria movimento de desconto */
						$mov_gravar['han_franquia'] = $han_franquia;
						$mov_gravar['han_cliente'] = $han_cliente;
						$mov_gravar['dat_lancamento'] = date('Y-m-d');
						$mov_gravar['dat_referencia'] = date('Y-m-d');
						$mov_gravar['dat_vencimento'] = date('Y-m-d');
						$mov_gravar['han_evento'] = $evento_maior;
						$mov_gravar['num_doc'] = date('Ym');
						$mov_gravar['flg_dc'] = 'C';
						$mov_gravar['val_lancamento'] = $val_pago;
						$mov_gravar['han_fatura'] = $prox_fatura['fatura'];
						$mov_gravar['dsc_observacao'] = "Desconto devido à duplicidade fat: ".$dados['fatura'];
						$mov_gravar['flg_origem'] = 'A';
						$this->bd->tabela = "fin_movimentos";
						$processa_bd = $this->bd->inserir($mov_gravar);
	        
						echo 'A'; print_r($mov_gravar);
						
						
						/* Da desconto na fatura */
						$fat_desc['val_fatura'] = $diferenca;
						$fat_desc['flg_status_remessa'] = 'AL';
						$fat_desc['flg_acao_remessa'] = 'DE';
						
						$this->bd->tabela = "fin_fatura";
						$this->bd->alterar($prox_fatura['fatura'], $fat_desc);
						
						echo 'B'; print_r($fat_desc);
						
						
						
					}
					elseif($val_pago == $prox_fatura['val_fatura']){
						
						/* Liquida próxima fatura */
						$fat_altera['dat_pagamento'] = $dat_pagamento;
						$fat_altera['val_pago'] = $prox_fatura['val_fatura'];
						$fat_altera['val_tarifa_bancaria'] = $val_tarifa;
						$fat_altera['han_retorno_auto'] = $retorno;
						$fat_altera['fatura_duplicidade'] = $dados['fatura'];
						
						$this->bd->tabela = "fin_fatura";
						$this->bd->alterar($prox_fatura['fatura'], $fat_altera);
						
						echo 'C'; print_r($fat_altera);
					}
					else{
						$diferenca = $val_pago - $prox_fatura['val_fatura'];
						
						/* Liquida próxima fatura */
						$fat_altera['dat_pagamento'] = $dat_pagamento;
						$fat_altera['val_pago'] = $prox_fatura['val_fatura'];
						$fat_altera['val_tarifa_bancaria'] = $val_tarifa;
						$fat_altera['han_retorno_auto'] = $retorno;
						$fat_altera['fatura_duplicidade'] = $dados['fatura'];
						
						$this->bd->tabela = "fin_fatura";
						$this->bd->alterar($prox_fatura['fatura'], $fat_altera);
						
						echo 'D'; print_r($fat_altera);
						
						
						$diferenca = round($diferenca,2);
					
						if($diferenca < 0){
							$diferenca = $diferenca * -1;
						}
						$prox_fatura2 = $this->bd->busca_proxima_fatura_aberto($han_cliente, $prox_fatura['fatura']);
						if($prox_fatura2['fatura'] != ''){
						
							/* Cria movimento de desconto */
							$mov_gravar['han_franquia'] = $han_franquia;
							$mov_gravar['han_cliente'] = $han_cliente;
							$mov_gravar['dat_lancamento'] = date('Y-m-d');
							$mov_gravar['dat_referencia'] = date('Y-m-d');
							$mov_gravar['dat_vencimento'] = date('Y-m-d');
							$mov_gravar['han_evento'] = $evento_maior;
							$mov_gravar['num_doc'] = date('Ym');
							$mov_gravar['flg_dc'] = 'C';
							$mov_gravar['val_lancamento'] = $diferenca;
							$mov_gravar['han_fatura'] = $prox_fatura2['fatura'];
							$mov_gravar['dsc_observacao'] = "Desconto devido à duplicidade fat: ".$prox_fatura['fatura'];
							$mov_gravar['flg_origem'] = 'A';
							
							$this->bd->tabela = "fin_movimentos";
							$processa_bd = $this->bd->inserir($mov_gravar);
				
							echo 'E'; print_r($mov_gravar);
							
							
							
							$diferenca = $prox_fatura2['val_fatura'] - $diferenca;
	
							
							/* Da desconto na fatura */
							$fat_desc['val_fatura'] = $diferenca;
							$fat_desc['flg_status_remessa'] = 'AL';
							$fat_desc['flg_acao_remessa'] = 'DE';
							
							$this->bd->tabela = "fin_fatura";
							$this->bd->alterar($prox_fatura2['fatura'], $fat_desc);
							
							echo 'F'; print_r($fat_desc);
						}
						else{
							
							/* Movimento de recebimento a maior */
							$mov_gravar['han_franquia'] = $han_franquia;
							$mov_gravar['han_cliente'] = $han_cliente;
							$mov_gravar['dat_lancamento'] = date('Y-m-d');
							$mov_gravar['dat_referencia'] = date('Y-m-d');
							$mov_gravar['dat_vencimento'] = date('Y-m-d');
							$mov_gravar['han_evento'] = $evento_maior;
							$mov_gravar['num_doc'] = date('Ym');
							$mov_gravar['flg_dc'] = 'C';
							$mov_gravar['val_lancamento'] = $diferenca;
							$mov_gravar['dsc_observacao'] = "Receb. a maior devido a duplicidade fat: ".$dados['fatura'];
							$mov_gravar['flg_origem'] = 'A';
							
							$this->bd->tabela = "fin_movimentos";
							$processa_bd = $this->bd->inserir($mov_gravar);
				
							echo 'G'; print_r($mov_gravar);
							
							
						}
					}
				}
				else{
					
					/* Movimento de recebimento a maior */
					$mov_gravar['han_franquia'] = $han_franquia;
					$mov_gravar['han_cliente'] = $han_cliente;
					$mov_gravar['dat_lancamento'] = date('Y-m-d');
					$mov_gravar['dat_referencia'] = date('Y-m-d');
					$mov_gravar['dat_vencimento'] = date('Y-m-d');
					$mov_gravar['han_evento'] = $evento_maior;
					$mov_gravar['num_doc'] = date('Ym');
					$mov_gravar['flg_dc'] = 'C';
					$mov_gravar['val_lancamento'] = $val_pago;
					$mov_gravar['dsc_observacao'] = "Receb. a maior devido a duplicidade fat: ".$dados['fatura'];
					$mov_gravar['flg_origem'] = 'A';
					
					$this->bd->tabela = "fin_movimentos";
					$processa_bd = $this->bd->inserir($mov_gravar);
		
					echo 'H'; print_r($mov_gravar);
			
					
				}
			}
			else{
				
				/* Movimento de recebimento a maior */
				$mov_gravar['han_franquia'] = $han_franquia;
				$mov_gravar['han_cliente'] = $han_cliente;
				$mov_gravar['dat_lancamento'] = date('Y-m-d');
				$mov_gravar['dat_referencia'] = date('Y-m-d');
				$mov_gravar['dat_vencimento'] = date('Y-m-d');
				$mov_gravar['han_evento'] = $evento_maior;
				$mov_gravar['num_doc'] = date('Ym');
				$mov_gravar['flg_dc'] = 'C';
				$mov_gravar['val_lancamento'] = $val_pago;
				$mov_gravar['dsc_observacao'] = "Receb. a maior devido a duplicidade fat: ".$dados['fatura'];
				$mov_gravar['flg_origem'] = 'A';
				
				$this->bd->tabela = "fin_movimentos";
				$processa_bd = $this->bd->inserir($mov_gravar);
	
				echo 'I'; print_r($mov_gravar);
				
			}
			
		}
		
		elseif($dados['modo'] == 'fatura'){
			
			
			// Outra Fatura
			$faturas2 = $this->bd->busca_outra_fatura($dados['outra_fatura']);
			//print_r($faturas2);
			foreach ($faturas2 as $i => $fat) {
				$diferenca = $val_pago - $fat['val_fatura'];
			}
			
			echo $diferenca.' = '.$val_pago.' - '.$fat['val_fatura'];
			
			if($diferenca > 0){
				
				/* Movimento de recebimento a maior */
				$mov_gravar['han_franquia'] = $han_franquia;
				$mov_gravar['han_cliente'] = $han_cliente;
				$mov_gravar['dat_lancamento'] = date('Y-m-d');
				$mov_gravar['dat_referencia'] = date('Y-m-d');
				$mov_gravar['dat_vencimento'] = date('Y-m-d');
				$mov_gravar['han_evento'] = $evento_maior;
				$mov_gravar['num_doc'] = date('Ym');
				$mov_gravar['flg_dc'] = 'C';
				$mov_gravar['val_lancamento'] = $diferenca;
				$mov_gravar['dsc_observacao'] = "Receb. a maior devido a duplicidade fat: ".$dados['fatura'];
				$mov_gravar['flg_origem'] = 'A';
				
				$this->bd->tabela = "fin_movimentos";
				$processa_bd = $this->bd->inserir($mov_gravar);
	
				echo 'J'; print_r($mov_gravar);
				
				
				
			}
			elseif($diferenca < 0){
				
				/* Movimento de recebimento a menor */
				$mov_gravar['han_franquia'] = $han_franquia;
				$mov_gravar['han_cliente'] = $han_cliente;
				$mov_gravar['dat_lancamento'] = date('Y-m-d');
				$mov_gravar['dat_referencia'] = date('Y-m-d');
				$mov_gravar['dat_vencimento'] = date('Y-m-d');
				$mov_gravar['han_evento'] = $evento_menor;
				$mov_gravar['num_doc'] = date('Ym');
				$mov_gravar['flg_dc'] = 'D';
				$mov_gravar['val_lancamento'] = $diferenca;
				$mov_gravar['dsc_observacao'] = "Receb. a menor devido a duplicidade fat: ".$dados['fatura'];
				$mov_gravar['flg_origem'] = 'A';
				
				$this->bd->tabela = "fin_movimentos";
				$processa_bd = $this->bd->inserir($mov_gravar);
	
				echo 'K'; print_r($mov_gravar);
			}
			
			
			$faturas2 = $this->bd->busca_outra_fatura($dados['outra_fatura']);
			foreach ($faturas2 as $i => $fat) {
		
				/* Liquida próxima fatura */
				$fat_altera['dat_pagamento'] = $dat_pagamento;
				$fat_altera['val_pago'] = $prox_fatura['val_fatura'];
				$fat_altera['val_tarifa_bancaria'] = $val_tarifa;
				$fat_altera['han_retorno_auto'] = $retorno;
				$fat_altera['fatura_duplicidade'] = $dados['fatura'];
				
				$this->bd->tabela = "fin_fatura";
				$this->bd->alterar($dados['outra_fatura'], $fat_altera);
				
				echo 'L'; print_r($fat_altera);
			
			}
			
			
		}
		
		/* Conclui duplicidade */
		$conclusao['data_solucionado'] = date('Y-m-d H:i:s');
		$conclusao['solucionado'] = 'S';
		$conclusao['han_usuario_solucao'] = $this->session->userdata('han_usuario');
		$conclusao['justificativa'] = $dados['justificativa'];
		
		$this->bd->tabela = "ret_divergencias";
		$this->bd->alterar($dados['id'], $conclusao);
		
		echo 'M'; print_r($conclusao);
		
	}
	
	function lista_info_faturas() {
		
		$dados = $this->input->post();

		//print_r($dados);print_r($faturas);die();
		
		$faturas = $this->bd->busca_dados_fatura($dados['fatura']);
		foreach ($faturas as $i => $fatura) {
			$val_pago_anterior 		= $fatura['val_pago_anterior'];
			$dat_pagamento_anterior = $fatura['dat_pagamento_anterior'];
			$cliente 				= $fatura['cliente'];
			$franquia 				= $fatura['franquia'];
			$carne 					= $fatura['carne'];
			$dat_vencimento 		= $fatura['dat_vencimento'];
			$dat_pagamento 			= $fatura['dat_pagamento'];
			$val_fatura 			= $fatura['val_fatura'];
			$han_cliente 			= $fatura['han_cliente'];
			$val_pago 				= $fatura['val_pago'];
			$retorno 				= $fatura['retorno'];
			$id 					= $fatura['handle'];
			
			if($fatura['han_usuario_recebeu'] <> ''){
				$usuario_recebeu = $this->bd->busca_usuario($fatura['han_usuario_recebeu']);
				$recebimento = '<b>Recebimento local</b> para o(a) funcionário(a) <b>'.$usuario_recebeu['nom_completo'].'</b>';
			}
			else{
				$retorno_anterior 		= $fatura['retorno_anterior'];
				$recebimento = '<b>Boleto bancário da conta '.$fatura['num_conta'].'-'.$fatura['num_dv_conta'].'</b> conforme retorno nº '.$retorno_anterior;
			}
			if($carne == 'Sim'){
				$tem_carne = 'de carnê';
			}
			else{
				$tem_carne = '';
			}
		}

		$conteudo = "
			<form id='divergencia_form' method='post' novalidate='novalidate' class='bv-form'><button type='submit' class='bv-hidden-submit' style='display: none; width: 0px; height: 0px;'></button>
				
				<fieldset>
					<h4>O que está acontecendo?</h4><br>
					<article class='col-sm-12 col-md-12 col-lg-12'>
						<div style='width: calc(100%); background: #ffe5f1; border-left: 5px solid #e7026e; color: #e7026e; padding: 15px 15px;'>
							A fatura <b>".$dados['fatura']."</b> ".$tem_carne.", do(a) cliente <b>".$cliente."</b>, franquia <b>".$franquia."</b>, com vencimento <b>".$this->pdata($dat_vencimento)."</b>, 
							no valor de <b>R$".number_format($val_fatura, 2, ',', '.')."</b> foi paga por meio de ".$recebimento." no dia <b>".$this->pdata($dat_pagamento_anterior).".</b>
							O valor pago na ocasião foi <b>R$".number_format($val_pago_anterior, 2, ',', '.')."</b><br>
							Porém em <b>".$this->pdata($dat_pagamento)."</b> ela foi paga novamente de acordo com o retorno bancário nº <b>".$retorno."</b> com o valor de <b>R$".number_format($val_pago, 2, ',', '.')."</b>, ocasionando uma <b>DUPLICIDADE</b>.
						</div>
					</article>
				</fieldset>
				
				<fieldset>
					
					<br>
					<h4>O que deseja fazer?</h4><br>
					<div class='row'>
						<div class='col-sm-12 col-md-12'>
							<div class='jarviswidget jarviswidget-color-greenDark caixa_tabela' id='wid-id-4' data-widget-editbutton='false'>
								<input type='radio' name='oque_fazer' id='oque_fazer' value='movimento' class='editor-active' onclick='fecha_outras_faturas(".$dados['fatura'].", \"".$carne."\")'>Gerar Movimento não faturado ou desconto (em caso de carnê)<br>
								<input type='radio' name='oque_fazer' id='oque_fazer' value='fatura' class='editor-active' onclick='abre_outras_faturas()'>Liquidar uma fatura em aberto
							</div>
						</div>
					</div>
				</fieldset>

				<fieldset id='lista_outras_faturas' style='display:none;'>
					<div id='dados_alert'></div>
					<div class='form-group has-feedback'>
						<div class='row'>
							<div class='col-sm-12 col-md-12'>
								<h4>Faturas em aberto</h4>
								<div class='jarviswidget jarviswidget-color-greenDark caixa_tabela' id='wid-id-4' data-widget-editbutton='false'>
									<div class='table-responsive'>
									
										<table class='table table-hover dados' style='margin-bottom: 0px;' id='lista_faturas'>
											<thead>
												<tr role='row'>
													<th></th>
													<th>Fatura</th>
													<th>Cliente</th>
													<th>Carnê</th>
													<th>Vencimento</th>
													<th>Valor Fatura</th>
												</tr>
											</thead>
											<tbody>";
											
												$outras_faturas = $this->bd->busca_dados_outras_fatura($han_cliente);
												foreach ($outras_faturas as $i => $fat) {
													
													$conteudo .= "<tr role='row'>";
														if($val_pago < $fat['val_fatura']){
															$conteudo .= "<td class='dt-body-center sorting_1 dtr-control'>
																<input type='radio' name='esta_fatura' id='esta_fatura' value='".$fat['fatura']."' class='editor-active' disabled='disabled'>
															</td>";
														}
														else{
															$conteudo .= "<td class='dt-body-center sorting_1 dtr-control'>
																<input type='radio' name='esta_fatura' id='esta_fatura' value='".$fat['fatura']."' class='editor-active' onclick='marca_essa_fatura(".$dados['id'].",".$dados['fatura'].",".$fat['fatura'].",".$val_pago.",".$fat['val_fatura'].",\"".$fat['carne']."\")'>
															</td>";
														}
														$conteudo .= "<td>".$fat['fatura']."</td>
														<td>".$fat['cliente']."</td>
														<td>".$fat['carne']."</td>
														<td>".$this->pdata($fat['dat_vencimento'])."</td>
														<td>R$ ".number_format($fat['val_fatura'], 2, ',', '.')."</td>
													</tr>
													";
													
												}
												
												$conteudo .= "
												<input type='hidden' id='id_divergencia' value='".$id."'>
												<input type='hidden' id='fatura_divergencia' value='".$dados['fatura']."'>
											</tbody>
										</table>
										
									</div>
								</div>
							</div>
						</div>
					</div>
				</fieldset>
				
				
				
				
				
				
				
				
				
				<fieldset id='lista_outras_faturas2' style='display:none;'>
					<div id='dados_alert2'></div>
					<div class='form-group has-feedback'>
						<div class='row'>
							<div class='col-sm-12 col-md-12'>
								
								<div class='jarviswidget jarviswidget-color-greenDark caixa_tabela' id='wid-id-4' data-widget-editbutton='false'>
									<div class='table-responsive'>
									
										<table class='table table-hover dados' style='margin-bottom: 0px;' id='lista_faturas'>
											<thead>
												<tr role='row'>
													<th></th>
													<th>O que será feito?</th>
												</tr>
											</thead>
											<tbody>";
											
											
												
												$faturas = $this->bd->busca_dados_fatura($dados['fatura']);
												//print_r($faturas);
												foreach ($faturas as $i => $fatura) {
													
													$han_franquia 		= $fatura['han_franquia'];
													$han_cliente 		= $fatura['han_cliente'];
													$val_pago			= $fatura['val_pago'];
													$dat_pagamento		= $fatura['dat_pagamento'];
													$val_tarifa			= $fatura['val_tarifa'];
													$retorno			= $fatura['retorno'];
													
													if($fatura['carne'] > ''){
														$evento_maior = 77;
														$evento_menor = 78;
													}
													else{
														$evento_maior = 26;
														$evento_menor = 27;
													}
												}
												
												
												
												if($fatura['carne'] == 'Sim'){
													$prox_fatura = $this->bd->busca_proxima_fatura_aberto($han_cliente, $dados['fatura']);
													if($prox_fatura['fatura'] != ''){
														if($val_pago < $prox_fatura['val_fatura']){
															$desconto = $prox_fatura['val_fatura'] - $val_pago;
															$desconto = round($desconto,2);
															$conteudo .= '<tr><td><i class="fa fa-chevron-right"></i></td><td>Insere movimento de desconto na fatura '.$prox_fatura['fatura'].' de R$ '.$val_pago.'</td></tr>';
															$conteudo .= '<tr><td><i class="fa fa-chevron-right"></i></td><td>Gera desconto de R$'.$val_pago.' na fatura '.$prox_fatura2['fatura'].'. Novo valor da fatura: R$'.$desconto.'</td></tr>';
														}
														elseif($val_pago == $prox_fatura['val_fatura']){
															$conteudo .= '<tr><td><i class="fa fa-chevron-right"></i></td><td>Liquida a fatura '.$prox_fatura['fatura'].' no valor de R$'.$prox_fatura['val_fatura'].'</td></tr>';
														}
														else{
															$diferenca = $val_pago - $prox_fatura['val_fatura'];
															$diferenca = round($diferenca,2);
															$conteudo .= '<tr><td><i class="fa fa-chevron-right"></i></td><td>Liquida a fatura '.$prox_fatura['fatura'].' no valor de R$ '.$prox_fatura['val_fatura'].'</td></tr>';
															
														
															if($diferenca < 0){
																$diferenca = $diferenca * -1;
																$diferenca = round($diferenca,2);
															}
															$prox_fatura2 = $this->bd->busca_proxima_fatura_aberto($han_cliente, $prox_fatura['fatura']);
															if($prox_fatura2['fatura'] != ''){
																$desconto = $prox_fatura2['val_fatura'] - $diferenca;
																$conteudo .= '<tr><td><i class="fa fa-chevron-right"></i></td><td>Insere movimento de desconto na fatura '.$prox_fatura2['fatura'].' de R$ '.$diferenca.'</td></tr>';
																$conteudo .= '<tr><td><i class="fa fa-chevron-right"></i></td><td>Gera desconto de R$ '.$diferenca.' na fatura '.$prox_fatura2['fatura'].'. Novo valor da fatura: R$ '.$desconto.'</td></tr>';
															}
															else{
																$conteudo .= '<tr><td><i class="fa fa-chevron-right"></i></td><td>Insere movimento não faturado no valor de R$ '.$diferenca.'</td></tr>';
															}
														}
													}
													else{
														$conteudo .= '<tr><td><i class="fa fa-chevron-right"></i></td><td>Insere movimento não faturado no valor de R$ '.$val_pago.'</td></tr>';
													}
												}
												else{
													$conteudo .= '<tr><td><i class="fa fa-chevron-right"></i></td><td>Insere movimento não faturado no valor de R$ '.$val_pago.'</td></tr>';
												}
										
											
											
												$conteudo .= "
												<input type='hidden' id='id_divergencia' value='".$id."'>
												<input type='hidden' id='fatura_divergencia' value='".$dados['fatura']."'>
											</tbody>
										</table>
										
									</div>
								</div>
							</div>
						</div>
					</div>
				</fieldset>
				
				
				
				
				
				
				
				
				
				<fieldset id='bloco_justificativa' style='display:none;'>
					<h4>Observações</h4><br>
					<div class='row'>
						<div class='col-sm-12 col-md-12'>
							<div class='jarviswidget jarviswidget-color-greenDark caixa_tabela' id='wid-id-4' data-widget-editbutton='false'>
								<textarea class='form-control' name='justificativa' id='justificativa' rows='2' data-bv-field='review'></textarea><i class='form-control-feedback' data-bv-icon-for='review' style='display: none;'></i>
								
							</div>
						</div>
					</div>
				</fieldset>
			
		";
        
        echo $conteudo;
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	/* NÃO LOCALIZADOS -------------------------------------------------------------------------------------------------------------------------------------------------------- */
	
	function divergencias_nao_localizados($filtro="N") {
    	
        // carrega tabela
        $this->template->add_js("asset/datatables/datatables.min.js");
		$this->template->add_css("asset/datatables/datatables.min.css");
		$this->template->add_css("asset/datatables/ajustes.css");
        $this->load->library('geradatatables');
		$table = new Geradatatables();
		$itens = array("<i class='fa fa-check'></i>","<i class='fa fa-search'></i>", "Fatura", "Cliente", "Franquia", "Vencimento", "Pagamento", "Valor Fatura", "Valor Pago", "Divergência");
		$html = $table->html("Listagem das Faturas com Divergência - Não Localizados", "lista_faturas", $itens);
		$this->template->write('conteudo', $html);
        $this->template->add_js($this->config->item("js") . "jquery.lista_divergencias_nao_localizados.js");

        $modal = '
		<div class="modal fade in" id="detalhes_cliente" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">

				
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title"><i class="fa fa-usd" style="color:#ccc"></i> &nbsp;Divergência - fatura: <span id="id_fat"><span></h4>
					</div>
					<div class="modal-body" id="dados_esta_fatura">
	
					</div>
					<div class="modal-footer" style="text-align:center;">
						<button type="button" class="btn btn-primary" onclick="processa_nao_localizados();">
							<i class="fa fa-check"></i> Solucionado
						</button>
						<input type="hidden" name="han_fatura" id="han_fat">
						<input type="hidden" name="id" id="handle_id">
						</form>
						
						<button type="button" class="btn btn-warning" onclick="fecha_modal();">
							<i class="fa fa-check"></i> Cancelar
						</button>
					</div>
				</div>
				
				
			</div>
		</div>';

        $this->template->write('conteudo', $modal);
        $this->template->render();
    }
	
	function lista_divergencias_nao_localizados($acao = FALSE, $filtros = array()) {
        $faturas = $this->bd->busca_divergencias_nao_localizados();
		
		//echo '<pre>'; echo $id; print_r($dados);
		
		foreach ($faturas as $i => $fatura) {
			if($fatura['solucionado'] == 'A'){
				$cor_botao = 'orange';
				$label_botao = 'Aguardando solução';		
			}
			else{
				$cor_botao = 'red';
				$label_botao = 'Solucionado?';
			}
			
        	$faturas[$i]['botoes'] = "<span class='alinhaicones'><a href='' onclick='abremodal3(".$fatura['fatura'].", ".$fatura['handle'].",".$fatura['han_tipo_divergencia'].");' title='".$label_botao."' style='color:".$cor_botao.";margin-left:3px'><i class='fa fa-warning' ></i></a></span>";
        }

        $json = '{"data":[';
        foreach ($faturas as $i => $fatura) {
            
            $virgula = ($i > 0) ? "," : "";
            $link_visualizacao = "<a title='Visualizar fechamento' href='$acao/{$fechamento['handle']}'><i class='fa fa-search txt-color-green'></i>";
            
            $json .= $virgula . '{"handle":"' . str_pad($fatura['fatura'],5,"0",STR_PAD_LEFT) . '",'
            . '"botoes":"' 			. $fatura['botoes'] . '",'
            . '"fatura":"' 			. $fatura['fatura'] . '",'
            . '"cliente":"' 		. $fatura['cliente'] . '",'
            . '"franquia":"' 		. $fatura['franquia'] . '",'
        	. '"dat_vencimento":"' 	. $this->pdata($fatura['dat_vencimento']) . '",'
        	. '"dat_pagamento":"' 	. $this->pdata($fatura['dat_pagamento']) . '",'
        	. '"val_fatura":"' 		. number_format($fatura['val_fatura'], 2, ',', '.') . '",'
        	. '"val_pago":"' 		. number_format($fatura['val_pago'], 2, ',', '.') . '",'
            . '"divergencia":"' 	. $fatura['divergencia'] . '"'
            . '}';
        }
		
        
        $json .=']}';
        
        $style = "style='width:100%; text-align:right; float:right;'";
        echo $json;
        
    }
	
	function resolve_nao_localizados() {
		
		$dados = $this->input->post();


		$conteudo = "
			<form id='divergencia_form' method='post' novalidate='novalidate' class='bv-form'><button type='submit' class='bv-hidden-submit' style='display: none; width: 0px; height: 0px;'></button>
			<fieldset>
				<h4>Solução encontrada</h4><br>
				<div class='row'>
					<div class='col-sm-12 col-md-12'>
						<div class='jarviswidget jarviswidget-color-greenDark caixa_tabela' id='wid-id-4' data-widget-editbutton='false'>
							<textarea class='form-control' name='justificativa' id='justificativa' rows='2' data-bv-field='review'></textarea><i class='form-control-feedback' data-bv-icon-for='review' style='display: none;'></i>
							<input type='hidden' id='id_divergencia' value='".$dados['id']."'>
							<input type='hidden' id='fatura_divergencia' value='".$dados['fatura']."'>
						</div>
					</div>
				</div>
			</fieldset>
			
		";
        
        echo $conteudo;
		
	}
	
	function conclui_nao_localizados(){
		$dados = $this->input->post();
		
		$sql1 = "UPDATE ret_divergencias SET data_solucionado = '".date('Y-m-d H:i:s')."',  solucionado = 'S', han_usuario_solucao = '".$this->session->userdata('han_usuario')."', justificativa = '".$dados['justificativa']."' WHERE handle = '".$dados['id']."'";
		//mysqli_query($conn, $sql1);
		echo $sql1 . "<br><br>";
	}
	
	
	
	
	
	
	
	
	
	
	
	
	

	

	/* RESOLVIDOS -------------------------------------------------------------------------------------------------------------------------------------------------------- */
    

	function divergencias_resolvidas($filtro="N") {
    	
        // carrega tabela
        $this->template->add_js("asset/datatables/datatables.min.js");
		$this->template->add_css("asset/datatables/datatables.min.css");
		$this->template->add_css("asset/datatables/ajustes.css");
        $this->load->library('geradatatables');
		$table = new Geradatatables();
		$itens = array("<i class='fa fa-check'></i>","<i class='fa fa-search'></i>", "Fatura", "Cliente", "Franquia", "Vencimento", "Pagamento", "Valor Fatura", "Valor Pago", "Divergência");
		$html = $table->html("Listagem das Faturas com Divergência - Solucionadas", "lista_faturas", $itens);
		$this->template->write('conteudo', $html);
        $this->template->add_js($this->config->item("js") . "jquery.lista_divergencias_resolvidas.js");
		
	
        $modal = '
		<div class="modal fade in" id="detalhes_cliente" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">

				
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title"><i class="fa fa-usd" style="color:#ccc"></i> &nbsp;Divergência - fatura: <span id="id_fat"><span></h4>
					</div>
					<div class="modal-body" id="dados_esta_fatura">
	
					</div>
					<div class="modal-footer" style="text-align:center;">
						
						<input type="hidden" name="han_fatura" id="han_fat">
						<input type="hidden" name="id" id="handle_id">
						</form>
						
						<button type="button" class="btn btn-warning" onclick="fecha_modal();">
							<i class="fa fa-check"></i> Cancelar
						</button>
					</div>
				</div>
				
				
			</div>
		</div>';
	
        $this->template->write('conteudo', $modal);
        $this->template->render();
    }

	function lista_divergencias_resolvidas($acao = FALSE, $filtros = array()) {
        $faturas = $this->bd->busca_divergencias_resolvidas();
		
		//echo '<pre>'; echo $id; print_r($dados);
		
		foreach ($faturas as $i => $fatura) {

        	$faturas[$i]['botoes'] = "<span class='alinhaicones'><a href='' onclick='abremodal4(".$fatura['fatura'].", ".$fatura['handle'].");' title='Ver Detalhes' style='margin-left:3px'><i class='fa fa-search-plus' ></i></a></span>";
        }

      
        
        $json = '{"data":[';
        foreach ($faturas as $i => $fatura) {
            
            $virgula = ($i > 0) ? "," : "";
            $link_visualizacao = "<a title='Visualizar fechamento' href='$acao/{$fechamento['handle']}'><i class='fa fa-search txt-color-green'></i>";
            
            $json .= $virgula . '{"handle":"' . str_pad($fatura['fatura'],5,"0",STR_PAD_LEFT) . '",'
            . '"botoes":"' 			. $fatura['botoes'] . '",'
            . '"fatura":"' 			. $fatura['fatura'] . '",'
            . '"cliente":"' 		. $fatura['cliente'] . '",'
            . '"franquia":"' 		. $fatura['franquia'] . '",'
        	. '"dat_vencimento":"' 	. $this->pdata($fatura['dat_vencimento']) . '",'
        	. '"dat_pagamento":"' 	. $this->pdata($fatura['dat_pagamento']) . '",'
        	. '"val_fatura":"' 		. number_format($fatura['val_fatura'], 2, ',', '.') . '",'
        	. '"val_pago":"' 		. number_format($fatura['val_pago'], 2, ',', '.') . '",'
            . '"divergencia":"' 	. $fatura['divergencia'] . '"'
            . '}';
        }
		
        
        $json .=']}';
        
        $style = "style='width:100%; text-align:right; float:right;'";
        echo $json;
        
    }

	function detalhes_resolvidas() {
		
		$dados = $this->input->post();

		//print_r($dados);print_r($faturas);die();
		
		$faturas = $this->bd->busca_dados_fatura($dados['fatura']);
		foreach ($faturas as $i => $fatura) {

			$conteudo = "
			<form id='divergencia_form' method='post' novalidate='novalidate' class='bv-form'><button type='submit' class='bv-hidden-submit' style='display: none; width: 0px; height: 0px;'></button>

				<fieldset>
					<h4>Observações</h4><br>
					<div class='row'>
						<div class='col-sm-12 col-md-12'>
							<table class='table table-bordered m-0'>
								<thead>
									<tr>
										<th colspan=2>Detalhes</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<th scope='row'>Divergêcia</th>
										<td>".$fatura['divergencia']."</td>
									</tr>
									<tr>
										<th scope='row'>Gerada em</th>
										<td>".$fatura['data_processado']."</td>
									</tr>
									<tr>
										<th scope='row'>Resolvida em</th>
										<td>".$fatura['data_solucionado']."</td>
									</tr>";
									if($fatura['han_usuario_solucao'] != ''){
										$usuario = $this->bd->busca_usuario($fatura['han_usuario_solucao']);
										$conteudo .= "
										<tr>
											<th scope='row'>Resolvida por</th>
											<td>".$usuario['nom_completo']."</td>
										</tr>";
									}
									$conteudo .= "<tr>
										<th scope='row'>Justificativa</th>
										<td>".$fatura['justificativa']."</td>
									</tr>
									<tr>
										<th scope='row'>Movimentos Gerados</th>
										<td>";
											$movimentos = $this->bd->busca_movimento($dados['fatura']);
											foreach ($movimentos as $i => $mov) {
												$conteudo .= $mov['dsc_observacao'];
											} $conteudo .= "
										</td>
									</tr>
									<tr>
										<th scope='row'>Fatura Baixada</th>
										<td>";
											$baixadas = $this->bd->fatura_baixada($dados['fatura']);
											foreach ($baixadas as $i => $baixada) {
												$conteudo = $baixada['handle'];
											} $conteudo .= "
										</td>
									</tr>";
								$conteudo .= "</tbody>
							</table>
						</div>
					</div>
				</fieldset>
			
			";
        }
        echo $conteudo;
		
	}
    
    
	function pdata ($data){
		$aux = explode("-", $data);
		return $aux[2].'/'.$aux[1].'/'.$aux[0];
	}


}

/* End of file grupo_usuarios.php */