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
        $objHelperPlugin->addAction("LC_Page_Admin_Contents_action_before", array(&$this, "admin_contents_action_before"), $priority);
        
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
    
    public function admin_contents_action_before(LC_Page $objPage)
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
                $this->arrForm = $this->lfSetViewParam_InputPage($objUpFile, $arrForm);
                // ページonload時のJavaScript設定
                $this->tpl_onload = $this->getAnchorHash($arrForm['image_key']);
                break;
            default:
                break;
        }
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

}
