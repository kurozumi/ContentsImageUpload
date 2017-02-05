<!--{*
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
 */
*}-->

    <form name="form1" id="form1" method="post" action="?" enctype="multipart/form-data">
        <input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
        <input type="hidden" name="mode" value="" />
        <input type="hidden" name="news_id" value="<!--{$arrForm.news_id.value|default:$tpl_news_id|h}-->" />
        <input type="hidden" name="image_key" value="" />
        <!--{foreach key=key item=item from=$arrForm.arrHidden}-->
        <input type="hidden" name="<!--{$key}-->" value="<!--{$item|h}-->" />
        <!--{/foreach}-->
        <!--{* ▼登録テーブルここから *}-->
        <table>
            <tr>
                <th>日付<span class="attention"> *</span></th>
                <td>
                    <!--{if $arrErr.year || $arrErr.month || $arrErr.day}--><span class="attention"><!--{$arrErr.year}--><!--{$arrErr.month}--><!--{$arrErr.day}--></span><!--{/if}-->
                    <select name="year" <!--{if $arrErr.year || $arrErr.month || $arrErr.day }-->style="background-color:<!--{$smarty.const.ERR_COLOR|h}-->"<!--{/if}-->>
                        <option value="" selected="selected">----</option>
                        <!--{html_options options=$arrYear selected=$arrForm.year.value}-->
                    </select>年
                    <select name="month" <!--{if $arrErr.year || $arrErr.month || $arrErr.day}-->style="background-color:<!--{$smarty.const.ERR_COLOR|h}-->"<!--{/if}-->>
                        <option value="" selected="selected">--</option>
                        <!--{html_options options=$arrMonth selected=$arrForm.month.value}-->
                    </select>月
                    <select name="day" <!--{if $arrErr.year || $arrErr.month || $arrErr.day}-->style="background-color:<!--{$smarty.const.ERR_COLOR|h}-->"<!--{/if}-->>
                        <option value="" selected="selected">--</option>
                        <!--{html_options options=$arrDay selected=$arrForm.day.value}-->
                    </select>日
                </td>
            </tr>
            <tr>
                <th>タイトル<span class="attention"> *</span></th>
                <td>
                    <!--{if $arrErr.news_title}--><span class="attention"><!--{$arrErr.news_title}--></span><!--{/if}-->
                    <textarea name="news_title" cols="60" rows="8" class="area60" maxlength="<!--{$smarty.const.MTEXT_LEN}-->" <!--{if $arrErr.news_title}-->style="background-color:<!--{$smarty.const.ERR_COLOR|h}-->"<!--{/if}-->><!--{"\n"}--><!--{$arrForm.news_title.value|h}--></textarea><br />
                    <span class="attention"> (上限<!--{$smarty.const.MTEXT_LEN}-->文字)</span>
                </td>
            </tr>
            <tr>
                <th>URL</th>
                <td>
                    <span class="attention"><!--{$arrErr.news_url}--></span>
                    <input type="text" name="news_url" size="60" class="box60"    value="<!--{$arrForm.news_url.value|h}-->" <!--{if $arrErr.news_url}-->style="background-color:<!--{$smarty.const.ERR_COLOR|h}-->"<!--{/if}--> maxlength="<!--{$smarty.const.URL_LEN}-->" />
                    <span class="attention"> (上限<!--{$smarty.const.URL_LEN}-->文字)</span>
                </td>
            </tr>
            <tr>
                <th>リンク</th>
                <td><label><input type="checkbox" name="link_method" value="2" <!--{if $arrForm.link_method.value eq 2}--> checked <!--{/if}--> /> 別ウィンドウで開く</label></td>
            </tr>
            <tr>
                <th>本文作成</th>
                <td>
                    <!--{if $arrErr.news_comment}--><span class="attention"><!--{$arrErr.news_comment}--></span><!--{/if}-->
                    <textarea name="news_comment" cols="60" rows="8" wrap="soft" class="area60" maxlength="<!--{$smarty.const.LTEXT_LEN}-->" style="background-color:<!--{if $arrErr.news_comment}--><!--{$smarty.const.ERR_COLOR|h}--><!--{/if}-->"><!--{"\n"}--><!--{$arrForm.news_comment.value|h}--></textarea><br />
                    <span class="attention"> (上限3000文字)</span>
                </td>
            </tr>
            <tr>
                <!--{assign var=key value="news_image"}-->
                <th>画像<br />[<!--{$smarty.const.NORMAL_IMAGE_WIDTH}-->×<!--{$smarty.const.NORMAL_IMAGE_WIDTH}-->]</th>
                <td>
                    <a name="<!--{$key}-->"></a>
                    <span class="attention"><!--{$arrErr[$key]}--></span>
                    <!--{if $arrForm.arrFile[$key].filepath != ""}-->
                    <img src="<!--{$arrForm.arrFile[$key].filepath}-->" alt="<!--{$arrForm.name|h}-->" />　<a href="" onclick="eccube.setModeAndSubmit('delete_image', 'image_key', '<!--{$key}-->');
                    return false;">[画像の取り消し]</a><br />
                    <!--{/if}-->
                    <input type="file" name="news_image" size="40" style="<!--{$arrErr[$key]|sfGetErrorColor}-->" />
                    <a class="btn-normal" href="javascript:;" name="btn" onclick="eccube.setModeAndSubmit('upload_image', 'image_key', '<!--{$key}-->');
                    return false;">アップロード</a>
                </td>
            </tr>
        </table>
        <!--{* ▲登録テーブルここまで *}-->

        <div class="btn-area">
            <ul>
                <li><a class="btn-action" href="javascript:;" onclick="eccube.fnFormModeSubmit('form1', 'edit', '', ''); return false;"><span class="btn-next">この内容で登録する</span></a></li>
            </ul>
        </div>
    </form>


