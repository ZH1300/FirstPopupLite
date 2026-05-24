<?php
/**
 * 首次访问弹窗公告插件 (美化增强版)
 * 
 * @package FirstPopupLite
 * @author I'm ZH
 * @link http://imzh.cn
 * @version 1.2.0
 * @license GNU General Public License v3.0
 * @update: 2026.05.24
 */
class FirstPopupLite_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Archive')->header = array('FirstPopupLite_Plugin', 'injectCss');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('FirstPopupLite_Plugin', 'injectHtmlAndJs');
    }

    public static function deactivate() {}

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        // 弹窗触发模式
        $popup_mode = new Typecho_Widget_Helper_Form_Element_Radio(
            'popup_mode',
            array(
                'first' => _t('仅首次访问弹出（带冷却时间）'),
                'always' => _t('每次打开网页都弹出')
            ),
            'first',
            '弹窗触发模式',
            '选择弹窗的显示频率。若选择“每次打开”，下方的冷却时间设置将自动失效。'
        );
        $form->addInput($popup_mode);

        $popup_content = new Typecho_Widget_Helper_Form_Element_Textarea(
            'popup_content', 
            NULL, 
            '<h3 style="margin:0 0 10px;font-size:20px;">🎉 欢迎来到本站</h3><p style="margin:0;color:#555;line-height:1.6;">我们更新了全新的主题，并优化了移动端体验。<br>感谢您的支持，祝您阅读愉快！</p>', 
            '公告内容（支持HTML）', 
            '留空则不显示弹窗。支持标准 HTML 标签以自定义排版。'
        );
        $popup_content->input->setAttribute('rows', 8)->setAttribute('cols', 80);
        $form->addInput($popup_content);

        $allow_html = new Typecho_Widget_Helper_Form_Element_Radio(
            'allow_html',
            array('1' => _t('允许'), '0' => _t('禁止')),
            '1',
            'HTML 解析',
            '允许后将按原样输出 HTML 代码；禁止则会自动转义为纯文本（更安全）。'
        );
        $form->addInput($allow_html);

        $cooldown_hours = new Typecho_Widget_Helper_Form_Element_Text(
            'cooldown_hours', 
            NULL, 
            '24', 
            '冷却时间（小时）', 
            '仅在“仅首次访问弹出”模式下生效。用户关闭后，在此时间内不再弹出。'
        );
        $form->addInput($cooldown_hours);
        
        $theme_color = new Typecho_Widget_Helper_Form_Element_Text(
            'theme_color', 
            NULL, 
            '#6366f1', 
            '按钮主题色', 
            'HEX颜色值，例如 #6366f1'
        );
        $form->addInput($theme_color);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    public static function injectCss()
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $config = $options->plugin('FirstPopupLite');

        if (empty($config->popup_content)) return;
        
        $color = htmlspecialchars($config->theme_color ?? '#6366f1');

        echo <<<CSS
<style id="first-popup-lite-css">
#fpl-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,.4);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);z-index:9999;display:flex;justify-content:center;align-items:center;opacity:0;visibility:hidden;transition:all .35s cubic-bezier(.4,0,.2,1)}
#fpl-overlay.show{opacity:1;visibility:visible}
#fpl-modal{background:#fff;width:92%;max-width:420px;border-radius:16px;box-shadow:0 25px 50px -12px rgba(0,0,0,.25);padding:32px 28px 24px;position:relative;transform:translateY(20px) scale(.96);transition:all .35s cubic-bezier(.34,1.56,.64,1)}
#fpl-overlay.show #fpl-modal{transform:translateY(0) scale(1)}
#fpl-close{position:absolute;top:16px;right:16px;width:32px;height:32px;border-radius:50%;border:none;background:transparent;font-size:20px;color:#94a3b8;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s}
#fpl-close:hover{background:#f1f5f9;color:#334155}
#fpl-content{font-size:15px;line-height:1.7;color:#334155;word-break:break-word}
#fpl-content a{color:{$color};text-decoration:none;border-bottom:1px dashed {$color}}
#fpl-content a:hover{border-bottom-style:solid}
#fpl-btn{display:block;width:100%;margin-top:24px;padding:12px;background:{$color};color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;transition:all .2s;letter-spacing:.5px}
#fpl-btn:hover{filter:brightness(1.1);transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.15)}
#fpl-btn:active{transform:translateY(0)}
@media(max-width:480px){#fpl-modal{padding:24px 20px 20px;border-radius:12px}#fpl-content{font-size:14px}}
</style>
CSS;
    }

    public static function injectHtmlAndJs()
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $config = $options->plugin('FirstPopupLite');

        if (empty($config->popup_content)) return;

        // 根据配置决定是否解析 HTML
        $content = ($config->allow_html == '1') 
            ? $config->popup_content 
            : nl2br(htmlspecialchars($config->popup_content));
            
        $isAlways = ($config->popup_mode === 'always') ? 'true' : 'false';
        $cooldownSeconds = (int)$config->cooldown_hours * 3600;

        echo '<div id="fpl-overlay"><div id="fpl-modal">';
        echo '<button id="fpl-close" aria-label="关闭">&times;</button>';
        echo '<div id="fpl-content">' . $content . '</div>';
        echo '<button id="fpl-btn">我知道了</button>';
        echo '</div></div>';

        echo "<script>
        (function(){
            var o=document.getElementById('fpl-overlay'),
                c=document.getElementById('fpl-close'),
                b=document.getElementById('fpl-btn'),
                k='fpl_closed_time',
                isAlways={$isAlways},
                t=localStorage.getItem(k);
            
            // 如果不是每次弹出模式，且还在冷却期内，则直接返回
            if(!isAlways && t && new Date().getTime()-t<{$cooldownSeconds}*1000) return;
            
            setTimeout(function(){o.classList.add('show')},800);
            
            function close(){
                o.classList.remove('show');
                // 仅在非每次弹出模式下记录关闭时间
                if(!isAlways) localStorage.setItem(k,new Date().getTime());
                setTimeout(function(){o.style.display='none'},350);
            }
            c.onclick=b.onclick=function(){close()};
            o.onclick=function(e){if(e.target===o)close()};
        })();
        </script>";
    }
}
