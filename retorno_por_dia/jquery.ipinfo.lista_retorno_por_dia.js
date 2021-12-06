$(document).ready(function () {
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
        			ajax: baseurl + "index.php/retorno_por_dia/lista_retorno_por_dia/",
			        columns: [
			        	{
                data:   "handle",
                
                render: function ( data, type, row ) {
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
						{"data": "agencia"},
			            {"data": "dat_vencimento"},
			            {"data": "dat_pagamento"},
			            {"data": "val_fatura"},
			            {"data": "val_pago"},
			            {"data": "evento"}
			 
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
						 //var logo = getBase64FromImageUrl('https://zapadmin.com.br/cgfo/asset/datatables/toppdf.jpg');
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
						
						console.log(doc.content);
						
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
			    } );
    
     $('#lista_faturas').on( 'change', 'input.editor-active', function () {
        
            alert('Selecionado');
    } );
    
    
    
    
    
    
    
    
    
    
    
  
  	
    
});

