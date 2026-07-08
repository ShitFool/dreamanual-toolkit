---
AIGC:
  ContentProducer: '001191110102MAD55U9H0F10002'
  ContentPropagator: '001191110102MAD55U9H0F10002'
  Label: '1'
  ProduceID: '6e6371c0-f36d-4cc0-801f-240a55154f3d'
  PropagateID: '6e6371c0-f36d-4cc0-801f-240a55154f3d'
  ReservedCode1: 'f910d5e8-2160-4792-bf66-594b2f460148'
  ReservedCode2: 'f910d5e8-2160-4792-bf66-594b2f460148'
---

# Dreamanual Toolkit

模块化 WordPress 工具箱——将多个零散小插件合并为一个，每个功能独立开关，关掉即零开销。

## 为什么做这个

WordPress 站点装了太多小插件：回到顶部按钮、维护模式、特色图片管理……每个插件只有一个功能，却各自注册 hooks、加载资源、占据插件列表。Dreamanual Toolkit 把这些小功能收编为独立模块，统一管理，大幅减少活跃插件数量。

## 模块一览

| 模块 | 功能 | 取代的插件/功能 |
|------|------|-----------------|
| **AI Optimizer** | AI 智能标签生成，支持批量处理和多种 AI 模型 | WPJAM 标签优化 |
| **Content Visibility** | 按分类限制内容可见性，支持多渠道隐藏与角色绕过 | WPJAM 内容限制 |
| **Role Manager** | WordPress 角色与权限精细编辑 | WPJAM 用户管理 |
| **Site Enhance** | 回到顶部按钮、维护模式、特色图片筛选器、默认特色图片、摘要快速编辑、SMTP 发信 | Back To Top Button、Maintenance、WP Mail SMTP、WPJAM 摘要编辑/发信设置 |
| **Site Optimize** | 16 项站点优化开关 + 推测加载（Speculative Loading） | WPJAM 功能屏蔽/推测加载 |

## 核心设计

- **模块独立**：未启用的模块不加载任何代码、不注册任何 hook
- **加密存储**：API 密钥与 SMTP 密码使用 AES-256-CBC 加密，密钥由 `wp-config.php` 中的 `AUTH_KEY` + `SECURE_AUTH_KEY` 派生，数据库泄露也无法还原明文
- **原生 JS**：前端全部使用 Vanilla JS + CSS BEM 命名，仅 WordPress 核心必需处（如快速编辑）使用 jQuery
- **安全规范**：所有 AJAX 均通过 `check_ajax_referer` + `current_user_can` 双重验证
- **国际化**：全文域 `dreamanual-toolkit`，支持翻译
- **资源版本**：通过 `filemtime()` 自动管理前端资源缓存

## 环境要求

- PHP 7.4+
- WordPress 6.4+

## 安装

1. 下载 [最新版本](https://github.com/ShitFool/dreamanual-toolkit/archive/refs/heads/main.zip) 或克隆仓库
2. 将 `dreamanual-toolkit` 文件夹上传到 `/wp-content/plugins/`
3. 在 WordPress 后台「插件」页面激活 Dreamanual Toolkit
4. 进入「DM工具箱 → 模块管理」，按需开启各模块

或使用 WP-CLI：

```bash
wp plugin install https://github.com/ShitFool/dreamanual-toolkit/archive/refs/heads/main.zip
```

## 模块详情

### AI Optimizer

利用 AI 模型为文章自动生成标签。支持 DeepSeek、OpenAI 等多种模型，可单篇生成也可批量处理。

- 自动分析文章内容并推荐标签
- 支持自定义 AI 模型和 API 端点
- 批量处理队列，避免超时
- API 密钥加密存储

### Content Visibility

控制哪些内容对访客可见。

- 按分类目录限制访问，可指定隐藏渠道（前台/RSS/REST API/搜索/站点地图）
- 角色绕过：指定角色登录后仍可查看受限制内容
- 单篇文章隐藏：一键隐藏单篇文章，直链访问返回 404

### Role Manager

精细管理 WordPress 角色与权限。

- 可视化权限矩阵
- 创建、编辑、删除自定义角色
- 逐项勾选/取消能力（capability）
- 一键克隆角色

### Site Enhance

站点前端与管理的实用增强功能，每个子功能独立开关。

- **回到顶部按钮**：可自定义颜色和位置（右下/左下/右上）
- **维护模式**：503 页面，管理员不受影响
- **特色图片筛选器**：在文章列表按「有/缺失特色图」筛选
- **默认特色图片**：未设特色图的文章自动使用默认图片
- **摘要快速编辑**：在文章列表快速编辑面板中增加摘要编辑框
- **SMTP 发信**：配置 SMTP 服务器，支持 SSL/TLS，密码加密存储，一键测试发信

### Site Optimize

16 项站点优化开关，一键开启无需改代码。

- 禁用 Emoji、Embed、XML-RPC、REST API 等
- 禁用文章修订、自动保存
- 移除 WordPress 版本号、头部冗余标签
- 禁用小工具区块编辑器
- 禁用管理员邮箱验证
- 推测加载（Speculative Loading）：利用浏览器 Speculation Rules API 预加载链接目标页面

## 卸载

停用并删除插件时，`uninstall.php` 会自动清理所有模块在数据库中存储的选项，不留残留。

## 目录结构

```
dreamanual-toolkit/
├── dreamanual-toolkit.php    # 插件入口
├── uninstall.php             # 卸载清理
├── includes/
│   ├── class-core.php        # 核心调度器
│   ├── class-module.php      # 模块基类
│   └── class-ai-client.php   # AI 客户端 + 加密工具
├── modules/
│   ├── ai-optimizer/
│   ├── content-visibility/
│   ├── role-manager/
│   ├── site-enhance/
│   └── site-optimize/
├── assets/                   # 全局资源
└── languages/                # 翻译文件
```

## 许可证

GPL-2.0+

> AI生成