<?php

class retorno_por_dia extends CI_Controller {

    function __construct() {
        parent::__construct();

        // carrega modelo - banco de dados;
        $this->load->model('retorno_por_dia_model', 'bd');
        $this->lang->load("retorno_por_dia");

        $this->load->helper('date');
        // função para montar título
        $this->template->write('titulo', create_breadcrumb());
    }
	
	function index(){
		
		// mostra o resultado de $this->_monta_form() trazendo os dados do $id no template
		$this->template->write('conteudo', $this->_monta_form());
		
	
		// habilita caixa em torno do conteúdo
		$this->template->write('caixa_conteudo', TRUE);
		$this->template->render();
	}
	

	
	function _monta_form() {

		
		// classe para construção de form
		$this->load->library('form');
    	$this->template->add_js($this->config->item('js') . 'smart/plugin/colorpicker/bootstrap-colorpicker.min.js');
        $this->template->add_js($this->config->item('js') . 'jquery.meiomask.min.js');
        $this->template->add_js('$(function(){$(\'input:text\').setMask(); $(\'#tab_topo\').tabs(); $(\'#cor\').colorpicker().on(\'changeColor\', function(e) {
            document.getElementsByTagName("body")[0].style.backgroundColor = e.color.toString(
                \'rgba\'); }); });', 'embed');


	
		//configuração dos Filtros
		$franquias = create_array_combo($this->bd->get_franquias(), array('handle', 'nom_franquia'));
		$ordenar = array(
			"RP.cod_movimento" => "MOVIMENTO", 
			"RP.han_franquia" => "FRANQUIA", 
			"R.dado_conta" => "CONTA", 
			"D.divergencia" => "DIVERGÊNCIA", 
			"RP.cliente" => "CLIENTE", 
			"RP.val_pago" => "VALOR PAGO"
		);
		$conta_bancaria = create_array_combo($this->bd->get_conta(), array("handle", "dado_conta"));		
		
		
		
		//echo '<pre>';print_r($conta_bancaria);die();
		
		$divergencias = create_array_combo($this->bd->get_divergencias(), array('handle', 'divergencia'));
		$this->form
		->open('retorno_por_dia/listar/', FALSE, array("enctype" => "multipart/form-data", "target" => "_blank",  'class' => " smart-form form-horizontal formcss"))
        ->html('<div id="cadastro-a" aria-labelledby="ui-id-25" class="ui-tabs-panel ui-widget-content ui-corner-bottom" role="tabpanel" aria-expanded="true" aria-hidden="false" style="display: block;padding:20px">')
		->html('<div class="row">')
		//->html('<section class="col col-4" style="text-align:center;padding:5px 0 0 0"><h4>Informe a data do processamento do retorno:</h4></section>')
		->text("data_retorno", 'Data Retorno', "required", $dados['data_retorno'], array("data-dateformat" => "dd/mm/yyyy", "alt" => "date", "class" => "datepicker", "size" => "20", "element_prefix"=>"<section class='col col-2'>"))
		
		->html('</div>')
		->select("franquias", $franquias, 'Franquias','')
		->select("conta_bancaria", $conta_bancaria, 'Conta Bancária')
		->select("divergencias", $divergencias, 'Divergências')
		->select("ordenar", $ordenar, 'Ordernar por')
	
		->html("<br class='clear'/>")
		->html('<div style="background-color:#fff;height:55px;padding:5px 0;border-radius:0;"')
		->label('&nbsp;')->submit('<i class="glyphicon glyphicon-ok"></i> &nbsp;&nbsp;Pesquisar', "submit", array("style" => "font-size:11pt", "target" => "_blank", "class" => "btn btn-primary btn-lg btn-xl txt-color-white bg-color-bt float-clear-right btn-new"))
		->html('</div>')
		->html("<br class='clear'/>") 
	
		->html("</div>");
		
		return $this->form->get(false, array("titulo_form" => "Pesquisar"));
	
    }
	
	
	
	function listar() {

		$this->load->library("table");
		$this->load->library('form');
		$this->template->add_js($this->config->item("js") . "smart/plugin/datatables/jquery.dataTables.min.js");
        $this->template->add_js($this->config->item("js") . "smart/plugin/datatables/dataTables.colVis.min.js");
        $this->template->add_js($this->config->item("js") . "smart/plugin/datatables/dataTables.tableTools.min.js");
        $this->template->add_js($this->config->item("js") . "smart/plugin/datatables/dataTables.bootstrap.min.js");
        $this->template->add_js($this->config->item("js") . "smart/plugin/datatable-responsive/datatables.responsive.min.js");

		$filtros = $this->input->post();
		//echo '<pre>';print_r($filtros);die();

		$this->form->html('<article class="col-sm-12 col-md-12 col-lg-12">');
			$this->form->html('<h4><b>Data do processamento do retorno '.$filtros['data_retorno'].'</b></h4><br>');
		$this->form->html('</article>');
		
		$resumo = $this->bd->busca_resumo($filtros);
		
		$this->form->html('<article class="col-sm-6 col-md-6 col-lg-6">');
		$this->form->html('<h4>Por Conta Bancária</h4>');
		$this->form->html('<div class="jarviswidget jarviswidget-color-greenDark caixa_tabela" id="wid-id-3" data-widget-editbutton="false">');
		$this->form->html('<div>
		<div class="widget-body no-padding">
			<div class="table-responsive">');
				
				$this->form->html('<table class="table table-hover dados">');
					$this->form->html('<thead>
						<tr class="">
							<th>Conta</th>
							<th style="text-align: right;">Quant.</th>
							<th style="text-align: right;">Total Títulos</th>
							<th style="text-align: right;">Total Tarifas</th>
							<th style="text-align: right;">Total Pago</th>
						</tr>
					</thead>
					<tbody>');
			foreach ($resumo as $linhas => $colunas) {
				$this->form->html('<tr>');
					$this->form->html('<td>'.$colunas['dado_conta'].'</td>');
					$this->form->html('<td style="text-align: right;">'.$colunas['quant_titulos'].'</td>');
					$this->form->html('<td style="text-align: right;">R$ '.number_format($colunas['som_titulo'], 2, ',', '.').'</td>');
					$this->form->html('<td style="text-align: right;">R$ '.number_format($colunas['som_tarifa'], 2, ',', '.').'</td>');
					$this->form->html('<td style="text-align: right;">R$ '.number_format($colunas['som_pago'], 2, ',', '.').'</td>');
				$this->form->html('</tr>');
			
				$tot_quant_titulos = $tot_quant_titulos + $colunas['quant_titulos'];
				$tot_som_titulo = $tot_som_titulo + $colunas['som_titulo'];
				$tot_som_tarifa = $tot_som_tarifa + $colunas['som_tarifa'];
				$tot_som_pago = $tot_som_pago + $colunas['som_pago'];
				
				
			}
			$this->form->html('<tr>');
				$this->form->html('<td><b>TOTAL</b></td>');
				$this->form->html('<td style="text-align: right;"><b>'.$tot_quant_titulos.'</b></td>');
				$this->form->html('<td style="text-align: right;"><b>R$ '.number_format($tot_som_titulo, 2, ',', '.').'</b></td>');
				$this->form->html('<td style="text-align: right;"><b>R$ '.number_format($tot_som_tarifa, 2, ',', '.').'</b></td>');
				$this->form->html('<td style="text-align: right;"><b>R$ '.number_format($tot_som_pago, 2, ',', '.').'</b></td>');
			$this->form->html('</tr>');
		$this->form->html('</tbody></table>');

	$this->form->html('</div></div></div></div></article>');


	$tot_quant_titulos = 0;
	$tot_som_titulo = 0;
	$tot_som_tarifa = 0;
	$tot_som_pago = 0;


	$divergencia = $this->bd->busca_resumo_divergencia($filtros);

	$this->form->html('<article class="col-sm-6 col-md-6 col-lg-6">');
	$this->form->html('<h4>Por Evento</h4>');
		$this->form->html('<div class="jarviswidget jarviswidget-color-greenDark caixa_tabela" id="wid-id-4" data-widget-editbutton="false">');
		$this->form->html('<div>
		<div class="widget-body no-padding">
			<div class="table-responsive">');
			
				$this->form->html('<table class="table table-hover dados">');
					$this->form->html('<thead>
						<tr class="">
							<th>Evento</th>
							<th style="text-align: right;">Quant.</th>
							<th style="text-align: right;">Total Títulos</th>
							<th style="text-align: right;">Total Tarifas</th>
							<th style="text-align: right;">Total Pago</th>
						</tr>
					<tbody>');
			foreach ($divergencia as $linhas => $colunas) {
				$this->form->html('<tr>');
					$this->form->html('<td>'.$colunas['divergencia'].'</td>');
					$this->form->html('<td style="text-align: right;">'.$colunas['quant_titulos'].'</td>');
					$this->form->html('<td style="text-align: right;">R$ '.number_format($colunas['som_titulo'], 2, ',', '.').'</td>');
					$this->form->html('<td style="text-align: right;">R$ '.number_format($colunas['som_tarifa'], 2, ',', '.').'</td>');
					$this->form->html('<td style="text-align: right;">R$ '.number_format($colunas['som_pago'], 2, ',', '.').'</td>');
				$this->form->html('</tr>');
				
				$tot_quant_titulos = $tot_quant_titulos + $colunas['quant_titulos'];
				$tot_som_titulo = $tot_som_titulo + $colunas['som_titulo'];
				$tot_som_tarifa = $tot_som_tarifa + $colunas['som_tarifa'];
				$tot_som_pago = $tot_som_pago + $colunas['som_pago'];
				
			}
			$this->form->html('<tr>');
				$this->form->html('<td><b>TOTAL</b></td>');
				$this->form->html('<td style="text-align: right;"><b>'.$tot_quant_titulos.'</b></td>');
				$this->form->html('<td style="text-align: right;"><b>R$ '.number_format($tot_som_titulo, 2, ',', '.').'</b></td>');
				$this->form->html('<td style="text-align: right;"><b>R$ '.number_format($tot_som_tarifa, 2, ',', '.').'</b></td>');
				$this->form->html('<td style="text-align: right;"><b>R$ '.number_format($tot_som_pago, 2, ',', '.').'</b></td>');
			$this->form->html('</tr>');
		$this->form->html('</tbody></table>');

	$this->form->html('</div></div></div></div></article>');












        $dados = $this->bd->busca_retorno_por_dia($filtros);

		//echo '<pre>';print_r($dados);die();

		$cont = 0;
        foreach ($dados as $linhas => $colunas) {
			$cont++;
			$dados[$linhas]['cont'] = $cont;
			foreach ($colunas as $i => $val) {
				
				if($i == 'val_titulo'){
					$dados[$linhas][$i] = '<span style="float: right;">R$ '.number_format($val, 2, ',', '.').'</span>';
				}
				elseif($i == 'val_tarifa'){
					$dados[$linhas][$i] = '<span style="float: right;">R$ '.number_format($val, 2, ',', '.').'</span>';
				}
				elseif($i == 'val_pago'){
					$dados[$linhas][$i] = '<span style="float: right;">R$ '.number_format($val, 2, ',', '.').'</span>';
				}
				else{
					$dados[$linhas][$i] = $val;
				}
			}
			
        }

        $this->table->dados = $dados;
        $this->table->titulo(array('Retorno','Fatura','Franq.','Cliente','Conta','Vencto','Evento','Valor','Tarifa','Pago','Divergência','Movimento','Baixa',''));

		$this->template->write('conteudo', $this->form->get(false, array("titulo_form" => "Pesquisar")));
        $this->template->write("conteudo", $this->table->gerar());
		
		
        $this->template->write("caixa_conteudo", true);

        $this->template->render();
		
	}
	

    

	
	function pdata ($data){
		$aux = explode("-", $data);
		return $aux[2].'/'.$aux[1].'/'.$aux[0];
	}



}

/* End of file grupo_usuarios.php */