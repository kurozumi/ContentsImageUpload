<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2014 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 *
 */

/**
 * プラグインの基底クラス
 *
 * @package Plugin
 * @author LOCKON CO.,LTD.
 * @version $Id: $
 */
class ContentsImageUpload extends SC_Plugin_Base
{
    /**
     * コンストラクタ
     *
     * @param  array $arrSelfInfo 自身のプラグイン情報
     * @return void
     */
    public function __construct(array $arrSelfInfo)
    {
        // プラグインを有効化したときの初期設定をココに追加する
        if($arrSelfInfo["enable"] == 1) {}

    }

    /**
     * インストール
     * installはプラグインのインストール時に実行されます.
     * 引数にはdtb_pluginのプラグイン情報が渡されます.
     *
     * @param  array $arrPlugin plugin_infoを元にDBに登録されたプラグイン情報(dtb_plugin)
     * @return void
     */
    public function install($arrPlugin, $objPluginInstaller = null)
    {
        // htmlディレクトリにファイルを配置。
        $src_dir = PLUGIN_UPLOAD_REALDIR . "{$arrPlugin["plugin_code"]}/html/";
        $dest_dir = HTML_REALDIR;
        SC_Utils::copyDirectory($src_dir, $dest_dir);

    }

    /**
     * アンインストール
     * uninstallはアンインストール時に実行されます.
     * 引数にはdtb_pluginのプラグイン情報が渡されます.
     *
     * @param  array $arrPlugin プラグイン情報の連想配列(dtb_plugin)
     * @return void
     */
    public function uninstall($arrPlugin, $objPluginInstaller = null)
    {
        // htmlディレクトリのファイルを削除。
        $target_dir = HTML_REALDIR;
        $source_dir = PLUGIN_UPLOAD_REALDIR . "{$arrPlugin["plugin_code"]}/html/";
        self::deleteDirectory($target_dir, $source_dir);


    }

    /**
     * 稼働
     * enableはプラグインを有効にした際に実行されます.
     * 引数にはdtb_pluginのプラグイン情報が渡されます.
     *
     * @param  array $arrPlugin プラグイン情報の連想配列(dtb_plugin)
     * @return void
     */
    public function enable($arrPlugin, $objPluginInstaller = null)
    {
        // テーブル作成
        self::createTable();
        
        // 有効時、プラグイン情報に値を入れたい場合使う
        self::updatePlugin($arrPlugin["plugin_code"], array(
            "free_field1" => "text1",
            "free_field2" => "text2",
            "free_field3" => "text3",
            "free_field4" => "text4",
        ));
        
        self::copyTemplate($arrPlugin);
    }

    /**
     * 停止
     * disableはプラグインを無効にした際に実行されます.
     * 引数にはdtb_pluginのプラグイン情報が渡されます.
     *
     * @param  array $arrPlugin プラグイン情報の連想配列(dtb_plugin)
     * @return void
     */
    public function disable($arrPlugin, $objPluginInstaller = null)
    {
        // テーブル削除
        self::dropTable();
        
        // 無効時、プラグイン情報に値を初期化したい場合使う
        self::updatePlugin($arrPlugin["plugin_code"], array(
            "free_field1" => null,
            "free_field2" => null,
            "free_field3" => null,
            "free_field4" => null,
        ));
        
        self::deleteTemplate($arrPlugin);
    }

    /**
     * プラグインヘルパーへ, コールバックメソッドを登録します.
     *
     * @param integer $priority
     */
    public function register(SC_Helper_Plugin $objHelperPlugin, $priority)
    {
        $objHelperPlugin->addAction("loadClassFileChange", array(&$this, "loadClassFileChange"), $priority);
        $objHelperPlugin->addAction("prefilterTransform", array(&$this, "prefilterTransform"), $priority);
        $objHelperPlugin->addAction("outputfilterTransform", array(&$this, "outputfilterTransform"), $priority);
        $objHelperPlugin->addAction("LC_Page_Admin_Contents_action_after", array(&$this, "admin_contents_action_after"), $priority);
        
    }

    /**
     * SC_系のクラスをフックする
     * 
     * @param type $classname
     * @param type $classpath
     */
    public function loadClassFileChange(&$classname, &$classpath)
    {
        $base_path = PLUGIN_UPLOAD_REALDIR . basename(__DIR__) . "/data/class/";
        $helper_path = $base_path . "helper/";
        
    }
    
    public function admin_contents_action_after(LC_Page $objPage)
    {
        $objFormParam = new SC_FormParam_Ex();
        
        $objUpFile = new SC_UploadFile_Ex(IMAGE_TEMP_REALDIR, IMAGE_SAVE_REALDIR);
        $this->lfInitFile($objUpFile);
        $objUpFile->setHiddenFileList($_POST);

        $mode = $objPage->getMode();
        switch ($mode) {
            case 'upload_image':
            case 'delete_image':
                // パラメーター初期化
                $this->lfInitFormParam_UploadImage($objFormParam);
                $this->lfInitFormParam($objFormParam, $_POST);
                $arrForm = $objFormParam->getHashArray();
                
                switch ($mode) {
                    case 'upload_image':
                        // ファイルを一時ディレクトリにアップロード
                        $this->arrErr[$arrForm['image_key']] = $objUpFile->makeTempFile($arrForm['image_key'], IMAGE_RENAME);
                        break;
                    case 'delete_image':
                        // ファイル削除
                        $this->lfDeleteTempFile($objUpFile, $arrForm['image_key']);
                        break;
                    default:
                        break;
                }
                
                // 入力画面表示設定
                $objPage->arrForm = $this->lfSetViewParam_InputPage($objUpFile, $arrForm);

                // ページonload時のJavaScript設定
                $objPage->tpl_onload = $this->getAnchorHash($arrForm['image_key']);
                break;
            case 'edit':
                // パラメーター初期化
                $this->lfInitFormParam_UploadImage($objFormParam);
                $this->lfInitFormParam($objFormParam, $_POST);
                $arrForm = $this->lfSetViewParam_InputPage($objUpFile, $objFormParam->getHashArray());
                $objPage->arrForm = array_merge($objPage->arrForm, $arrForm);

                if(!$objPage->arrErr) {
                    //$this->doRegist($objPage->tpl_news_id, $objUpFile);

                    // 一時ファイルを本番ディレクトリに移動する
                    $this->lfSaveUploadFiles($objUpFile, $objPage->tpl_news_id);
    
                    
                }
                
                $arrForm['arrFile'] = $objUpFile->getFormFileList(IMAGE_TEMP_URLPATH, IMAGE_SAVE_URLPATH);
                
                print_r($objPage->arrForm);
                
                break;
            default:
                break;
        }
    }
    
    /**
     * 登録処理を実行.
     *
     * @param  integer  $news_id
     * @param  array    $sqlval
     * @param  SC_Helper_News_Ex   $objNews
     * @return multiple
     */
    public function doRegist($news_id, $objUpFile)
    {
        $sqlval['news_id'] = $news_id;
        $sqlval = array_merge($sqlval, $objUpFile->getDBFileList());

        return $this->saveNewImage($sqlval);
    }
    
    /**
     * ニュースの登録.
     *
     * @param  array    $sqlval
     * @return multiple 登録成功:ニュースID, 失敗:FALSE
     */
    public function saveNewImage($sqlval)
    {
        $objQuery =& SC_Query_Ex::getSingletonInstance();

        $news_id = $objQuery->get("news_id", "plg_news_image", "news_id", array($sqlval['news_id']));
        
        $sqlval['update_date'] = 'CURRENT_TIMESTAMP';
        // 新規登録
        if ($news_id == null) {
            // INSERTの実行
            $sqlval['create_date'] = 'CURRENT_TIMESTAMP';
            $ret = $objQuery->insert('plg_news_image', $sqlval);
        // 既存編集
        } else {
            $where = 'news_id = ?';
            $ret = $objQuery->update('plg_news_image', $sqlval, $where, array($news_id));
        }

        return ($ret) ? $sqlval['news_id'] : FALSE;
    }
    
    /**
     * アップロードファイルパラメーター情報の初期化
     * - 画像ファイル用
     *
     * @param  SC_UploadFile_Ex $objUpFile SC_UploadFileインスタンス
     * @return void
     */
    public function lfInitFile(&$objUpFile)
    {
        $objUpFile->addFile('画像', 'news_image', array('jpg', 'gif', 'png'), IMAGE_SIZE, false, NORMAL_IMAGE_WIDTH, NORMAL_IMAGE_WIDTH);
    }
    
    /**
     * パラメーター情報の初期化
     * - 画像ファイルアップロードモード
     *
     * @param  SC_FormParam_Ex $objFormParam SC_FormParamインスタンス
     * @return void
     */
    public function lfInitFormParam_UploadImage(&$objFormParam)
    {
        $objFormParam->addParam('image_key', 'image_key', '', '', array());
    }
    
   /**
     * パラメーター情報の初期化
     *
     * @param  SC_FormParam_Ex $objFormParam SC_FormParamインスタンス
     * @param  array  $arrPost      $_POSTデータ
     * @return void
     */
    public function lfInitFormParam(&$objFormParam, $arrPost)
    {
        $objFormParam->addParam('save_news_image', 'save_news_image', '', '', array());
        $objFormParam->addParam('temp_news_image', 'temp_news_image', '', '', array());

        $objFormParam->setParam($arrPost);
        $objFormParam->convParam();
    }
    
   /**
     * 表示用フォームパラメーター取得
     * - 入力画面
     *
     * @param  SC_UploadFile_Ex $objUpFile   SC_UploadFileインスタンス
     * @param  array  $arrForm     フォーム入力パラメーター配列
     * @return array  表示用フォームパラメーター配列
     */
    public function lfSetViewParam_InputPage(&$objUpFile, &$arrForm)
    {
        // アップロードファイル情報取得(Hidden用)
        $arrForm['arrHidden'] = $objUpFile->getHiddenFileList();

        // 画像ファイル表示用データ取得
        $arrForm['arrFile'] = $objUpFile->getFormFileList(IMAGE_TEMP_URLPATH, IMAGE_SAVE_URLPATH);

        return $arrForm;
    }

  /**
     * 表示用フォームパラメーター取得
     * - 確認画面
     *
     * @param  SC_UploadFile_Ex $objUpFile   SC_UploadFileインスタンス
     * @param  SC_UploadFile_Ex $objDownFile SC_UploadFileインスタンス
     * @param  array  $arrForm     フォーム入力パラメーター配列
     * @return array  表示用フォームパラメーター配列
     */
    public function lfSetViewParam_ConfirmPage(&$objUpFile, &$arrForm)
    {
        // 画像ファイル用データ取得
        $arrForm['arrFile'] = $objUpFile->getFormFileList(IMAGE_TEMP_URLPATH, IMAGE_SAVE_URLPATH);

        return $arrForm;
    }
    
    /**
     * フォームパラメーター取得
     * - 編集/複製モード
     *
     * @param  SC_UploadFile_Ex  $objUpFile   SC_UploadFileインスタンス
     * @param  integer $product_id  商品ID
     * @return array   フォームパラメーター配列
     */
    public function lfGetFormParam_PreEdit(&$objUpFile, $news_id)
    {
        $arrForm = array();

        // DBから商品データ取得
        $arrForm = $this->lfGetProductData_FromDB($news_id);
        // DBデータから画像ファイル名の読込
        $objUpFile->setDBFileList($arrForm);

        return $arrForm;
    }
    
    /**
     * DBから商品データを取得する
     *
     * @param  integer $news_id ニュースID
     * @return string   商品データ配列
     */
    public function lfGetProductData_FromDB($news_id)
    {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $arrProduct = array();

        // 商品データ取得
        $col = '*';
        $table = <<< __EOF__
            dtb_products AS T1
            LEFT JOIN (
                SELECT product_id AS product_id_sub,
                    product_code,
                    price01,
                    price02,
                    deliv_fee,
                    stock,
                    stock_unlimited,
                    sale_limit,
                    point_rate,
                    product_type_id,
                    down_filename,
                    down_realfilename
                FROM dtb_products_class
            ) AS T2
                ON T1.product_id = T2.product_id_sub
__EOF__;
        $where = 'product_id = ?';
        $objQuery->setLimit('1');
        $arrProduct = $objQuery->select($col, $table, $where, array($product_id));

        // カテゴリID取得
        $col = 'category_id';
        $table = 'dtb_product_categories';
        $where = 'product_id = ?';
        $objQuery->setOption('');
        $arrProduct[0]['category_id'] = $objQuery->getCol($col, $table, $where, array($product_id));

        // 規格情報ありなしフラグ取得
        $objDb = new SC_Helper_DB_Ex();
        $arrProduct[0]['has_product_class'] = $objDb->sfHasProductClass($product_id);

        // 規格が登録されていなければ規格ID取得
        if ($arrProduct[0]['has_product_class'] == false) {
            $arrProduct[0]['product_class_id'] = SC_Utils_Ex::sfGetProductClassId($product_id, '0', '0');
        }

        // 商品ステータス取得
        $objProduct = new SC_Product_Ex();
        $productStatus = $objProduct->getProductStatus(array($product_id));
        $arrProduct[0]['product_status'] = $productStatus[$product_id];

        // 関連商品データ取得
        $arrRecommend = $this->lfGetRecommendProductsData_FromDB($product_id);
        $arrProduct[0] = array_merge($arrProduct[0], $arrRecommend);

        return $arrProduct[0];
    }
    
    /**
     * アップロードファイルを保存する
     *
     * @param  object  $objUpFile   SC_UploadFileインスタンス
     * @param  integer $news_id  ニュースID
     * @return void
     */
    public function lfSaveUploadFiles(&$objUpFile, $news_id)
    {
        // TODO: SC_UploadFile::moveTempFileの画像削除条件見直し要
        $objImage = new SC_Image_Ex($objUpFile->temp_dir);
        $arrKeyName = $objUpFile->keyname;
        $arrTempFile = $objUpFile->temp_file;
        $arrSaveFile = $objUpFile->save_file;
        $arrImageKey = array();
        foreach ($arrTempFile as $key => $temp_file) {
            if ($temp_file) {
                $objImage->moveTempImage($temp_file, $objUpFile->save_dir);
                $arrImageKey[] = $arrKeyName[$key];
                if (!empty($arrSaveFile[$key])
                    && !$this->lfHasSameNewsImage($news_id, $arrImageKey, $arrSaveFile[$key])
                    && !in_array($temp_file, $arrSaveFile)
                ) {
                    $objImage->deleteImage($arrSaveFile[$key], $objUpFile->save_dir);
                }
            }
        }
    }
    
    /**
     * 同名画像ファイル登録の有無を確認する.
     *
     * 画像ファイルの削除可否判定用。
     * 同名ファイルの登録がある場合には画像ファイルの削除を行わない。
     * 戻り値： 同名ファイル有り(true) 同名ファイル無し(false)
     *
     * @param  string  $news_id         ニュースID
     * @param  string  $arrImageKey     対象としない画像カラム名
     * @param  string  $image_file_name 画像ファイル名
     * @return boolean
     */
    public function lfHasSameNewsImage($news_id, $arrImageKey, $image_file_name)
    {
        if (!SC_Utils_Ex::sfIsInt($news_id)) return false;
        if (!$arrImageKey) return false;
        if (!$image_file_name) return false;

        $arrWhere = array();
        $sqlval = array('0', $news_id);
        foreach ($arrImageKey as $image_key) {
            $arrWhere[] = "{$image_key} = ?";
            $sqlval[] = $image_file_name;
        }
        $where = implode(' OR ', $arrWhere);
        $where = "del_flg = ? AND (($news_id <> ? AND ({$where}))";

        $arrKeyName = $this->objUpFile->keyname;
        foreach ($arrKeyName as $key => $keyname) {
            if (in_array($keyname, $arrImageKey)) continue;
            $where .= " OR {$keyname} = ?";
            $sqlval[] = $image_file_name;
        }
        $where .= ')';

        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $exists = $objQuery->exists('plg_news_image', $where, $sqlval);

        return $exists;
    }
    
    /**
     * アップロードファイルパラメーター情報から削除
     * 一時ディレクトリに保存されている実ファイルも削除する
     *
     * @param  SC_UploadFile_Ex $objUpFile SC_UploadFileインスタンス
     * @param  string $image_key 画像ファイルキー
     * @return void
     */
    public function lfDeleteTempFile(&$objUpFile, $image_key)
    {
        // TODO: SC_UploadFile::deleteFileの画像削除条件見直し要
        $arrTempFile = $objUpFile->temp_file;
        $arrKeyName = $objUpFile->keyname;

        foreach ($arrKeyName as $key => $keyname) {
            if ($keyname != $image_key) continue;

            if (!empty($arrTempFile[$key])) {
                $temp_file = $arrTempFile[$key];
                $arrTempFile[$key] = '';

                if (!in_array($temp_file, $arrTempFile)) {
                    $objUpFile->deleteFile($image_key);
                } else {
                    $objUpFile->temp_file[$key] = '';
                    $objUpFile->save_file[$key] = '';
                }
            } else {
                $objUpFile->temp_file[$key] = '';
                $objUpFile->save_file[$key] = '';
            }
        }
    }
    
    /**
     * アンカーハッシュ文字列を取得する
     * アンカーキーをサニタイジングする
     *
     * @param  string $anchor_key フォーム入力パラメーターで受け取ったアンカーキー
     * @return <type>
     */
    public function getAnchorHash($anchor_key)
    {
        if ($anchor_key != '') {
            return "location.hash='#" . htmlspecialchars($anchor_key) . "'";
        } else {
            return '';
        }
    }

    /**
     * テンプレートをフックする
     *
     * @param string &$source
     * @param LC_Page_Ex $objPage
     * @param string $filename
     * @return void
     */
    public function prefilterTransform(&$source, LC_Page_Ex $objPage, $filename)
    {
        $objTransform = new SC_Helper_Transform($source);
       
        switch ($objPage->arrPageLayout['device_type_id']) {
            case DEVICE_TYPE_PC:
                break;
            case DEVICE_TYPE_MOBILE:
                break;
            case DEVICE_TYPE_SMARTPHONE:
                break;
            case DEVICE_TYPE_ADMIN:
            default:
                // 管理画面編集
                if (strpos($filename, "contents/index.tpl") !== false) {
                    $template_path = 'contents/plg_ContentsImageUpload_form.tpl';
                    $template = "<!--{include file='{$template_path}'}-->";
                    $objTransform->select('form', 0)->replaceElement($template);
                }

                // ブロック編集
                break;
        }
        $source = $objTransform->getHTML();

    }

    /**
     * テンプレートをフックする
     * Smartyの編集はできない
     *
     * @param string &$source
     * @param LC_Page_Ex $objPage
     * @param string $filename
     * @return void
     */
    public function outputfilterTransform(&$source, LC_Page_Ex $objPage, $filename)
    {
        $objTransform = new SC_Helper_Transform($source);
        $template_dir = PLUGIN_UPLOAD_REALDIR . basename(__DIR__) . "/data/Smarty/templates/";
        switch ($objPage->arrPageLayout['device_type_id']) {
            case DEVICE_TYPE_PC:
                break;
            case DEVICE_TYPE_MOBILE:
                break;
            case DEVICE_TYPE_SMARTPHONE:
                break;
            case DEVICE_TYPE_ADMIN:
            default:
                break;
        }
        $source = $objTransform->getHTML();

    }
    
    /**
     * プラグイン情報更新
     * 
     * @param string $plugin_code
     * @param array $free_fields
     */
    public static function updatePlugin($plugin_code, array $free_fields){
        $objQuery = & SC_Query_Ex::getSingletonInstance();
        $objQuery->update("dtb_plugin", $free_fields, "plugin_code = ?", array($plugin_code));
    }
    
    /**
     * 次に割り当てるMasterDataのIDを取得する
     * 
     * @param string $mtb
     * @return int
     */
    public static function getNextMasterDataId($mtb)
    {
        $objQuery = & SC_Query_Ex::getSingletonInstance();
        return $objQuery->max("id", $mtb) + 1;
    }

    /**
     * 次に割り当てるMasterDataのRANKを取得する
     * 
     * @param string $mtb
     * @return int
     */
    public static function getNextMasterDataRank($mtb)
    {
        $objQuery = & SC_Query_Ex::getSingletonInstance();
        return $objQuery->max("rank", $mtb) + 1;
    }
    
    /**
     * MasterDataに追加
     * 
     * @param type $mtb
     * @param type $name
     * @return int
     */
    public static function insertMasterDataId($mtb, $name, $id=null)
    {
        if(is_null($id))
            $id = self::getNextMasterDataId($mtb);

        $objQuery = & SC_Query_Ex::getSingletonInstance();
        $objQuery->insert($mtb, array(
            'id'   => $id,
            'name' => $name,
            'rank' => self::getNextMasterDataRank($mtb)));

        $masterData = new SC_DB_MasterData_Ex();
        $masterData->clearCache($mtb);
        
        return $id;
    }
    
    /**
     * MasterDataの指定IDを削除
     * 
     * @param SC_Query $objQuery
     * @param string $mtb
     * @param int $id
     */
    public static function deleteMasterDataId($mtb, $id)
    {
        $objQuery = & SC_Query_Ex::getSingletonInstance();
        $objQuery->delete($mtb, "id=?", array($id));

        $masterData = new SC_DB_MasterData_Ex();
        $masterData->clearCache($mtb);

    }
    
    /**
     * 指定されたパスを比較して再帰的に削除します。
     * 
     * @param string $target_dir 削除対象のディレクトリ
     * @param string $source_dir 比較対象のディレクトリ
     */
    public static function deleteDirectory($target_dir, $source_dir)
    {
        if($dir = opendir($source_dir)) {
            while ($name = readdir($dir)) {
                if ($name == '.' || $name == '..') {
                    continue;
                }

                $target_path = $target_dir . '/' . $name;
                $source_path = $source_dir . '/' . $name;

                if (is_file($source_path)) {
                    if (is_file($target_path)) {
                        unlink($target_path);
                        GC_Utils::gfPrintLog("$target_path を削除しました。");
                    }
                } elseif (is_dir($source_path)) {
                    if (is_dir($target_path)) {
                        self::deleteDirectory($target_path, $source_path);
                    }
                }
            }
            closedir($dir);
        }
    }
    
    /**
     * 本体にテンプレートをコピー
     * 
     * @param type $arrPlugin
     */
    public static function copyTemplate($arrPlugin)
    {
        $src_dir = PLUGIN_UPLOAD_REALDIR . "{$arrPlugin["plugin_code"]}/data/Smarty/templates/";

        // 管理画面テンプレートを配置。
        $dest_dir = TEMPLATE_ADMIN_REALDIR;
        SC_Utils::copyDirectory($src_dir . "admin/", $dest_dir);

        // PCテンプレートを配置。
        $dest_dir = SC_Helper_PageLayout_Ex::getTemplatePath(DEVICE_TYPE_PC);
        SC_Utils::copyDirectory($src_dir . "default/", $dest_dir);

        // スマホテンプレートを配置。
        $dest_dir = SC_Helper_PageLayout_Ex::getTemplatePath(DEVICE_TYPE_SMARTPHONE);
        SC_Utils::copyDirectory($src_dir . "sphone/", $dest_dir);

        // モバイルテンプレートを配置。
        $dest_dir = SC_Helper_PageLayout_Ex::getTemplatePath(DEVICE_TYPE_MOBILE);
        SC_Utils::copyDirectory($src_dir . "mobile/", $dest_dir);

    }

    /**
     * 本体にコピーしたテンプレートを削除
     * 
     * @param type $arrPlugin
     */
    public static function deleteTemplate($arrPlugin)
    {
        $src_dir = PLUGIN_UPLOAD_REALDIR . "{$arrPlugin["plugin_code"]}/data/Smarty/templates/";

        // 管理画面テンプレートを削除。 
        $target_dir = TEMPLATE_ADMIN_REALDIR;
        self::deleteDirectory($target_dir, $src_dir . "admin/");

        // PCテンプレートを削除。
        $target_dir = SC_Helper_PageLayout_Ex::getTemplatePath(DEVICE_TYPE_PC);
        self::deleteDirectory($target_dir, $src_dir . "default/");

        // スマホテンプレートを削除。
        $target_dir = SC_Helper_PageLayout_Ex::getTemplatePath(DEVICE_TYPE_SMARTPHONE);
        self::deleteDirectory($target_dir, $src_dir . "sphone");

        // モバイルテンプレートを削除。
        $target_dir = SC_Helper_PageLayout_Ex::getTemplatePath(DEVICE_TYPE_MOBILE);
        self::deleteDirectory($target_dir, $src_dir . "mobile");

    }
    
    /**
     * テーブルの追加
     *
     * @return void
     */
    public static function createTable()
    {
        $objQuery = & SC_Query_Ex::getSingletonInstance();
        switch (DB_TYPE) {
            case "pgsql":
                $sql = <<< __EOS__
                    CREATE TABLE plg_news_image (
                    news_id int NOT NULL,
                    news_image text,
                    create_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    update_date timestamp NOT NULL,
                    PRIMARY KEY (news_id)
                    );
__EOS__;
                break;
            case "mysql":
                $sql = <<< __EOS__
                    CREATE TABLE plg_news_image (
                    news_id int NOT NULL,
                    news_image text,
                    create_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    update_date timestamp NOT NULL,
                    PRIMARY KEY (news_id)
                    ) ENGINE=InnoDB ;
__EOS__;
                break;
        }
        $objQuery->query($sql);

    }

    /**
     * テーブルの削除
     *
     * @return void
     */
    public static function dropTable()
    {
        $objQuery = & SC_Query_Ex::getSingletonInstance();
        $objQuery->query("DROP TABLE plg_news_image");

    }

}
