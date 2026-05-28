<?php
/**
 * 首次访问弹窗公告插件
 * 
 * @package FirstPopupLite
 * @author I'm ZH
 * @link http://imzh.cn
 * @version 1.3.2
 * @license GNU General Public License v3.0
 * @update: 2026.05.28
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
            '<h3 style="margin:0 0 12px;font-size:22px;background:linear-gradient(135deg,#6366f1,#a855f7);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">🎉 欢迎来到本站</h3><p style="margin:0;color:#475569;line-height:1.8;font-size:15px;">我们更新了全新的主题，并优化了移动端体验。<br>感谢您的支持，祝您阅读愉快！</p>', 
            '公告内容（支持HTML）', 
            '留空则不显示弹窗。支持标准 HTML 标签以自定义排版。标题推荐使用渐变文字效果。'
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
            'HEX颜色值，例如 #6366f1。按钮将自动生成同色系渐变效果。'
        );
        $form->addInput($theme_color);

        $popup_delay = new Typecho_Widget_Helper_Form_Element_Text(
            'popup_delay',
            NULL,
            '800',
            '弹窗延时（毫秒）',
            '延迟显示弹窗的时间，单位毫秒（例如 800 = 0.8 秒）。设置为 0 则立即显示。'
        );
        $popup_delay->input->setAttribute('type', 'number')->setAttribute('min', '0')->setAttribute('step', '100');
        $form->addInput($popup_delay);
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
/* 遮罩层：优化渲染性能，使用半透明背景（低端设备避免重blur） */
#fpl-overlay{
    position:fixed;top:0;left:0;width:100%;height:100%;
    background:rgba(15,23,42,0.55);
    z-index:9999;display:flex;justify-content:center;align-items:center;
    opacity:0;visibility:hidden;
    transition:opacity 0.3s ease, visibility 0.3s ease;
    will-change:opacity;
}
/* 仅在支持高性能backdrop-filter的浏览器启用毛玻璃效果，避免卡顿 */
@supports (backdrop-filter: blur(12px)) {
    #fpl-overlay{
        background:rgba(15,23,42,0.45);
        backdrop-filter:blur(12px) saturate(180%);
        -webkit-backdrop-filter:blur(12px) saturate(180%);
    }
}
#fpl-overlay.show{opacity:1;visibility:visible}

/* 卡片：开启硬件加速 + 平滑缩放 */
#fpl-modal{
    background:rgba(255,255,255,.96);
    width:90%;max-width:440px;
    border-radius:24px;
    border:1px solid rgba(255,255,255,.6);
    box-shadow:
        0 20px 35px -8px rgba(0,0,0,.2),
        0 0 0 1px rgba(0,0,0,.02);
    padding:36px 32px 28px;
    position:relative;
    transform:translateY(20px) scale(0.96);
    transition:transform 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
    will-change:transform;
}
#fpl-overlay.show #fpl-modal{
    transform:translateY(0) scale(1);
}

/* 关闭按钮：简化动画，减少重绘 */
#fpl-close{
    position:absolute;top:16px;right:16px;
    width:36px;height:36px;border-radius:50%;border:none;
    background:rgba(0,0,0,.04);
    font-size:18px;color:#94a3b8;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    transition:all 0.2s ease;
}
#fpl-close:hover{background:rgba(0,0,0,.08);color:#334155;transform:rotate(90deg)}

/* 内容区排版 */
#fpl-content{font-size:15px;line-height:1.8;color:#334155;word-break:break-word}
#fpl-content h3{font-weight:700;letter-spacing:-.3px}
#fpl-content a{
    color:{$color};text-decoration:none;
    border-bottom:1.5px dashed {$color};
    transition:border-color .2s,color .2s;
}
#fpl-content a:hover{border-bottom-style:solid;filter:brightness(1.15)}
#fpl-content p{margin:0}
#fpl-content b,#fpl-content strong{color:#1e293b}

/* 按钮：仅transform动画，保持高性能 */
#fpl-btn{
    display:block;width:100%;margin-top:28px;padding:14px;
    background:linear-gradient(135deg,{$color},color-mix(in srgb,{$color},#a855f7 40%));
    color:#fff;border:none;border-radius:14px;
    font-size:15px;font-weight:600;cursor:pointer;
    letter-spacing:.5px;position:relative;overflow:hidden;
    transition:transform 0.2s ease, box-shadow 0.2s ease;
    box-shadow:0 4px 12px -3px color-mix(in srgb,{$color} 50%,transparent);
    will-change:transform;
}
#fpl-btn::after{
    content:'';position:absolute;top:0;left:-100%;
    width:60%;height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,.25),transparent);
    transition:left 0.6s ease;
}
#fpl-btn:hover{transform:translateY(-2px);box-shadow:0 8px 20px -4px color-mix(in srgb,{$color} 60%,transparent)}
#fpl-btn:hover::after{left:120%}
#fpl-btn:active{transform:translateY(0) scale(0.98)}

/* 移动端：移除blur、微调内边距 */
@media(max-width:480px){
    #fpl-modal{
        width:92%;padding:28px 22px 22px;
        border-radius:20px;
        margin:0 16px;
    }
    #fpl-content{font-size:14px;line-height:1.75}
    #fpl-content h3{font-size:19px !important}
    #fpl-btn{padding:13px;border-radius:12px;font-size:14px;margin-top:22px}
    #fpl-close{width:32px;height:32px;font-size:16px;top:12px;right:12px}
    /* 移动端强制禁用backdrop-filter保证流畅 */
    #fpl-overlay{backdrop-filter:none !important; -webkit-backdrop-filter:none !important; background:rgba(15,23,42,0.65);}
}

/* 暗色模式：背景半透明微调 */
@media(prefers-color-scheme:dark){
    #fpl-modal{background:rgba(30,41,59,.94);border-color:rgba(255,255,255,.08)}
    #fpl-content{color:#cbd5e1}
    #fpl-content b,#fpl-content strong{color:#f1f5f9}
    #fpl-close{background:rgba(255,255,255,.08);color:#64748b}
    #fpl-close:hover{background:rgba(255,255,255,.14);color:#e2e8f0}
}
</style>
CSS;
    }

    public static function injectHtmlAndJs()
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $config = $options->plugin('FirstPopupLite');

        if (empty($config->popup_content)) return;

        $content = ($config->allow_html == '1') 
            ? $config->popup_content 
            : nl2br(htmlspecialchars($config->popup_content));
            
        $isAlways = ($config->popup_mode === 'always') ? 'true' : 'false';
        $cooldownSeconds = (int)$config->cooldown_hours * 3600;
        $popupDelay = max(0, (int)($config->popup_delay ?? 800));

        echo '<div id="fpl-overlay"><div id="fpl-modal">';
        echo '<button id="fpl-close" aria-label="关闭">&times;</button>';
        echo '<div id="fpl-content">' . $content . '</div>';
        echo '<button id="fpl-btn">我知道了</button>';
        echo '</div></div>';

        echo <<<JS
<script>
(function(){
    var overlay = document.getElementById('fpl-overlay');
    var closeBtn = document.getElementById('fpl-close');
    var confirmBtn = document.getElementById('fpl-btn');
    var storageKey = 'fpl_closed_time';
    var isAlways = {$isAlways};
    var cooldownMs = {$cooldownSeconds} * 1000;
    var delayMs = {$popupDelay};

    // 检查冷却时间
    if(!isAlways){
        var closedTime = localStorage.getItem(storageKey);
        if(closedTime && (new Date().getTime() - parseInt(closedTime) < cooldownMs)){
            return;
        }
    }

    // 平滑显示弹窗：确保DOM完全就绪，并使用 requestAnimationFrame 对齐帧渲染
    function showModal() {
        requestAnimationFrame(function(){
            overlay.classList.add('show');
        });
    }

    function closeModal() {
        overlay.classList.remove('show');
        if(!isAlways){
            localStorage.setItem(storageKey, new Date().getTime().toString());
        }
        // 等待过渡动画结束后隐藏元素（避免遮挡点击）
        setTimeout(function(){
            overlay.style.display = 'none';
        }, 350);
    }

    // 绑定关闭事件
    closeBtn.onclick = confirmBtn.onclick = closeModal;
    overlay.onclick = function(e) {
        if(e.target === overlay) closeModal();
    };

    // 等待页面主要内容加载完成后再开始延迟显示，避免资源竞争导致丢帧
    var startDelay = function() {
        setTimeout(showModal, delayMs);
    };
    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', startDelay);
    } else {
        startDelay();
    }
})();
</script>
JS;
    }
}
