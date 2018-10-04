<?php

namespace Bow\Http;

use Bow\Router\Route;
use Bow\Session\Session;
use Bow\Support\Collection;
use Bow\Support\Str;

class Request
{
    /**
     * Variable d'instance
     *
     * @static self
     */
    private static $instance ;

    /**
     * Variable de paramètre issue de url définie par l'utilisateur
     * e.g /users/:id . alors params serait params->id == une la value suivant /users/1
     *
     * @var UrlParameter
     */
    public $param;

    /**
     * @var Input
     */
    private $input;

    /**
     * Constructeur
     */
    private function __construct()
    {
        $this->input = new Input();

        $this->param = new UrlParameter([]);

        @Session::add('__bow.old', $this->input->all());
    }

    /**
     * Singletion loader
     *
     * @return null|self
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Get request value
     *
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->input->get($key, $default);
    }

    /**
     * Check if key is exists
     *
     * @param string $key
     * @return mixed
     */
    public function has($key)
    {
        return $this->input->has($key);
    }

    /**
     * Get all input value
     *
     * @return array
     */
    public function all()
    {
        return $this->input->all();
    }

    /**
     * retourne uri envoyer par client.
     *
     * @return string
     */
    public function uri()
    {
        $pos = strpos($_SERVER['REQUEST_URI'], '?');

        if ($pos) {
            $uri = substr($_SERVER['REQUEST_URI'], 0, $pos);
        } else {
            $uri = $_SERVER['REQUEST_URI'];
        }

        return $uri;
    }

    /**
     * retourne le nom host du serveur.
     *
     * @return string
     */
    public function hostname()
    {
        return $_SERVER['HTTP_HOST'];
    }

    /**
     * retourne url envoyé par client.
     *
     * @return string
     */
    public function url()
    {
        return $this->origin().$this->uri();
    }

    /**
     * origin le nom du serveur + le scheme
     *
     * @return string
     */
    public function origin()
    {
        if (!isset($_SERVER['REQUEST_SCHEME'])) {
            return 'http://' . $this->hostname();
        }

        return $this->scheme().'://'.$this->hostname();
    }

    /**
     * Request scheme
     *
     * @return string
     */
    public function scheme()
    {
        return isset($_SERVER['REQUEST_SCHEME']) ? strtolower($_SERVER['REQUEST_SCHEME']) : 'http';
    }

    /**
     * retourne path envoyé par client.
     *
     * @return string
     */
    public function time()
    {
        return $_SESSION['REQUEST_TIME'];
    }

    /**
     * Retourne la methode de la requete.
     *
     * @return string
     */
    public function method()
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;

        if ($method == 'POST') {
            if (array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
                if (in_array($_SERVER['HTTP_X_HTTP_METHOD'], ['PUT', 'DELETE'])) {
                    $method = $_SERVER['HTTP_X_HTTP_METHOD'];
                }
            }
        }

        return $method;
    }

    /**
     * Si la réquête est de type POST
     *
     * @return bool
     */
    public function isPost()
    {
        if ($this->method() == 'POST') {
            return true;
        }

        return false;
    }

    /**
     * Si la réquête est de type GET
     *
     * @return bool
     */
    public function isGet()
    {
        if ($this->method() == 'GET') {
            return true;
        }

        return false;
    }

    /**
     * Si la réquête est de type PUT
     *
     * @return bool
     */
    public function isPut()
    {
        if ($this->method() == 'PUT' || $this->input->get('_method', null) == 'PUT') {
            return true;
        }

        return false;
    }

    /**
     * Si la réquête est de type DELETE
     *
     * @return bool
     */
    public function isDelete()
    {
        if ($this->method() == 'DELETE' || $this->input->get('_method', null) == 'DELETE') {
            return true;
        }

        return false;
    }

    /**
     * Charge la factory pour le FILES
     *
     * @param  string $key
     * @return UploadFile|Collection
     */
    public function file($key)
    {
        if (!isset($_FILES[$key])) {
            return null;
        }

        if (!is_array($_FILES[$key]['name'])) {
            return new UploadFile($_FILES[$key]);
        }

        $files = $_FILES[$key];

        $collect = [];

        foreach ($files['name'] as $key => $name) {
            $file['name'] = $name;

            $file['type'] = $files['type'][$key];

            $file['size'] = $files['size'][$key];

            $file['error'] = $files['error'][$key];

            $file['tmp_name'] = $files['tmp_name'][$key];

            $collect[] = new UploadFile($file);
        }

        return new Collection($collect);
    }

    /**
     * Charge la factory pour le FILES
     *
     * @return array
     */
    public function files()
    {
        $files = [];

        foreach ($_FILES as $key => $file) {
            $files[$key] = static::file($key);
        }

        return $files;
    }

    /**
     * @param mixed $key
     * @return bool
     */
    public static function hasFile($key)
    {
        return isset($_FILES[$key]);
    }

    /**
     * Change le factory RequestData pour tout les entrés PHP (GET, FILES, POST)
     *
     * @param  string $key
     * @return Input
     */
    public function input($key = null)
    {
        if (!is_null($key)) {
            return $this->input->get($key);
        }

        return $this->input;
    }

    /**
     * Accès au donnée de la précédente requete
     *
     * @param  mixed $key
     * @return mixed
     */
    public static function old($key)
    {
        $old = Session::get('__bow.old', []);

        return isset($old[$key]) ? $old[$key] : null;
    }

    /**
     * Vérifie si on n'est dans le cas d'un requête AJAX.
     *
     * @return boolean
     */
    public function isAjax()
    {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            return false;
        }

        $xhr_obj = Str::lower($_SERVER['HTTP_X_REQUESTED_WITH']);

        if ($xhr_obj == 'xmlhttprequest' || $xhr_obj == 'activexobject') {
            return true;
        }

        return false;
    }

    /**
     * Vérifie si une url match avec le pattern
     *
     * @param  string $match Un regex
     * @return int
     */
    public function is($match)
    {
        return preg_match('~' . $match . '~', $this->uri());
    }

    /**
     * clientAddress, L'address ip du client
     *
     * @return string
     */
    public function ip()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * clientPort, Retourne de port du client
     *
     * @return string
     */
    public function port()
    {
        return $_SERVER['REMOTE_PORT'];
    }

    /**
     * Retourne la provenance de la requête courante.
     *
     * @return string
     */
    public function referer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
    }

    /**
     * retourne la langue de la requête.
     *
     * @return string|null
     */
    public function language()
    {
        return Str::slice($this->locale(), 0, 2);
    }

    /**
     * retourne la locale de la requête.
     *
     * la locale c'est langue original du client
     * e.g fr => locale = fr_FR // français de france
     * e.g en => locale [ en_US, en_EN]
     *
     * @return string|null
     */
    public function locale()
    {
        $local = '';

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $tmp = explode(';', $_SERVER['HTTP_ACCEPT_LANGUAGE'])[0];

            preg_match('/^([a-z]+(?:-|_)?[a-z]+)/i', $tmp, $match);

            $local = end($match);
        }

        return $local;
    }

    /**
     * retourne la lang du naviagateur.
     *
     * @return string|null
     */
    public function lang()
    {
        $accept_language = $this->getHeader('accept_language');

        $language = explode(',', explode(';', $accept_language)[0])[0];

        preg_match('/([a-z]+)/', $language, $match);

        return end($match);
    }

    /**
     * le protocol de la requête.
     *
     * @return mixed
     */
    public function protocol()
    {
        return $_SERVER['SERVER_PROTOCOL'];
    }

    /**
     * Get Request header
     *
     * @param  string $key
     * @return bool|string
     */
    public function getHeader($key)
    {
        $key = str_replace('-', '_', strtoupper($key));

        if ($this->hasHeader($key)) {
            return $_SERVER[$key];
        }

        if ($this->hasHeader('HTTP_'.$key)) {
            return $_SERVER['HTTP_'.$key];
        }

        return null;
    }

    /**
     * Verifir si une entête existe.
     *
     * @param  string $key
     * @return bool
     */
    public function hasHeader($key)
    {
        return isset($_SERVER[strtoupper($key)]);
    }

    /**
     * Permet de récupérer un paramètre définir dans la route.
     *
     * @param  string $key
     * @return string
     */
    public function getParameter($key)
    {
        return $this->param[$key];
    }

    /**
     * Permet de récupérer les paramètres définir dans la route.
     *
     * @return object
     */
    public function getParameters()
    {
        return $this->param;
    }

    /**
     * Get session information
     *
     * @return Session
     */
    public function session()
    {
        return session();
    }

    /**
     * Get cookie
     *
     * @param string $property
     * @return mixed
     */
    public function cookie($property = null)
    {
        return cookie($property);
    }

    /**
     * @param $property
     * @return mixed
     */
    public function __get($property)
    {
        return $this->input->get($property);
    }

    /**
     * __call
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if (!method_exists(static::$instance, $name)) {
            throw new \RuntimeException('Method ' . $name . ' not exists');
        }

        return call_user_func_array([static::class, $name], $arguments);
    }
}
