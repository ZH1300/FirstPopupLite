<div align="center">

# FirstPopupLite

_🛠️ Typecho 首次访问弹窗公告插件 🛠️_

[![Typecho](https://img.shields.io/badge/Typecho-1.2%2B-blue.svg)](https://typecho.org/)
[![License](https://img.shields.io/badge/License-GPL--3.0-green.svg)](https://www.gnu.org/licenses/gpl-3.0.html)
[![Version](https://img.shields.io/badge/Version-1.1.0-orange.svg)]()

</div>

一个轻量级、无依赖的 Typecho 弹窗公告插件。专为“首次访问提醒”场景设计，利用浏览器本地存储实现智能防打扰机制，确保公告只在用户第一次打开网页时弹出。

## ✨ 功能特性

-   **首次访问触发**：精准识别新用户或新会话，在首次加载时展示公告。
-   **智能冷却机制**：用户点击关闭后，在设定的时间（如 24 小时）内不再重复弹出。
-   **零外部依赖**：纯原生 HTML/CSS/JS 实现，不引入任何第三方库，极致轻量。
-   **支持 HTML 内容**：公告内容支持基础 HTML 标签，可插入链接、加粗文本等。
-   **响应式适配**：自动适配移动端与桌面端，弹窗居中显示且带有平滑过渡动画。
-   **安全输出**：内置内容转义处理，防止 XSS 注入风险。

## 📦 安装步骤

1.  下载本仓库代码或克隆到本地。
2.  将 `FirstPopupLite` 文件夹上传至 Typecho 插件目录：`/usr/plugins/`。
3.  登录 Typecho 后台，进入 **控制台** -> **插件**。
4.  找到 **FirstPopupLite** 并点击 **启用**。
5.  点击 **设置** 按钮，填写公告内容及冷却时间即可生效。

## ⚙️ 配置说明

| 配置项 | 默认值 | 说明 |
| :--- | :--- | :--- |
| 公告内容 | 欢迎光临本站！... | 支持 HTML 标签（如 `<br>`, `<b>`, `<a>`）。**留空则不渲染弹窗**。 |
| 冷却时间 | 24 | 单位：小时。用户关闭弹窗后，在此时间内再次访问不会弹出。 |

> 💡 **提示**：若希望每次打开浏览器都弹出（即仅单次会话有效），可将冷却时间设置为较短的值（如 `0.1` 小时）。若希望长期只弹一次，可设置为 `168`（一周）或更长。

## 📄 许可证

本项目基于 [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.html) 开源。

您可以自由地使用、修改和分发本代码，但衍生作品必须同样以 GPL-3.0 协议开源并公开源代码。

## 🤝 贡献与支持

如果您在使用过程中遇到问题或有改进建议，欢迎提交 [Issue](../../issues) 或 [Pull Request](../../pulls)。

---
