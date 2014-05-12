<?php

defined('SYSPATH') OR die('No direct script access.');

/**
 * REST 專用路由，所有控制器必需放在 api 資料夾底下
 * 支援子資料夾(最多一層)的方式呼叫控制器，檔案結構範例如下
 * 
 * <Controller>
 *   └<Api>
 *     └<Product>
 *       └Collection.php
 * 　  └Product.php
 */
/*
Route::set('rest-api', '(<directory>(/<controller>(/<action>(/<id>))))(.<format>)', array(
            'directory' => '.+?',
            'id' => '\d+'
        ))->filter(function($route, $params, $request) {
                    if (!method_exists('Controller_' . $params['controller'], $params['action'])) {
                        $file = Kohana::find_file('classes/Controller', $params['directory'] . '/' . $params['controller'] . "/" . $params['action']);
                        if (is_file($file)) {
                            $params['directory'] = $params['directory'] . '/' . $params['controller'];
                            $params['controller'] = $params['action'];
                            $params['action'] = 'index';
                        }
                        if (is_numeric($params['action'])) {
                            $params['id'] = $params['action'];
                            $params['action'] = 'index';
                        }
                    }
                    return array_filter($params);
                })
        ->defaults(array(
            'directory' => 'api',
            'controller' => 'resource',
            'action' => 'index',
            'format' => 'json',
        ));

*/
