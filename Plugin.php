<?php
/**
 * 首次访问弹窗公告插件 (轻量版)
 * 
 * @package FirstPopupLite
 * @author I`m ZH
 * @version 1.0.0
 * @link http://imzh.cn/
 * @license GNU General Public License v3.0
 * @update: 2026.05.24
 */
class FirstPopupLite_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件
     */
    public static function activate()
    {
        // 仅在前台页面注入资源
        Typecho_Plugin::factory('Widget_Archive')->header = ['FirstPopupLite_Plugin', 'injectCss'];
        Typecho_Plugin::factory('Widget_Archive')->footer = ['FirstPopupLite_Plugin', 'injectHtmlAndJs'];
        return _t('插件已激活，请前往设置公告内容。');
    }

    /**
     * 禁用插件
     */
    public static function deactivate() {}

    /**
     * 插件配置面板
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $popup_content = new Typecho_Widget_Helper_Form_Element_Textarea(
            'popup_content',
            null,
            '欢迎光临本站！这是您的首次访问公告。',
            '公告内容（支持HTML）',
            '留空则不显示弹窗。'
        );
        $form->addInput($popup_content);

        $cooldown_hours = new Typecho_Widget_Helper_Form_Element_Text(
            'cooldown_hours',
            null,
            '24',
            '冷却时间（小时）',
            '用户关闭后，在此时间内不再弹出。'
        );
        $form->addInput($cooldown_hours);
    }

    /**
     * 个人配置（未使用）
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    /**
     * 注入CSS样式
     */
    public static function injectCss()
    {
        // 仅在前台且内容非空时输出
        if (!self::shouldInject()) {
            return;
        }

        echo <<<CSS
<style id="fpl-lite-style">
#fpl-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;display:flex;justify-content:center;align-items:center;opacity:0;visibility:hidden;transition:opacity 0.3s ease, visibility 0.3s}
#fpl-overlay.show{opacity:1;visibility:visible}
#fpl-modal{background:#fff;width:90%;max-width:400px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.2);padding:20px;transform:scale(0.95);transition:transform 0.3s ease}
#fpl-overlay.show #fpl-modal{transform:scale(1)}
#fpl-close{float:right;font-size:24px;font-weight:bold;cursor:pointer;color:#aaa;line-height:1}
#fpl-close:hover{color:#333}
#fpl-btn{display:block;width:100%;margin-top:15px;padding:10px;background:#4a90e2;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:14px}
#fpl-btn:hover{opacity:0.9}
</style>
CSS;
    }

    /**
     * 注入HTML结构和JS逻辑
     */
    public static function injectHtmlAndJs()
    {
        if (!self::shouldInject()) {
            return;
        }

        $config = self::getConfig();
        $content = $config['popup_content'];
        $cooldownSeconds = (int)$config['cooldown_hours'] * 3600;

        // 输出弹窗HTML
        echo '<div id="fpl-overlay"><div id="fpl-modal">';
        echo '<span id="fpl-close">&times;</span>';
        echo '<div class="fpl-content">' . $content . '</div>';
        echo '<button id="fpl-btn">我知道了</button>';
        echo '</div></div>';

        // 输出JS (IIFE 避免全局污染)
        echo <<<JS
<script>
(function() {
    var overlay = document.getElementById('fpl-overlay');
    if (!overlay) return;
    var closeBtn = document.getElementById('fpl-close');
    var okBtn = document.getElementById('fpl-btn');
    var storageKey = 'fpl_closed_time';
    var closedTime = localStorage.getItem(storageKey);
    var now = Date.now();
    var cooldownMs = {$cooldownSeconds} * 1000;

    // 检查是否在冷却期内
    if (closedTime && (now - parseInt(closedTime, 10) < cooldownMs)) {
        return;
    }

    // 延迟显示，提升体验
    setTimeout(function() {
        overlay.classList.add('show');
    }, 1000);

    function closePopup() {
        overlay.classList.remove('show');
        localStorage.setItem(storageKey, Date.now().toString());
    }

    if (closeBtn) closeBtn.onclick = closePopup;
    if (okBtn) okBtn.onclick = closePopup;
    overlay.onclick = function(e) {
        if (e.target === overlay) closePopup();
    };
})();
</script>
JS;
    }

    /**
     * 判断是否应当注入资源（前台页面 + 公告内容非空）
     *
     * @return bool
     */
    private static function shouldInject()
    {
        // 非前台请求不注入
        if (!defined('__TYPECHO_ROOT_DIR__') || !Typecho_Widget::widget('Widget_Archive')->have()) {
            return false;
        }

        $config = self::getConfig();
        return !empty($config['popup_content']);
    }

    /**
     * 获取插件配置（安全且统一）
     *
     * @return array
     */
    private static function getConfig()
    {
        $options = Helper::options();
        $pluginConfig = $options->plugin('FirstPopupLite');
        
        return [
            'popup_content'   => isset($pluginConfig->popup_content) ? $pluginConfig->popup_content : '',
            'cooldown_hours'  => isset($pluginConfig->cooldown_hours) ? (int)$pluginConfig->cooldown_hours : 24,
        ];
    }
}
