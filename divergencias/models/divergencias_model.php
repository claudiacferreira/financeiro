<?php

class divergencias_model extends MY_Model {

    function __construct() {
        parent::__construct();
        $this->franquia = FALSE;
        $this->tabela = 'cad_fornecedor';
        $this->CI->db->_escape_char = FALSE;
	}
	
	
	function busca_divergencias_resolver(){

		$sql = "SELECT
		
		RD.handle,
		RD.solucionado,
		RD.han_tipo_divergencia,
		
    	RP.fatura, 
    	RP.cliente,
    	RP.dat_vencimento, 
    	RP.dat_pagamento, 
		RP.val_pago,
		
		CONCAT(FF.han_franquia, ' - ',CF.nom_franquia) AS franquia, 
    	FF.val_fatura, 
		FF.val_pago AS val_pago_anterior, 
    	FF.dat_pagamento AS dat_pagamento_anterior, 
		IF(FF.han_carne IS NULL,'Não','Sim') AS carne,
		FF.han_retorno,
		FF.han_usuario_recebeu,
		
    	TD.divergencia,
		
    	CB.num_conta,
    	CB.num_dv_conta
		
    	FROM ret_divergencias RD
        LEFT JOIN ret_tipo_divergencia TD ON TD.handle = RD.han_tipo_divergencia
        LEFT JOIN ret_retorno_processado RP ON RP.handle = RD.han_retorno_processado
        LEFT JOIN fin_fatura FF ON FF.handle = RD.han_fatura
		LEFT JOIN cad_boleto CB ON CB.handle = FF.han_boleto
        LEFT JOIN cad_franquia CF ON CF.handle = FF.han_franquia
        WHERE (
			FF.dat_pagamento IS NULL AND (
				RD.han_tipo_divergencia = '3' OR 
				RD.han_tipo_divergencia = '14' OR 
				RD.han_tipo_divergencia = '15'
			)) AND RD.solucionado <> 'D'
		AND RD.solucionado <> 'S'
		order by FF.han_franquia ASC";
		
		
		$query = $this->db->query($sql);
		return ($query->result_array());
		
    }
	
    function busca_divergencias_duplicidade(){

		$sql = "SELECT
		
		RD.handle,
		RD.solucionado,
		RD.han_tipo_divergencia,
		
    	RP.fatura, 
    	RP.cliente,
    	RP.dat_vencimento, 
    	RP.dat_pagamento, 
		RP.val_pago,
		
		CONCAT(FF.han_franquia, ' - ',CF.nom_franquia) AS franquia, 
    	FF.val_fatura, 
		FF.val_pago AS val_pago_anterior, 
    	FF.dat_pagamento AS dat_pagamento_anterior, 
		IF(FF.han_carne IS NULL,'Não','Sim') AS carne,
		FF.han_retorno,
		FF.han_usuario_recebeu,
		
    	TD.divergencia,
		
    	CB.num_conta,
    	CB.num_dv_conta
		
    	FROM ret_divergencias RD
        LEFT JOIN ret_tipo_divergencia TD ON TD.handle = RD.han_tipo_divergencia
        LEFT JOIN ret_retorno_processado RP ON RP.handle = RD.han_retorno_processado
        LEFT JOIN fin_fatura FF ON FF.handle = RD.han_fatura
		LEFT JOIN cad_boleto CB ON CB.handle = FF.han_boleto
        LEFT JOIN cad_franquia CF ON CF.handle = FF.han_franquia
        WHERE 
			RD.han_tipo_divergencia = '4' 
			AND RD.solucionado <> 'D'
			AND RD.solucionado <> 'S'
		order by FF.han_franquia ASC";
		
		
		$query = $this->db->query($sql);
		return ($query->result_array());
		
    }
	
	function busca_divergencias_nao_localizados(){

		$sql = "SELECT
		
		RD.handle,
		RD.solucionado,
		RD.han_tipo_divergencia,
		
    	RP.fatura, 
    	RP.cliente,
    	RP.dat_vencimento, 
    	RP.dat_pagamento, 
		RP.val_pago,
		
		CONCAT(FF.han_franquia, ' - ',CF.nom_franquia) AS franquia, 
    	FF.val_fatura, 
		FF.val_pago AS val_pago_anterior, 
    	FF.dat_pagamento AS dat_pagamento_anterior, 
		IF(FF.han_carne IS NULL,'Não','Sim') AS carne,
		FF.han_retorno,
		FF.han_usuario_recebeu,
		
    	TD.divergencia,
		
    	CB.num_conta,
    	CB.num_dv_conta
		
    	FROM ret_divergencias RD
        LEFT JOIN ret_tipo_divergencia TD ON TD.handle = RD.han_tipo_divergencia
        LEFT JOIN ret_retorno_processado RP ON RP.handle = RD.han_retorno_processado
        LEFT JOIN fin_fatura FF ON FF.handle = RD.han_fatura
		LEFT JOIN cad_boleto CB ON CB.handle = FF.han_boleto
        LEFT JOIN cad_franquia CF ON CF.handle = FF.han_franquia
        WHERE RD.han_tipo_divergencia = '1'
		order by FF.han_franquia ASC";
		
		
		$query = $this->db->query($sql);
		return ($query->result_array());
		
    }
	
	
	function busca_divergencias_resolvidas(){
		

		$sql = "
		SELECT
		RD.handle,
    	RP.fatura, 
    	RP.cliente, 
    	FF.han_franquia AS franquia, 
    	RP.dat_vencimento, 
    	RP.dat_pagamento, 
    	FF.val_fatura, 
    	RP.val_pago,
    	TD.divergencia,
    	RD.solucionado,
    	CB.num_conta,
    	CB.num_dv_conta,
		RD.han_tipo_divergencia,
		IF(FF.han_carne IS NULL,'Não','Sim') AS carne
    	FROM ret_divergencias RD
        LEFT JOIN ret_tipo_divergencia TD ON TD.handle = RD.han_tipo_divergencia
        LEFT JOIN ret_retorno_processado RP ON RP.handle = RD.han_retorno_processado
        LEFT JOIN fin_fatura FF ON FF.handle = RD.han_fatura
		LEFT JOIN cad_boleto CB ON CB.handle = FF.han_boleto
        LEFT JOIN cad_franquia CF ON CF.handle = FF.han_franquia
        WHERE RD.solucionado = 'S'
		ORDER BY RP.dat_pagamento DESC";
		
		
		$query = $this->db->query($sql);
		return ($query->result_array());
		
		
    }
	
	
	function busca_dados_fatura($id = FALSE){
		
		if($id <> ''){
			$sql = "
			SELECT
		
			RD.handle,
			RD.solucionado,
			RD.han_tipo_divergencia,
			RD.justificativa,
			RD.han_usuario_solucao,
			RD.data_processado,
			RD.data_solucionado,
			
			RP.fatura, 
			RP.cliente,
			RP.dat_vencimento, 
			RP.dat_pagamento, 
			RP.val_pago,
			RP.han_retorno AS retorno,
			RP.val_tarifa,

			CONCAT(FF.han_franquia, ' - ',CF.nom_franquia) AS franquia, 
			FF.han_franquia,
			FF.val_fatura, 
			FF.val_pago AS val_pago_anterior, 
			FF.dat_pagamento AS dat_pagamento_anterior, 
			IF(FF.han_carne IS NULL,'Não','Sim') AS carne,
			FF.han_retorno,
			FF.han_usuario_recebeu,
			FF.han_retorno_auto AS retorno_anterior,
			FF.han_cliente,
			
			TD.divergencia,
			
			CB.num_conta,
			CB.num_dv_conta
			
			FROM ret_divergencias RD
			LEFT JOIN ret_tipo_divergencia TD ON TD.handle = RD.han_tipo_divergencia
			LEFT JOIN ret_retorno_processado RP ON RP.handle = RD.han_retorno_processado
			LEFT JOIN fin_fatura FF ON FF.handle = RD.han_fatura
			LEFT JOIN cad_boleto CB ON CB.handle = FF.han_boleto
			LEFT JOIN cad_franquia CF ON CF.handle = FF.han_franquia
			WHERE FF.handle = ".$id."
			ORDER BY FF.han_franquia ASC";
			
			
			$query = $this->db->query($sql);
			return ($query->result_array());
		}
		else{
			return false;
		}
    }
	
	function busca_proxima_fatura_aberto($han_cliente = FALSE, $id = FALSE){
		
		if($id <> ''){
			$sql = "
			
			SELECT
			FF.handle AS fatura, 
			CL.nom_cliente AS cliente, 
			FF.han_franquia AS franquia, 
			FF.dat_vencimento, 
			FF.val_fatura,
			IF(FF.han_carne IS NULL,'Não','Sim') AS carne
			FROM fin_fatura FF
			LEFT JOIN cad_cliente CL ON CL.handle = FF.han_cliente
			WHERE FF.handle > ".$id."
			AND FF.han_cliente = ".$han_cliente."
			AND FF.dat_pagamento IS NULL
			AND FF.han_carne IS NOT NULL
			ORDER BY FF.dat_vencimento ASC";
			
			
			$query = $this->db->query($sql);
			return ($query->row_array());
		}
		else{
			return false;
		}
    }

	function busca_movimento($id = FALSE){
		
		if($id <> ''){
			$sql = "
			SELECT dsc_observacao
			FROM fin_movimentos
			WHERE dsc_observacao LIKE '%".$id."%'";
			
			$query = $this->db->query($sql);
			return ($query->result_array());
		}
		else{
			return false;
		}
    }
	
	function fatura_baixada($id = FALSE){
		
		if($id <> ''){
			$sql = "
			SELECT handle
			FROM fin_fatura
			WHERE fatura_duplicidade LIKE ".$id;
			
			$query = $this->db->query($sql);
			return ($query->result_array());
		}
		else{
			return false;
		}
    }
	
	function busca_dados_outras_fatura($id = FALSE){
		
		if($id <> ''){
			$sql = "
			SELECT
			FF.handle AS fatura, 
			CL.nom_cliente AS cliente, 
			FF.han_franquia AS franquia, 
			FF.dat_vencimento, 
			FF.val_fatura,
			IF(FF.han_carne IS NULL,'Não','Sim') AS carne
			FROM fin_fatura FF
			LEFT JOIN cad_cliente CL ON CL.handle = FF.han_cliente
			WHERE FF.dat_pagamento IS NULL
			AND FF.han_cliente = ".$id."
			ORDER BY FF.dat_vencimento ASC";
			
			
			$query = $this->db->query($sql);
			return ($query->result_array());
		}
		else{
			return false;
		}
    }
	
	
	
	
	
	
	
	
	function busca_outra_fatura($id = FALSE){
		
		if($id <> ''){
			$sql = "
			SELECT
			FF.handle AS fatura, 
			CL.nom_cliente AS cliente, 
			FF.han_franquia AS franquia, 
			FF.dat_vencimento, 
			FF.val_fatura,
			IF(FF.han_carne IS NULL,'Não','Sim') AS carne
			FROM fin_fatura FF
			LEFT JOIN cad_cliente CL ON CL.handle = FF.han_cliente
			WHERE FF.dat_pagamento IS NULL
			AND FF.handle = ".$id."
			ORDER BY FF.dat_vencimento ASC";
			
			
			$query = $this->db->query($sql);
			return ($query->result_array());
		}
		else{
			return false;
		}
    }
	
	function busca_usuario($id = FALSE){
		
		if($id <> ''){
			$sql = "
			SELECT nom_completo
			FROM adm_usuarios
			WHERE handle = ".$id."
			LIMIT 1 ";
			
			
			$query = $this->db->query($sql);
			return ($query->row_array());
		}
		else{
			return false;
		}
    }
	
}