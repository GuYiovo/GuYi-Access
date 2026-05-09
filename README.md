<div align="center">

# GuYi Access Pro
**高性能、高颜值、支持跨语言的开源卡密授权验证基建引擎**

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![PHP](https://img.shields.io/badge/PHP-%E2%89%B5%207.2-777BB4.svg)]()
[![Security](https://img.shields.io/badge/Security-AES--256--GCM-success.svg)]()
[![Languages](https://img.shields.io/badge/Support-30%2B_Languages-ff69b4.svg)]()
[![Chat](https://img.shields.io/badge/QQ群-1077643184-0088ff.svg)](https://qm.qq.com/q/X3suYdjWAA)

无论您使用 **C/C++** 编写原生工具，使用 **Flutter** 开发多端应用，还是用 **易语言/Python** 编写脚本。<br>
GuYi Access 都为您准备了极其详尽的、开箱即用的对接源码。

<br>

<!-- 建议您在这里放一张后台数据总览的精美截图，极大地提升项目的吸引力 -->
> 🖼️ **系统截图：** *(请在此处替换为您后台的真实截图 `![Dashboard](assets/screenshot.png)`)*

<br>

🌐 **[官方网站 - 全球高速线路 (推荐)](https://official.可爱.top/)** ｜ 🌍 **[官方网站 - 海外备用线路](https://guyiovo.github.io/GuYi-Access-wed/)** ｜ 🏠 **[作者主页](https://可爱.top/)**

👨‍💻 **联系作者:** QQ 156440000 ｜ ✉️ **Email:** 156440000@qq.com

</div>

---

## 📋 目录

- [✨ 核心特性](#-核心特性)
- [🛠️ 部署与安装](#️-部署与安装)
- [🚀 快速开始](#-快速开始)
- [💻 支持的生态矩阵](#-支持的生态矩阵)
- [💬 社区与支持](#-社区与支持)
- [📄 开源协议](#-开源协议)

## ✨ 核心特性

本项目不仅是一个简单的发卡验证端，更是一个完整的**软件生命周期授权管控平台**：

### 🛡️ 极致的安全防护
- **军事级通信加密**：全栈标配可选的 `AES-256-GCM` 认证加密，彻底杜绝中间人抓包篡改（如伪造到期时间）。
- **防高频防刷机制**：内置请求并发限制与防恶意 CC 机制。
- **全局云端黑名单**：支持精准到 `设备特征码(Hash)` 与 `客户端 IP` 的跨应用全局封禁。

### ⚡ 强大的业务架构
- **多应用项目隔离**：单后台可无限制管控无数个软件/项目，每个项目拥有独立 `AppKey` 与独立卡密库。
- **云端动态变量下发**：支持为每个应用配置“公开/私有”云变量，无需更新客户端即可随时动态修改更新地址、公告、配置项。
- **极简高效 API**：单端点（Single Endpoint）设计，一个 POST 请求搞定验证、激活、绑定与数据拉取。

### 🎨 现代化的管理体验
- **Glassmorphism 拟物设计**：后台采用全新毛玻璃 UI 引擎，深度适配 PC 端与移动端（自适应底部导航）。
- **可视化数据大屏**：实时展示库存分布、活跃设备心跳、卡密耗损率与多维度审计日志。
- **完美系统迁移**：支持将全站（应用、卡密、变量、黑名单、设置）一键打包为 JSON 导出，并在新服务器一键无损导入恢复。
- **便捷库存操作**：支持自定义任意时长制卡、批量加时/扣时、全局在用卡密补偿等贴心功能。

## 🛠️ 部署与安装

### 环境要求
- **PHP**: ≥ 7.2 (推荐 7.4 - 8.x)
- **数据库**: MySQL (需启用 PDO 扩展)
- **扩展支持**: 需启用 JSON 扩展

### 安装步骤
1. **上传源码**：将本项目的所有文件上传至您的服务器网站根目录。
2. **设置权限**：确保网站根目录具有可写入权限（用于生成数据库配置文件）。
3. **运行安装向导**：在浏览器中访问您的域名安装路径，例如 `http://您的域名/install.php`。
4. **配置数据库**：根据页面提示，输入您的 MySQL 数据库连接信息，并设置后台管理员账号。
5. **完成安装**：安装成功后，系统会自动销毁 `install.php` 以确保安全。接着您可以登录后台进行个性化配置。

## 🚀 快速开始

### 1. 获取应用 AppKey

在完成上方的**服务端部署**后，登录您的后台系统，在【应用管理】中点击“创建新应用”，即可获取该应用的专属 `64位 AppKey` 以及 API 接口地址。

> ⚠️ **安全警告**：AppKey 不仅用于应用识别，更是 AES-256-GCM 的解密密钥，请务必在您的客户端代码中进行混淆或加壳保护！

### 2. 客户端对接

我们为所有常用语言提供了即插即用的加密通信代码。请前往 [官方网站 API 文档区](https://official.可爱.top/#docs) 右侧的**代码演示面板**，选择您正在使用的编程语言，一键复制核心验证逻辑。

#### 接口概览

```http
POST /Verifyfile/api.php
Content-Type: application/json

{
  "app_key": "您的 64位十六进制密钥",
  "card_code": "用户输入的卡密",
  "device_hash": "可选的硬件机器码"
}
```

**响应示例：**

```json
{
  "status": "success",
  "message": "验证成功",
  "data": {
    "expire_time": "2026-12-31 23:59:59",
    "variables": {
      "update_url": "https://pan.example.com",
      "notice": "欢迎使用最新版本！"
    }
  }
}
```

## 💻 支持的生态矩阵

GuYi Access 的设计初衷是为了打破语言壁垒。目前代码演示已涵盖（但不限于）以下开发环境：

| 系统/原生层 | 后端/微服务 | 前端/移动端 | 脚本/辅助 |
|-------------|-------------|-------------|-----------|
| C / C++     | Go          | Flutter / Dart| 易语言    |
| Rust        | Node.js     | Vue.js / React| Python    |
| C# / .NET   | Java (Spring)| Swift (iOS)  | Lua       |
| VB.NET      | PHP         | Kotlin (Android)| Shell   |

## 💬 社区与支持

遇到对接问题？需要定制化功能？或者想获取最新版本的更新推送？欢迎加入我们的技术生态交流群：

- **官方技术交流群**：1077643184
- **一键加群链接**：[👉 点击这里加入 QQ 群](https://qm.qq.com/q/X3suYdjWAA)
- **问题反馈邮箱**：karacsonyerik594@gmail.com

## 📄 开源协议

本项目采用 [MIT License](https://opensource.org/licenses/MIT) 开源协议。

您可以自由地将 GuYi Access 用于个人或商业项目中。在使用、修改或分发过程中，**保留原作者的版权信息**是对开源精神最大的支持。

---

**Made with ♥ for Developers by GuYi.**