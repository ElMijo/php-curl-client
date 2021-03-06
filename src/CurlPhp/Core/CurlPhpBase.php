<?php

namespace CurlPhp\Core;

use CurlPhp\Core\CurlPhpMensajes as MSG;
use CurlPhp\Core\CurlPhpCabeceras;
use CurlPhp\Core\CurlPhpError;
use CurlPhp\Core\CurlPhpRespuesta;


/**
* Clase que contiene los metodoa base de la conexión cURL
*/
class CurlPhpBase extends CurlPhpCabeceras
{
	/**
 	* Versión de la clase
 	*/
	const VERSION = '1.0';

	/**
  * Respuesta de la ejecucion del objeto CuRL
  * @var String
  */
  private $respuesta_pura = NULL;

	/**
 	* Cuerpo de la respuesta de la peticion
 	* @var string
 	*/
	protected $cuerpo_respuesta = '';


	/**
 	* Arreglo con los COOKIES que se desean incluir en el Request
 	* @var array
 	*/
  protected $cookies = array();

  /**
  * Arreglo con los cabeceras que se desean incluir en el Request
  * @var array
  */
  protected $cabeceras = array();
  /**
  * Arreglo con las OPCIONES que se desean incluir en el Request
  * @var array
  */
  protected $opciones = array();

  /**
  * Objeto CuRL
  * @var CuRL
  */
  protected $curl             = NULL;


	function __construct()
	{
		if(!extension_loaded('curl')) {

      throw new \ErrorException(MSG::NO_EXTENCION_CURL);

    }

		$this->curl = curl_init();
		$this->defineUserAgentPorDefecto();
    $this->definirOpcionCuRL(CURLINFO_HEADER_OUT, true);
    $this->definirOpcionCuRL(CURLOPT_HEADER, true);
    $this->definirOpcionCuRL(CURLOPT_RETURNTRANSFER, true);

	}


	/**
 	* Permite definir el User Agent que se utilizara en la conexion
 	* @param  String     $user_agent     Cadena cvon el User Agent
 	* @return CurlPhp
 	*/
	public function defineUserAgent($user_agent = '')
  {

   	$this->definirOpcionCuRL(CURLOPT_USERAGENT, $user_agent);

   	return $this;
  }

  /**
   * Permite definir opociones propias de CuRL
   * @param String                     $option     Cadena con el nombre de la opcion que se desea definir
   * @param String|Boolean|Numeric     $value      Valor que se desea definir
   * @param Boolean                                Devuelve TRUE si la operacion se realizo corectamente o FALSE en caso contrario
   */
	public function definirOpcionCuRL($opcion, $valor)
  {

       	$opciones_requeridas = array(
           	CURLINFO_HEADER_OUT    => 'CURLINFO_HEADER_OUT',
           	CURLOPT_HEADER         => 'CURLOPT_HEADER',
           	CURLOPT_RETURNTRANSFER => 'CURLOPT_RETURNTRANSFER',
       	);

       	if (in_array($opcion, array_keys($opciones_requeridas), true) && !($valor === true)) {
          	trigger_error($opciones_requeridas[$opcion] . MSG::OPCION_NECESARIA, E_USER_WARNING);
       	}

       	$this->opciones[$opcion] = $valor;

       	return curl_setopt($this->curl, $opcion, $valor);
   	}

   	/**
   	* Permite obtener un valor definido entre las opciones CuRL
   	* @param  String                          $option     Cadena con el nombre de la opcion que se desea obtener
   	* @return String|Boolean|Numeric|NULL     $value      Devuelve el Valor definido o NULL en caso de no estar definido
   	*/
   	public function optenerOpcionCuRL($opcion){

       	return isset($this->opciones[$opcion])?$this->opciones[$opcion]:NULL;

   	}

   	/**
   	* Permite definir la o las cabeceras que se desean incluir en la conexion
   	* @param  array     $cabeceras     Arreglo asociativo de Cabeceras
   	* @return CurlPhp
   	*/
   	public function definirCabeceras($cabeceras = array()){

   		if($this->is_array_assoc($cabeceras)){

   			$cabeceras = $this->preparaCabeceras($cabeceras);

    		$this->definirOpcionCuRL(CURLOPT_HTTPHEADER, $cabeceras);

   		}

   		return $this;

   	}

    /**
     * Permite definir el metodo de autenticación basica como metodo de autenticacion
     * @param  String     $usuario     Cadena con el nombre de usuario
     * @param  String     $clave       Cadena con la clave
     * @return CurlPhp
     */
	public function definirBasicAuthentication($usuario = '', $clave = '')
    {
        $this->definirOpcionCuRL(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $this->definirOpcionCuRL(CURLOPT_USERPWD, "$usuario:$clave");
        return $this;
    }

    /**
     * Permite definir la url Referrer de la conexión
     * @param  string     $referrer     Cadena con la url que se desea asignar como referrer
     * @return CurlPhp
     */
	public function definirReferrer($referrer = '')
    {
        $this->definirOpcionCuRL(CURLOPT_REFERER, $referrer);
        return $this;
    }

    /**
     * Permite definir Cookies que se desean incluir en la conexion
     * @param  String     $key       Cadena con el nombre de la Coohie que se quiere definir
     * @param  String     $value     Cadena con el valor de la cookie
     * @return CurlPhp
     */
    public function definirCookie($key, $value)
    {
        $this->cookies[$key] = $value;
        $this->definirOpcionCuRL(CURLOPT_COOKIE, http_build_query($this->cookies, '', '; '));
        return $this;
    }

    /**
     * Permite importar Cookies desde un archivo
     * @param  String     $cookie_file     Cadena de texto con la ruta del archivo que se desea importar
     * @return CurlPhp
     */
    public function definirCookieFile($cookie_file)
    {
        $this->definirOpcionCuRL(CURLOPT_COOKIEFILE, $cookie_file);
        return $this;
    }

    /**
     * Permite definir el archivo donde de desean almacenar las Cookies localmente
     * @param  sTRING     $cookie_jar     Cadena de texto con la ruta del archivo donde se desea almacenar las Cookies
     * @return CurlPhp
     */
    public function definirCookieJar($cookie_jar)
    {
        $this->definirOpcionCuRL(CURLOPT_COOKIEJAR, $cookie_jar);
        return $this;
    }

   	/**
   	* Permite eliminar cabeceras de la conexión
   	* @param  String     $key     Cadena con el nombre de la cabecera a eliminar
   	* @return CurlPhp
   	*/
   	public function eliminarCabecera($key)
   	{
       	$this->definirCabeceras(array("$key" => ''));
       	unset($this->cabeceras[$key]);
       	return $this;
   	}


    /**
   	 * Permite cerrar el objeto CuRL
   	*/
   	public function cerrar()
   	{
       	if (is_resource($this->curl)) {
            curl_close($this->curl);
   	    }
   	}


  protected function ejecurtarCurl()
  {

    $this->respuesta_pura = curl_exec($this->curl);

    $cabeceras = '';

    if (!(strpos($this->respuesta_pura, "\r\n\r\n") === false))
    {
      $respuesta_pura_procesada = explode("\r\n\r\n", trim($this->respuesta_pura));

      if(count($respuesta_pura_procesada)>2)
      {
        $respuesta_pura_procesada = array($respuesta_pura_procesada[1],$respuesta_pura_procesada[2]);
      }

      list($cabeceras,$this->cuerpo_respuesta) = $respuesta_pura_procesada;

    }

    $this->extraerCabeceras($cabeceras);

    return (object)array(
      "errores"   => new CurlPhpError($this->curl,$this->cabeceras_respuesta),
      "respuesta" => new CurlPhpRespuesta($this->cabeceras_solicitud,$this->cabeceras_respuesta,$this->cuerpo_respuesta)
    );

    /*return */
  }

	/**
   	* Permite definir un User Agent por defecto
   	* @return CurlPhp
   	*/
	private function defineUserAgentPorDefecto()
   	{
       	$user_agent = 'mppi-curl-php/'. self::VERSION . ' (+http://git.mppi.gob.ve/janselmi/mppi-curl-php)';
       	$user_agent .= ' PHP/' . PHP_VERSION;
       	$curl_version = curl_version();
       	$user_agent .= ' curl/' . $curl_version['version'];
       	$this->defineUserAgent($user_agent);
       	return $this;
   }

       /**
     * Permite validar si un arrays es asociativo
     * @param  Array      $array     Arreglo que se desea evaluar
     * @return boolean               Devuelve TRUE si el arreglo es asociativo o FAÑSE en caso contrario
     */
    private function is_array_assoc($array = array())
    {
        return (bool)count(array_filter(array_keys($array), 'is_string'));
    }
}
?>