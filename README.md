# 华东师范大学校园论坛

CampusForum 是一个面向校园交流的论坛项目，支持分类浏览、发帖讨论、学生身份认证和后台内容管理。

## 主要功能

- 论坛首页展示公告与帖子，列表支持按板块和标签筛选、按标题搜索和分页浏览。
- 用户可以注册登录、编辑个人资料、使用 Markdown 发帖和上传图片，也可以评论、点赞和举报。
- 用户可以提交学生认证材料，由管理员审核。站内通知集中展示互动与审核消息。
- 管理后台提供数据概览，以及用户、学生认证、帖子、评论、举报、板块和公告管理。

## 技术栈

后端使用 PHP 8.3+、Laravel 13 和 Blade，前端使用 Vue 3、DHTMLX Suite、Vditor、Tailwind CSS 和 Vite。示例环境默认使用 MySQL，自动化测试使用内存 SQLite。

## 本地启动

准备 PHP 8.3+、Composer 2、MySQL 和 Node.js 20.19+（20.x）或 22.12+。PHP 需启用 `pdo_mysql`，运行测试还需启用 `pdo_sqlite`。

1. 克隆仓库并安装依赖。

   ```bash
   git clone https://github.com/Scudlen7/CampusForum.git
   cd CampusForum
   composer install
   npm ci
   ```

2. 从 `.env.example` 复制一份 `.env`。在 MySQL 中创建空数据库，并在 `.env` 中填写 `DB_HOST`、`DB_PORT`、`DB_DATABASE`、`DB_USERNAME` 和 `DB_PASSWORD`。

3. 初始化应用、运行迁移并启动服务。

   ```bash
   php artisan key:generate
   php artisan migrate
   php artisan storage:link
   npm run build
   php artisan serve
   ```

4. 打开 [论坛首页](http://127.0.0.1:8000)。开发前端时，可以在另一个终端运行 `npm run dev`，由 Vite 提供热更新。

### 本地演示数据

在本地开发库执行 `php artisan db:seed`，可载入板块、公告、演示内容和管理员账号。执行后可用 `admin@ecnu.edu.cn` / `admin123456` 登录，再进入 [管理后台](http://127.0.0.1:8000/admin)。默认管理员凭据仅用于本地演示。

## 项目结构

| 目录 | 内容 |
| --- | --- |
| `app/Http/` | 页面和 JSON 接口控制器、访问控制中间件 |
| `app/Models/`、`app/Services/`、`app/Observers/` | 数据模型、业务服务和模型事件处理 |
| `resources/views/`、`resources/js/`、`resources/css/` | Blade 页面、Vue 组件、Vite 入口和样式 |
| `routes/web.php` | 页面、论坛接口和管理接口路由。接口使用 Laravel 会话认证 |
| `database/` | 迁移、数据工厂和种子数据 |
| `tests/` | PHPUnit 单元测试和功能测试 |

运行 `php artisan test` 可验证论坛、学生认证和后台删除等流程。界面布局和交互细节见 [设计说明](DESIGN.md)。
