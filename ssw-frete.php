<?php



/**

 * Plugin Name: Calculo de Frete SSW

 * Description: Plugin de frete integrado com o sistema SSW, utilizado por transportadoras.

 * Author: <a href="https://www.linkedin.com/in/marcelo-assun%C3%A7%C3%A3o-dos-santos-junior-19162317a/" _target='blank'>Marcelo Assunção</a> 

 * Version: 1.0.0

 */





if (!defined('ABSPATH')):

    exit();

endif;





define('SSW_FRETE_PATH', plugin_dir_path(__FILE__));

define('SSW_FRETE_URL', plugin_dir_url(__FILE__));



define('SSW_FRETE_INCLUDES_PATH', plugin_dir_path(__FILE__) . 'includes/');

define('SSW_FRETE_INCLUDES_URL', plugin_dir_url(__FILE__) . 'includes/');



define('SSW_FRETE_VIEWS_PATH', plugin_dir_path(__FILE__) . 'views/');

define('SSW_FRETE_VIEWS_URL', plugin_dir_url(__FILE__) . 'views/');



define('SSW_FRETE_ASSETS_PATH', plugin_dir_path(__FILE__) . 'assets/');

define('SSW_FRETE_ASSETS_URL', plugin_dir_url(__FILE__) . 'assets/');





if (!class_exists('sswFrete')):



    class sswFrete{



        /**

         * Instance of this class

         *

         * @var object

         */







        protected static $sswFrete = null;





        private function __construct(){

            /**

             * Include plugin files

             */



            $this->enqueue_includes();



            // $this->enqueue_views();







        }











        public static function ssw_frete_start(){



            if (self::$sswFrete == null):



                self::$sswFrete = new self();



            endif;







            return self::$sswFrete;



        }





        private function enqueue_includes(){



            include_once SSW_FRETE_INCLUDES_PATH . 'class-ssw-frete.php';



        }







        // private function enqueue_views(){



        //     include_once SSW_FRETE_VIEWS_PATH . 'view-ssw-frete.php';



        // }







    }











    //Start's when plugins are loaded plugin



    // add_action('plugins_loaded', array('sswFrete', 'ssw_frete_start'));







     

    



    add_action('init', array('sswFrete', 'ssw_frete_start'));





endif;