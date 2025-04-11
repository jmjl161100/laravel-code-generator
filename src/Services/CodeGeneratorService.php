<?php

namespace Jmjl161100\LaravelCodeGenerator\Services;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use SplFileInfo;

class CodeGeneratorService
{
    protected Filesystem $files;

    protected mixed $config;

    protected Command $command;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * 从模板生成代码文件
     *
     * @param  array  $replacements  替换参数数组
     */
    public function generateFromStubs(array $replacements, Command $command): void
    {
        $this->validateReplacements($replacements);

        $this->command = $command;
        $stubsPath = $this->config['stubs_path'];
        $targetPath = $replacements['target_path'] ?: $this->config['default_target_path'];

        $this->ensureDirectoryExists($stubsPath, "Stubs directory not found: {$stubsPath}");
        $this->ensureDirectoryExists($targetPath, null, true);

        foreach ($this->files->allFiles($stubsPath) as $stubFile) {
            $this->processStubFile($stubFile, $replacements, $targetPath);
        }
    }

    /**
     * 处理单个模板文件
     *
     * @param  SplFileInfo  $stubFile  文件资源
     * @param  array  $replacements  替换参数数组
     * @param  string  $targetPath  目标路径
     */
    protected function processStubFile(
        SplFileInfo $stubFile,
        array $replacements,
        string $targetPath
    ): void {
        $relativePath = $stubFile->getRelativePath();
        $fileName = $stubFile->getFilename();

        // 文件名处理
        $newFileName = $this->generateFileName($fileName, $replacements['name']);

        // 内容处理
        $content = $this->processFileContent(
            $stubFile->getContents(),
            $replacements['name']
        );

        // 目标路径处理
        $targetDir = $this->buildTargetDirectory($targetPath, $relativePath);
        $this->ensureDirectoryExists($targetDir, null, true);

        // 写入文件
        $newFilePath = "{$targetDir}/{$newFileName}";
        $this->putFile($newFilePath, $content, $replacements['force']);
    }

    /**
     * 写入文件
     *
     * @param  string  $filePath  文件地址
     * @param  string  $content  文件内容
     * @param  bool  $force  是否覆盖现有文件
     */
    protected function putFile(string $filePath, string $content, bool $force): void
    {
        if ($force) {
            $this->files->put($filePath, $content);
        }

        if ($this->files->exists($filePath)) {
            throw new RuntimeException("File {$filePath} already exists,To overwrite, use the --force option.");
        } else {
            $this->files->put($filePath, $content);
        }
    }

    /**
     * 生成目标文件名
     *
     * @param  string  $stubFileName  模板文件名
     * @param  string  $newFileBaseName  新文件基础名
     */
    protected function generateFileName(string $stubFileName, string $newFileBaseName): string
    {
        return $this->replaceFileNameSuffix(
            $stubFileName,
            $newFileBaseName,
            $this->config['preserve_suffixes']
        );
    }

    /**
     * 处理文件内容
     *
     * @param  string  $content  文件内容
     * @param  string  $newClassBaseName  新类基础名
     */
    protected function processFileContent(string $content, string $newClassBaseName): string
    {
        return $this->replaceClassNameWithSuffix(
            $content,
            $newClassBaseName,
            $this->config['preserve_suffixes']
        );
    }

    /**
     * 带后缀的文件名替换（保留指定后缀）
     *
     * @param  string  $stubFileName  模板文件名
     * @param  string  $newFileBaseName  新文件基础名
     * @param  array  $preserveSuffixes  保留后缀数组
     */
    protected function replaceFileNameSuffix(
        string $stubFileName,
        string $newFileBaseName,
        array $preserveSuffixes
    ): string {
        $filename = pathinfo($stubFileName, PATHINFO_FILENAME);
        $extension = pathinfo($stubFileName, PATHINFO_EXTENSION);

        foreach ($this->handleSuffixes($preserveSuffixes) as $suffix) {
            if (str_ends_with($filename, $suffix)) {
                $prefixLength = strlen($filename) - strlen($suffix);

                return $newFileBaseName.substr($filename, $prefixLength).'.'.$extension;
            }
        }

        return $newFileBaseName.'.'.$extension;
    }

    /**
     * 带后缀的类名替换（保留指定后缀）
     *
     * @param  string  $content  文件内容
     * @param  string  $newClassBaseName  新类基础名
     * @param  array  $preserveSuffixes  保留后缀数组
     */
    protected function replaceClassNameWithSuffix(
        string $content,
        string $newClassBaseName,
        array $preserveSuffixes
    ): string {
        return preg_replace_callback(
            '/\bclass\s+\K\w+\b/',
            function ($matches) use ($newClassBaseName, $preserveSuffixes) {
                $oldClassName = $matches[0];

                return $this->mergeClassNames($oldClassName, $newClassBaseName, $preserveSuffixes);
            },
            $content,
            1
        );
    }

    /**
     * 合并新旧类名（保留后缀逻辑）
     *
     * @param  string  $oldClassName  旧类名
     * @param  string  $newClassBaseName  新类基础名
     * @param  array  $preserveSuffixes  保留后缀数组
     */
    protected function mergeClassNames(
        string $oldClassName,
        string $newClassBaseName,
        array $preserveSuffixes
    ): string {
        foreach ($this->handleSuffixes($preserveSuffixes) as $suffix) {
            if (str_ends_with($oldClassName, $suffix)) {
                return $newClassBaseName.$suffix;
            }
        }

        return $newClassBaseName;
    }

    /**
     * 处理后缀
     *
     * @param  array  $preserveSuffixes  保留后缀数组
     */
    protected function handleSuffixes(array $preserveSuffixes): array
    {
        $preserveSuffixes = array_unique(array_filter($preserveSuffixes));
        usort($preserveSuffixes, fn ($a, $b) => strlen($b) - strlen($a));

        return $preserveSuffixes;
    }

    /**
     * 构建目标目录
     *
     * @param  string  $basePath  目标路径
     * @param  string  $relativePath  相对路径
     */
    protected function buildTargetDirectory(string $basePath, string $relativePath): string
    {
        return $relativePath ? "{$basePath}/{$relativePath}" : $basePath;
    }

    /**
     * 路径存在性检查
     *
     * @param  string  $path  要检测的路径
     * @param  string|null  $exceptionMessage  不存在时提示的信息
     * @param  bool  $createIfMissing  如果缺失是否创建
     */
    protected function ensureDirectoryExists(
        string $path,
        ?string $exceptionMessage = null,
        bool $createIfMissing = false
    ): void {
        if ($this->files->exists($path)) {
            return;
        }

        if ($exceptionMessage) {
            throw new RuntimeException($exceptionMessage);
        }

        if ($createIfMissing) {
            $this->files->makeDirectory($path, 0755, true);
        }
    }

    /**
     * 参数校验
     *
     * @param  array  $replacements  替换参数数组
     */
    protected function validateReplacements(array $replacements): void
    {
        if (! isset($replacements['name'])) {
            throw new RuntimeException('Missing required replacement parameter: NAME');
        }

        $this->config = config('codegenerator.'.$replacements['stub_id']);
    }
}
