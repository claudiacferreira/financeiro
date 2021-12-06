$(document).ready(function () {
  
	carrega_tabela();
  
    $('#lista_faturas').on( 'change', 'input.editor-active', function () {
        alert('Selecionado');
    });

  	
});




function carrega_tabela(){
	  $('#lista_faturas').dataTable( {
		responsive: true,
		destroy: true,
		colReorder: true,
		language: {
			"lengthMenu": "Exibir _MENU_ registros",
			"zeroRecords": "Nenhum registro encontrado, desculpe",
			"info": "Exibindo página _PAGE_ de _PAGES_",
			"infoEmpty": "Nenhum registro disponível",
			"infoFiltered": "(filtrado do total de  _MAX_ registros)",
			"loadingRecords": "Carregando...",
			"processing":     "Processando...",
			"search":         "Filtrar:",
			"paginate": {
				"first":      "Primeiro",
				"last":       "Último",
				"next":       "Próxima",
				"previous":   "Anterior"
			},
			"aria": {
				"sortAscending":  ": Ative para Organizar em Ordem Crescente",
				"sortDescending": ": Ative para Organizar em Ordem Decrescente"
			},
			  select: {
				rows: "%d registros selecionados"
			}	
		},
		ajax: baseurl + "index.php/divergencias/lista_divergencias_duplicidade/",
		columns: [
			{
			data:   "handle",
			
			render: function ( data, type, row ) {
				
				//console.log(row);
				
				if ( type === 'display' ) {
					return '<input type="checkbox" class="editor-active">';
				}
				return data;
			},
			className: "dt-body-center",
			orderable: false
			},{"data": "botoes", orderable: false},
			{"data": "fatura"},
			{"data": "cliente"},
			{"data": "franquia"},
			{"data": "dat_vencimento"},
			{"data": "dat_pagamento"},
			{"data": "val_fatura"},
			{"data": "val_pago"},
			{"data": "divergencia"}
 
		],
		rowCallback: function ( row, data ) {
			// Set the checked state of the checkbox in the table
			$('input.editor-active', row).prop( 'checked', data.handle == 1 );
		},
		select: {
			style: 'os',
			selector: 'td:not(:last-child)' // no row selection on last column
		},
		dom:
			"<'row mb-3'<'col-sm-12 col-md-6 d-flex justify-content-start'f><'col-sm-12 col-md-6 d-flex justify-content-end'lB>>" +
			"<'row'<'col-sm-12'tr>>" +
			"<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
		buttons: [
			{
				extend:    'colvis',
				text:      'Exibir Colunas',
				titleAttr: 'Col visibility',
				className: 'mr-sm-3 botaocolunas',
				exportOptions: {
					columns: ':visible',
					modifier: { order: 'index' }
				}
			},
			{
				extend: 'pdfHtml5',
				text: 'PDF',
				filename: 'dt_custom_pdf',
				titleAttr: 'Generate PDF',
				className: 'btn-outline-danger btn-sm mr-1',
				exportOptions: {
					columns: ':visible',
					modifier: { order: 'index' }
				},
		
				customize: function (doc) {
					
					//Remove the title created by datatTables
					doc.content.splice(0,1);
					//Create a date string that we use in the footer. Format is dd-mm-yyyy
					var now = new Date();
					var jsDate = now.getDate()+'-'+(now.getMonth()+1)+'-'+now.getFullYear();
					// Logo converted to base64
					//var logo = getBase64FromImageUrl('toppdf.jpg');
					// The above call should work, but not when called from codepen.io
					// So we use a online converter and paste the string in.
					// Done on http://codebeautify.org/image-to-base64-converter
					// It's a LONG string scroll down to see the rest of the code !!!
					var logo = '';
					// A documentation reference can be found at
					// https://github.com/bpampuch/pdfmake#getting-started
					// Set page margins [left,top,right,bottom] or [horizontal,vertical]
					// or one number for equal spread
					// It's important to create enough space at the top for a header !!!
					doc.pageMargins = [20,100,20,30];
					// Set the font size fot the entire document
					doc.defaultStyle.fontSize = 7;
					// Set the fontsize for the table header
					doc.styles.tableHeader.fontSize = 7;
					// Create a header object with 3 columns
					// Left side: Logo
					// Middle: brandname
					// Right side: A document title
					doc['header']=(function() {
						return {
							columns: [
								{
									image: logo,
									width: 550
								}
							],
							margin: 23
						}
					});
					// Create a footer object with 2 columns
					// Left side: report creation date
					// Right side: current page and total pages
					/*doc['footer']=(function(page, pages) {
						return {
							columns: [
								{
									alignment: 'left',
									text: ['Created on: ', { text: jsDate.toString() }]
								},
								{
									alignment: 'right',
									text: ['page ', { text: page.toString() },	' of ',	{ text: pages.toString() }]
								}
							],
							margin: 20
						}
					});*/
					// Change dataTable layout (Table styling)
					// To use predefined layouts uncomment the line below and comment the custom lines below
					// doc.content[0].layout = 'lightHorizontalLines'; // noBorders , headerLineOnly
					var objLayout = {};
					
					objLayout['hLineWidth'] = function(i) { return .5; };
					objLayout['vLineWidth'] = function(i) { return .5; };
					objLayout['hLineColor'] = function(i) { return '#aaa'; };
					objLayout['vLineColor'] = function(i) { return '#aaa'; };
					objLayout['paddingLeft'] = function(i) { return 4; };
					objLayout['paddingRight'] = function(i) { return 4; };
					objLayout['paddingRight'] = function(i) { return 4; };
					doc.content[0].layout = objLayout;
					
					//console.log(doc.content);
					
					doc.content[0].table.widths = Array(doc.content[0].table.body[0].length + 1).join('*').split('');
					
					
					
				}
			},
			{
				extend: 'excelHtml5',
				text: 'Excel',
				titleAttr: 'Generate Excel',
				className: 'btn-outline-success btn-sm mr-1',
				exportOptions: {
					columns: ':visible',
					modifier: { order: 'index' }
				}
			},
			{
				extend: 'csvHtml5',
				text: 'CSV',
				titleAttr: 'Generate CSV',
				className: 'btn-outline-primary btn-sm mr-1',
				exportOptions: {
					columns: ':visible',
					modifier: { order: 'index' }
				}
			},
			{
				extend: 'copyHtml5',
				text: 'Copiar',
				titleAttr: 'Copy to clipboard',
				className: 'btn-outline-primary btn-sm mr-1',
				exportOptions: {
					columns: ':visible',
					modifier: { order: 'index' }
				}
			},
			{
				extend: 'print',
				text: 'Imprimir',
				titleAttr: 'Print Table',
				className: 'btn-outline-primary btn-sm',
				exportOptions: {
					columns: ':visible',
					modifier: { order: 'index' }
				}
			}
		],
	});
    
}











function abremodal2(fatura, id, id_divergencia){
	
	if(id_divergencia == 4){
		console.log(fatura+' - '+id);
		
		$.ajax({
			url : baseurl + "index.php/divergencias/lista_info_faturas/",
			type : 'post',
			data : {
				id : id,
				fatura : fatura
			},
			beforeSend : function(){
				$("#dados_esta_fatura").html("CARREGANDO...");
			}
		})
		.done(function(msg){
			//$("#resultado").html(msg);
			//console.log(msg);
			$('#dados_esta_fatura').html(msg);
		})
		.fail(function(jqXHR, textStatus, msg){
			//alert(msg);
		});
		
		
		
		event.preventDefault();
		$('#detalhes_cliente').modal({backdrop: 'static', keyboard: false});
		$('#id_fat').html(fatura);
		$('#han_fat').val(fatura);
		$('#handle_id').val(id);
	}
	else{
		$('#dados_esta_fatura').html('NENHUMA INFORMAÇÃO À EXIBIR');
	}
};


function marca_essa_fatura(id, fatura, outra_fatura, val_pago, val_outra_fatura, e_carne){
	console.log(id+' - '+fatura+' - '+outra_fatura+' - '+val_pago+' - '+val_outra_fatura+' - '+e_carne);
	$("#btn_processa").css("display", "block");
	$('#outra_fatura_divergencia').val(outra_fatura);
	var total = val_pago - val_outra_fatura;
	total = round(total, 2);
	if(val_pago != val_outra_fatura){
		if(val_pago > val_outra_fatura){
			if(e_carne == 'Sim'){
				$('#dados_alert').html('<article class="col-sm-12 col-md-12 col-lg-12"><div style="width: calc(100%); background: #fff3d9; border-left: 5px solid #ffb20e; color: #c18300; padding: 15px 15px;">O valor pago é maior que o valor da fatura que você escolheu, por este motivo será gerado um desconto no valor de <b>R$ '+total+'</b> para a próxima fatura</div><br></article>');
			}
			else{
				$('#dados_alert').html('<article class="col-sm-12 col-md-12 col-lg-12"><div style="width: calc(100%); background: #fff3d9; border-left: 5px solid #ffb20e; color: #c18300; padding: 15px 15px;">O valor pago é maior que o valor da fatura que você escolheu, por este motivo será gerado um movimento não faturado no valor de <b>R$ '+total+'</b> com a diferença</div><br></article>');
			}
		}
		else if(val_pago < val_outra_fatura){
			if(e_carne == 'Sim'){
				$('#dados_alert').html('<article class="col-sm-12 col-md-12 col-lg-12"><div style="width: calc(100%); background: #FFC8C8; border-left: 5px solid #FF4C4C; color: #BD1515; padding: 15px 15px;">Fatura de Carnê - FAVOR GERAR UMA FATURA AVULSA COM A DIFERENÇA DE <b>R$ '+total+'</b> E ENTRAR EM CONTATO COM O CLIENTE PARA INFORMAR O PROBLEMA!</div><br></article>');
			}
			else{
				$('#dados_alert').html('<article class="col-sm-12 col-md-12 col-lg-12"><div style="width: calc(100%); background: #fff3d9; border-left: 5px solid #ffb20e; color: #c18300; padding: 15px 15px;">O valor pago é menor que o valor da fatura que você escolheu, por este motivo será gerado um movimento não faturado no valor de <b>R$ '+total+'</b> com a diferença</div><br></article>');
			}
		}
	}
	else {
		//console.log('perfeito');
	}
	
	
}

function processa_duplicidade(){
	var id = $('#id_divergencia').val();
	var fatura = $('#fatura_divergencia').val();
	var outra_fatura = $('input[id="esta_fatura"]:checked').val();
	var modo = $('input[id="oque_fazer"]:checked').val();
	var justificativa = $('#justificativa').val();
	
	
	//console.log('processa_duplicidade '+' - '+id+' - '+fatura+' - '+outra_fatura+' - '+modo+' - '+justificativa);
	

	$.ajax({
		url : baseurl + "index.php/divergencias/liquida_fatura",
		type : 'post',
		data : {
			id : id,
			fatura : fatura,
			outra_fatura : outra_fatura,
			modo : modo,
			justificativa : justificativa
		},
		beforeSend : function(){
			//$("#resultado").html("ENVIANDO...");
		}
	})
	.done(function(msg){
		console.log(msg);
		$('#detalhes_cliente').modal('hide');
		carrega_tabela();
	})
	.fail(function(jqXHR, textStatus, msg){
		console.log(msg);
	});
}


function round  (num, places) {
	if (!("" + num).includes("e")) {
		return +(Math.round(num + "e+" + places)  + "e-" + places);
	} else {
		let arr = ("" + num).split("e");
		let sig = ""
		if (+arr[1] + places > 0) {
			sig = "+";
		}

		return +(Math.round(+arr[0] + "e" + sig + (+arr[1] + places)) + "e-" + places);
	}
}

function abre_outras_faturas(){ // Abre outras faturas
	$("#lista_outras_faturas2").css("display", "none");
	$("#lista_outras_faturas").css("display", "block");
	$("#bloco_justificativa").css("display", "block");
	$("#btn_processa").css("display", "none");
	$('#dados_alert2').html('');
}
function fecha_outras_faturas(fatura, carne){
	$("#lista_outras_faturas").css("display", "none");
	$("#lista_outras_faturas2").css("display", "block");
	$("#bloco_justificativa").css("display", "block");
	console.log(fatura+' - '+carne);
	$("#btn_processa").css("display", "block");
	
	if(carne == 'Sim'){
		$('#dados_alert2').html('<article class="col-sm-12 col-md-12 col-lg-12"><div style="width: calc(100%); background: #fff3d9; border-left: 5px solid #ffb20e; color: #c18300; padding: 15px 15px;">A fatura em questão é uma fatura de carnê, caso haja alguma fatura em aberto o desconto será dado na próxima fatura, senão será gerado um movimento não faturado com a diferença.</div><br></article>');
	}
	
	
	
	
}



function fecha_modal(){
	$('#detalhes_cliente').modal('hide');
}


