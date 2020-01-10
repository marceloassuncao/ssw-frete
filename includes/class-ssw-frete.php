<?php
if (!defined('ABSPATH')):
    exit();
endif;


add_filter( 'woocommerce_shipping_methods', 'register_ssw_frete' );

function register_ssw_frete( $methods ) {

    // $method contains available shipping methods
	$methods[ 'ssw_frete' ] = 'WC_SSW_Frete';

	return $methods;
}


/**
 * WC_SSW_Frete class.
 *
 * @class 		WC_SSW_Frete
 * @version		1.0.0
 * @package		Shipping-for-WooCommerce/Classes
 * @category	Class
 * @author 		Marcelo Assunção
 */
class WC_SSW_Frete extends WC_Shipping_Method {

	/**
	 * Constructor. The instance ID is passed to this.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                    = 'ssw_frete';
		$this->instance_id           = absint( $instance_id );
		$this->method_title          = 'SSW Frete';
		$this->method_description    = 'SSW Frete - Método de entrega personalizável com suporte a todas as transportadoras registradas no sistema SSW.';
		$this->supports              = array(
			'shipping-zones',
			'instance-settings',
		);
		$this->instance_form_fields = array(
			'enabled' => array(
				'title' 		=> __( 'Enable/Disable' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Ativar método de entrega?' ),
				'default' 		=> 'no',
			),
			'title' => array(
				'title' 		=> __( 'Method Title' ),
				'type' 			=> 'text',
				'description' 	=> __( 'Nome do Frete/Transportadora.' ),
                'placeholder'		=> __( 'Nome da Transportadora' ),
                'default'		=> 'Frete SSW',
				'desc_tip'		=> true
            ),
            'dominio' => array(
				'title' 		=> 'Dominio',
				'type' 			=> 'text',
				'description' 	=> __( 'Dominio configurado no sistema.' ),
				'placeholder'		=> __( 'ABC' ),
				'desc_tip'		=> true
			),'login' => array(
				'title' 		=> __( 'Login' ),
				'type' 			=> 'text',
				'description' 	=> __( '' ),
				'placeholder'		=> __( 'ABC!123' ),
				'desc_tip'		=> true
            ),'senha' => array(
				'title' 		=> __( 'Senha' ),
				'type' 			=> 'text',
				'description' 	=> __( '' ),
				'placeholder'		=> __( 'ABC!123' ),
				'desc_tip'		=> true
            ),'cnpjPagador' => array(
				'title' 		=> __( 'CNPJ Pagador' ),
				'type' 			=> 'text',
				'description' 	=> __( 'Informar apenas Números!' ),
				'placeholder'		=> __( '12345678912345' ),
				'desc_tip'		=> true
            ),'senhaPagador' => array(
				'title' 		=> __( 'Senha Pagador' ),
				'type' 			=> 'text',
				'description' 	=> __( '' ),
				'placeholder'		=> __( 'ABC!123' ),
				'desc_tip'		=> true
            ),'cepOrigem' => array(
				'title' 		=> __( 'Cep Origem' ),
				'type' 			=> 'text',
                'description' 	=> __( 'Cep de Origem/Saída do produto para entrega.
                OBS: Informar apenas Números!' ),
				'placeholder'		=> __( '01234567' ),
				'desc_tip'		=> true
            ),
        );
        
		$this->enabled                       = $this->get_option( 'enabled' );
        $this->title                         = $this->get_option( 'title' );
        $this->dominio                      = $this->get_option( 'dominio' );
        $this->login                        = $this->get_option( 'login' );
        $this->senha                        = $this->get_option( 'senha' );
        $this->cnpjPagador                  = $this->get_option( 'cnpjPagador' );
        $this->senhaPagador                 = $this->get_option( 'senhaPagador' );
        $this->cepOrigem                    = $this->get_option( 'cepOrigem' );
        $this->ssw_frete_option            = [
            'enabled' => $this->enabled,
            'title' => $this->title,
            'dominio' => $this->dominio,
            'login' => $this->login,
            'senha' => $this->senha,
            'cnpjPagador' => $this->cnpjPagador,
            'senhaPagador' => $this->senhaPagador,
            'cepOrigem' => $this->cepOrigem,
        ];
        

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }
    
    /**
     * calculate_shipping function.
     * @param array $package (default: array())
     */
    public function calculate_shipping( $package = array() ) {

        $ssw_frete_calc = calcSSWFrete($package, $this->ssw_frete_option);
        
        if($this->enabled == 'yes' && $ssw_frete_calc['erro'] == '0'){
            $shipping_name = $this->title. ' - (Entrega em até '.$ssw_frete_calc['prazo'].' dias)';
            $cost = $ssw_frete_calc['totalFrete'];

            $rate = array(
                'id' => $this->id,
                'label' => $shipping_name,
                'cost' => $cost
            );
            if(get_current_user_id() == 4 || get_current_user_id() == 1){
                $this->add_rate( $rate );
            }
            
            
        }
    }



}

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function calcSSWFrete($package = null, $ssw_frete_option = null){
    if(!$package || !$ssw_frete_option){
        return;
    }
    
    $cep = str_replace('-', '', $package['destination']['postcode']);

    $valorNF = $package['contents_cost'];
    settype($valorNF, "float");
    $qtd = 0;
    $peso_total = 0;
    $volume_total = 0;
    $test_dimensao = [];
    foreach ($package['contents'] as $key => $value) {
        $qtd += $value['quantity'];
        $peso = 0;
        $prod_id = ($value['variation_id'] ? $value['variation_id'] : $value['product_id']);
        // $prod_id = $value['product_id'];
        $product = wc_get_product( $prod_id );
        $peso = $product->get_weight();
        $peso_total += $peso*$qtd;

        $comprimento = $product->get_length();
        $largura = $product->get_width();
        $altura = $product->get_height();
        $test_dimensao[] = ['qtd' => $qtd, 'peso'=>$peso, 'c' => $comprimento, 'l' => $largura, 'a' => $altura];
        $volume = $comprimento*$largura*$altura;

        $volume_total += $volume*$qtd;
        
    }       

$postfields = "<Envelope xmlns=\"http://schemas.xmlsoap.org/soap/envelope/\">\n    <Body>\n        <cotar xmlns=\"urn:sswinfbr.sswCotacaoCliente\">\n           <dominio>".$ssw_frete_option['dominio']."</dominio>\n            <login>".$ssw_frete_option['login']."</login>\n            <senha>".$ssw_frete_option['senha']."</senha>\n            <cnpjPagador>".$ssw_frete_option['cnpjPagador']."</cnpjPagador>\n            <senhaPagador>".$ssw_frete_option['senhaPagador']."</senhaPagador>\n            <cepOrigem>".$ssw_frete_option['cepOrigem']."</cepOrigem>\n            <cepDestino>".$cep."</cepDestino>\n            <valorNF>".$valorNF."</valorNF>\n            <quantidade>".$qtd."</quantidade>\n            <peso>".$peso_total."</peso>\n            <mercadoria></mercadoria>\n            <volume>".$volume_total."</volume>\n        </cotar>\n    </Body>\n</Envelope>";
// return htmlentities($postfields);
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://ssw.inf.br/ws/sswCotacaoCliente/index.php",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => $postfields,
  CURLOPT_HTTPHEADER => array(
    "Accept: text/xml",
    "Accept-Encoding: gzip, deflate",
    "Content-Type: text/xml",
    "Host: ssw.inf.br",
    "cache-control: no-cache"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);
if ($err) {
    return;
}
$response = get_string_between($response, '<return xsi:type="xsd:string">', '</return>');
$response = html_entity_decode($response);
$response = (array)simplexml_load_string($response);
return $response;

}