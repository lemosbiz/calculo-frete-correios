<?php
/*
2020-07-20
Class created by Roberto Lemos
www.websimples.info
free cost calculator to correios

To-do:
Peso cubico
Cálculo correios:
O preço a ser cobrado corresponderá ao maior dos dois pesos (bruto ou cúbico).
Um exemplo: Um objeto pesando 7,76 kg e medindo 45 cm de comprimento, 38 cm de largura e 40 cm de altura terá seu preço determinado da seguinte forma:
1. Calcular o peso cúbico: Volume = 45 x 38 x 40 = 68.400 cm3 Peso cúbico = 68.400 / 6000 = 11,40, ou seja, 12kg
2. Pesar o objeto Peso real = 8 kg
3. Será cobrado o maior dos dois pesos, ou seja, 12kg

*/
$tokenDeTeste = 'frete625';

class CorreiosFrete {
    
    public function __construct($action) {
        
        // Trás informação de conexão com o banco de dados
        $this->action = $action;
        
        $this->config = array();
        $this->config['peso_max'] = 30;
        $this->config['comprimento_min'] = 16;
        $this->config['comprimento_max'] = 105;
        $this->config['altura_min'] = 2;
        $this->config['altura_max'] = 105;
        $this->config['largura_min'] = 11;
        $this->config['largura_max'] = 105;
        $this->config['valor_min'] = 20;
        $this->config['valor_max'] = 3000;
        
        // Configuração de serviços dos correios
        $this->codServico = array();
        
        // Sem contrato
        $this->codServicoSemCon = array('40010','40045','40215','40290','41106');
        $this->codServico[40010] = 'SEDEX sem contrato';
        $this->codServico[40045] = 'SEDEX a Cobrar, sem contrato';
        $this->codServico[40215] = 'SEDEX 10, sem contrato';
        $this->codServico[40290] = 'SEDEX Hoje, sem contrato';
        $this->codServico[41106] = 'PAC sem contrato';
        
        // Com contrato
        $this->codServicoCcmCon = array('40126','40096','40436','40444','40568','40606','41211','41068','81019','81027','81035','81868','81833','81850');
        $this->codServico[40126] = 'SEDEX a Cobrar, com contrato';
        $this->codServico[40096] = 'SEDEX com contrato';
        $this->codServico[40436] = 'SEDEX com contrato';
        $this->codServico[40444] = 'SEDEX com contrato';
        $this->codservico[40568] = 'SEDEX com contrato';
        $this->codServico[40606] = 'SEDEX com contrato';
        $this->codServico[41211] = 'PAC com contrato';
        $this->codServico[41068] = 'PAC com contrato';
        $this->codServico[81019] = 'E-SEDEX, com contrato';
        $this->codServico[81027] = 'E-SEDEX Prioritário, com contrato';
        $this->codServico[81035] = 'E-SEDEX Express, com contrato';
        $this->codServico[81868] = '(Grupo 1) E-SEDEX, com contrato';
        $this->codServico[81833] = '(Grupo 2 ) E-SEDEX, com contrato';
        $this->codServico[81850] = '(Grupo 3 ) E-SEDEX, com contrato'; 
        
        // Aqui estarão as informações que utilizaremos para o cálculo de frete
        // $this->getfrete = new stdClass();
       	 
	}
    
    
    private function getServico($codigo) {
        if (!isset($codigo)) {
            $retorno['resultado'] = false;
            $retorno['erro'][] = 'INFORME CODSERVICO';
        }
        elseif (!is_numeric($codigo)) {
            $retorno['resultado'] = false;
            $retorno['erro'][] = 'CODSERVICO INVALIDO';
        }
        elseif (!isset($this->codServico[$codigo])) {
            $retorno['resultado'] = false;
            $retorno['erro'][] = 'CODSERVICO NAO ENCOTNRADO';
        }
        elseif (isset($this->codServico[$codigo])) {
            $this->getFrete['nCdServico'] = $codigo;
            $retorno['resultado'] = $this->codServico[$codigo];
        }
        return $retorno;
    }
    
    
    public function validaDados($dados) {
        // Valor padrão de formato para caixa - Essa classe não está preparada para cálculo em outros formatos como rolo / prisma / envelope
        $this->getFrete['nCdFormato'] = '1';
        $this->getFrete['nVlDiametro'] = 0;
        // Valor padrão para retorno em formato xml das informações
        $this->getFrete['StrRetorno'] = 'xml';
        
        
        // Seta nCdServico -> verificar $this->getServico
        if (!isset($dados['nCdServico'])) {
            $retorno['erro'][] = 'INFORME nCdServico';
        }
        elseif (!is_numeric($dados['nCdServico'])) {
            $retorno['erro'][] = 'nCdServico INVALIDO';
        } 
        elseif(isset($this->getServico($dados['nCdServico'])['erro'])) {
            $retorno['erro'][] = 'nCdServico NAO ENCONTRADO';
        } else {
            // Validação de usuário e senha para caso de frete com contrato
            if (in_array($this->getFrete['nCdServico'],$this->codServicoCcmCon)) {
                if ((!isset($dados['nCdEmpresa'])) || (!isset($dados['sDsSenha']))) {
                    $retorno['erro'][] = 'nCdEmpresa / sDsSenha PARA FRETE COM CONTRATO';
                }
            }
        }
        
        // sCepOrigem
        if (!isset($dados['sCepOrigem'])) {
            $retorno['erro'][] = 'INFORME sCepOrigem';
        }
        elseif (!is_numeric($dados['sCepOrigem'])) {
            $retorno['erro'][] = 'sCepOrigem INVALIDO';
        } 
        else {
            $this->getFrete['sCepOrigem'] = $dados['sCepOrigem'];
        }
        
        // sCepDestino
        if (!isset($dados['sCepDestino'])) {
            $retorno['erro'][] = 'INFORME sCepDestino';
        }
        elseif (!is_numeric($dados['sCepDestino'])) {
            $retorno['erro'][] = 'sCepDestino INVALIDO';
        } 
        else {
            $this->getFrete['sCepDestino'] = $dados['sCepDestino'];
        }
        
        // nVlPeso (kg)
        if (!isset($dados['nVlPeso'])) {
            $retorno['erro'][] = 'INFORME nVlPeso';
        }
        elseif (!is_numeric($dados['nVlPeso'])) {
            $retorno['erro'][] = 'nVlPeso INVALIDO';
        }
        elseif ($dados['nVlPeso'] > $this->config['peso_max']) {
            $retorno['erro'][] = 'nVlPeso PRECISA SER MENOR QUE '.$this->config['peso_max'];
        }
        else {
            $this->getFrete['nVlPeso'] = round($dados['nVlPeso'],2);
        }
        
        // nVlComprimento (cm)
        if (!isset($dados['nVlComprimento'])) {
            $retorno['erro'][] = 'INFORME nVlComprimento';
        }
        elseif (!is_numeric($dados['nVlComprimento'])) {
            $retorno['erro'][] = 'nVlComprimento INVALIDO';
        }
        elseif ($dados['nVlComprimento'] > $this->config['comprimento_max']) {
            $retorno['erro'][] = 'nVlComprimento PRECISA SER MENOR QUE '.$this->config['comprimento_max'];
        }
        else {
            $this->getFrete['nVlComprimento'] = round($dados['nVlComprimento'],2);
        }
        
        // nVlAltura (cm)
        if (!isset($dados['nVlAltura'])) {
            $retorno['erro'][] = 'INFORME nVlAltura';
        }
        elseif (!is_numeric($dados['nVlAltura'])) {
            $retorno['erro'][] = 'nVlAltura INVALIDO';
        }
        elseif ($dados['nVlAltura'] > $this->config['altura_max']) {
            $retorno['erro'][] = 'nVlAltura PRECISA SER MENOR QUE '.$this->config['altura_max'];
        }
        else {
            $this->getFrete['nVlAltura'] = round($dados['nVlComprimento'],2);
        }
        
        // nVlLargura (cm)
        if (!isset($dados['nVlLargura'])) {
            $retorno['erro'][] = 'INFORME nVlLargura';
        }
        elseif (!is_numeric($dados['nVlLargura'])) {
            $retorno['erro'][] = 'nVlLargura INVALIDO';
        }
        elseif ($dados['nVlLargura'] > $this->config['largura_max']) {
            $retorno['erro'][] = 'nVlLargura PRECISA SER MENOR QUE '.$this->config['largura_max'];
        }
        else {
            $this->getFrete['nVlLargura'] = round($dados['nVlLargura'],2);
        }
        
        // nVlValorDeclarado (cm)
        if (!isset($dados['nVlValorDeclarado'])) {
            $retorno['erro'][] = 'INFORME nVlValorDeclarado';
        }
        elseif (!is_numeric($dados['nVlValorDeclarado'])) {
            $retorno['erro'][] = 'nVlValorDeclarado INVALIDO';
        }
        elseif ($dados['nVlValorDeclarado'] < $this->config['valor_min']) {
            $retorno['erro'][] = 'nVlValorDeclarado PRECISA SER MENOR QUE '.$this->config['valor_min'];
        }
        elseif ($dados['nVlValorDeclarado'] > $this->config['valor_max']) {
            $retorno['erro'][] = 'nVlValorDeclarado PRECISA SER MENOR QUE '.$this->config['valor_max'];
        }
        else {
            $this->getFrete['nVlValorDeclarado'] = round($dados['nVlValorDeclarado'],2);
        }
        
        //sCdMaoPropria
        if (!isset($dados['sCdMaoPropria'])) {
            $this->getFrete['sCdMaoPropria'] = 'N';
        }
        elseif (strtoupper($dados['sCdMaoPropria']) == 'N') {
            $this->getFrete['sCdMaoPropria'] = 'N';
        }
        elseif (strtoupper($dados['sCdMaoPropria']) == 'S') {
            $this->getFrete['sCdMaoPropria'] = 'S';
        }
        else {
            $retorno['erro'][] = 'sCdMaoPropria INVALIDO PODE SER S OU N';
        }
        
        //sCdAvisoRecebimento
        if (!isset($dados['sCdAvisoRecebimento'])) {
            $this->getFrete['sCdAvisoRecebimento'] = 'N';
        }
        elseif (strtoupper($dados['sCdAvisoRecebimento']) == 'N') {
            $this->getFrete['sCdAvisoRecebimento'] = 'N';
        }
        elseif (strtoupper($dados['sCdAvisoRecebimento']) == 'S') {
            $this->getFrete['sCdAvisoRecebimento'] = 'S';
        }
        else {
            $retorno['erro'][] = 'sCdAvisoRecebimento INVALIDO PODE SER S OU N';
        }
        
        if (isset($retorno['erro'])) {
            $retorno['resultado'] = false;
        } else {
            $retorno['resultado'] = $this->getFrete;
        }
        
        if (isset($retorno['erro'])) {
            $this->erro = $retorno['erro'];
        }
        
        return $retorno;
    }
    
    public function calculaFrete() {
        $data = http_build_query($this->getFrete);
        $url = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx';
        $curl = curl_init($url . '?' . $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        if (curl_errno($curl)=='0') {
            $this->result = simplexml_load_string($result);
        } else {
            $this->erro[] = 'SITE DOS CORREIOS RETORNOU ERRO';
        }
    }
    
}




if ((isset($_REQUEST['token'])) && ($_REQUEST['token']==$tokenDeTeste)) {
    // Token para produtos que estão no carrinho
    $my['cookiecar'] = 'ae786hkpgy806pig1gg7j9z1h70il1owfv4kcn0smg3r7k882odkxclejvx6wm2b';

    // Conexão com a base de dados
    include __DIR__.'/class.database.php';
    $action = new DataBase(array('companyFile' => 'artesdanet'));
    $frete = new CorreiosFrete($action);
    
    
    
    $query = "
        SELECT 
        cookie, COUNT(id) AS quantidade_produtos, SUM(quantidade) AS quantidade, SUM(valor_produto_total) AS valor_produtos,
        SUM(valor_desconto) AS valor_desconto, SUM(valor_total) AS valor_total, SUM(peso_total) AS peso_total, SUM(valor_real) AS valor_real,
        GROUP_CONCAT(produto_id) AS produtos_id, ROUND(((pow(sum(volume_cubico),1/3))*1.1),2) as aresta_frete
        FROM  (
            SELECT
            loja_carrinho.id, loja_carrinho.cookie, loja_carrinho.quantidade, loja_carrinho.cor, loja_carrinho.tamanho, loja_carrinho.especificacoes,
            loja_carrinho.valor_unitario,loja_carrinho.valor_desconto,
            (
            case when ((produto.largura <= produto.comprimento) and (produto.largura <= produto.altura)) 
            then (((produto.altura + produto.comprimento) + (produto.largura * loja_carrinho.quantidade)) / 3) 
            when ((produto.altura <= produto.comprimento) and (produto.altura <= produto.largura)) 
            then (((produto.largura + produto.comprimento) + (produto.altura * loja_carrinho.quantidade)) / 3) 
            when ((produto.comprimento <= produto.largura) and (produto.comprimento <= produto.altura)) 
            then (((produto.altura + produto.largura) + (produto.comprimento * loja_carrinho.quantidade)) / 3) END) 
            AS dimensao_calculo,
            (((produto.largura * produto.altura) * produto.comprimento) * loja_carrinho.quantidade) AS volume_cubico,
            pow((((produto.largura * produto.altura) * produto.comprimento) * loja_carrinho.quantidade),(1 / 3)) AS aresta_cubo,
            produto.peso AS produto_peso_unitario,
            (produto.peso * loja_carrinho.quantidade) AS peso_total,
            (loja_carrinho.valor_unitario - loja_carrinho.valor_desconto) AS valor_real,
            (loja_carrinho.valor_unitario * loja_carrinho.quantidade) AS valor_produto_total,
            (loja_carrinho.valor_desconto * loja_carrinho.quantidade) AS valor_desconto_total,
            ((loja_carrinho.valor_unitario - loja_carrinho.valor_desconto) * loja_carrinho.quantidade) AS valor_total,
            loja_carrinho.ativo,
            produto.id AS produto_id, produto.titulo AS produto, produto.descricao AS produto_descricao 
            FROM loja_carrinho 
            INNER JOIN produto ON loja_carrinho.fk_produto = produto.id
            WHERE ISNULL(loja_carrinho.fk_pedido) AND loja_carrinho.ativo = '1' AND loja_carrinho.cookie = '".$my['cookiecar']."'
            ) carrinho";
    $result = $action->query_db($query);
    $frete->carrinho = $action->array_db($result)[0]; 
    
    var_dump($frete->carrinho);

    $dados['nCdServico'] = '40010';
    $dados['sCepOrigem'] = '66050290';
    $dados['sCepDestino'] = '66615620';
    $dados['nVlPeso'] = '0.3';
    $dados['nVlComprimento'] = '50';
    $dados['nVlAltura'] = '20';
    $dados['nVlLargura'] = '20';
    $dados['nVlValorDeclarado'] = '1000.50';
    $dados['sCdMaoPropria'] = 'N';
    $dados['sCdAvisoRecebimento'] = 'N';

    $frete->validaDados($dados);
    
    if (isset($frete->erro)) {
        var_dump($frete->erro);
    } else {
        $frete->calculaFrete();
        var_dump($frete->getFrete); 
        var_dump($frete->result); 
    }
    
}
exit;

    
    
    
    
    
    
    
    
    
    
	//******************************************FUNÇÃO FRETE***********************************************************//
	function frete($action,$cookie_loja,$form) {
		// Rotina de retorno de dados para frete buscar na loja
		/* if ((!isset($form['tipo_frete'])) || ($form['tipo_frete'] == 1)) {
			$x=0;
			$frete[$x]['res']['codigo']='1';
			$frete[$x]['res']['descricao']='Recebimento pelo consumidor em nossa loja fisica';
			$frete[$x]['res']['valor']='0,00';
			$frete[$x]['res']['prazo']='0';
			$frete[$x]['res']['valor_declarado']=$form['valor_declarado'];
		}*/
		// Rotina de cálculo de fretes pelos correios
		if ((!isset($form['tipo_frete'])) || ($form['tipo_frete'] > 40000)) {
			// Tratamento dos tipos de serviços dos correios
			if (isset($form['tipo_frete'])) { 
				$data['nCdServico']=$form['tipo_frete']; }
			else {
				$data['nCdServico'] = '40010,41106'; 
				/*
				
				04510 - PAC (código antigo 41106 , alterado em 05/05/2017)
				04014 - SEDEX (código antigo 40010, alterado em 05/05/2017)
				40045 - SEDEX a Cobrar
				40215 - SEDEX 10
				40290 - SEDEX Hoje
				$data['nCdServico'] = '04014,04510';
				*/
				
			}



			//Código da sua empresa, se você tiver contrato com os correios saberá qual é esse código… 
			$data['nCdEmpresa'] = '';
			// Senha de acesso ao serviço. Geralmente é os 8 primeiros números do CNPJ correspondente ao código administrativo
			// Caso não tiver é só passar o parâmetro em branco – Se quiser alterar a senha é só clicar aqui 
			// http://www.corporativo.correios.com.br/encomendas/servicosonline/recuperaSenha
			$data['sDsSenha'] = '';
			
			
			// Dados que serão erados pela loja
			// Cep de Origem - apenas números
			if (!is_numeric($form['cep_origem'])) { $frete['erro'][]='CEP de Origem precisa ser numérico'; } 
			else { $data['sCepOrigem'] = $form['cep_origem']; }
			
			// Cep de destino
			if (!isset($form['cep_destino'])) { $frete['erro'][]='CEP de Destino precisa ser numérico'; } 
			else { $data['sCepDestino'] = $form['cep_destino']; }
			
			// Peso em Kg
			if (!isset($form['peso_total'])) { 
				$data['nVlPeso']='0.5'; 
			}
			else { 
				$peso=round($form['peso_total'],2); 
				if ($peso<30) {
					$data['nVlPeso']=$peso;
					$data['multiplicador']=1;
				}
				else {
					$multiplicador=explode('.',$peso/30);
					$data['multiplicador']=$multiplicador[0]+1;
				}
			}
	
			// Formato - 1= caixa 2= prisma 3=envelope
			$data['nCdFormato'] = '1';
			
			// largura, comprimento e altura
			if (!isset($form['aresta_frete'])) { 
				$data['nVlAltura']='11'; 
				$data['nVlLargura']='11';
				$data['nVlComprimento']='16';
			}
			if ($form['aresta_frete']<16) {
				$data['nVlAltura']='11'; 
				$data['nVlLargura']='11';
				$data['nVlComprimento']='16';
			}
			else {
				if ($form['aresta_frete']>65) {
					$multiplicador=explode('.',$form['aresta_frete']/65);
					$teste_multiplicador=$multiplicador[0]+1;
					if ($teste_multiplicador>$data['multiplicador']) {
						$data['multiplicador']=$teste_multiplicador;
					}
					$form['aresta_frete']=$form['aresta_frete']/$data['multiplicador'];
				}
				
				$data['nVlAltura']=$form['aresta_frete']; 
				$data['nVlLargura']=$form['aresta_frete']; 
				$data['nVlComprimento']=$form['aresta_frete']; 
			}
			//print_r($data);
			$form['nVlDiametro'] = '0';
			
			
			// Valor declarado 
			if (!isset($form['valor_declarado'])) { $data['nVlValorDeclarado']=200; }
			else { 
				if ($form['valor_declarado']>10000) { $data['nVlValorDeclarado']=10000; }
				else { $data['nVlValorDeclarado']=$form['valor_declarado']; }
			}
			
			// Serviço adicionam mão própria
			$data['sCdMaoPropria'] = 'n';
			// serviço de aviso de recebimento
			$data['sCdAvisoRecebimento'] = 'n';
			$data['StrRetorno'] = 'xml';	
			
			
			//print_r($data);
			
			// Multiplicador para cálculos acima de dimensões e pesos possiveis					
			$multiplicador_final=$data['multiplicador'];

			if (!isset($erro)) {
				$data = http_build_query($data);
				$url = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx';
				$curl = curl_init($url . '?' . $data);

				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$result = curl_exec($curl);
				if (curl_errno($curl)=='0') {
				$result = simplexml_load_string($result);
				//print_r($result);
				foreach($result -> cServico as $row) {
					//Os dados de cada serviço estará aqui
	//				print_r($row);
					 if($row -> Erro == 0) {
						if (!isset($x)) { $x=0; } else { $x=$x+1; }
						$frete[$x]['res']['codigo'] = (string) $row -> Codigo;
						 if ($frete[$x]['res']['codigo'] == 40010) { $frete[$x]['res']['descricao'] = 'SEDEX Correios'; }
						elseif ($frete[$x]['res']['codigo'] == 41106) { $frete[$x]['res']['descricao'] = 'PAC Correios'; }
						else { }
	//					else { $frete[$x]['res']['descricao'] = 'SERVIÇO DESCONHECIDO Correios'; }
						$frete[$x]['res']['valor'] = number_format((string) $row -> Valor*$multiplicador_final,2);
						$frete[$x]['res']['prazo'] = (string) $row -> PrazoEntrega;
						$frete[$x]['res']['valor_declarado'] = $form['valor_declarado'];
					//	print_r($frete[$x]);
					} else {
						$frete['erro'][] = (string) $row -> MsgErro;
					}
				}
			} 
			else {
				$frete['erro'][]='Erro ao conectar com o site dos correios';
			}
		}
		
		}


		// Cálculo de frete da loja
		if ((!isset($form['tipo_frete'])) || ($form['tipo_frete'] == 2)) {
			if (!isset($x)) { $x=0; } else { $x=$x+1; }
			
			$query = "select * from frete_loja_view where cep_inicial < ".$action->secure($form['cep_destino'])." and cep_final > ".$action->secure($form['cep_destino']). " and ativo = '1'";
			//var_dump($query); die;
			$result=$action->query_db($query);
			if ($action->result_quantidade($result)==1) {
				$r=$action->array_db($result);
				$frete[$x]['res']['codigo']=2;
				$frete[$x]['res']['descricao']='Frete proprio da loja';
//				echo lala.$form['valor_declarado'].'<br />'.$r[0]['valor_minimo_gratuidade'];
				if ($form['valor_declarado']>=$r[0]['valor_minimo_gratuidade']) {
					$frete[$x]['res']['valor']='0,00';
				}
				else {
					$frete[$x]['res']['valor']=$r[0]['valor_frete'];
				}
				$frete[$x]['res']['prazo']=$r[0]['prazo_entrega'];
				$frete[$x]['res']['valor_declarado']=$form['valor_declarado'];
			}
		}			
		// Caso haja erro, retorna erro
		else {
		}
//	print_r($frete);
	return($frete);
	}	















class carrinho {
	
	
//*************************************** RETORNA DADOS DO CARRINHO ************************************************//
	public function retorna_dados($action,$cookie_loja) {
		$query="select * from loja_carrinho_view where cookie = '".$action->secure($cookie_loja)."' and ativo = '1' and pedido_id is null order by produto asc";
		$result=$action->query_db($query);
		if ($action->result_quantidade($result)>0) { 
			$carrinho['produto'] = $action->array_db($result); 
			// Cálculo dos dados consolidados
			$querycar = "
				select cookie, 
				count(id) as quantidade_produtos, 
				sum(quantidade) as quantidade, 
				sum(valor_produto_total) as valor_produtos,
				sum(valor_desconto) as valor_desconto,
				sum(valor_total) as valor_total,
				sum(peso_total) as peso_total,
				sum(valor_real),
				sum(loja_carrinho_view.produto), 
				sum(ativo), 
				sum(produto_descricao),
				sum(produto_id),
				#IF(count(id) = 1, round(dimensao_calculo), round(dimensao_calculo*count(id)/2)) as aresta_frete
				((pow(sum(volume_cubico),1/3))*1.1) as aresta_frete
				#aresta_cubo as aresta_frete
				#round(sum(dimensao_calculo)) as aresta_frete
				from loja_carrinho_view where cookie = '".$action->secure($cookie_loja)."' and ativo ='1' and pedido_id is null
			";
			$result=$action->query_db($querycar);
			$r=$action->array_db($result);
			$carrinho['consolidado']=$r[0]; 
			//print_r($carrinho['consolidado']);
			return $carrinho;
			}
		else { 
			return false; 
		}
	}
	
//*************************************** LIMPA O CARRINHO ************************************************//
	public function limpa_carrinho($action,$cookie_loja) {
		$query="update loja_carrinho set ativo = '0' where cookie = '".$action->secure($cookie_loja)."'";
		$result=$action->query_db($query);
		$resultado['resultado'] = true;
		$resultado['act']='limpa_carrinho';
		return($resultado);
	}
	
//*************************************** ADICIONA PRODUTO NO CARRINHO************************************************//
	public function add_produto($action,$cookie_loja,$form) {
		// Tratamento de erro para quantidade e produto, ambos devem ser numéricos
		if ((!is_numeric($form['quantidade'])) || (!is_numeric($form['produto']))) { 
			$resultado['erro'][] = 'Produto não encontrado'; 
			$resultado['resultado'] = false;
			}
		else {
			// Verifica se o produto existe
			$query="select id, titulo, valor_item as valor_unitario, (CASE WHEN (valor_desconto is null) THEN 0.00 ELSE valor_desconto END) as valor_desconto from produto where id = ".$action->secure($form['produto'])." and ativo = '1'";
			$result=$action->query_db($query);
			if ($action->result_quantidade($result)==0) {
				$resultado['erro'][] = 'Produto não encontrado'; 
				$resultado['resultado'] = false;
			}
			else {
				// retorna dados do produto
				$produto=$action->array_db($result);
			}
		}
		
		if (!isset($resultado['erro'])) {
			// verifica se ele já se encontra cadastrado no carrinho de compras do usuário
			$query = "select id, quantidade from loja_carrinho where fk_produto = ".$action->secure($form['produto'])." and cookie = '".$action->secure($cookie_loja)."' and ativo = '1'";
			$result=$action->query_db($query);
			// Caso positivo, chama a função de update de dados
			if ($action->result_quantidade($result)>0) {
				$r=$action->array_db($result);
				$form['quantidade']=$form['quantidade']+$r[0]['quantidade'];
				$resultado=$this->muda_quantidade($action,$cookie_loja,$form);
				$resultado['act']='muda_quantidade';
			}
			// Caso seja um item novo no carrinho, verificar se tem em estoque e adicionar no carrinho
			else {
				/*$produto[0]['quantidade']=$form['quantidade'];
				// Verifica se tem estoque do produto, caso não tenha, retorna erro
				if ($form['quantidade']>$produto[0]['estoque']) { 
					$resultado['erro'][] = 'Produto indisponivel (falta de estoque). Limitado a '.$produto[0]['estoque'].' unidade(s)<br /><a style="color:#fff" href="produto.php?go='.$produto[0]['id'].'">Clique aqui para adicionar com uma quantidade em estoque</a>'; 
					$resultado['resultado'] = false;
				}else { */
				
					$query = "	
					insert into loja_carrinho (
					cookie,
					fk_produto,
					quantidade,
					valor_unitario,
					valor_desconto,
					titulo,
					descricao,
					ativo)
					
					VALUES (
					'".$action->secure($cookie_loja)."',
					".$action->secure($produto[0]['id']).",
					".$action->secure($form['quantidade']).",
					'".$action->secure($produto[0]['valor_unitario'])."',
					'".$action->secure($produto[0]['valor_desconto'])."',
					'".$action->secure($produto[0]['titulo'])."',
					'".$action->secure($produto[0]['descricao'])."',
					'1')";
					$result=$action->query_db($query);
					$resultado['resultado']=true;
					$resultado['produto']=$produto[0]; 
					$resultado['act']='add_produto';
				
			}
		}
	return $resultado;
	}
	
//******************************** MODIFICA QUANTIDADE DE PRODUTO NO CARRINHO **************************************//
	public function muda_quantidade($action,$cookie_loja,$form) {
		if ((!is_numeric($form['quantidade'])) || (!is_numeric($form['produto']))) { 
			$resultado['erro'][] = 'Indique a quantidade'; 
			$resultado['resultado'] = false;
		}
		else { 
			$query = "select id, titulo from produto where id = ".$action->secure($form['produto']);
			$result=$action->query_db($query);
			if ($action->result_quantidade($result)==0) {
				$resultado['erro'][]='Produto não encontrado';
				$resultado['resultado'] = false;
			}
			else {
				$produto=$action->array_db($result);
				/*if ($produto[0]['estoque']<$form['quantidade']) {
				$resultado['erro'][]='Produto indisponivel (falta de estoque). Limitado a '.$produto[0]['estoque'].' unidade(s)'; 
				$resultado['resultado'] = false;
				}else {*/
				$query="update loja_carrinho set quantidade = ".$form['quantidade']." where fk_produto = ".$form['produto']." and cookie = '".$action->secure($cookie_loja)."' and ativo = '1' limit 1";
				$result=$action->query_db($query);
				$resultado['resultado']=true;

				$resultado['produto']=$produto[0]['titulo'];
				$resultado['quantidade']=$form['quantidade'];	
			}
		}
		$resultado['act']='muda_quantidade';
		//print_r($resultado);
		return $resultado;
	}
	
//*************************************** RETIRA PRODUTO DO CARRINHO ************************************************//
	public function del_produto($action,$cookie_loja,$form) {
		// Verifica se é numérico
		if (!is_numeric($form['produto'])) { 
			$resultado['resultado'] = false;
			$resultado['erro'][] = 'Produto indeterminado'; 
		}
		else {
			// Verifica se o produto existe na base de dados
			$query = "select * from produto where id = ".$action->secure($form['produto']);
			$result=$action->query_db($query);
			if ($action->result_quantidade($result)==0) {
				$resultado['resultado'] = false;
				$resultado['erro'][] = 'Produto não encontrado'; 
			}
			// Caso exista, trás o produto para o resultado da operação e exclui ele do carrinho de compras
			else {
				$r=$action->array_db($result);
				$resultado['produto']=$r[0]['titulo'];
				$resultado['produto_id']=$r[0]['id'];
				$query="delete from loja_carrinho where fk_produto = ".$action->secure($form['produto'])." and cookie = '".$cookie_loja."' and ativo = '1' limit 1";
				$result=$action->query_db($query);
				$resultado['resultado']=true;
				$resultado['act']='del_produto';
			}
		}
		return $resultado;		
	}
	
	
	//******************************************FUNÇÃO FRETE***********************************************************//
	public function frete($action,$cookie_loja,$form) {
		// Rotina de retorno de dados para frete buscar na loja
		/* if ((!isset($form['tipo_frete'])) || ($form['tipo_frete'] == 1)) {
			$x=0;
			$frete[$x]['res']['codigo']='1';
			$frete[$x]['res']['descricao']='Recebimento pelo consumidor em nossa loja fisica';
			$frete[$x]['res']['valor']='0,00';
			$frete[$x]['res']['prazo']='0';
			$frete[$x]['res']['valor_declarado']=$form['valor_declarado'];
		}*/
		// Rotina de cálculo de fretes pelos correios
		if ((!isset($form['tipo_frete'])) || ($form['tipo_frete'] > 40000)) {
			// Tratamento dos tipos de serviços dos correios
			if (isset($form['tipo_frete'])) { 
				$data['nCdServico']=$form['tipo_frete']; }
			else {
				$data['nCdServico'] = '40010,41106'; 
				/*
				
				04510 - PAC (código antigo 41106 , alterado em 05/05/2017)
				04014 - SEDEX (código antigo 40010, alterado em 05/05/2017)
				40045 - SEDEX a Cobrar
				40215 - SEDEX 10
				40290 - SEDEX Hoje
				$data['nCdServico'] = '04014,04510';
				*/
				
			}



			//Código da sua empresa, se você tiver contrato com os correios saberá qual é esse código… 
			$data['nCdEmpresa'] = '';
			// Senha de acesso ao serviço. Geralmente é os 8 primeiros números do CNPJ correspondente ao código administrativo
			// Caso não tiver é só passar o parâmetro em branco – Se quiser alterar a senha é só clicar aqui 
			// http://www.corporativo.correios.com.br/encomendas/servicosonline/recuperaSenha
			$data['sDsSenha'] = '';
			
			
			// Dados que serão erados pela loja
			// Cep de Origem - apenas números
			if (!is_numeric($form['cep_origem'])) { $frete['erro'][]='CEP de Origem precisa ser numérico'; } 
			else { $data['sCepOrigem'] = $form['cep_origem']; }
			
			// Cep de destino
			if (!isset($form['cep_destino'])) { $frete['erro'][]='CEP de Destino precisa ser numérico'; } 
			else { $data['sCepDestino'] = $form['cep_destino']; }
			
			// Peso em Kg
			if (!isset($form['peso_total'])) { 
				$data['nVlPeso']='0.5'; 
			}
			else { 
				$peso=round($form['peso_total'],2); 
				if ($peso<30) {
					$data['nVlPeso']=$peso;
					$data['multiplicador']=1;
				}
				else {
					$multiplicador=explode('.',$peso/30);
					$data['multiplicador']=$multiplicador[0]+1;
				}
			}
	
			// Formato - 1= caixa 2= prisma 3=envelope
			$data['nCdFormato'] = '1';
			
			// largura, comprimento e altura
			if (!isset($form['aresta_frete'])) { 
				$data['nVlAltura']='11'; 
				$data['nVlLargura']='11';
				$data['nVlComprimento']='16';
			}
			if ($form['aresta_frete']<16) {
				$data['nVlAltura']='11'; 
				$data['nVlLargura']='11';
				$data['nVlComprimento']='16';
			}
			else {
				if ($form['aresta_frete']>65) {
					$multiplicador=explode('.',$form['aresta_frete']/65);
					$teste_multiplicador=$multiplicador[0]+1;
					if ($teste_multiplicador>$data['multiplicador']) {
						$data['multiplicador']=$teste_multiplicador;
					}
					$form['aresta_frete']=$form['aresta_frete']/$data['multiplicador'];
				}
				
				$data['nVlAltura']=$form['aresta_frete']; 
				$data['nVlLargura']=$form['aresta_frete']; 
				$data['nVlComprimento']=$form['aresta_frete']; 
			}
			//print_r($data);
			$form['nVlDiametro'] = '0';
			
			
			// Valor declarado 
			if (!isset($form['valor_declarado'])) { $data['nVlValorDeclarado']=200; }
			else { 
				if ($form['valor_declarado']>10000) { $data['nVlValorDeclarado']=10000; }
				else { $data['nVlValorDeclarado']=$form['valor_declarado']; }
			}
			
			// Serviço adicionam mão própria
			$data['sCdMaoPropria'] = 'n';
			// serviço de aviso de recebimento
			$data['sCdAvisoRecebimento'] = 'n';
			$data['StrRetorno'] = 'xml';	
			
			
			//print_r($data);
			
			// Multiplicador para cálculos acima de dimensões e pesos possiveis					
			$multiplicador_final=$data['multiplicador'];

			if (!isset($erro)) {
				$data = http_build_query($data);
				$url = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx';
				$curl = curl_init($url . '?' . $data);

				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$result = curl_exec($curl);
				if (curl_errno($curl)=='0') {
				$result = simplexml_load_string($result);
				//print_r($result);
				foreach($result -> cServico as $row) {
					//Os dados de cada serviço estará aqui
	//				print_r($row);
					 if($row -> Erro == 0) {
						if (!isset($x)) { $x=0; } else { $x=$x+1; }
						$frete[$x]['res']['codigo'] = (string) $row -> Codigo;
						 if ($frete[$x]['res']['codigo'] == 40010) { $frete[$x]['res']['descricao'] = 'SEDEX Correios'; }
						elseif ($frete[$x]['res']['codigo'] == 41106) { $frete[$x]['res']['descricao'] = 'PAC Correios'; }
						else { }
	//					else { $frete[$x]['res']['descricao'] = 'SERVIÇO DESCONHECIDO Correios'; }
						$frete[$x]['res']['valor'] = number_format((string) $row -> Valor*$multiplicador_final,2);
						$frete[$x]['res']['prazo'] = (string) $row -> PrazoEntrega;
						$frete[$x]['res']['valor_declarado'] = $form['valor_declarado'];
					//	print_r($frete[$x]);
					} else {
						$frete['erro'][] = (string) $row -> MsgErro;
					}
				}
			} 
			else {
				$frete['erro'][]='Erro ao conectar com o site dos correios';
			}
		}
		
		}


		// Cálculo de frete da loja
		if ((!isset($form['tipo_frete'])) || ($form['tipo_frete'] == 2)) {
			if (!isset($x)) { $x=0; } else { $x=$x+1; }
			
			$query = "select * from frete_loja_view where cep_inicial < ".$action->secure($form['cep_destino'])." and cep_final > ".$action->secure($form['cep_destino']). " and ativo = '1'";
			//var_dump($query); die;
			$result=$action->query_db($query);
			if ($action->result_quantidade($result)==1) {
				$r=$action->array_db($result);
				$frete[$x]['res']['codigo']=2;
				$frete[$x]['res']['descricao']='Frete proprio da loja';
//				echo lala.$form['valor_declarado'].'<br />'.$r[0]['valor_minimo_gratuidade'];
				if ($form['valor_declarado']>=$r[0]['valor_minimo_gratuidade']) {
					$frete[$x]['res']['valor']='0,00';
				}
				else {
					$frete[$x]['res']['valor']=$r[0]['valor_frete'];
				}
				$frete[$x]['res']['prazo']=$r[0]['prazo_entrega'];
				$frete[$x]['res']['valor_declarado']=$form['valor_declarado'];
			}
		}			
		// Caso haja erro, retorna erro
		else {
		}
//	print_r($frete);
	return($frete);
	}	
}


// Aqui funciona a nossa mágica ;)
// Adicionar esse arquivo a todas as páginas que funcionam com funções do carrinho de compras
// Ou deixar dentro do próprio arquivo da classe para facilitar a vida dos "café com leite"

$car = new carrinho();

if (isset($_POST['act'])) {
	
	if ($_POST['act']=='add_produto') {
		$form['quantidade']=$_POST['quantidade'];
		$form['produto']=$_POST['go'];
		$resultado=$car->add_produto($action,$cookie_loja,$form);
		
	}
	
	elseif ($_POST['act']=='muda_quantidade') {
		$form['quantidade']=$_POST['quantidade'];
		$form['produto']=$_POST['go'];
		$resultado=$car->muda_quantidade($action,$cookie_loja,$form);
	}
	
	elseif ($_POST['act']=='del_produto') {
		$form['produto']=$_POST['go'];
		$resultado=$car->del_produto($action,$cookie_loja,$form);
	}
	
	elseif ($_POST['act']=='limpa_carrinho') {
		$form['quantidade']=$_POST['quantidade'];
		$form['produto']=$_POST['go'];
		$resultado=$car->limpa_carrinho($action,$cookie_loja,$form);
		
	}
	else {
	}
	

	if($_POST['act']=='getFrete'){	
		if($_POST['tipo_endereco'] == '0'){

			$endLoja = '
				<table width="100%" cellspacing="3" cellpadding="3" style="text-transform: uppercase">
					<tr>
						<td>ENDEREÇO ESCOLHIDO!</td>
					</tr>
					
					<tr>
						<td><strong>Dados do endereço da Loja Física</strong></td>
					</tr>
					
					<tr>
						<td><b>Cidade:</b> '.$config['cidade_pagseguro'].' - '.$config['estado_pagseguro'].'</td>
					</tr>
					
					<tr>
						<td><b>ENDEREÇO:</b> '.$config['endereco_pagseguro'].'</td>
					</tr>
					
					<tr>
						<td><b>Número:</b> '.$config['numero_pagseguro'].'</td>
					</tr>
					
					<tr>
						<td><b>CEP:</b> '.$config['cep_pagseguro'].'</td>
					</tr>

				</table>';	
		}else{
			
			$query = "select * from cadastro_cliente_endereco where id = '".$_POST['tipo_endereco']."' and ativo = '1' ";
			$result  = $action->query_db($query);
			$r = $action->array_db($result);
			$r = $r[0];
			$cep_destino = onlynumbers($r['cep']); 
			
		} 

	}else{	
		//aqui faz o calculo do cep(inicial)
		if (isset($_POST['cep_destino'])) {
			setcookie ('cep_destino',onlynumbers($_POST['cep_destino']),time()+36000,"/"); 
			$cep_destino = onlynumbers($_POST['cep_destino']); 
		}
		else {
			if (isset($_COOKIE['cep_destino'])) { 
				$cep_destino=$_COOKIE['cep_destino']; 
			}
		}
			
	}
	$carrinho = $car -> retorna_dados($action,$cookie_loja);
	
	//INÍCIO DA ROTINA DE CÁLCULO DE FRETE
	if ((is_numeric($user['id'])) && (is_numeric($_POST['endereco']))) {
		$query = "select * from cadastro_cliente_endereco where fk_cadastro = ".$action->secure($user['id'])." and id = ".$action->secure($_POST['endereco']);
		$result=$action->query_db($query);
		if ($action->result_quantidade($result)>0) {
			$r=$action->array_db($result);
			$cep_destino=onlynumbers($r[0]['cep']);
		}
	}
	
	// Calcula frete
	if ((isset($cep_destino)) && (sizeof($carrinho)>0)) {
	
		$dadosfrete['aresta_frete']		=$carrinho['consolidado']['aresta_frete'];
		$dadosfrete['cep_origem']		=$config['cep_pagseguro'];
		$dadosfrete['valor_declarado']	=$carrinho['consolidado']['valor_total'];
		$dadosfrete['peso_total']		=$carrinho['consolidado']['peso_total'];
		$dadosfrete['cep_destino'] 		= onlynumbers($cep_destino); 
		
		
		/*if (is_numeric($_POST['tipo_frete'])) { 
			$query = "select * from loja_frete_tipo where codigo = ".$action->secure($_POST['tipo_frete'])." and ativo = '1'";
			$result=$action->query_db($query);
			if ($action->result_quantidade($result)>0) {
				$dadosfrete['tipo_frete']=$_POST['tipo_frete']; 
			}
		}*/
//		print_r($dadosfrete);
		$frete = $car -> frete($action,$cookie_loja,$dadosfrete);
//		print_r($frete);
//		print_r($frete);
	}

	$carrinho = $car -> retorna_dados($action,$cookie_loja);
// FINAL DA ROTINA DE CÁLCULO DE FRETE
}


else {
	$carrinho = $car -> retorna_dados($action,$cookie_loja);
}

if (isset($resultado['erro'])) { 
	for ($x=0;$x<sizeof($resultado['erro']);$x++) {
		$erro.='<li>'.$resultado['erro'][$x].'</li>'; 
	}
}

// debug
//$debug_carrinho=true;
if ((isset($debug_carrinho)) && ($debug_carrinho==true)) {
	echo 'resultado<pre>'; @print_r($resultado); echo '</pre>';
	echo 'carrinho<pre>'; @print_r($carrinho); echo '</pre>';
	echo 'dadosfrete<pre>'; @print_r($dadosfrete); echo '</pre>';
	echo 'frete<pre>'; @print_r($frete); echo '</pre>';
	echo 'cookie<pre>'; @print_r($_COOKIE); echo '</pre>';
	echo 'request<pre>'; @print_r($_REQUEST); echo '</pre>';
}
