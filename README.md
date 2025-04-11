```markdown
# Laravel Code Generator

一款为 Laravel 项目设计的现代化代码生成工具，可快速生成控制器、服务类等模板代码，支持自定义模板。

## 功能特性

- 🚀 快速生成标准化的 Laravel 组件代码
- 📁 支持自定义模板（Stubs）文件
- 📦 开箱即用的 Laravel 服务集成
- 🔧 遵循 PSR-4 规范生成代码结构
```
## 安装

通过 Composer 安装：

```bash
composer require jmjl161100/laravel-code-generator
```

## 配置

### 发布配置文件（可选）

```bash
php artisan vendor:publish --provider="Jmjl161100\LaravelCodeGenerator\CodeGeneratorServiceProvider" --tag=codegenerator-config
```

生成配置文件 `config/codegenerator.php`，可配置以下选项：

```php
return [
    // 默认模板组
    'default' => [
        'stubs_path' => base_path('stubs'), // 模板路径
        'default_target_path' => base_path(), // 目标路径
        'preserve_suffixes' => [ // 保留后缀
            'Controller',
            'Service',
        ],
    ],
    // 自定义模板组
    'key' => [
        'stubs_path' => '',
        'default_target_path' => '',
        'preserve_suffixes' => [
            //
        ],
    ],
];
```

### 发布模板文件（可选）

```bash
php artisan vendor:publish --provider="Jmjl161100\LaravelCodeGenerator\CodeGeneratorServiceProvider" --tag=codegenerator-stubs
```

## artisan

```bash
php artisan generate:code   {name : The name of the generated component}
                            {--stub_id=default : The stub ID in config}
                            {--target= : The target directory}
                            {--f|force : Overwrite existing files}
```

## 使用说明

执行 php artisan generate:code {name} 后根据 config/codegenerator.php 配置在目标路径下生成与模板路径同层级关系的文件，并根据 name 自动生成新文件名称

## 使用示例

```bash
php artisan generate:code Productsa
```

## 贡献流程

1. Fork 项目仓库
2. 创建功能分支 (`git checkout -b feature/your-feature`)
3. 提交修改 (`git commit -am 'Add some feature'`)
4. 推送分支 (`git push origin feature/your-feature`)
5. 创建 Pull Request

## 许可证

本项目基于 [MIT 许可证](LICENSE.md) 开源。
