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
            $this->getfrete['nCdServico'] = $codigo;
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
            if (in_array($this->getfrete['nCdServico'],$this->codServicoCcmCon)) {
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
        return $retorno;
    }
    
    private function getFrete($url) {
        
    }
    
    public function valculaFrete($dados) {
        
    }
    
}
    
   


$action = false;
$frete = new CorreiosFrete($action);
 
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

$f = $frete->validaDados($dados);

var_dump($f);
    
exit;
    
