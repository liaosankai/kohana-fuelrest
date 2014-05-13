<?php

defined('SYSPATH') or die('No direct script access.');

abstract class Kohana_Controller_Api_Resource extends Controller_Rest {

    /**
     * @var string 資料模型名稱
     */
    protected $_model_name = NULL;

    /**
     * @var ORM 資料模型物件
     */
    protected $_model = NULL;

    /**
     * @var array 取得單筆資料時的關聯
     */
    protected $_find_with = array();

    /**
     * @var array 取得多筆資料時的關聯
     */
    protected $_find_all_with = array();

    /**
     * @var array 預備輸出的回應結果
     */
    protected $_responses;

    /**
     * 前置函式
     */
    public function before()
    {
        parent::before();

        // 接收以 GET 模式改變語系設定
        I18n::lang(Arr::get($_GET, 'lang', 'en'));

        //嘗試建立 ORM 模型物件
        $this->_model = ORM::factory($this->_model_name);
    }

    /**
     * 輸出回應結果之前用來特別處理
     */
    public function before_output()
    {
        //這個函式應該被覆寫來處理每個 Ctrl 特別需求
    }

    /**
     * 計算資源資料數量
     *
     * @param int $id
     * @return type
     */
    public function get_count()
    {
        return array(
            'total' => $this->_model->count_all()
        );
    }

    /**
     * 讀取資源
     *
     * @param int $id 資源識別碼
     * @return array
     */
    public function get_read($id = NULL)
    {
        return $this->get_index($id);
    }

    public function get_index($id = NULL)
    {

        $id = Arr::get($_REQUEST, 'id', $id);

        //若有指定資源識別碼僅回傳單一筆資料
        if (is_numeric($id)) {
            foreach ($this->_find_with as $with) {
                $this->_model->with($with);
            }
            $this->_model->where("{$this->_model->object_name()}.id", '=', $id)->find();
            if (!$this->_model->loaded()) {
                return $this->_error_404($id);
            }
            $this->_responses = $this->_model->as_array();
        } else {
            foreach ($this->_find_all_with as $with) {
                $this->_model->with($with);
            }
            //未指定資源識別碼則回傳所有資料
            $this->_responses = array();
            foreach ($this->_model->find_all() as $row) {
                $this->_responses[] = $row->as_array();
            }
        }
        $this->handle_responses();
        return $this->_responses;
    }

    /**
     * 建立資源資料
     *
     * @return array
     */
    public function post_create()
    {
        return $this->post_index();
    }

    public function post_index()
    {
        try {
            $this->before_create();
            $this->before_save();
            $this->_model->values($_POST);
            $this->_model->save();
            $id = $this->_model->id;
            // 重新讀取 with 的部分
            $this->_model->clear();
            foreach ($this->_find_with as $with) {
                $this->_model->with($with);
            }
            $this->_model->where("{$this->_model->object_name()}.id", '=', $id)->find();

            $this->_responses = $this->_model->as_array();
            $this->after_create();
            $this->after_save();
            $this->http_status = 201;
            $this->handle_responses();
            return $this->_responses;
        } catch (ORM_Validation_Exception $exc) {
            return $this->_error_400($exc);
        } catch (Exception $exc) {
            return $this->_error_500($exc);
        }
    }

    /**
     * 更新資源資料
     *
     * @param int $id 資源識別碼
     * @return array
     */
    public function put_update($id = NULL)
    {
        $data = $this->put_index($id);
        return $data;
    }

    public function put_index($id = NULL)
    {
        $_PUT = $_REQUEST;
        try {
            $id = Arr::get($_PUT, 'id', $id);
            $this->_model->where("{$this->_model->object_name()}.id", '=', $id)->find();
            if (!$this->_model->loaded()) {
                return $this->_error_404($id);
            }
            $this->before_update();
            $this->before_save();
            $this->_model->values($_PUT);
            $this->_model->save();
            // 重新讀取 with 的部分
            $this->_model->clear();
            foreach ($this->_find_with as $with) {
                $this->_model->with($with);
            }
            $this->_model->where("{$this->_model->object_name()}.id", '=', $id)->find();

            $this->_responses = $this->_model->as_array();
            $this->after_update();
            $this->after_save();
            $this->http_status = 200;
            $this->handle_responses();
            return $this->_responses;
        } catch (ORM_Validation_Exception $exc) {
            return $this->_error_400($exc);
        } catch (Exception $exc) {
            return $this->_error_500($exc);
        }
    }

    /**
     * 刪除資源資料
     *
     * @param int $id 資源識別碼
     * @return array
     */
    public function delete_delete($id = NULL)
    {
        return $this->delete_index($id);
    }

    public function delete_index($id = NULL)
    {

        $_DELETE = $_REQUEST;

        try {
            $id = Arr::get($_DELETE, 'id', $id);
            $this->_model->where("{$this->_model->object_name()}.id", '=', $id)->find();
            if (!$this->_model->loaded()) {
                return $this->_error_404($id);
            }
            $this->before_delete();
            $this->_model->delete();
            $this->after_delete();
            $this->http_status = 204;
            return $this->_responses;
        } catch (Exception $exc) {
            return $this->_error_500($exc);
        }
    }

    /**
     *  HTTP STATUS 代碼參考
     *
     * 200 完成 - 查詢完成，要求的作業已正確的執行，通常在完成 GET 請求時顯示
     * 201 完成 - 新增成功，通常在完成 POST 請求時顯示
     * 204 完成 - 異動成功，通常在完成 DELETE、PUT、PATCH 請求時顯示
     * 400 錯誤請求 - 通常發生在資料新增(POST)、更新(PUT)時，所輸入欄位資料不正確(驗證失敗)，或傳送的參數格式、類型不正確。
     * 401 尚未驗證 - 使用者尚未登入或提供有效 API 驗證金鑰。
     * 403 權限不足 - 使用者雖然已經登入，但擁有的權限不足夠執行要求的作業。通常是權限不足查詢(GET)
     * 404 資料不存在 - 不正確的URL，或是欲查詢(GET)、更新(PUT)、刪除(DELETE)的資料識別碼(ID)不存在。
     * 405 操作不予許 - 沒有更新(PUT)、刪除(DELETE)此資源的權限
     * 500 伺服器錯誤 - 伺服器端發生了未知的狀況或錯誤。
     */

    /**
     * 處理資料驗證失敗的例外
     *
     * @access private
     * @param ORM_Validation_Exception ＄exc 驗證失敗例外
     * @return array $errors 失敗的原因
     */
    protected function _error_400($exc = NULL)
    {
        $errors = array(
            'message' => 'Some data Invaild',
            'errors' => array(),
        );
        foreach ($exc->errors($this->_model_name) as $field => $error) {
            $errors['errors'][] = $error;
        }
        $this->http_status = 400;
        return $errors;
    }

    /**
     * 處理資料驗證失敗的例外
     *
     * @access private
     * @param ORM_Validation_Exception ＄exc 驗證失敗例外
     * @return array $errors 失敗的原因
     */
    protected function _error_404($id = NULL)
    {
        $errors = array(
            'message' => "id: {$id} not found",
        );
        $this->http_status = 404;
        return $errors;
    }

    /**
     * 處理未知的例外
     *
     * @access private
     * @param Exception ＄exc 驗證失敗例外
     * @return array $errors 失敗的原因
     */
    protected function _error_500($exc)
    {
        $errors = array(
            'message' => 'Unknow Exception:' . $exc->getMessage(),
        );
        $this->http_status = 500;
        return $errors;
    }

    /**
     * 在輸出前處理回應資料
     */
    public function handle_responses()
    {
        
    }

    /**
     * 動作事件處理(應該被覆寫)
     */
    public function before_save()
    {
        
    }

    public function after_save()
    {
        
    }

    public function before_create()
    {
        
    }

    public function after_create()
    {
        
    }

    public function before_update()
    {
        
    }

    public function after_update()
    {
        
    }

    public function before_delete()
    {
        
    }

    public function after_delete()
    {
        
    }

}

// Controller/Api/Resource.php
