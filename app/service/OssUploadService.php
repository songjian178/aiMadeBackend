<?php
declare(strict_types=1);

namespace app\service;

use AlibabaCloud\Oss\V2 as Oss;
use AlibabaCloud\Oss\V2\Models\PutObjectRequest;

/**
 * 阿里云 OSS 简单上传（PutObject），供业务接口调用。
 *
 * 配置见 README「阿里云 OSS 上传配置」与 .env。
 */
class OssUploadService
{
    /** @var list<string> */
    protected const ALLOWED_IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

    protected string $accessKeyId;

    protected string $accessKeySecret;

    protected string $region;

    protected string $bucket;

    protected ?string $endpoint;

    protected ?string $publicBaseUrl;

    protected int $maxBytes;

    public function __construct()
    {
        $this->accessKeyId = trim((string)env('OSS_ACCESS_KEY_ID', ''));
        $this->accessKeySecret = trim((string)env('OSS_ACCESS_KEY_SECRET', ''));
        $this->region = trim((string)env('OSS_REGION', 'cn-hangzhou'));
        $this->bucket = trim((string)env('OSS_BUCKET', ''));
        $endpoint = trim((string)env('OSS_ENDPOINT', ''));
        $this->endpoint = $endpoint !== '' ? $endpoint : null;
        $base = trim((string)env('OSS_PUBLIC_BASE_URL', ''));
        $this->publicBaseUrl = $base !== '' ? $base : null;
        $this->maxBytes = (int)env('OSS_UPLOAD_MAX_BYTES', 10 * 1024 * 1024);
        if ($this->maxBytes < 1) {
            $this->maxBytes = 10 * 1024 * 1024;
        }
    }

    /**
     * @return list<string>
     */
    public static function allowedImageExtensions(): array
    {
        return self::ALLOWED_IMAGE_EXT;
    }

    public function assertConfigured(): void
    {
        if ($this->accessKeyId === '' || $this->accessKeySecret === '') {
            throw new \RuntimeException('OSS 未配置访问密钥（OSS_ACCESS_KEY_ID / OSS_ACCESS_KEY_SECRET）');
        }
        if ($this->bucket === '') {
            throw new \RuntimeException('OSS 未配置存储桶（OSS_BUCKET）');
        }
    }

    /**
     * 根据原始扩展名生成对象 Key：{Ymd}/{随机32位hex}.{ext}
     */
    public function buildObjectKey(string $extension): string
    {
        $ext = strtolower($extension);
        $ext = ltrim($ext, '.');
        if (!in_array($ext, self::ALLOWED_IMAGE_EXT, true)) {
            throw new \RuntimeException('不支持的图片格式，仅允许：' . implode('、', self::ALLOWED_IMAGE_EXT));
        }

        $dateFolder = date('Ymd');
        $random = bin2hex(random_bytes(16));

        return $dateFolder . '/' . $random . '.' . $ext;
    }

    /**
     * 上传本地文件到 OSS，返回可访问 URL 与对象 Key。
     *
     * @return array{url:string,key:string}
     */
    public function uploadImageFile(string $localPath, string $originalExtension): array
    {
        $this->assertConfigured();

        if (!is_file($localPath) || !is_readable($localPath)) {
            throw new \RuntimeException('临时文件不可读');
        }

        $size = filesize($localPath);
        if ($size !== false && $size > $this->maxBytes) {
            throw new \RuntimeException('文件过大，最大允许 ' . (int)($this->maxBytes / 1024 / 1024) . 'MB');
        }

        $key = $this->buildObjectKey($originalExtension);

        $credentialsProvider = new Oss\Credentials\StaticCredentialsProvider(
            $this->accessKeyId,
            $this->accessKeySecret
        );

        $cfg = Oss\Config::loadDefault();
        $cfg->setCredentialsProvider($credentialsProvider);
        $cfg->setRegion($this->region);
        if ($this->endpoint !== null) {
            $cfg->setEndpoint($this->endpoint);
        }

        $client = new Oss\Client($cfg);

        $stream = fopen($localPath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException('无法读取上传文件');
        }

        try {
            $request = new PutObjectRequest($this->bucket, $key);
            $request->body = Oss\Utils::streamFor($stream);
            $request->contentType = Oss\Utils::guessContentType($key) ?? 'application/octet-stream';

            $result = $client->putObject($request);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $code = (int)($result->statusCode ?? 0);
        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException('OSS 上传失败，HTTP 状态 ' . $code);
        }

        return [
            'url' => $this->buildPublicUrl($key),
            'key' => $key,
        ];
    }

    protected function buildPublicUrl(string $key): string
    {
        $key = ltrim($key, '/');
        if ($this->publicBaseUrl !== null) {
            return rtrim($this->publicBaseUrl, '/') . '/' . $key;
        }

        $region = $this->region;
        $ossHostPrefix = str_starts_with($region, 'oss-')
            ? $region
            : 'oss-' . $region;

        return 'https://' . $this->bucket . '.' . $ossHostPrefix . '.aliyuncs.com/' . $key;
    }
}
