<?php

defined('SYSPATH') or die('No direct script access.');

abstract class Kohana_Controller_Rest extends Kohana_Controller {

    /**
     * @var integer 輸出的 HTTP 態碼
     */
    protected $http_status = NULL;

    /**
     * @var string 預設的輸出格式
     */
    protected $rest_format = 'json';

    /**
     * @var string the 最後輸出的格式
     */
    protected $format = NULL;

    /**
     * @var string 當輸出 xml 格式時，基本的節點名稱
     */
    protected $xml_basenode = 'xml';

    /**
     * @var mix 最後欲執行的 method
     */
    protected $method = null;

    /**
     * @var array 支援的輸出格式
     */
    private $_supported_formats = array(
        'xml' => 'application/xml',
        'yaml' => 'application/yaml',
        'json' => 'application/json',
        'jsonp' => 'text/javascript',
        'serialized' => 'application/vnd.php.serialized',
        'php' => 'text/plain',
        'html' => 'text/html',
    );

    /**
     * @var mix 準備輸出的內容
     */
    private $content = null;

    /**
     * @var mix 最後欲執行的 action
     */
    private $_action = null;

    /**
     * 可支援 GET、PUT、DELETE、POST 為前綴詞的執行方法
     * 例如 get_index、put_index、delete_index 等…
     * 依照不同的請求 method 呼叫對應的 action
     *
     * @return  Response
     */
    public function execute()
    {


        // 先處理以 RAW 的 JSON 傳入的資料
        $json = json_decode($this->request->body(), TRUE);
        $json = is_array($json) ? $json : array();

        // 再處理以 form-data 或 x-www-form-urlencode 傳入的資料
        // 並與 RAW 的資料合併
        switch ($this->request->method()) {
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                parse_str($this->request->body(), $_POST);
                $_POST = array_merge($json, $_POST);
                break;
            case 'POST':
                $_POST = array_merge($json, $_POST);
            default:
                break;
        }

        $_REQUEST = array_merge($_POST, $_GET);

        // 以 method 為前綴搜尋 action
        $action = strtolower($this->request->method() . "_" . $this->request->action());

        // 如果指定的 action 不存在，改試著以 action_ 為前綴搜尋
        if (method_exists($this, $action)) {
            $this->_action = $action;
            $this->method = strtolower($this->request->method());
        } else {
            $this->_action = 'action_' . $this->request->action();
            $this->method = 'action';
        }
        // 取得參數集合
        $params = $this->request->param();

        // 取出其中的 format 格式參數
        $this->format = Arr::pull($params, 'format');

        // 在執行 action 之前，呼叫前置函式
        $this->before();
        // 若 action 存在就執行，不存在呼叫 action_not_fount() 進行處理
        if (method_exists($this, $this->_action)) {
            $this->content = call_user_func_array(array($this, $this->_action), $params);
        } else {
            $this->content = $this->action_not_found();
        }
        // 在執行 action 之後，呼叫前置後式
        $this->after();

        // 回應結果
        return $this->response;
    }

    /**
     * 依照格式輸出 REST 結果
     */
    public function after()
    {
        if ($this->method != 'action') {
            //設定輸出的 HTTP 狀態碼
            $this->response->status($this->http_status);

            //檢查輸出格式是否支援
            $this->format = array_key_exists($this->format, $this->_supported_formats) ? $this->format : $this->rest_format;

            //指定輸出格式的類型
            $this->response->headers('Content-Type', Arr::get($this->_supported_formats, $this->format));

            $formater = Format::factory($this->content);
            //如果需要特別傳送參數的格式，再抽出來獨立處理
            switch ($this->format) {
                case 'html':
                    $this->content = empty($this->content) ? '' : var_dump($this->content, true);
                    break;
                case 'xml':
                    $this->content = $formater->to_xml($this->content, null, $this->xml_basenode);
                    break;
                default:
                    $this->content = call_user_func_array(array($formater, "to_{$this->format}"), array());
            }
            $this->response->body($this->content);
        }
    }

    /**
     * 指定的函式(方法)不存在
     */
    public function action_not_found()
    {
        $this->http_status = 404;
        return array(
            'message' => sprintf('The requested URL %s was not found on this server.', $this->request->uri()),
        );
    }

}

// End Rest
