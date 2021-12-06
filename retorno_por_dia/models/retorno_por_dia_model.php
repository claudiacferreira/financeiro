<?php

class retorno_por_dia_model extends MY_Model {

    function __construct() {
        parent::__construct();
        $this->franquia = FALSE;
        $this->tabela = 'fin_fatura';
        $this->CI->db->_escape_char = FALSE;
    }
	
	function busca_resumo($filtros = false){
		
		unset($filtros['submit']);
		unset($filtros['ordenar']);
		
		$data = cdata($filtros['data_retorno']);
		$where = 'WHERE R.data_inicio_processamento BETWEEN "'.$data.' 00:00:00" AND "'.$data.' 23:59:59"';
		if($filtros){
            
            if($filtros['franquias'] <> ''){
                $where .= " AND RP.han_franquia = ".$filtros['franquias'];
            }
            if($filtros['divergencias'] <> ''){
				$where .= " AND RP.han_tipo_divergencia = ".$filtros['divergencias'];
            }
			if($filtros['conta_bancaria'] <> ''){
				$where .= " AND R.dado_conta = '".$filtros['conta_bancaria']."'";
            }
        }
		
		if($data <> ''){
			$this->db->select('
			R.dado_conta,
			COUNT(RP.handle) AS quant_titulos, 
			SUM(RP.val_titulo) AS som_titulo,
			SUM(RP.val_tarifa) AS som_tarifa,
			SUM(RP.val_pago) AS som_pago
			FROM ret_retorno_processado RP
			INNER JOIN ret_retorno R ON R.handle = RP.han_retorno
			'.$where.'
			GROUP BY R.dado_conta
			ORDER BY RP.handle ASC
			');
			return $this->db->get()->result_array();
		}
		else{
			return NULL;
		}
    }
	
	function busca_resumo_divergencia($filtros = false){
		
		unset($filtros['submit']);
		unset($filtros['ordenar']);
		
		$data = cdata($filtros['data_retorno']);
		$where = 'WHERE R.data_inicio_processamento BETWEEN "'.$data.' 00:00:00" AND "'.$data.' 23:59:59"';
		if($filtros){
            
           if($filtros['franquias'] <> ''){
                $where .= " AND RP.han_franquia = ".$filtros['franquias'];
            }
            if($filtros['divergencias'] <> ''){
				$where .= " AND RP.han_tipo_divergencia = ".$filtros['divergencias'];
            }
			if($filtros['conta_bancaria'] <> ''){
				$where .= " AND R.dado_conta = '".$filtros['conta_bancaria']."'";
            }
        }
		
		if($data <> ''){
			$this->db->select('
			D.divergencia,
			COUNT(RP.handle) AS quant_titulos, 
			SUM(RP.val_titulo) AS som_titulo,
			SUM(RP.val_tarifa) AS som_tarifa,
			SUM(RP.val_pago) AS som_pago
			FROM ret_retorno_processado RP
			INNER JOIN ret_retorno R ON R.handle = RP.han_retorno
			LEFT JOIN ret_tipo_divergencia D ON D.handle = RP.han_tipo_divergencia
			'.$where.'
			GROUP BY RP.han_tipo_divergencia
			ORDER BY RP.handle ASC;

			');
			return $this->db->get()->result_array();
		}
		else{
			return NULL;
		}
    }
	
	function busca_retorno_por_dia($filtros = false){
		
		unset($filtros['submit']);
		$data = cdata($filtros['data_retorno']);
		$where = 'WHERE R.data_inicio_processamento BETWEEN "'.$data.' 00:00:00" AND "'.$data.' 23:59:59"';
		if($filtros){
            
            if($filtros['franquias'] <> ''){
                $where .= " AND RP.han_franquia = ".$filtros['franquias'];
            }
            if($filtros['divergencias'] <> ''){
				$where .= " AND RP.han_tipo_divergencia = ".$filtros['divergencias'];
            }
			if($filtros['conta_bancaria'] <> ''){
				$where .= " AND R.dado_conta = '".$filtros['conta_bancaria']."'";
            }
			
        }
		if($filtros['ordenar']){
			$order = " ORDER BY ".$filtros['ordenar']." ASC";
		}
		
		if($data <> ''){
			$this->db->select('
			RP.han_retorno,
			RP.han_retorno AS retorno,
			CONCAT(RP.fatura,"-",RP.digito) AS fatura,
			RP.han_franquia,
			RP.cliente,
			R.dado_conta,
			DATE_FORMAT(RP.dat_vencimento, "%d/%m/%Y") AS dat_vencimento,
			DATE_FORMAT(RP.dat_pagamento, "%d/%m/%Y") AS dt_pagto,
			RP.val_titulo,
			RP.val_tarifa,
			RP.val_pago, 
			D.divergencia,
			CM.movimento,
			DATE_FORMAT(F.dat_pagamento, "%d/%m/%Y") AS fatura_paga
			FROM ret_retorno_processado RP
			INNER JOIN ret_retorno R ON R.handle = RP.han_retorno
			LEFT JOIN ret_tipo_divergencia D ON D.handle = RP.han_tipo_divergencia
			LEFT JOIN ret_codigo_movimento CM ON CM.codigo = RP.cod_movimento
			LEFT JOIN fin_fatura F ON F.handle = RP.fatura
			'.$where.' 
			'.$order
			);
			return $this->db->get()->result_array();
		}
		else{
			return NULL;
		}
    }
	
	
	
	function get_conta() {
        $this->db
		->select("
		REPLACE(REPLACE(REPLACE(num_conta, '/', ''), '-', ''), '.', '') AS handle, dsc_conta as dado_conta
		FROM cad_conta_bancaria
		WHERE REPLACE(REPLACE(REPLACE(num_conta, '/', ''), '-', ''), '.', '') IN (SELECT DISTINCT(dado_conta) FROM ret_retorno)
		");
        return $this->db->get()->result_array();
    }	
	
	function get_franquias() {
        $sql = '
			SELECT handle, CONCAT(handle," - ",nom_franquia) AS nom_franquia
			FROM cad_franquia
			WHERE nom_franquia NOT LIKE "*%"
			ORDER BY handle ASC
        ';
        $query = $this->db->query($sql);
		return ($query->result_array());
    }
	
	function get_divergencias() {
        $sql = '
			SELECT handle, divergencia
			FROM ret_tipo_divergencia
			ORDER BY handle ASC
        ';
        $query = $this->db->query($sql);
		return ($query->result_array());
    }
}

/* End of file adm_grupo_usuarios_model.php */